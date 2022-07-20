<?php

/**
 * Class Forminator_Addon_Hubspot_Quiz_Hooks
 *
 * @since 1.0 HubSpot Addon
 *
 */
class Forminator_Addon_Hubspot_Quiz_Hooks extends Forminator_Addon_Quiz_Hooks_Abstract {

	/**
	 * Addon instance are auto available form abstract
	 * Its added here for development purpose,
	 * Auto-complete will resolve addon directly to `HubSpot` instance instead of the abstract
	 * And its public properties can be exposed
	 *
	 * @since 1.0 HubSpot Addon
	 * @var Forminator_Addon_Hubspot
	 */
	protected $addon;

	/**
	 * Form Settings Instance
	 *
	 * @since 1.0 HubSpot Addon
	 * @var Forminator_Addon_Hubspot_Quiz_Settings | null
	 */
	protected $quiz_settings_instance;

	/**
	 * Forminator_Addon_Hubspot_Quiz_Hooks constructor.
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $quiz_id
	 *
	 * @throws Forminator_Addon_Exception
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $quiz_id ) {
		parent::__construct( $addon, $quiz_id );
		$this->_submit_quiz_error_message = __( 'HubSpot failed to process submitted data. Please check your quiz and try again', 'forminator' );
	}

	/**
	 * Save status of request sent and received for each connected HubSpot Connection
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param array $submitted_data
	 * @param array $form_entry_fields
	 *
	 * @return array
	 */
	public function add_entry_fields( $submitted_data, $form_entry_fields = array() ) {

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 * Filter HubSpot submitted form data to be processed
		 *
		 * @since 1.4
		 *
		 * @param array $submitted_data
		 * @param int $quiz_id current quiz ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon quiz Settings instance.
		 */
		$submitted_data = apply_filters(
			'forminator_addon_hubspot_quiz_submitted_data',
			$submitted_data,
			$quiz_id,
			$quiz_settings_instance
		);

		$quiz_submitted_data  = get_quiz_submitted_data( $this->quiz, $submitted_data, $form_entry_fields );

		$addon_setting_values = $this->quiz_settings_instance->get_quiz_settings_values();
		$quiz_settings        = $this->quiz_settings_instance->get_quiz_settings();
		$addons_fields        = $this->quiz_settings_instance->get_form_fields();

		$form_entry_fields   = forminator_lead_form_data( $submitted_data );
		$submitted_data      = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );
		$submitted_data      = array_merge( $submitted_data, $quiz_submitted_data );

		$data = array();

