<?php

require_once dirname( __FILE__ ) . '/class-forminator-addon-mailchimp-form-settings-exception.php';

/**
 * Class Forminator_Addon_Mailchimp_Form_Settings
 * Form Settings Mailchimp Process
 *
 * @since 1.0 Mailchimp Addon
 */
class Forminator_Addon_Mailchimp_Form_Settings extends Forminator_Addon_Form_Settings_Abstract {

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var Forminator_Addon_Mailchimp
	 */
	protected $addon;

	/**
	 * Stores mailchimp group data
	 *
	 * @var array Groups
	 */
	private $groups_data = array();

	/**
	 * Stores mailchimp GDPR data
	 *
	 * @var array
	 */
	private $gdpr_data = array();

	/**
	 * Stores mailchimp tags data ( static segments )
	 *
	 * @var array Tags
	 */
	private $tags_data = array();

	/**
	 * Forminator_Addon_Mailchimp_Form_Settings constructor.
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $form_id
	 *
	 * @throws Forminator_Addon_Exception
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
		parent::__construct( $addon, $form_id );

		$this->_update_form_settings_error_message = __(
			'The update to your settings for this form failed, check the form input and try again.',
			'forminator'
		);
	}

	/**
	 * For settings Wizard steps
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return array
	 */
	public function form_settings_wizards() {
		// Already filtered on Forminator_Addon_Abstract::get_wizard.
		$this->addon_form_settings = $this->get_form_settings_values();
		// Numerical array steps.
		$steps = array(
			// 1
			array(
				'callback'     => array( $this, 'choose_mail_list' ),
				'is_completed' => array( $this, 'step_choose_mail_list_is_completed' ),
			),
			// 2
			array(
				'callback'     => array( $this, 'choose_tags' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
			// 3
			array(
				'callback'     => array( $this, 'choose_group' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
			// 4
			array(
				'callback'     => array( $this, 'choose_gdpr' ),
				'is_completed' => array( $this, 'step_is_completed' ),
			),
			// 5
			array(
				'callback'     => array( $this, 'map_fields' ),
				'is_completed' => array( $this, 'step_map_fields_is_completed' ),
			),
		);

		if ( ! empty( $this->addon_form_settings['mail_list_id'] ) ) {
			$this->set_tags();
			$this->set_groups();
			$this->set_gdpr_fields();
			// Note: order DESC is neccessary here!
			if ( empty( $this->gdpr_data ) ) {
				unset( $steps[3] );
			}
			if ( empty( $this->groups_data ) ) {
				unset( $steps[2] );
			}
			if ( empty( $this->tags_data ) ) {
				unset( $steps[1] );
			}
		} else {
			unset( $steps[3], $steps[2], $steps[1] );
		}

		return $steps;
	}

	/**
	 * Choose Mail wizard
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function choose_mail_list( $submitted_data ) {
		$default_data = array(
			'mail_list_id'         => '',
			'enable_double_opt_in' => '',
			'enable_gdpr'          => '',
			'gdpr_text'            => '',
		);

		$current_data = $this->get_current_data( $default_data, $submitted_data );

		forminator_addon_maybe_log( __METHOD__, 'current_data', $current_data );

		$is_submit = ! empty( $submitted_data );

		$error_message        = '';
		$input_error_messages = array();

		$html_select_mail_list = '';
		$html_field_mail_list  = '';

		try {
			$api        = $this->addon->get_api();
			$mail_lists = $api->get_all_lists();
			$lists      = wp_list_pluck( $mail_lists, 'name', 'id' );

			// Get mailchimp list to be selected, bail on empty.
			if ( empty( $mail_lists ) ) {
				throw new Forminator_Addon_Mailchimp_Exception( 'Your Mailchimp Audiences is empty, please create one.' );
			}

			$html_select_mail_list  = '<div class="forminator-select-refresh">';
			$html_select_mail_list .= '<select name="mail_list_id" class="sui-select">';
			$html_select_mail_list .= self::get_select_html( $lists, $current_data['mail_list_id'] );
			$html_select_mail_list .= '</select>';
			$html_select_mail_list .= self::refresh_button();
			$html_select_mail_list .= '</div>';

			// logic when user submit mail list.
			if ( $is_submit ) {
				forminator_addon_maybe_log( __METHOD__, '$submitted_data', $submitted_data );
				$mail_list_name = $this->get_choosen_mail_list_name( $mail_lists, $submitted_data );
				forminator_addon_maybe_log( __METHOD__, '$mail_list_name', $mail_list_name );
				if ( empty( $mail_list_name ) ) {
					throw new Forminator_Addon_Mailchimp_Form_Settings_Exception( __( 'Please select a valid Email Audience', 'forminator' ), 'mail_list_id' );
				}
				if ( ! empty( $this->addon_form_settings['mail_list_id'] ) && $this->addon_form_settings['mail_list_id'] !== $submitted_data['mail_list_id'] ) {
					// reset cache cuz List id is changed.
					unset(
						$this->addon_form_settings['tags_data'],
						$this->addon_form_settings['groups_data'],
						$this->addon_form_settings['gdpr_data'],
						$this->addon_form_settings['tags'],
						$this->addon_form_settings['group'],
						$this->addon_form_settings['group_interest'],
						$this->addon_form_settings['gdpr']
					);
				}
				$this->addon_form_settings['mail_list_id']   = $submitted_data['mail_list_id'];
				$this->addon_form_settings['mail_list_name'] = $mail_list_name;

				if ( isset( $submitted_data['enable_double_opt_in'] ) ) {
					$this->addon_form_settings['enable_double_opt_in'] = $submitted_data['enable_double_opt_in'];
				} else {
					$this->addon_form_settings['enable_double_opt_in'] = 0;
				}

				// GDPR fields.
				if ( Forminator_Addon_Mailchimp::is_enable_gdpr() ) {
					if ( isset( $submitted_data['enable_gdpr'] ) ) {
						$this->addon_form_settings['enable_gdpr'] = $submitted_data['enable_gdpr'];
					} else {
						$this->addon_form_settings['enable_gdpr'] = 0;
					}
					if ( isset( $submitted_data['gdpr_text'] ) ) {
						$this->addon_form_settings['gdpr_text'] = $submitted_data['gdpr_text'];
					}
				}

				$this->save_form_settings_values( $this->addon_form_settings );
			}

			$html_field_mail_list
				= '<div class="sui-form-field" style="margin-bottom: 10px;">
						<label class="sui-label">' . __( 'Email Audience', 'forminator' ) . '</label>
						' . $html_select_mail_list . '
					</div>';

		} catch ( Forminator_Addon_Mailchimp_Form_Settings_Exception $e ) {
			// send errors with related input.
			$input_error_messages = $e->get_input_exceptions();
			if ( isset( $input_error_messages['mail_list_id'] ) ) {
				$html_field_mail_list
					= '<div class="sui-form-field sui-form-field-error" style="margin-bottom: 10px;">
							<label class="sui-label">' . __( 'Email Audience', 'forminator' ) . '</label>
							' . $html_select_mail_list . '
							<span class="sui-error-message">' . esc_html( $input_error_messages['mail_list_id'] ) . '</span>
						</div>';
			}
		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			// send error back to client.
			$error_message = '<div role="alert" class="sui-notice sui-notice-red sui-active" style="display: block; text-align: left;" aria-live="assertive">';

				$error_message .= '<div class="sui-notice-content">';

					$error_message .= '<div class="sui-notice-message">';

						$error_message .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';

						$error_message .= '<p>' . $e->getMessage() . '</p>';

					$error_message .= '</div>';

				$error_message .= '</div>';

			$error_message .= '</div>';
		}

		$buttons = array();
		// add disconnect button if already is_form_connected.
		if ( $this->addon->is_form_connected( $this->form_id ) ) {
			$buttons['disconnect']['markup'] = Forminator_Addon_Mailchimp::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate Mailchimp from this Form.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
									Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
									'</div>';

		$gdpr_fields = '';
		if ( Forminator_Addon_Mailchimp::is_enable_gdpr() ) {
			$gdpr_fields = '<div class="sui-form-field">' .
						'<label class="sui-label">' . __( 'Enable GDPR', 'forminator' ) . '</label>
								<input type="checkbox" name="enable_gdpr" value="1" ' . checked( 1, $current_data['enable_double_opt_in'], false ) . '>
							</div>

							<div class="sui-form-field">
								<label class="sui-label">' . __( 'GDPR Text', 'forminator' ) . '</label>
								<textarea name="gdpr_text">' . $current_data['gdpr_text'] . '</textarea>
							</div>';
		}

		$html  = '<div class="forminator-integration-popup__header">';
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . __( 'Choose Audience', 'forminator' ) . '</h3>';
			$html .= '<p class="sui-description">' . __( 'Choose the audience you want to send form data to.', 'forminator' ) . '</p>';
			$html .= $error_message;
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
			$html .= $html_field_mail_list;
			$html .= '<div class="sui-form-field">';
				$html .= '<label class="sui-toggle">';
					$html .= '<input type="checkbox" name="enable_double_opt_in" value="1" id="forminator_addon_mailchimp_enable_double_opt_in" ' . checked( 1, $current_data['enable_double_opt_in'], false ) . ' />';
					$html .= '<span class="sui-toggle-slider"></span>';
				$html .= '</label>';
				$html .= '<span class="sui-toggle-label" for="forminator_addon_mailchimp_enable_double_opt_in">' . __( 'Use Double Opt in', 'forminator' ) . '</span>';
			$html .= '</div>';
			$html .= $gdpr_fields;
		$html .= '</form>';

		return array(
			'html'       => $html,
			'redirect'   => false,
			'buttons'    => $buttons,
			'has_errors' => ( ! empty( $error_message ) || ! empty( $input_error_messages ) ),
			'size'       => 'small',
		);

	}

	/**
	 * Get current data based on submitted or saved data
	 *
	 * @param array $current_data Default data.
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	private function get_current_data( $current_data, $submitted_data ) {
		foreach ( array_keys( $current_data ) as $key ) {
			if ( isset( $submitted_data[ $key ] ) ) {
				$current_data[ $key ] = $submitted_data[ $key ];
			} elseif ( isset( $this->addon_form_settings[ $key ] ) ) {
				$current_data[ $key ] = $this->addon_form_settings[ $key ];
			}
		}

		return $current_data;
	}

	/**
	 * Check if it's submission a step.
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $step Step.
	 * @return boolean
	 */
	private static function is_submit( $submitted_data, $step ) {
		if ( ! empty( $submitted_data ) ) {
			return true;
		}

		$post_data = filter_input( INPUT_POST, 'data' );

		if ( ! is_array( $post_data ) && is_string( $post_data ) ) {
			$post_string = $post_data;
			$post_data   = array();
			wp_parse_str( $post_string, $post_data );

			return in_array( 'is_submit', array_keys( $post_data ), true ) && strval( $step ) === $post_data['is_submit'];
		}

		return false;
	}

	/**
	 * Choose Tags wizard
	 *
	 * @since 1.15.3 Mailchimp Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function choose_tags( $submitted_data ) {
		$step         = 2;
		$default_data = array(
			'tags' => array(),
		);
		$is_submit    = self::is_submit( $submitted_data, $step );

		if ( $is_submit && empty( $submitted_data ) ) {
			$submitted_data = $default_data;
		}
		$current_data = $this->get_current_data( $default_data, $submitted_data );

		forminator_addon_maybe_log( __METHOD__, 'current_data', $current_data );

		$selectbox = $this->get_second_step_options_tags( $current_data['tags'] );

		// Logic when user submit tags.
		if ( $is_submit ) {
			forminator_addon_maybe_log( __METHOD__, '$submitted_data', $submitted_data );

			if ( is_array( $submitted_data['tags'] ) ) {

				$save_tags = array();
				// Store the tag id and tag name.
				foreach ( $submitted_data['tags'] as $tag_id ) {

					if ( '-1' === $tag_id || empty( $this->tags_data[ $tag_id ] ) ) {
						continue;
					}

					$save_tags[ $tag_id ] = esc_html( $this->tags_data[ $tag_id ] );
				}

				$this->addon_form_settings['tags'] = $save_tags;
			}

			$this->save_form_settings_values( $this->addon_form_settings );
		}

		$buttons = array(
			'cancel' => array(
				'markup' => Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Back', 'forminator' ), 'sui-button-ghost forminator-addon-back' ),
			),
			'next'   => array(
				'markup' => '<div class="sui-actions-right">' .
					Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
				'</div>',
			),
		);

		$html  = '<div class="forminator-integration-popup__header">';
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . esc_html__( 'Mailchimp Tags', 'forminator' ) . '</h3>';
			$html .= '<p class="sui-description">' . esc_html__( 'Mailchimp tags help you organize your audience. You can add as many tags as you’d like to your form subscribers.', 'forminator' ) . '</p>';
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
			$html .= $selectbox;
			$html .= '<input type="hidden" name="is_submit" value="' . $step . '">';
		$html .= '</form>';

		return array(
			'html'     => $html,
			'redirect' => false,
			'buttons'  => $buttons,
			'has_back' => true,
		);
	}

	/**
	 * Choose Groups wizard
	 *
	 * @since 1.15.3 Mailchimp Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function choose_group( $submitted_data ) {
		$default_data = array(
			'group' => '',
		);
		$is_submit    = ! empty( $submitted_data );

		if ( $is_submit && empty( $submitted_data ) ) {
			$submitted_data = $default_data;
		}
		$current_data = $this->get_current_data( $default_data, $submitted_data );

		forminator_addon_maybe_log( __METHOD__, 'current_data', $current_data );

		$selectbox = $this->get_third_step_options_groups( $current_data['group'] );

		// Logic when user submit group.
		if ( $is_submit ) {
			forminator_addon_maybe_log( __METHOD__, '$submitted_data', $submitted_data );
			$group_id = $submitted_data['group'];

			// Store the selected group_id.
			$this->addon_form_settings['group'] = $group_id;

			if ( ! empty( $submitted_data['group'] ) ) {
				// Store the group name.
				$this->addon_form_settings['group_name'] = $this->groups_data[ $group_id ]['name'];

				// Store the group type.
				$this->addon_form_settings['group_type'] = $this->groups_data[ $group_id ]['type'];
			}
			$this->addon_form_settings['group_interest'] = isset( $submitted_data['group_interest'] ) ? $submitted_data['group_interest'] : '';
			$interests                                   = $this->get_interests();

			$this->addon_form_settings['interest_options'] = $interests;

			$this->addon_form_settings['group_interest_placeholder'] = isset( $submitted_data['group_interest_placeholder'] ) ? $submitted_data['group_interest_placeholder'] : '';

			$this->save_form_settings_values( $this->addon_form_settings );
		}

		$buttons = array(
			'cancel' => array(
				'markup' => Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Back', 'forminator' ), 'sui-button-ghost forminator-addon-back' ),
			),
			'next'   => array(
				'markup' => '<div class="sui-actions-right">' .
					Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
				'</div>',
			),
		);

		$html  = '<div class="forminator-integration-popup__header">';
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . esc_html__( 'Mailchimp Groups', 'forminator' ) . '</h3>';
			$html .= '<p class="sui-description">' . esc_html__( 'Mailchimp groups allow you to categorize your audience based on their interests. Use the options below to group your audience based on submitted form data.', 'forminator' ) . '</p>';
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
			$html .= $selectbox;
		$html .= '</form>';

		return array(
			'html'     => $html,
			'redirect' => false,
			'buttons'  => $buttons,
			'has_back' => true,
		);
	}

	/**
	 * Choose GDPR wizard
	 *
	 * @since 1.15.3 Mailchimp Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function choose_gdpr( $submitted_data ) {
		$step         = 4;
		$default_data = array(
			'gdpr' => array(),
		);
		$is_submit    = self::is_submit( $submitted_data, $step );

		if ( $is_submit && empty( $submitted_data ) ) {
			$submitted_data = $default_data;
		}
		$current_data = $this->get_current_data( $default_data, $submitted_data );

		forminator_addon_maybe_log( __METHOD__, 'current_data', $current_data );

		$checkboxes = $this->get_forth_step_options_gdpr( array_keys( $current_data['gdpr'] ) );

		// Logic when user submit gdpr.
		if ( $is_submit ) {
			forminator_addon_maybe_log( __METHOD__, '$submitted_data', $submitted_data );

			if ( is_array( $submitted_data['gdpr'] ) ) {

				$save_data = array();
				// Store the gdpr id and gdpr title.
				foreach ( $submitted_data['gdpr'] as $gdpr_id ) {

					if ( empty( $this->gdpr_data[ $gdpr_id ] ) ) {
						continue;
					}

					$save_data[ $gdpr_id ] = esc_html( $this->gdpr_data[ $gdpr_id ] );
				}

				$this->addon_form_settings['gdpr'] = $save_data;
			}

			$this->save_form_settings_values( $this->addon_form_settings );
		}

		$buttons = array(
			'cancel' => array(
				'markup' => Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Back', 'forminator' ), 'sui-button-ghost forminator-addon-back' ),
			),
			'next'   => array(
				'markup' => '<div class="sui-actions-right">' .
					Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
				'</div>',
			),
		);

		$html  = '<div class="forminator-integration-popup__header">';
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . esc_html__( 'Mailchimp GDPR Permissions', 'forminator' ) . '</h3>';
			$html .= '<p class="sui-description">' . esc_html__( 'You can optionally opt-in the subscribers into your Mailchimp’s audience permissions. Choose the GDPR permissions to opt-in your subscribers.', 'forminator' ) . '</p>';
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
			$html .= $checkboxes;
			$html .= '<input type="hidden" name="is_submit" value="' . $step . '">';
		$html .= '</form>';

		return array(
			'html'     => $html,
			'redirect' => false,
			'buttons'  => $buttons,
			'has_back' => true,
		);
	}

	/**
	 * Returns Mailchimp group interests list
	 *
	 * @param array $data Data.
	 * @return string
	 */
	public function get_group_interests( $data ) {
		if ( ! empty( $data['global_id'] ) ) {
			$this->addon->multi_global_id = $data['global_id'];
		}

		$this->addon_form_settings = $this->get_form_settings_values();

		if ( ! empty( $data['group'] ) ) {
			$group = $data['group'];
		} else {
			return '';
		}

		$api       = $this->addon->get_api();
		$interests = $api->get_interests(
			$this->addon_form_settings['mail_list_id'],
			$group
		);

		// If no group was selected or the selected group doesn't have interests.
		if ( empty( $interests ) || ! is_array( $interests ) ) {
			return '';
		}

		if ( isset( $this->addon_form_settings['group'] ) && isset( $this->addon_form_settings['group_interest'] ) && $this->addon_form_settings['group'] === $group ) {
			$interest_id = $this->addon_form_settings['group_interest'];
		} else {
			$interest_id = '';
		}

		$this->set_groups();
		$groups     = wp_list_pluck( $this->groups_data, 'type', 'id' );
		$group_type = isset( $groups[ $group ] ) ? $groups[ $group ] : '';

		$html = $this->get_group_interest_options( $group_type, $interests, $interest_id );

		return $html;
	}

	/**
	 * Return an array of options used to display the settings of Group interests.
	 *
	 * @todo use $interest_id to show the selected values if set. This can be an array if group type is checkbox.
	 *
	 * @param string $type Group type.
	 * @param array  $interests Interests.
	 * @param string $interest_id Interest ID.
	 * @return array
	 */
	private function get_group_interest_options( $type, $interests, $interest_id ) {
		if ( in_array( $type, array( 'dropdown' ), true ) ) {
			$type = 'select';
		}
		if ( in_array( $type, array( 'hidden' ), true ) ) {
			$type = 'checkboxes';
		}

		$choose_prompt = esc_html__( 'Select Interest(s)', 'forminator' );
		$input_name    = 'group_interest';

		$html  = '<div class="sui-form-field">';
		$html .= '<label class="sui-label" for="' . $input_name . '">' . $choose_prompt . '</label>';

		switch ( $type ) {
			case 'checkboxes':
				$html .= self::get_checkboxes_html( $interests, $input_name . '[]', $interest_id );
				break;

			case 'radio':
				$html .= self::get_radios_html( $interests, $input_name, $interest_id );
				break;

			default:
				$html .= '<select id="' . $input_name . '" name="' . $input_name . '" class="sui-select">';
				$html .= self::get_select_html( $interests, $interest_id );
				$html .= '</select>';
				break;
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Return html of options used to display the settings of the 2nd step for tags.
	 *
	 * @since 1.15.3
	 *
	 * @param string $selected_ids Selected Tag ID.
	 * @return array
	 */
	private function get_second_step_options_tags( $selected_ids ) {
		ob_start();
		?>
			<div class="sui-form-field">
                <label class="sui-label" for="tags"><strong><?php echo esc_html__( 'Tags', 'forminator' ) . '</strong>&nbsp;(' . esc_html__( 'Optional', 'forminator' ) . ')'; ?></label>
				<select class="sui-select" name="tags[]" id="tags"
                        multiple="multiple"
                        data-tags="false"
                        data-token-separators="[',']"
                        data-placeholder="<?php esc_html_e( 'Start typing to add tags', 'forminator' ); ?>"
                        data-allow-clear="false">

				<?php foreach ( $this->tags_data as $tag_id => $name ) { ?>
					<option value="<?php echo esc_attr( $tag_id ); ?>"<?php selected( in_array( $tag_id, array_keys( $selected_ids ), true ) ); ?>><?php echo esc_html( $name ); ?></option>
				<?php } ?>
                </select>
            </div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Return html of options used to display the settings of the 3rd step for groups.
	 *
	 * @since 1.15.3
	 *
	 * @param string $selected_id Selected group ID.
	 * @return array
	 */
	private function get_third_step_options_groups( $selected_id ) {
		$lists = wp_list_pluck( $this->groups_data, 'name', 'id' );

		$html  = '<div class="sui-form-field">';
		$html .= '<label class="sui-label" for="group"><strong>' . esc_html__( 'Group category', 'forminator' ) . '</strong>&nbsp;(' . esc_html__( 'Optional', 'forminator' ) . ')</label>';
		$html .= '<select id="group" name="group" data-nonce="' . wp_create_nonce( 'forminator_mailchimp_interests' ) . '" class="sui-select" data-placeholder="' . esc_html__( 'Select group category', 'forminator' ) . '">';
			$html .= '<option></option>';
			$html .= self::get_select_html( $lists, $selected_id );
		$html .= '</select>';
		$html .= '<span class="sui-description">' . esc_html__( 'Select a group category to see more options.', 'forminator' ) . '</span>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Return html of options used to display the settings of the 4rd step for GDPR.
	 *
	 * @since 1.15.3
	 *
	 * @param string $selected_ids Selected GDRP ID.
	 * @return array
	 */
	private function get_forth_step_options_gdpr( $selected_ids ) {
		$html  = '<div class="sui-form-field">';
		$html .= '<label class="sui-label"><strong>' . esc_html__( 'GDPR permissions', 'forminator' ) . '</strong>&nbsp;(' . esc_html__( 'Optional', 'forminator' ) . ')</label>';
		$html .= self::get_checkboxes_html( $this->gdpr_data, 'gdpr[]', $selected_ids );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Step mapping fields on wizard
	 *
	 * @since 1.0 Mailchimp Addon
	 * @since 1.2 Refactor `hasBack` to `has_back`
	 *
	 * @param $submitted_data
	 *
	 * @return array
	 */
	public function map_fields( $submitted_data ) {
		$is_close              = false;
		$is_submit             = ! empty( $submitted_data );
		$error_message         = '';
		$html_input_map_fields = '';
		$input_error_messages  = array();

		try {
			// get merge fields.
			$mailchimp_fields_list_request = $this->addon->get_api()->get_list_merge_fields( $this->addon_form_settings['mail_list_id'], array() );
			$mailchimp_required_fields     = array();
			$mailchimp_required_fields_ids = array();
			$mailchimp_fields_list         = array();
			if ( isset( $mailchimp_fields_list_request->merge_fields ) && is_array( $mailchimp_fields_list_request->merge_fields ) && ! empty( $mailchimp_fields_list_request->merge_fields ) ) {
				$mailchimp_fields_list = $mailchimp_fields_list_request->merge_fields;
			}

			$current_data = array( 'fields_map' => array() );
			foreach ( $mailchimp_fields_list as $item ) {
				if ( $item->required ) {
					$mailchimp_required_fields []    = $item;
					$mailchimp_required_fields_ids[] = $item->merge_id;
				}
				if ( 'address' === $item->type ) {
					$address_fields = $this->mail_address_fields();
					foreach ( $address_fields as $addr => $address ) {
						$current_data['fields_map'][ $item->tag ][ $addr ] = '';
					}
				} else {
					$current_data['fields_map'][ $item->tag ] = '';
				}
			}

			// find type of email.
			$email_fields = array();
			foreach ( $this->form_fields as $form_field ) {
				if ( 'email' === $form_field['type'] ) {
					$email_fields[] = $form_field;
				}
			}

			// EMAIL.
			$current_data['fields_map']['EMAIL'] = '';
			if ( isset( $submitted_data['fields_map']['EMAIL'] ) ) {
				$current_data['fields_map']['EMAIL'] = $submitted_data['fields_map']['EMAIL'];
			} elseif ( isset( $this->addon_form_settings['fields_map']['EMAIL'] ) ) {
				$current_data['fields_map']['EMAIL'] = $this->addon_form_settings['fields_map']['EMAIL'];
			}

			foreach ( $current_data['fields_map'] as $key => $current_field ) {
				if ( is_array( $current_field ) ) {
					foreach ( $current_field as $a => $current ) {
						if ( isset( $submitted_data['fields_map'][ $key ][ $a ] ) ) {
							$current_data['fields_map'][ $key ][ $a ] = $submitted_data['fields_map'][ $key ][ $a ];
						} elseif ( isset( $this->addon_form_settings['fields_map'][ $key ] ) ) {
							$current_data['fields_map'][ $key ][ $a ] = $this->addon_form_settings['fields_map'][ $key ][ $a ];
						}
					}
				} else {
					if ( isset( $submitted_data['fields_map'][ $key ] ) ) {
						$current_data['fields_map'][ $key ] = $submitted_data['fields_map'][ $key ];
					} elseif ( isset( $this->addon_form_settings['fields_map'][ $key ] ) ) {
						$current_data['fields_map'][ $key ] = $this->addon_form_settings['fields_map'][ $key ];
					}
				}
			}

			/** Build table map fields input */
			ob_start();
			$this->get_input_map_fields( $email_fields, $mailchimp_fields_list, $current_data );
			$html_input_map_fields = ob_get_clean();

			if ( $is_submit ) {
				$this->step_map_fields_validate( $mailchimp_fields_list, $mailchimp_required_fields, $submitted_data );
				$this->save_form_settings_values( $this->addon_form_settings );
				$is_close = true;
			}
		} catch ( Forminator_Addon_Mailchimp_Form_Settings_Exception $e ) {
			$input_error_messages = $e->get_input_exceptions();
			if ( ! empty( $html_input_map_fields ) ) {
				foreach ( $input_error_messages as $input_id => $message ) {
					if ( is_array( $message ) ) {
						foreach ( $message as $addr => $m ) {
							$html_input_map_fields = str_replace( '{{$error_css_class_' . $input_id . '_' . $addr . '}}', 'sui-form-field-error', $html_input_map_fields );
							$html_input_map_fields = str_replace( '{{$error_message_' . $input_id . '_' . $addr . '}}', '<span class="sui-error-message">' . esc_html( $m ) . '</span>', $html_input_map_fields );
						}
					} else {
						$html_input_map_fields = str_replace( '{{$error_css_class_' . $input_id . '}}', 'sui-form-field-error', $html_input_map_fields );
						$html_input_map_fields = str_replace( '{{$error_message_' . $input_id . '}}', '<span class="sui-error-message">' . esc_html( $message ) . '</span>', $html_input_map_fields );
					}
				}
			}
		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			$error_message = '<div role="alert" class="sui-notice sui-notice-red sui-active" style="display: block; text-align: left;" aria-live="assertive">';

				$error_message .= '<div class="sui-notice-content">';

					$error_message .= '<div class="sui-notice-message">';

						$error_message .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';

						$error_message .= '<p>' . $e->getMessage() . '</p>';

					$error_message .= '</div>';

				$error_message .= '</div>';

			$error_message .= '</div>';
		}

		//cleanup map fields input markup placeholder
		if ( ! empty( $html_input_map_fields ) ) {
			$replaced_html_input_map_fields = $html_input_map_fields;
			$replaced_html_input_map_fields = preg_replace( '/\{\{\$error_css_class_(.+)\}\}/', '', $replaced_html_input_map_fields );
			$replaced_html_input_map_fields = preg_replace( '/\{\{\$error_message_(.+)\}\}/', '', $replaced_html_input_map_fields );
			if ( ! is_null( $replaced_html_input_map_fields ) ) {
				$html_input_map_fields = $replaced_html_input_map_fields;
			}
		}

		$buttons = array(
			'cancel' => array(
				'markup' => Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Back', 'forminator' ), 'sui-button-ghost forminator-addon-back' ),
			),
			'next'   => array(
				'markup' => '<div class="sui-actions-right">' .
					Forminator_Addon_Mailchimp::get_button_markup( esc_html__( 'Save', 'forminator' ), 'sui-button-primary forminator-addon-finish' ) .
				'</div>',
			),
		);

		$notification = array();

		if ( $is_submit && empty( $error_message ) && empty( $input_error_messages ) ) {
			$notification = array(
				'type' => 'success',
				'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . __( 'is activated successfully.' ),
			);
		}

		$html  = '<div class="forminator-integration-popup__header">';
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . __( 'Assign Fields', 'forminator' ) . '</h3>';
			$html .= '<p class="sui-description">' . __( 'Lastly, match up your form fields with your campaign fields to make sure we\'re sending data to the right place.', 'forminator' ) . '</p>';
			$html .= $error_message;
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
			$html .= $html_input_map_fields;
		$html .= '</form>';

		return array(
			'html'         => $html,
			'redirect'     => false,
			'is_close'     => $is_close,
			'buttons'      => $buttons,
			'has_errors'   => ! empty( $error_message ) || ! empty( $input_error_messages ),
			'notification' => $notification,
			'size'         => 'normal',
			'has_back'     => true,
		);
	}

	/**
	 * Get input of Map Fields
	 * its table with html select options as input
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $email_fields
	 * @param $mailchimp_fields_list
	 * @param $current_data
	 */
	private function get_input_map_fields( $email_fields, $mailchimp_fields_list, $current_data ) {
		?>
		<table class="sui-table">
			<thead>
			<tr>
				<th>Mailchimp Field</th>
				<th>Forminator Field</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>Email Address <span class="integrations-required-field">*</span></td>
				<td>
					<div class="sui-form-field {{$error_css_class_EMAIL}}">
						<?php // DEV NOTE: Select without JS. ?>
						<select class="sui-select" name="fields_map[EMAIL]">
							<?php if ( empty( $email_fields ) ) { ?>
								<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
							<?php } else { ?>
								<?php foreach ( $email_fields as $email_field ) { ?>
									<option value="<?php echo esc_attr( $email_field['element_id'] ); ?>"
										<?php selected( $current_data['fields_map']['EMAIL'], $email_field['element_id'] ); ?>>
										<?php echo esc_html( $email_field['field_label'] . ' | ' . $email_field['element_id'] ); ?>
									</option>
								<?php } ?>
							<?php } ?>
						</select>
						{{$error_message_EMAIL}}
					</div>
				</td>
			</tr>
			<?php
			foreach ( $mailchimp_fields_list as $item ) {
				$require_sign = $item->required ? '<span class="integrations-required-field">*</span>' : '';
				if ( 'address' === $item->type ) {
					$address_fields = $this->mail_address_fields();
					foreach ( $address_fields as $addr => $address ) {
						$address_require_sign = 'addr2' === $addr ? '' : $require_sign;
						?>
						<tr>
							<td><?php echo esc_html( $item->name . ' - ' . $address ); ?> <?php echo wp_kses_post( $address_require_sign ); ?></td>
							<td>
								<div class="sui-form-field {{$error_css_class_<?php echo esc_attr( $item->tag ); ?>_<?php echo esc_attr( $addr ); ?>}}">
									<select class="sui-select" name="fields_map[<?php echo esc_attr( $item->tag ); ?>][<?php echo esc_attr( $addr ); ?>]">
										<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
										<?php foreach ( $this->form_fields as $form_field ) { ?>
											<option value="<?php echo esc_attr( $form_field['element_id'] ); ?>"
												<?php selected( $current_data['fields_map'][ $item->tag ][ $addr ], $form_field['element_id'] ); ?>>
												<?php echo esc_html( $form_field['field_label'] . ' | ' . $form_field['element_id'] ); ?>
											</option>
										<?php } ?>
									</select>
									{{$error_message_<?php echo esc_attr( $item->tag ); ?>_<?php echo esc_attr( $addr ); ?>}}
								</div>
							</td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr>
						<td><?php echo esc_html( $item->name ); ?> <?php echo wp_kses_post( $require_sign ); ?></td>
						<td>
							<div class="sui-form-field {{$error_css_class_<?php echo esc_attr( $item->tag ); ?>}}">
								<select class="sui-select" name="fields_map[<?php echo esc_attr( $item->tag ); ?>]">
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
									<?php foreach ( $this->form_fields as $form_field ) { ?>
										<option value="<?php echo esc_attr( $form_field['element_id'] ); ?>"
											<?php selected( $current_data['fields_map'][ $item->tag ], $form_field['element_id'] ); ?>>
											<?php echo esc_html( $form_field['field_label'] . ' | ' . $form_field['element_id'] ); ?>
										</option>
									<?php } ?>
								</select>
								{{$error_message_<?php echo esc_attr( $item->tag ); ?>}}
							</div>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get mail List Name of submitted data
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $mail_lists
	 * @param $submitted_data
	 *
	 * @return string
	 */
	private function get_choosen_mail_list_name( $mail_lists, $submitted_data ) {
		$mail_list_id = isset( $submitted_data['mail_list_id'] ) ? $submitted_data['mail_list_id'] : 0;

		$mail_list_name = '';
		foreach ( $mail_lists as $mail_list ) {
			if ( $mail_list_id === $mail_list->id ) {
				$mail_list_name = $mail_list->name;
				break;
			}
		}

		return $mail_list_name;
	}

	/**
	 * Set the tags of the given list.
	 *
	 * @since 1.15.3
	 */
	private function set_tags() {
		if ( isset( $this->addon_form_settings['tags_data'] ) ) {
			$tags = $this->addon_form_settings['tags_data'];
		} else {
			$list_id = $this->addon_form_settings['mail_list_id'];
			$api     = $this->addon->get_api();
			$tags    = $api->get_tags( $list_id );

			$this->addon_form_settings['tags_data'] = $tags;
			$this->save_form_settings_values( $this->addon_form_settings );
		}
		$this->tags_data = $tags;
	}

	/**
	 * Set the GDPR fields that belong to the given list.
	 *
	 * @since 1.15.3
	 */
	private function set_gdpr_fields() {
		if ( isset( $this->addon_form_settings['gdpr_data'] ) ) {
			$gdpr_fields = $this->addon_form_settings['gdpr_data'];
		} else {
			$list_id = $this->addon_form_settings['mail_list_id'];
			$api     = $this->addon->get_api();

			$gdpr_fields = $api->get_gdpr_fields( $list_id );

			$this->addon_form_settings['gdpr_data'] = $gdpr_fields;
			$this->save_form_settings_values( $this->addon_form_settings );
		}

		$this->gdpr_data = $gdpr_fields;
	}

	/**
	 * Init the list groups.
	 *
	 * @since 1.15.3
	 */
	private function set_groups() {
		if ( isset( $this->addon_form_settings['groups_data'] ) ) {
			$groups_data = $this->addon_form_settings['groups_data'];
		} else {
			$list_id = $this->addon_form_settings['mail_list_id'];
			$api     = $this->addon->get_api();
			$groups  = $api->get_list_categories( $list_id, array( 'count' => 1000 ) );

			$groups_data = array();

			foreach ( $groups as $group ) {
				$group = (array) $group;

				// Create an array with the groups data to use it before saving.
				$groups_data[ $group['id'] ]['id']   = $group['id'];
				$groups_data[ $group['id'] ]['type'] = $group['type'];
				$groups_data[ $group['id'] ]['name'] = $group['title'] . ' ( ' . ucfirst( $group['type'] ) . ' )';
			}

			$this->addon_form_settings['groups_data'] = $groups_data;
			$this->save_form_settings_values( $this->addon_form_settings );
		}

		$this->groups_data = $groups_data;
	}

	/**
	 * Calls the API to fetch remote interest options
	 *
	 * @return array
	 */
	private function get_interests() {
		$list_id = $this->addon_form_settings['mail_list_id'];
		$group   = $this->addon_form_settings['group'];
		$api     = $this->addon->get_api();

		if ( empty( $group ) ) {
			return array();
		}

		$interests = $api->get_category_interests( $list_id, $group, array( 'count' => 1000 ) );
		if ( is_wp_error( $interests ) || ! is_array( $interests->interests ) ) {
			$interests = array();
		} else {
			$interests = wp_list_pluck( $interests->interests, 'name', 'id' );
		}

		return $interests;
	}

	/**
	 * Validate submitted data by user as expected by merge field on mailchimp mail list
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $mailchimp_fields_list
	 * @param $mailchimp_required_fields
	 * @param $post_data
	 *
	 * @return array current addon form settings
	 * @throws Forminator_Addon_Mailchimp_Exception
	 * @throws Forminator_Addon_Mailchimp_Form_Settings_Exception
	 */
	public function step_map_fields_validate( $mailchimp_fields_list, $mailchimp_required_fields, $post_data ) {

		$forminator_field_element_ids = array();
		$address_value                = array();
		foreach ( $this->form_fields as $form_field ) {
			$forminator_field_element_ids[] = $form_field['element_id'];
		}

		//map mailchimp maped with tag as its key
		$tag_maped_mailchimp_fields = array();
		foreach ( $mailchimp_fields_list as $item ) {
			$tag_maped_mailchimp_fields[ $item->tag ] = $item;
		}

		if ( ! isset( $post_data['fields_map'] ) ) {
			$this->_update_form_settings_error_message = 'Please assign fields.';
			throw new Forminator_Addon_Mailchimp_Exception( $this->_update_form_settings_error_message );
		}
		$post_data = $post_data['fields_map'];

		if ( ! isset( $this->addon_form_settings['fields_map'] ) ) {
			$this->addon_form_settings['fields_map'] = array();
		}

		// set fields_map from post_data for reuse.
		foreach ( $post_data as $mailchimp_field_tag => $forminator_field_id ) {
			$this->addon_form_settings['fields_map'][ $mailchimp_field_tag ] = $post_data[ $mailchimp_field_tag ];
		}

		$input_exceptions = new Forminator_Addon_Mailchimp_Form_Settings_Exception();
		// EMAIL : super required**.
		if ( ! isset( $post_data['EMAIL'] ) || empty( $post_data['EMAIL'] ) ) {
			$this->_update_form_settings_error_message = __( 'Please choose valid Forminator field for email address.', 'forminator' );
			$input_exceptions->add_input_exception( $this->_update_form_settings_error_message, 'EMAIL' );
		}

		//check required fields fulfilled
		foreach ( $mailchimp_required_fields as $mailchimp_required_field ) {
			if ( 'address' === $mailchimp_required_field->type ) {
				$address_fields = $this->mail_address_fields();
				foreach ( $address_fields as $addr => $address ) {
					if ( 'addr2' !== $addr ) {
						if ( ! isset( $post_data[ $mailchimp_required_field->tag ][ $addr ] ) || empty( $post_data[ $mailchimp_required_field->tag ][ $addr ] ) ) {
							$this->_update_form_settings_error_message =
								/* translators: ... */
								sprintf( __( '%s is required by Mailchimp, please choose valid Forminator field.', 'forminator' ), $mailchimp_required_field->name );
							$input_exceptions->add_sub_input_exception( $this->_update_form_settings_error_message, $mailchimp_required_field->tag, $addr );
						}
					}
				}
			}
			if ( ! isset( $post_data[ $mailchimp_required_field->tag ] ) || empty( $post_data[ $mailchimp_required_field->tag ] ) ) {
				$this->_update_form_settings_error_message =
					/* translators: ... */
					sprintf( __( '%s is required by Mailchimp, please choose valid Forminator field.', 'forminator' ), $mailchimp_required_field->name );
				$input_exceptions->add_input_exception( $this->_update_form_settings_error_message, $mailchimp_required_field->tag );
			}
		}

		// Check availibility on forminator field.
		foreach ( $this->addon_form_settings['fields_map'] as $mailchimp_field_tag => $forminator_field_id ) {
			if ( empty( $forminator_field_id ) ) {
				continue;
			}
			if ( is_array( $forminator_field_id ) ) {
				foreach ( $forminator_field_id as $addr => $field_id ) {
					if ( ! empty( $field_id ) ) {
						$address_value[ $mailchimp_field_tag ][ $addr ] = $field_id;
					}
				}
				foreach ( $forminator_field_id as $addr => $field_id ) {
					if ( 'addr2' === $addr ) {
						continue;
					}
					if ( ! empty( $address_value ) && ! in_array( $field_id, $forminator_field_element_ids, true ) ) {
						$mailchimp_field      = $tag_maped_mailchimp_fields[ $mailchimp_field_tag ];
						$mailchimp_field_name = $mailchimp_field->name;

						$this->_update_form_settings_error_message =
							/* translators: ... */
							sprintf( __( 'Please choose valid Forminator field for %s.', 'forminator' ), $mailchimp_field_name );
						$input_exceptions->add_sub_input_exception( $this->_update_form_settings_error_message, $mailchimp_field_tag, $addr );
					}
				}
			}
			if ( ! is_array( $forminator_field_id ) && ! in_array( $forminator_field_id, $forminator_field_element_ids, true ) ) {
				if ( 'EMAIL' === $mailchimp_field_tag ) {
					$mailchimp_field_name = __( 'Email Address', 'forminator' );
				} else {
					$mailchimp_field      = $tag_maped_mailchimp_fields[ $mailchimp_field_tag ];
					$mailchimp_field_name = $mailchimp_field->name;
				}

				$this->_update_form_settings_error_message =
					/* translators: ... */
					sprintf( __( 'Please choose valid Forminator field for %s.', 'forminator' ), $mailchimp_field_name );
				$input_exceptions->add_input_exception( $this->_update_form_settings_error_message, $mailchimp_field_tag );
			}
		}

		if ( $input_exceptions->input_exceptions_is_available() ) {
			throw $input_exceptions;
		}

		return $this->addon_form_settings;
	}

	/**
	 * Check if map fields is completed
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	public function step_map_fields_is_completed() {
		$this->addon_form_settings = $this->get_form_settings_values();
		if ( ! $this->step_choose_mail_list_is_completed() ) {

			return false;
		}

		if ( empty( $this->addon_form_settings['fields_map'] ) ) {

			return false;
		}

		if ( ! is_array( $this->addon_form_settings['fields_map'] ) ) {
			return false;
		}

		if ( count( $this->addon_form_settings['fields_map'] ) < 1 ) {

			return false;
		}

		/**
		 * TODO: check if saved fields_map still valid, by request merge_fields on mailchimp
		 * Easy achieved but will add overhead on site
		 * force_form_disconnect();
		 * save_force_form_disconnect_reason();
		 */

		return true;

	}

	/**
	 * Check if mail list already selected completed
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	public function step_choose_mail_list_is_completed() {
		$this->addon_form_settings = $this->get_form_settings_values();
		if ( ! isset( $this->addon_form_settings['mail_list_id'] ) ) {
			// preliminary value.
			$this->addon_form_settings['mail_list_id'] = 0;

			return false;
		}

		if ( empty( $this->addon_form_settings['mail_list_id'] ) ) {
			return false;
		}

		/**
		 * TODO: check if saved mail list id still valid, by request info on mailchimp
		 * Easy achieved but will add overhead on site
		 * force_form_disconnect();
		 * save_force_form_disconnect_reason();
		 */

		return true;
	}

	/**
	 * Return as if the step is indeed completed.
	 * The second and third steps are optional, so no real validation is done here.
	 *
	 * @return boolean
	 */
	public function step_is_completed() {
		return $this->step_choose_mail_list_is_completed();
	}

}
