<?php
/** @noinspection HtmlUnknownTarget */

require_once dirname( __FILE__ ) . '/class-forminator-addon-mailchimp-exception.php';
require_once dirname( __FILE__ ) . '/lib/class-forminator-addon-mailchimp-wp-api.php';

/**
 * Class Forminator_Addon_Mailchimp
 * The class that defines mailchimp addon
 *
 * @since 1.0 Mailchimp Addon
 */
class Forminator_Addon_Mailchimp extends Forminator_Addon_Abstract {

	/**
	 * Mailchimp Addon Instance
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @var self|null
	 */
	private static $_instance = null;

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_slug = 'mailchimp';

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_MAILCHIMP_VERSION;

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_short_title = 'Mailchimp';

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_title = 'Mailchimp';

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_url = 'https://wpmudev.com';

	/**
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Class name of form settings
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @var string
	 */
	protected $_form_settings = 'Forminator_Addon_Mailchimp_Form_Settings';

	/**
	 * Class name of form hooks
	 *
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_form_hooks = 'Forminator_Addon_Mailchimp_Form_Hooks';

	/**
	 * Class name of quiz settings
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @var string
	 */
	protected $_quiz_settings = 'Forminator_Addon_Mailchimp_Quiz_Settings';

	/**
	 * Class name of quiz hooks
	 *
	 * @since 1.0 Mailchimp Addon
	 * @var string
	 */
	protected $_quiz_hooks = 'Forminator_Addon_Mailchimp_Quiz_Hooks';

	/**
	 * Hold account information that currently connected
	 * Will be saved to @see Forminator_Addon_Mailchimp::save_settings_values()
	 *
	 * @since 1.0 Mailchimp Addon
	 * @var array
	 */
	private $_connected_account = array();

	protected $_position = 2;

	/**
	 * Forminator_Addon_Mailchimp constructor.
	 * - Set dynamic translatable text(s) that will be displayed to end-user
	 * - Set dynamic icons and images
	 *
	 * @since 1.0 Mailchimp Addon
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description                = __( 'Make form data as Mailchimp List', 'forminator' );
		$this->_activation_error_message   = __( 'Sorry but we failed to activate Mailchimp Integration, don\'t hesitate to contact us', 'forminator' );
		$this->_deactivation_error_message = __( 'Sorry but we failed to deactivate Mailchimp Integration, plese try again', 'forminator' );

		$this->_update_settings_error_message = __(
			'Sorry, we failed to update settings, please check your form and try again',
			'forminator'
		);

		$this->_icon     = forminator_addon_mailchimp_assets_url() . 'icons/mailchimp.png';
		$this->_icon_x2  = forminator_addon_mailchimp_assets_url() . 'icons/mailchimp@2x.png';
		$this->_image    = forminator_addon_mailchimp_assets_url() . 'img/mailchimp.png';
		$this->_image_x2 = forminator_addon_mailchimp_assets_url() . 'img/mailchimp@2x.png';

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_forminator_mailchimp_get_group_interests', array( $this, 'ajax_group_interests' ) );
		}

		$this->is_multi_global = true;
	}

	/**
	 * Get addon instance
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return self|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Hook before save settings values
	 * to include @see Forminator_Addon_Mailchimp::$_connected_account
	 * for future reference
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public function before_save_settings_values( $values ) {
		forminator_addon_maybe_log( __METHOD__, $values );

		if ( ! empty( $this->_connected_account ) ) {
			$values['connected_account'] = $this->_connected_account;
		}

		return $values;
	}

	/**
	 * Flag for check whether mailchimp addon is connected globally
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	public function is_connected() {
		try {
			// check if its active.
			if ( ! $this->is_active() ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Mailchimp is not active', 'forminator' ) );
			}

			// if user completed settings.
			$is_connected = $this->settings_is_complete();

		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			$is_connected = false;
		}

		/**
		 * Filter connected status of mailchimp
		 *
		 * @since 1.1
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_mailchimp_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Check if user already completed settings
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	private function settings_is_complete() {
		$setting_values = $this->get_settings_values();

		// check api_key and connected_account exists and not empty.
		return isset( $setting_values['api_key'] ) && $setting_values['api_key'] && isset( $setting_values['connected_account'] ) && ! empty( $setting_values['connected_account'] );
	}

	/**
	 * Flag for check if and addon connected to a form
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $form_id
	 *
	 * @return bool
	 */
	public function is_form_connected( $form_id ) {

		try {
			// initialize with null.
			$form_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Mailchimp addon not connected.', 'forminator' ) );
			}

			$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
			if ( ! $form_settings_instance instanceof Forminator_Addon_Mailchimp_Form_Settings ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Form settings instance is not valid Forminator_Addon_Mailchimp_Form_Settings.', 'forminator' ) );
			}
			$wizards = $form_settings_instance->form_settings_wizards();
			//last step is completed
			$last_step             = end( $wizards );
			$last_step_is_complete = call_user_func( $last_step['is_completed'] );
			if ( ! $last_step_is_complete ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Form settings is not yet completed.', 'forminator' ) );
			}

			$is_form_connected = true;
		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			$is_form_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of mailchimp with the form
		 *
		 * @since 1.1
		 *
		 * @param bool                                          $is_form_connected
		 * @param int                                           $form_id                Current Form ID.
		 * @param Forminator_Addon_Mailchimp_Form_Settings|null $form_settings_instance Instance of form settings, or null when unavailable.
		 *
		 */
		$is_form_connected = apply_filters( 'forminator_addon_mailchimp_is_form_connected', $is_form_connected, $form_id, $form_settings_instance );

		return $is_form_connected;

	}