		/**
		 * Fires before send message to HubSpot
		 *
		 * @since 1.4
		 *
		 * @param int $quiz_id current Form ID.
		 * @param array $submitted_data
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		do_action( 'forminator_addon_hubspot_before_send_message', $quiz_id, $submitted_data, $quiz_settings_instance );

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to hubspot.
			if ( $quiz_settings_instance->is_multi_quiz_settings_complete( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_contact_sync( $key, $submitted_data, $addon_setting_value, $form_entry_fields ),
				);
			}
		}

		$entry_fields = $data;
		/**
		 * Filter HubSpot entry fields to be saved to entry model
		 *
		 * @since 1.4
		 *
		 * @param array $entry_fields
		 * @param int $quiz_id current Form ID.
		 * @param array $submitted_data
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$data = apply_filters(
			'forminator_addon_hubspot_entry_fields',
			$entry_fields,
			$quiz_id,
			$submitted_data,
			$quiz_settings_instance
		);

		return $data;

	}

	/**
	 * Get status on send message to HubSpot
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $connection_id
	 * @param $submitted_data
	 * @param $connection_settings
	 * @param $form_entry_fields
	 *
	 * @return array `is_sent` true means its success send data to HubSpot, false otherwise
	 */
	private function get_status_on_contact_sync( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {
		// initialize as null.
		$api = null;

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;
		$quiz_settings          = $this->quiz_settings_instance->get_quiz_settings();

		$multi_global_ids = $this->addon->get_multi_global_ids();
		$name_suffix      = ! empty( $this->addon->multi_global_id )
				&& ! empty( $multi_global_ids[ $this->addon->multi_global_id ] )
				? ' - ' . $multi_global_ids[ $this->addon->multi_global_id ] : '';
		$connection_name  = isset( $connection_settings['name'] ) ? $connection_settings['name'] : '';
		$connection_name .= $name_suffix;

		//check required fields
		try {
			$api  = $this->addon->get_api();
			$args = array();

			$list_id = $connection_settings['list_id'];

			$deafult_fields = $connection_settings['fields_map'];
			$custom_fields_map = array_filter( $connection_settings['custom_fields_map'] );

			$fields_map = array_merge( $deafult_fields, $custom_fields_map );

			$email_element_id = $connection_settings['fields_map']['email'];
			if ( ! isset( $submitted_data[ $email_element_id ] ) || empty( $submitted_data[ $email_element_id ] ) ) {
				/* translators: ... */
				throw new Forminator_Addon_Hubspot_Exception( __( 'Email on element %1$s not found or not filled on submitted data.', 'forminator' ) );
			}
			$email         = $submitted_data[ $email_element_id ];
			$email         = strtolower( trim( $email ) );
			$args['email'] = $email;

			// processed.
			unset( $fields_map['email'] );
			$common_fields = array(
				'firstname',
				'lastname',
				'jobtitle',
			);
			$extra_field = array();
			if ( ! empty( $custom_fields_map ) ) {
				foreach( $custom_fields_map as $custom => $custom_field ) {
					if( ! empty( $custom ) ) {
						$extra_field[] = $custom;
					}
				}
			}
			$common_fields = array_merge( $common_fields, $extra_field );
			foreach ( $common_fields as $common_field ) {
				// not setup.
				if ( ! isset( $fields_map[ $common_field ] ) ) {
					continue;
				}

				if ( ! empty( $fields_map[ $common_field ] ) ) {
					$element_id = $fields_map[ $common_field ];

					if ( isset( $submitted_data[ $element_id ] ) && ( ! empty( $submitted_data[ $element_id ] ) || 0 === (int) $submitted_data[ $element_id ] ) ) {
						$element_value = $submitted_data[ $element_id ];
						if ( is_array( $element_value ) ) {
							$element_value = implode( ',', $element_value );
						}
					}
					if ( isset( $element_value ) ) {
						$args[ $common_field ] = $element_value;
						unset( $element_value ); // unset for next loop.
					}
				}
				// processed.
				unset( $fields_map[ $common_field ] );
			}
			/**
			 * Filter arguments to passed on to Contact Sync HubSpot API
			 *
			 * @since 1.2
			 *
			 * @param array $args
			 * @param int $quiz_id Current Quiz id.
			 * @param string $connection_id ID of current connection.
			 * @param array $submitted_data
			 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array $quiz_settings Displayed Quiz settings.
			 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_hubspot_create_contact_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_settings,
				$quiz_settings_instance
			);

			$contact_id = $api->add_update_contact( $args );
			// Add contact to contact list.
			$toObjectId = null;
			if ( ! empty( $list_id ) && ! empty( $contact_id ) && ! is_object( $contact_id ) && (int) $contact_id > 0 ) {
				$toObjectId = $contact_id;
				$api->add_to_contact_list( $contact_id, $args['email'], $list_id );
			}

			$create_ticket = isset( $connection_settings['create_ticket'] ) ? $connection_settings['create_ticket'] : '';
			if( empty( $connection_settings['name'] ) ) {
				$connection_settings['name'] = 'HubSpot';
			}
			$fromObjectId  = null;
			if ( '1' === $create_ticket ) {
				$ticket['pipeline_id']        = $connection_settings['pipeline_id'];
				$ticket['status_id']          = $connection_settings['status_id'];
				$ticket_name                  = str_ireplace( '{quiz_name}', forminator_get_name_from_model( $this->quiz ), $connection_settings['ticket_name'] );
				$ticket_name                  = forminator_addon_replace_custom_vars( $ticket_name, $submitted_data, $this->lead_model, $form_entry_fields, false );
				$ticket['ticket_name']        = $ticket_name;
				$ticket_description           = str_ireplace( '{quiz_name}', forminator_get_name_from_model( $this->quiz ), $connection_settings['ticket_description'] );
				$ticket_description           = forminator_addon_replace_custom_vars( $ticket_description, $submitted_data, $this->lead_model, $form_entry_fields, false );
				$ticket['ticket_description'] = $ticket_description;
				$supported_file               = isset( $submitted_data[ $connection_settings['supported_file'] ] ) ? $submitted_data[ $connection_settings['supported_file'] ] : array();
				$supported_file_url           = '';

				if ( ! empty( $supported_file['file_url'] ) ) {
					if ( is_array( $supported_file['file_url'] ) ) {
						$supported_file_url = implode( ', ', $supported_file['file_url'] );
					} else {
						$supported_file_url = $supported_file['file_url'];
					}
				}

				$ticket['supported_file'] = $supported_file_url;

				$object_id = $api->create_ticket( $ticket );

				if ( ! is_null( $toObjectId ) && ! is_object( $object_id ) && (int) $object_id > 0 ) {
					$fromObjectId              = $object_id;
					$associate['fromObjectId'] = $fromObjectId;
					$associate['toObjectId']   = $toObjectId;
					$api->ticket_associate_contact( $associate );
				}
			}

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_name,
				'description'     => __( 'Successfully send data to HubSpot', 'forminator' ),
				'data_sent'       => $api->get_last_data_sent(),
				'data_received'   => $api->get_last_data_received(),
				'url_request'     => $api->get_last_url_request(),
				'contact_id'      => $toObjectId,
				'ticket_id'       => $fromObjectId,
			);

		} catch ( Forminator_Addon_Hubspot_Exception $e ) {

			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to HubSpot' );

			$addon_entry_fields = array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_name,
				'data_sent'       => ( ( $api instanceof Forminator_Addon_Hubspot_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Addon_Hubspot_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Addon_Hubspot_Wp_Api ) ? $api->get_last_url_request() : '' ),
				'contact_id'      => null,
				'ticket_id'       => null,
			);

			return $addon_entry_fields;
		}
	}


	/**
	 * It wil add new row on entry table of submission page, with couple of subentries
	 * subentries included are defined in @see Forminator_Addon_Hubspot_Quiz_Hooks::get_additional_entry_item()
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param                             $addon_meta_data
	 *
	 * @return array
	 */
	public function on_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 *
		 * Filter HubSpot metadata that previously saved on db to be processed
		 *
		 * @since 1.4
		 *
		 * @param array $addon_meta_data
		 * @param int $quiz_id current quiz ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_hubspot_metadata',
			$addon_meta_data,
			$quiz_id,
			$quiz_settings_instance
		);

		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return array();
		}

		return $this->on_render_entry_multi_connection( $addon_meta_datas );

	}

