<?php

/**
 * Class Forminator_Addon_Aweber_Quiz_Hooks
 *
 * @since 1.0 Aweber Addon
 *
 */
class Forminator_Addon_Aweber_Quiz_Hooks extends Forminator_Addon_Quiz_Hooks_Abstract {

	/**
	 * Addon instance are auto available quiz abstract
	 * Its added here for development purpose,
	 * Auto-complete will resolve addon directly to `Aweber` instance instead of the abstract
	 * And its public properties can be exposed
	 *
	 * @since 1.0 Aweber Addon
	 * @var Forminator_Addon_Aweber
	 */
	protected $addon;

	/**
	 * Quiz Settings Instance
	 *
	 * @since 1.0 Aweber Addon
	 * @var Forminator_Addon_Aweber_Quiz_Settings | null
	 */
	protected $quiz_settings_instance;

	/**
	 * Forminator_Addon_Aweber_Quiz_Hooks constructor.
	 *
	 * @since 1.0 Aweber Addon
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $quiz_id
	 *
	 * @throws Forminator_Addon_Exception
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $quiz_id ) {
		parent::__construct( $addon, $quiz_id );
		$this->_submit_quiz_error_message = __( 'AWeber failed to process submitted data. Please check your quiz and try again', 'forminator' );
	}

	/**
	 * Save status of request sent and received for each connected AWeber Connection
	 *
	 * @since 1.0 AWeber Addon
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
		 * Filter AWeber submitted quiz data to be processed
		 *
		 * @since 1.3
		 *
		 * @param array $submitted_data
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		$submitted_data = apply_filters(
			'forminator_addon_aweber_quiz_submitted_data',
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
		$lead_submitted_data = forminator_addons_lead_submitted_data( $addons_fields, $form_entry_fields );
		$submitted_data      = array_merge( $submitted_data, $quiz_submitted_data );

		$data = array();

		/**
		 * Fires before adding subscriber to AWeber
		 *
		 * @since 1.3
		 *
		 * @param int $quiz_id current Quiz ID.
		 * @param array $submitted_data
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		do_action( 'forminator_addon_aweber_before_add_subscriber', $quiz_id, $submitted_data, $quiz_settings_instance );

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to aweber.
			if ( $quiz_settings_instance->is_multi_quiz_settings_complete( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_add_subscriber( $key, $submitted_data, $addon_setting_value, $form_entry_fields, $lead_submitted_data ),
				);
			}
		}

		$entry_fields = $data;
		/**
		 * Filter AWeber entry fields to be saved to entry model
		 *
		 * @since 1.3
		 *
		 * @param array $entry_fields
		 * @param int $quiz_id current Quiz ID.
		 * @param array $submitted_data
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		$data = apply_filters(
			'forminator_addon_aweber_entry_fields',
			$entry_fields,
			$quiz_id,
			$submitted_data,
			$quiz_settings_instance
		);

		return $data;

	}

	/**
	 * Get status on add subscriber to AWeber
	 *
	 * @since 1.0 AWeber Addon
	 *
	 * @param $connection_id
	 * @param $submitted_data
	 * @param $connection_settings
	 * @param $form_entry_fields
	 *
	 * @return array `is_sent` true means its success send data to AWeber, false otherwise
	 */
	private function get_status_on_add_subscriber( $connection_id, $submitted_data, $connection_settings, $form_entry_fields, $lead_submitted_data ) {
		// initialize as null.
		$api = null;

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;
		$quiz_settings          = $this->quiz_settings_instance->get_quiz_settings();

		//check required fields
		try {
			$api  = $this->addon->get_api();
			$args = array();

			if ( ! isset( $connection_settings['list_id'] ) ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'List ID not properly setup.', 'forminator' ) );
			}

			$list_id = $connection_settings['list_id'];

			$fields_map    = $connection_settings['fields_map'];
			$fields_mapper = $connection_settings['fields_mapper'];

			$email_element_id = $connection_settings['fields_map']['default_field_email'];
			if ( ! isset( $submitted_data[ $email_element_id ] ) || empty( $submitted_data[ $email_element_id ] ) ) {
				throw new Forminator_Addon_Aweber_Exception(/* translators: ... */
					sprintf( __( 'Email Address on element %1$s not found or not filled on submitted data.', 'forminator' ), $email_element_id )
				);
			}
			$email         = $submitted_data[ $email_element_id ];
			$email         = strtolower( trim( $email ) );
			$args['email'] = $email;