	/**
	 * Flag for check if and addon connected to a form
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $quiz_id
	 *
	 * @return bool
	 */
	public function is_quiz_connected( $quiz_id ) {

		try {
			// initialize with null.
			$quiz_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Mailchimp addon not connected.', 'forminator' ) );
			}

			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Mailchimp_Quiz_Settings ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Form settings instance is not valid Forminator_Addon_Mailchimp_Quiz_Settings.', 'forminator' ) );
			}
			$wizards = $quiz_settings_instance->quiz_settings_wizards();
			//last step is completed
			$last_step             = end( $wizards );
			$last_step_is_complete = call_user_func( $last_step['is_completed'] );
			if ( ! $last_step_is_complete ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Form settings is not yet completed.', 'forminator' ) );
			}

			$is_quiz_connected = true;
		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			$is_quiz_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of mailchimp with the form
		 *
		 * @since 1.1
		 *
		 * @param bool                                          $is_quiz_connected
		 * @param int                                           $quiz_id                Current Form ID.
		 * @param Forminator_Addon_Mailchimp_Quiz_Settings|null $quiz_settings_instance Instance of form settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_mailchimp_is_form_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;

	}

	/**
	 * Return with true / false, you may update you setting update message too
	 *
	 * @see   _update_settings_error_message
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $api_key
	 *
	 * @return bool
	 */
	protected function validate_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			$this->_update_settings_error_message = __( 'Please add valid Mailchimp API Key.', 'forminator' );

			return false;
		}

		try {
			// Check API Key by validating it on get_info request.
			$info = $this->get_api( $api_key )->get_info();
			forminator_addon_maybe_log( __METHOD__, $info );

			$this->_connected_account = array(
				'account_id'   => $info->account_id,
				'account_name' => $info->account_name,
				'email'        => $info->email,
			);

		} catch ( Forminator_Addon_Mailchimp_Wp_Api_Exception $e ) {
			$this->_update_settings_error_message = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * Get API Instance
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param null $api_key
	 *
	 * @return Forminator_Addon_Mailchimp_Wp_Api|null
	 * @throws Forminator_Addon_Mailchimp_Wp_Api_Exception
	 */
	public function get_api( $api_key = null ) {
		if ( is_null( $api_key ) ) {
			$api_key = $this->get_api_key();
		}
		$api = Forminator_Addon_Mailchimp_Wp_Api::get_instance( $api_key );
		return $api;
	}

	/**
	 * Get currently saved api key
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return string|null
	 */
	private function get_api_key() {
		/** @var array $setting_values */
		$setting_values = $this->get_settings_values();
		if ( isset( $setting_values['api_key'] ) ) {
			return $setting_values['api_key'];
		}

		return null;
	}

	/**
	 * Build settings help on settings
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return string
	 */
	public function settings_help() {

		// Display how to get mailchimp API Key by default.
		/* translators:  placeholder is URL to get API Key of MailChimp */
		$help = sprintf( __( 'Please get your Mailchimp API key %1$s', 'forminator' ), '<a href="https://admin.mailchimp.com/account/api-key-popup" target="_blank">here</a>' );

		$help = '<span class="sui-description" style="margin-top: 20px;">' . $help . '</span>';

		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			$connected_account = $setting_values['connected_account'];

			// Show currently connected mailchimp account if its already connected.
			/* translators:  placeholder is Name and Email of Connected MailChimp Account */
			$help = '<span class="sui-description" style="margin-top: 20px;">' . __( 'Change your API Key or disconnect this Mailchimp Integration below.' ) . '</span>';

		}

		return $help;

	}

	public function settings_description() {

		$description = '';

		/** @var array $setting_values */
		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			// Show currently connected mailchimp account if its already connected.
			/* translators:  placeholder is Name and Email of Connected MailChimp Account */
			$description .= '<span class="sui-description">' . esc_html__( 'Please note that changing your API Key or disconnecting this integration will affect ALL of your connected forms.', 'forminator' ) . '</span>';

		}

		return $description;

	}

	public function settings_account() {

		$myaccount = '';

		/** @var array $setting_values */
		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			$connected_account = $setting_values['connected_account'];

			// Show currently connected mailchimp account if its already connected.
			$myaccount .= sprintf(
				/* translators:  placeholder is Name and Email of Connected MailChimp Account */
				__( 'Your Mailchimp is connected to %1$s: %2$s.', 'forminator' ),
				'<strong>' . $connected_account['account_name'] . '</strong>',
				$connected_account['email']
			);

			$myaccount = '<div role="alert" class="sui-notice sui-notice-red sui-active" style="display: block; text-align: left;" aria-live="assertive">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
						<p>' . $myaccount . '</p>
					</div>
				</div>
			</div>';

		}

		return $myaccount;

	}

	/**
	 * Flag to show full log on entries
	 * By default API request(s) are not shown on submissions page
	 * set @see FORMINATOR_ADDON_MAILCHIMP_SHOW_FULL_LOG to `true` on wp-config.php to show it
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	public static function is_show_full_log() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_SHOW_FULL_LOG' ) && FORMINATOR_ADDON_MAILCHIMP_SHOW_FULL_LOG ) {
			return true;
		}

		return false;
	}

	/**
	 * Flag if delete member on delete entry enabled
	 *
	 * Default is `true`,
	 * which can be changed via `FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER` constant
	 *
	 * @return bool
	 */
	public static function is_enable_delete_member() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER' ) && FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER ) {
			return true;
		}

		return false;
	}

	/**
	 * Flag to show full if GDPR feature enabled
	 * GDPR is experimental feature on 1.0 version of this mailchimp addon
	 * And disabled by default to enable it set @see FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR to true in wp-config.php
	 * Please bear in mind that currently its experimental, means not properly and thoroughly tested
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return bool
	 */
	public static function is_enable_gdpr() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR' ) && FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR ) {
			return true;
		}

		return false;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 Mailchimp Addon
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'configure_api_key' ),
				'is_completed' => array( $this, 'settings_is_complete' ),
			),
		);
	}

	/**
	 * Wizard of configure_api_key
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param     $submitted_data
	 * @param int $form_id
	 *
	 * @return array
	 */
	public function configure_api_key( $submitted_data, $form_id = 0 ) {
		$error_message         = '';
		$api_key_error_message = '';
		$setting_values        = $this->get_settings_values();
		$identifier            = '';
		$api_key               = $this->get_api_key();
		if ( ! empty( $setting_values['identifier'] ) ) {
			$identifier = $setting_values['identifier'];
		}

		// ON Submit.
		if ( isset( $submitted_data['api_key'] ) ) {
			$api_key           = $submitted_data['api_key'];
			$identifier        = isset( $submitted_data['identifier'] ) ? $submitted_data['identifier'] : '';
			$api_key_validated = $this->validate_api_key( $api_key );

			/**
			 * Filter validating api key result
			 *
			 * @since 1.1
			 *
			 * @param bool   $api_key_validated
			 * @param string $api_key API Key to be validated.
			 */
			$api_key_validated = apply_filters( 'forminator_addon_mailchimp_validate_api_key', $api_key_validated, $api_key );

			$save_values = array(
				'api_key'    => $api_key,
				'identifier' => $identifier,
			);
			if ( ! $api_key_validated ) {
				$api_key_error_message = $this->_update_settings_error_message;
			} else {
				$show_success = true;
				if ( ! forminator_addon_is_active( $this->_slug ) ) {
					$activated = Forminator_Addon_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$error_message = '<div class="sui-notice sui-notice-error"><p>' . Forminator_Addon_Loader::get_instance()->get_last_error_message() . '</p></div>';
						$show_success  = false;
					} else {
						$this->save_settings_values( $save_values );
					}
				} else {
					$this->save_settings_values( $save_values );
				}

				if ( $show_success ) {
					if ( ! empty( $form_id ) ) {
						// initiate form settings wizard.
						return $this->get_form_settings_wizard( array(), $form_id, 0, 0 );
					}

					$html  = '<div class="forminator-integration-popup__header">';
						/* translators: ... */
						$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . sprintf( __( '%1$s Added', 'forminator' ), 'Mailchimp' ) . '</h3>';
					$html .= '</div>';
					$html .= '<p class="sui-description" style="text-align: center;">' . __( 'You can now go to your forms and assign them to this integration.' ) . '</p>';

					return array(
						'html'         => $html,
						'buttons'      => array(
							'close' => array(
								'markup' => self::get_button_markup( esc_html__( 'Close', 'forminator' ), 'forminator-addon-close forminator-integration-popup__close' ),
							),
						),
						'redirect'     => false,
						'has_errors'   => false,
						'notification' => array(
							'type' => 'success',
							'text' => '<strong>' . $this->get_title() . '</strong> ' . __( 'is connected successfully.' ),
						),
					);
				}
			}
		}

		$buttons = array();

		$is_edit = false;

		if ( $this->is_connected() ) {
			$is_edit = true;
		}

		if ( $is_edit ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);

			$buttons['submit'] = array(
				'markup' => '<div class="sui-actions-right">' .
							self::get_button_markup( esc_html__( 'Save', 'forminator' ), 'forminator-addon-connect' ) .
							'</div>',
			);
		} else {
			$buttons['submit'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Connect', 'forminator' ), 'forminator-addon-connect' ),
			);
		}

		$html  = '<div class="forminator-integration-popup__header">';
			/* translators: ... */
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . sprintf( __( 'Configure %1$s', 'forminator' ), 'Mailchimp' ) . '</h3>';
			$html .= $this->settings_help();
			$html .= $error_message;
		$html .= '</div>';
		$html .= '<form>';
			// FIELD: API Key
			$html .= '<div class="sui-form-field ' . ( ! empty( $api_key_error_message ) ? 'sui-form-field-error' : '' ) . '">';
				$html .= '<label class="sui-label">' . __( 'API Key', 'forminator' ) . '</label>';
				$html .= '<div class="sui-control-with-icon">';
					/* translators: ... */
					$html .= '<input name="api_key" value="' . esc_attr( $api_key ) . '" placeholder="' . sprintf( __( 'Enter %1$s API Key', 'forminator' ), 'Mailchimp' ) . '" class="sui-form-control" />';
					$html .= '<i class="sui-icon-key" aria-hidden="true"></i>';
				$html .= '</div>';
				$html .= ( ! empty( $api_key_error_message ) ? '<span class="sui-error-message">' . esc_html( $api_key_error_message ) . '</span>' : '' );
				$html .= $this->settings_description();
			$html .= '</div>';
			// FIELD: Identifier
			$html .= '<div class="sui-form-field">';
				$html .= '<label class="sui-label">' . esc_html__( 'Identifier', 'forminator' ) . '</label>';
				$html .= '<input name="identifier" value="' . esc_attr( $identifier ) . '" placeholder="' . esc_attr__( 'E.g., Business Account', 'forminator' ) . '" class="sui-form-control" />';
				$html .= '<span class="sui-description">' . esc_html__( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ) . '</span>';
			$html .= '</div>';
		$html .= '</form>';
		$html .= $this->settings_account();

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => ! empty( $error_message ) || ! empty( $api_key_error_message ),
		);
	}

	/**
	 * Flag for check if and addon connected to a poll(poll settings such as list id completed)
	 *
	 * Please apply necessary WordPress hook on the inheritance class
	 *
	 * @since   1.6.1
	 *
	 * @param $poll_id
	 *
	 * @return boolean
	 */
	public function is_poll_connected( $poll_id ) {
		return false;
	}

	/**
	 * Flag for check if has lead form addon connected to a quiz
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @since 1.0 Mailchimp Addon
	 *
	 * @param $quiz_id
	 *
	 * @return bool
	 */
	public function is_quiz_lead_connected( $quiz_id ) {

		try {
			// initialize with null.
			$quiz_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Mailchimp addon not connected.', 'forminator' ) );
			}
			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );

			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Mailchimp_Quiz_Settings ) {
				throw new Forminator_Addon_Mailchimp_Exception( __( 'Form settings instance is not valid Forminator_Addon_Mailchimp_Quiz_Settings.', 'forminator' ) );
			}

			$quiz_settings = $quiz_settings_instance->get_quiz_settings();

			if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
				$is_quiz_connected = true;
			} else {
				$is_quiz_connected = false;
			}
		} catch ( Forminator_Addon_Mailchimp_Exception $e ) {
			$is_quiz_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of mailchimp with the form
		 *
		 * @since 1.1
		 *
		 * @param bool                                          $is_quiz_connected
		 * @param int                                           $quiz_id                Current Form ID.
		 * @param Forminator_Addon_Mailchimp_Quiz_Settings|null $quiz_settings_instance Instance of form settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_mailchimp_is_quiz_lead_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;

	}

	/**
	 * AJAX load group interests
	 */
	public function ajax_group_interests() {
		forminator_validate_ajax( 'forminator_mailchimp_interests' );
		$html      = '';
		$post_data = filter_input( INPUT_POST, 'data' );
		$data      = array();
		wp_parse_str( $post_data, $data );
		$module_id = isset( $data['module_id'] ) ? $data['module_id'] : '';
		if ( $module_id ) {
			$form_settings_instance = $this->get_addon_settings( $module_id, 'form' );
			$html                   = $form_settings_instance->get_group_interests( $data );
		}

		wp_send_json_success( $html );
	}
}