	/**
	 * Loop through addon meta data on multiple HubSpot setup(s)
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $addon_meta_datas
	 *
	 * @return array
	 */
	private function on_render_entry_multi_connection( $addon_meta_datas ) {
		$additional_entry_item = array();
		foreach ( $addon_meta_datas as $addon_meta_data ) {
			$additional_entry_item[] = $this->get_additional_entry_item( $addon_meta_data );
		}

		return $additional_entry_item;

	}

	/**
	 * Format additional entry item as label and value arrays
	 *
	 * - Integration Name : its defined by user when they adding HubSpot integration on their form
	 * - Sent To HubSpot : will be Yes/No value, that indicates whether sending data to HubSpot API was successful
	 * - Info : Text that are generated by addon when building and sending data to HubSpot @see Forminator_Addon_Hubspot_Quiz_Hooks::add_entry_fields()
	 * - Below subentries will be added if full log enabled, @see Forminator_Addon_Hubspot::is_show_full_log() @see FORMINATOR_ADDON_HUBSPOT_SHOW_FULL_LOG
	 *      - API URL : URL that wes requested when sending data to HubSpot
	 *      - Data sent to HubSpot : encoded body request that was sent
	 *      - Data received from HubSpot : json encoded body response that was received
	 *
	 * @param $addon_meta_data
	 *
	 * @since 1.0 HubSpot Addon
	 * @return array
	 */
	private function get_additional_entry_item( $addon_meta_data ) {

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}
		$status                = $addon_meta_data['value'];
		$additional_entry_item = array(
			'label' => __( 'HubSpot Integration', 'forminator' ),
			'value' => '',
		);