			//find existing subscriber first
			/**
			 * Filter arguments to passed on to Find Subscriber AWeber API
			 *
			 * @since 1.3
			 *
			 * @param array $args
			 * @param int $quiz_id Current Quiz id.
			 * @param string $connection_id ID of current connection.
			 * @param array $submitted_data
			 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array $form_entry_fields default entry fields of quiz.
			 * @param array $quiz_settings Displayed Quiz settings.
			 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_aweber_find_subscriber_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_entry_fields,
				$quiz_settings,
				$quiz_settings_instance
			);

			$subscriber_is_exist = false;
			$existing_subscriber = null;
			$setting_values      = $this->addon->get_settings_values();

			$existing_subscriber_request = $api->find_account_list_subscriber( $setting_values['account_id'], $list_id, $args );

			if ( isset( $existing_subscriber_request->entries ) && is_array( $existing_subscriber_request->entries ) ) {
				$existing_subscriber_entries = $existing_subscriber_request->entries;
				if ( isset( $existing_subscriber_entries[0] ) ) {
					$existing_subscriber = $existing_subscriber_entries[0];
					if ( isset( $existing_subscriber->id ) ) {
						$subscriber_is_exist = true;
						// https://labs.aweber.com/docs/reference/1.0#subscriber_entry.
						// you can not modify or delete Subscribers with a status of 'unconfirmed'.
						if ( isset( $existing_subscriber->status ) && 'unconfirmed' === $existing_subscriber->status ) {
							throw new Forminator_Addon_Aweber_Exception( __( 'Unconfirmed subscriber can\'t be modified.', 'forminator' ) );
						}
					}
				}
			}

			// processed.
			unset( $fields_map['default_field_email'] );

			$name_element_id = $connection_settings['fields_map']['default_field_name'];

			if ( isset( $submitted_data[ $name_element_id ] ) && ! empty( $submitted_data[ $name_element_id ] ) ) {
				$name = $submitted_data[ $name_element_id ];

			}

			if ( isset( $name ) ) {
				$args['name'] = $name;
			}

			// processed.
			unset( $fields_map['default_field_name'] );

			$custom_fields = array();
			// process rest extra fields if available.
			foreach ( $fields_map as $field_id => $element_id ) {
				if ( ! empty( $element_id ) ) {

					if ( isset( $submitted_data[ $element_id ] ) && ( ! empty( $submitted_data[ $element_id ] ) || 0 === (int) $submitted_data[ $element_id ] ) ) {
						$element_value = $submitted_data[ $element_id ];
						if ( is_array( $element_value ) ) {
							$element_value = implode( ',', $element_value );
						}
					}

					if ( isset( $fields_mapper[ $field_id ] ) && isset( $element_value ) ) {
						$custom_fields[ $fields_mapper[ $field_id ] ] = (string) $element_value; // custom value must be string.
						unset( $element_value ); // unset for next loop.
					}
				}
			}
			if ( ! empty( $custom_fields ) ) {
				$args['custom_fields'] = $custom_fields;
			}

			if ( isset( $connection_settings['ad_tracking'] ) && ! empty( $connection_settings['ad_tracking'] ) ) {
				$ad_tracking = $connection_settings['ad_tracking'];

				// disable all_fields here.
				$ad_tracking = str_ireplace( '{all_fields}', '', $ad_tracking );
				$ad_tracking = str_ireplace( '{quiz_name}', forminator_get_name_from_model( $this->quiz ), $ad_tracking );
				$ad_tracking = forminator_addon_replace_custom_vars( $ad_tracking, $lead_submitted_data, $this->lead_model, $form_entry_fields, false );
				/**
				 * Filter `ad_tracking` to passed onto API
				 *
				 * @since 1.2
				 *
				 * @param string $card_name
				 * @param int $quiz_id Current Quiz id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of quiz.
				 * @param array $quiz_settings Displayed Quiz settings.
				 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
				 */
				$ad_tracking = apply_filters(
					'forminator_addon_aweber_subscriber_ad_tracking',
					$ad_tracking,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);