		$sub_entries = array();
		if ( isset( $status['connection_name'] ) ) {
			$sub_entries[] = array(
				'label' => __( 'Integration Name', 'forminator' ),
				'value' => $status['connection_name'],
			);
		}

		if ( isset( $status['is_sent'] ) ) {
			$is_sent       = true === $status['is_sent'] ? __( 'Yes', 'forminator' ) : __( 'No', 'forminator' );
			$sub_entries[] = array(
				'label' => __( 'Sent To HubSpot', 'forminator' ),
				'value' => $is_sent,
			);
		}

		if ( isset( $status['description'] ) ) {
			$sub_entries[] = array(
				'label' => __( 'Info', 'forminator' ),
				'value' => $status['description'],
			);
		}

		if ( Forminator_Addon_Hubspot::is_show_full_log() ) {
			// too long to be added on entry data enable this with `define('FORMINATOR_ADDON_HUBSPOT_SHOW_FULL_LOG', true)`.
			if ( isset( $status['url_request'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'API URL', 'forminator' ),
					'value' => $status['url_request'],
				);
			}

			if ( isset( $status['data_sent'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data sent to HubSpot', 'forminator' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_sent'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}

			if ( isset( $status['data_received'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data received from HubSpot', 'forminator' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_received'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
		}

		$additional_entry_item['sub_entries'] = $sub_entries;

		// return single array.
		return $additional_entry_item;
	}

	/**
	 * HubSpot will add a column on the title/header row
	 * its called `HubSpot Info` which can be translated on forminator lang
	 *
	 * @since 1.0 HubSpot Addon
	 * @return array
	 */
	public function on_export_render_title_row() {

		$export_headers = array(
			'info' => __( 'HubSpot Info', 'forminator' ),
		);

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 * Filter HubSpot headers on export file
		 *
		 * @since 1.2
		 *
		 * @param array $export_headers headers to be displayed on export file.
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$export_headers = apply_filters(
			'forminator_addon_hubspot_export_headers',
			$export_headers,
			$quiz_id,
			$quiz_settings_instance
		);

		return $export_headers;
	}

	/**
	 * HubSpot will add a column that give user information whether sending data to HubSpot successfully or not
	 * It will only add one column even its multiple connection, every connection will be separated by comma
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param                             $addon_meta_data
	 *
	 * @return array
	 */
	public function on_export_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 *
		 * Filter HubSpot metadata that previously saved on db to be processed
		 *
		 * @since 1.4
		 *
		 * @param array $addon_meta_data
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_hubspot_metadata',
			$addon_meta_data,
			$quiz_id,
			$quiz_settings_instance
		);

		$export_columns = array(
			'info' => $this->get_from_addon_meta_data( $addon_meta_data, 'description', '' ),
		);

		/**
		 * Filter HubSpot columns to be displayed on export submissions
		 *
		 * @since 1.4
		 *
		 * @param array $export_columns column to be exported.
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Quiz_Entry_Model $entry_model Form Entry Model.
		 * @param array $addon_meta_data meta data saved by addon on entry fields.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$export_columns = apply_filters(
			'forminator_addon_hubspot_export_columns',
			$export_columns,
			$quiz_id,
			$entry_model,
			$addon_meta_data,
			$quiz_settings_instance
		);

		return $export_columns;
	}

	/**
	 * Get Addon meta data, will be recursive if meta data is multiple because of multiple connection added
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param        $addon_meta_data
	 * @param        $key
	 * @param string $default
	 *
	 * @return string
	 */
	protected function get_from_addon_meta_data( $addon_meta_data, $key, $default = '' ) {
		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return $default;
		}

		$addon_meta_data = $addon_meta_data[0];

		// make sure its `status`, because we only add this.
		if ( 'status' !== $addon_meta_data['name'] ) {
			if ( stripos( $addon_meta_data['name'], 'status-' ) === 0 ) {
				$meta_data = array();
				foreach ( $addon_meta_datas as $addon_meta_data ) {
					// make it like single value so it will be processed like single meta data.
					$addon_meta_data['name'] = 'status';

					// add it on an array for next recursive process.
					$meta_data[] = $this->get_from_addon_meta_data( array( $addon_meta_data ), $key, $default );
				}

				return implode( ', ', $meta_data );
			}

			return $default;

		}

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return $default;
		}
		$status = $addon_meta_data['value'];
		if ( isset( $status[ $key ] ) ) {
			$connection_name = '';
			if ( 'connection_name' !== $key ) {
				if ( isset( $status['connection_name'] ) ) {
					$connection_name = '[' . $status['connection_name'] . '] ';
				}
			}

			return $connection_name . $status[ $key ];
		}

		return $default;
	}

	/**
	 * It will delete sent chat
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param  array $addon_meta_data
	 *
	 * @return bool
	 */
	public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		// attach hook first.
		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 *
		 * Filter HubSpot addon metadata that previously saved on db to be processed
		 *
		 * @since 1.4
		 *
		 * @param array $addon_meta_data
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_hubspot_metadata',
			$addon_meta_data,
			$quiz_id,
			$entry_model,
			$quiz_settings_instance
		);

		/**
		 * Fires when HubSpot connected form delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param array $addon_meta_data addon meta data.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings $quiz_settings_instance HubSpot Addon Quiz Settings instance.
		 */
		do_action(
			'forminator_addon_hubspot_on_before_delete_submission',
			$quiz_id,
			$entry_model,
			$addon_meta_data,
			$quiz_settings_instance
		);
		if ( ! Forminator_Addon_Hubspot::is_enable_delete_member() ) {
			// its disabled, go for it!
			return true;
		}
		try {

			$contact_to_delete = array();
			$ticket_to_delete  = array();
			if ( is_array( $addon_meta_data ) ) {
				foreach ( $addon_meta_data as $addon_meta ) {

					if ( isset( $addon_meta['value'] ) && is_array( $addon_meta['value'] ) ) {
						$addon_meta_value = $addon_meta['value'];
						if ( isset( $addon_meta_value['is_sent'] ) && $addon_meta_value['is_sent'] ) {
							if ( isset( $addon_meta_value['contact_id'] ) && ! is_null( $addon_meta_value['contact_id'] ) ) {
								$contact_to_delete[] = $addon_meta_value['contact_id'];
							}
							if ( isset( $addon_meta_value['ticket_id'] ) && ! is_null( $addon_meta_value['ticket_id'] ) ) {
								$ticket_to_delete[] = $addon_meta_value['ticket_id'];
							}
						}
					}
				}
			}

			$contact_to_delete = apply_filters(
				'forminator_addon_hubspot_contact_to_delete',
				$contact_to_delete,
				$quiz_id,
				$addon_meta_data,
				$quiz_settings_instance
			);
			if ( ! empty( $contact_to_delete ) ) {
				$api = $this->addon->get_api();
				foreach ( $contact_to_delete as $contact ) {

					if ( ! empty( $contact ) ) {
						$api->delete_contact( $contact );
					}
				}
			}

			$ticket_to_delete = apply_filters(
				'forminator_addon_hubspot_ticket_to_delete',
				$ticket_to_delete,
				$quiz_id,
				$addon_meta_data,
				$quiz_settings_instance
			);
			if ( ! empty( $ticket_to_delete ) ) {
				$api = $this->addon->get_api();
				foreach ( $ticket_to_delete as $ticket ) {

					if ( ! empty( $ticket ) ) {
						$api->delete_ticket( $ticket );
					}
				}
			}

			return true;

		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			// use wp_error, for future usage it can be returned to page entries.
			$wp_error
				= new WP_Error( 'forminator_addon_hubspot_delete_contact', $e->getMessage() );
			// handle this in addon by self, since page entries cant handle error messages on delete yet.
			wp_die(
				esc_html( $wp_error->get_error_message() ),
				esc_html( $this->addon->get_title() ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);

			return false;
		}

	}
}