				if ( ! empty( $ad_tracking ) && is_string( $ad_tracking ) ) {
					if ( strlen( $ad_tracking ) > 20 ) {
						// 20 chars max.
						$ad_tracking = substr( $ad_tracking, 0, 20 );
					}
					$args['ad_tracking'] = $ad_tracking;
				}
			}

			if ( isset( $connection_settings['tags'] ) && ! empty( $connection_settings['tags'] ) ) {
				$tags = array();
				foreach ( $connection_settings['tags'] as $tag ) {
					if ( stripos( $tag, '{' ) === 0
					     && stripos( $tag, '}' ) === ( strlen( $tag ) - 1 )
					) {
						// translate to value.
						$element_id = str_ireplace( '{', '', $tag );
						$element_id = str_ireplace( '}', '', $element_id );
						if ( isset( $submitted_data[ $element_id ] ) && ! empty( $submitted_data[ $element_id ] ) ) {
							$element_value = $submitted_data[ $element_id ];
							if ( is_array( $element_value ) ) {
								$element_value = implode( ',', $element_value );
							}
						}

						if ( isset( $element_value ) ) {
							$tags[] = strtolower( (string) $element_value ); // tag must be string.
							unset( $element_value ); // unset for next loop.
						}
					} else {
						$tags[] = strtolower( $tag );
					}
				}

				/**
				 * Filter `tags` to passed onto API
				 *
				 * @since 1.2
				 *
				 * @param string $card_name
				 * @param int $quiz_id Current Quiz id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of quiz.
				 * @param array $quiz_settings Displayed Quiz settings.
				 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
				 */
				$tags = apply_filters(
					'forminator_addon_aweber_subscriber_tags',
					$tags,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);

				if ( ! empty( $tags ) ) {
					sort( $tags );
					$args['tags'] = $tags;
				}
			}

			$ip_address = Forminator_Geo::get_user_ip();

			/**
			 * Filter `ip_address` to passed onto API
			 *
			 * @since 1.2
			 *
			 * @param string $card_name
			 * @param int $quiz_id Current Quiz id.
			 * @param string $connection_id ID of current connection.
			 * @param array $submitted_data
			 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array $form_entry_fields default entry fields of quiz.
			 * @param array $quiz_settings Displayed Quiz settings.
			 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
			 */
			$ip_address = apply_filters(
				'forminator_addon_aweber_subscriber_ip_address',
				$ip_address,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_entry_fields,
				$quiz_settings,
				$quiz_settings_instance
			);

			$args['ip_address'] = $ip_address;

			if ( ! $subscriber_is_exist ) {
				/**
				 * Filter arguments to passed on to Add Subscriber AWeber API
				 *
				 * @since 1.3
				 *
				 * @param array $args
				 * @param int $quiz_id Current Quiz id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of quiz.
				 * @param array $quiz_settings Displayed Quiz settings.
				 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
				 */
				$args = apply_filters(
					'forminator_addon_aweber_add_subscriber_args',
					$args,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);

				$api->add_account_list_subscriber( $setting_values['account_id'], $list_id, $args );

			} else {
				/**
				 * This will only update information
				 * subscribed, unconfirmed, unsubscribed status wont be updated
				 * use hooks @see forminator_addon_aweber_update_subscriber_args, if needed
				 */
				// update if exist.
				$current_tags = array();
				if ( isset( $existing_subscriber->tags ) && is_array( $existing_subscriber->tags ) ) {
					$current_tags = $existing_subscriber->tags;
				}

				if ( ! isset( $args['tags'] ) ) {
					$args['tags'] = array();
				}

				$add_tags    = array_diff( $args['tags'], $current_tags );
				$remove_tags = array_diff( $current_tags, $args['tags'] );

				sort( $add_tags );
				sort( $remove_tags );

				$new_tags = array(
					'add'    => $add_tags,
					'remove' => $remove_tags,
				);

				$args['tags'] = $new_tags;

				/**
				 * Filter arguments to passed on to Add Subscriber AWeber API
				 *
				 * @since 1.3
				 *
				 * @param array $args
				 * @param int $quiz_id Current Quiz id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of quiz.
				 * @param array $quiz_settings Displayed Quiz settings.
				 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
				 */
				$args = apply_filters(
					'forminator_addon_aweber_update_subscriber_args',
					$args,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);

				$api->update_account_list_subscriber( $setting_values['account_id'], $list_id, $existing_subscriber->id, $args );
			}

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => __( 'Successfully send data to AWeber', 'forminator' ),
				'data_sent'       => $api->get_last_data_sent(),
				'data_received'   => $api->get_last_data_received(),
				'url_request'     => $api->get_last_url_request(),
				'list_id'         => $list_id, // for delete reference.
			);

		} catch ( Forminator_Addon_Aweber_Exception $e ) {
			$addon_entry_fields = array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $api instanceof Forminator_Addon_Aweber_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Addon_Aweber_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Addon_Aweber_Wp_Api ) ? $api->get_last_url_request() : '' ),
			);

			return $addon_entry_fields;
		}
	}

	/**
	 * It wil add new row on entry table of submission page, with couple of subentries
	 * subentries included are defined in @see Forminator_Addon_Aweber_Quiz_Hooks::get_additional_entry_item()
	 *
	 * @since 1.0 AWeber Addon
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
		 * Filter active metadata that previously saved on db to be processed
		 *
		 * @since 1.3
		 *
		 * @param array $addon_meta_data
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_aweber_metadata',
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
	 * Loop through addon meta data on multiple AWeber(s)
	 *
	 * @since 1.0 AWeber Addon
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
	 * - Integration Name : its defined by user when they adding AWeber integration on their quiz
	 * - Sent To AWeber : will be Yes/No value, that indicates whether sending data to AWeber was successful
	 * - Info : Text that are generated by addon when building and sending data to AWeber @see Forminator_Addon_Aweber_Quiz_Hooks::add_entry_fields()
	 * - Below subentries will be added if full log enabled, @see Forminator_Addon_Aweber::is_show_full_log() @see FORMINATOR_ADDON_AWEBER_SHOW_FULL_LOG
	 *      - API URL : URL that wes requested when sending data to AWeber
	 *      - Data sent to AWeber : encoded body request that was sent
	 *      - Data received from AWeber : json encoded body response that was received
	 *
	 * @param $addon_meta_data
	 *
	 * @since 1.0 AWeber Addon
	 * @return array
	 */
	private function get_additional_entry_item( $addon_meta_data ) {

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}
		$status                = $addon_meta_data['value'];
		$additional_entry_item = array(
			'label' => __( 'AWeber Integration', 'forminator' ),
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
				'label' => __( 'Sent To AWeber', 'forminator' ),
				'value' => $is_sent,
			);
		}

		if ( isset( $status['description'] ) ) {
			$sub_entries[] = array(
				'label' => __( 'Info', 'forminator' ),
				'value' => $status['description'],
			);
		}

		if ( Forminator_Addon_Aweber::is_show_full_log() ) {
			// too long to be added on entry data enable this with `define('FORMINATOR_ADDON_AWEBER_SHOW_FULL_LOG', true)`.
			if ( isset( $status['url_request'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'API URL', 'forminator' ),
					'value' => $status['url_request'],
				);
			}

			if ( isset( $status['data_sent'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data sent to AWeber', 'forminator' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_sent'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}

			if ( isset( $status['data_received'] ) ) {
				$sub_entries[] = array(
					'label' => __( 'Data received from AWeber', 'forminator' ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_received'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
		}

		$additional_entry_item['sub_entries'] = $sub_entries;

		// return single array.
		return $additional_entry_item;
	}

	/**
	 * AWeber will add a column on the title/header row
	 * its called `AWeber Info` which can be translated on forminator lang
	 *
	 * @since 1.0 AWeber Addon
	 * @return array
	 */
	public function on_export_render_title_row() {

		$export_headers = array(
			'info' => __( 'AWeber Info', 'forminator' ),
		);

		$quiz_id                = $this->quiz_id;
		$quiz_settings_instance = $this->quiz_settings_instance;

		/**
		 * Filter AWeber headers on export file
		 *
		 * @since 1.3
		 *
		 * @param array $export_headers headers to be displayed on export file.
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		$export_headers = apply_filters(
			'forminator_addon_aweber_export_headers',
			$export_headers,
			$quiz_id,
			$quiz_settings_instance
		);

		return $export_headers;
	}

	/**
	 * AWeber will add a column that give user information whether sending data to AWeber successfully or not
	 * It will only add one column even its multiple connection, every connection will be separated by comma
	 *
	 * @since 1.0 AWeber Addon
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
		 * Filter AWeber metadata that previously saved on db to be processed
		 *
		 * @since 1.3
		 *
		 * @param array $addon_meta_data
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_aweber_metadata',
			$addon_meta_data,
			$quiz_id,
			$quiz_settings_instance
		);

		$export_columns = array(
			'info' => $this->get_from_addon_meta_data( $addon_meta_data, 'description', '' ),
		);

		/**
		 * Filter AWeber columns to be displayed on export submissions
		 *
		 * @since 1.3
		 *
		 * @param array $export_columns column to be exported.
		 * @param int $quiz_id current Quiz ID.
		 * @param Forminator_Form_Entry_Model $entry_model Form Entry Model.
		 * @param array $addon_meta_data meta data saved by addon on entry fields.
		 * @param Forminator_Addon_Aweber_Quiz_Settings $quiz_settings_instance AWeber Addon Quiz Settings instance.
		 */
		$export_columns = apply_filters(
			'forminator_addon_aweber_export_columns',
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
	 * @since 1.0 AWeber Addon
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

	// /**
	// * DELETE NOT SUPPORTED HERE, BECAUSE AWEBER API NOT RETURNING SUBSCRIBER ID ON CREATE SUBSCRIBER.
	// *.
	// * @param Forminator_Form_Entry_Model $entry_model.
	// * @param                             $addon_meta_data.
	// */.
	// public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {.
	// parent::on_before_delete_entry( $entry_model, $addon_meta_data );.
	// }.
}
