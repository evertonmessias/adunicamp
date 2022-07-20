<?php

require_once dirname( __FILE__ ) . '/class-forminator-addon-aweber-exception.php';
require_once dirname( __FILE__ ) . '/lib/class-forminator-addon-aweber-wp-api.php';

/**
 * Class Forminator_Addon_Aweber
 * Aweber Addon Main Class
 *
 * @since 1.0 Aweber Addon
 */
final class Forminator_Addon_Aweber extends Forminator_Addon_Abstract {

	/**
	 * @var self|null
	 */
	private static $_instance = null;

	protected $_slug                   = 'aweber';
	protected $_version                = FORMINATOR_ADDON_AWEBER_VERSION;
	protected $_min_forminator_version = '1.1';
	protected $_short_title            = 'AWeber';
	protected $_title                  = 'AWeber';
	protected $_url                    = 'https://wpmudev.com';
	protected $_full_path              = __FILE__;
	protected $_position               = 7;

	protected $_form_settings = 'Forminator_Addon_Aweber_Form_Settings';
	protected $_form_hooks    = 'Forminator_Addon_Aweber_Form_Hooks';

	protected $_quiz_settings = 'Forminator_Addon_Aweber_Quiz_Settings';
	protected $_quiz_hooks    = 'Forminator_Addon_Aweber_Quiz_Hooks';

	private $_app_id = 'd806984a';

	/**
	 * Connected Account Info
	 *
	 * @var integer
	 */
	private $_account_id = 0;

	/**
	 * Forminator_Addon_Aweber constructor.
	 *
	 * @since 1.0 Aweber Addon
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description                = __( 'Get awesome by your form.', 'forminator' );
		$this->_activation_error_message   = __( 'Sorry but we failed to activate AWeber Integration, don\'t hesitate to contact us', 'forminator' );
		$this->_deactivation_error_message = __( 'Sorry but we failed to deactivate AWeber Integration, please try again', 'forminator' );

		$this->_update_settings_error_message = __(
			'Sorry, we failed to update settings, please check your form and try again',
			'forminator'
		);

		$this->_icon     = forminator_addon_aweber_assets_url() . 'icons/aweber.png';
		$this->_icon_x2  = forminator_addon_aweber_assets_url() . 'icons/aweber@2x.png';
		$this->_image    = forminator_addon_aweber_assets_url() . 'img/aweber.png';
		$this->_image_x2 = forminator_addon_aweber_assets_url() . 'img/aweber@2x.png';

		$this->is_multi_global = true;

		$this->global_id_for_new_integrations = uniqid( '', true );
	}

	/**
	 * Get Instance
	 *
	 * @since 1.0 Aweber Addon
	 * @return self|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Override on is_connected
	 *
	 * @since 1.0 Aweber Addon
	 *
	 * @return bool
	 */
	public function is_connected() {
		try {
			// check if its active.
			if ( ! $this->is_active() ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'AWeber is not active', 'forminator' ) );
			}

			// if user completed api setup.
			$is_connected   = false;
			$setting_values = $this->get_settings_values();
			// if user completed api setup.
			if ( isset( $setting_values['account_id'] ) && ! empty( $setting_values['account_id'] ) ) {
				$is_connected = true;
			}
		} catch ( Forminator_Addon_Aweber_Exception $e ) {
			$is_connected = false;
		}

		/**
		 * Filter connected status of Aweber
		 *
		 * @since 1.0
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_aweber_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Check if Aweber is connected with current form
	 *
	 * @since 1.0 Aweber Addon
	 *
	 * @param $form_id
	 *
	 * @return bool
	 */
	public function is_form_connected( $form_id ) {
		try {
			$form_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Aweber_Exception( __( ' AWeber is not connected', 'forminator' ) );
			}

			$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
			if ( ! $form_settings_instance instanceof Forminator_Addon_Aweber_Form_Settings ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'Invalid Form Settings of AWeber', 'forminator' ) );
			}

			// Mark as active when there is at least one active connection.
			if ( false === $form_settings_instance->find_one_active_connection() ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'No active AWeber connection found in this form', 'forminator' ) );
			}

			$is_form_connected = true;

		} catch ( Forminator_Addon_Aweber_Exception $e ) {
			$is_form_connected = false;
		}

		/**
		 * Filter connected status of Aweber with the form
		 *
		 * @since 1.0
		 *
		 * @param bool                                       $is_form_connected
		 * @param int                                        $form_id                Current Form ID.
		 * @param Forminator_Addon_Aweber_Form_Settings|null $form_settings_instance Instance of form settings, or null when unavailable.
		 *
		 */
		$is_form_connected = apply_filters( 'forminator_addon_aweber_is_form_connected', $is_form_connected, $form_id, $form_settings_instance );

		return $is_form_connected;
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 Aweber Addon
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag show full log on entries
	 *
	 * @since 1.0 Aweber Addon
	 * @return bool
	 */
	public static function is_show_full_log() {
		$show_full_log = false;
		if ( defined( 'FORMINATOR_ADDON_AWEBER_SHOW_FULL_LOG' ) && FORMINATOR_ADDON_AWEBER_SHOW_FULL_LOG ) {
			$show_full_log = true;
		}

		/**
		 * Filter Flag show full log on entries
		 *
		 * @since  1.2
		 *
		 * @params bool $show_full_log
		 */
		$show_full_log = apply_filters( 'forminator_addon_aweber_show_full_log', $show_full_log );

		return $show_full_log;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Aweber Addon
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 AWeber Addon
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'authorize_access' ),
				'is_completed' => array( $this, 'authorize_access_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'wait_authorize_access' ),
				'is_completed' => array( $this, 'is_authorized' ),
			),
		);
	}

	/**
	 * Authorize Access wizard
	 *
	 * @since 1.0 AWeber Addon
	 * @return array
	 */
	public function authorize_access() {
		$template = forminator_addon_aweber_dir() . 'views/settings/authorize.php';

		$buttons = array();

		$template_params = array(
			'account_id'   => $this->_account_id,
			'auth_url'     => $this->get_auth_url(),
			'is_connected' => $this->is_connected(),
		);

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Wait Authorize Access wizard
	 *
	 * @since 1.0 AWeber Addon
	 * @return array
	 */
	public function wait_authorize_access() {
		$template         = forminator_addon_aweber_dir() . 'views/settings/wait-authorize.php';
		$template_success = forminator_addon_aweber_dir() . 'views/settings/success-authorize.php';

		$buttons = array();

		$is_poll = true;

		$template_params = array(
			'account_id' => $this->_account_id,
			'auth_url'   => $this->get_auth_url(),
		);

		if ( $this->_account_id ) {
			$is_poll  = false;
			$template = $template_success;
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @since 1.0 AWeber Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function is_authorized( $submitted_data ) {
		$setting_values = $this->get_settings_values();

		// check account_id there.
		return isset( $setting_values['account_id'] ) && ! empty( $setting_values['account_id'] );
	}

	/**
	 * Pseudo step
	 *
	 * @since 1.0 AWeber Addon
	 * @return bool
	 */
	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Register a page for redirect url of AWeber auth
	 *
	 * @since 1.0 AWeber Addon
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * AWeber Authorize Page
	 *
	 * @since 1.0 AWeber Addon
	 *
	 * @param $query_args
	 *
	 * @return string
	 */
	public function authorize_page_callback( $query_args ) {
		$template        = forminator_addon_aweber_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['authorization_code'] ) && ! empty( $query_args['authorization_code'] ) ) {
			try {
				$authorization_code = $query_args['authorization_code'];

				$identifier  = ! empty( $query_args['identifier'] ) ? $query_args['identifier'] : '';
				$split_codes = explode( '|', $authorization_code );

				//https://labs.aweber.com/docs/authentication#distributed-app
				//the authorization code is an application key, application secret, request token, token secret, and oauth_verifier, delimited by pipes (|).
				if ( ! is_array( $split_codes ) || 5 !== count( $split_codes ) ) {
					new Forminator_Addon_Aweber_Exception( __( 'Invalid Authorization Code', 'forminator' ) );
				}

				$application_key    = $split_codes[0];
				$application_secret = $split_codes[1];
				$request_token      = $split_codes[2];
				$token_secret       = $split_codes[3];
				$oauth_verifier     = $split_codes[4];

				if ( ! empty( $query_args['global_id'] ) ) {
					$this->multi_global_id = $query_args['global_id'];
				}

				$api = $this->validate_access_token( $application_key, $application_secret, $request_token, $token_secret, $oauth_verifier );

				$this->_account_id = $this->get_validated_account_id( $api );
				if ( ! $this->is_active() ) {
					$activated = Forminator_Addon_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Addon_Loader::get_instance()->get_last_error_message();
						throw new Forminator_Addon_Aweber_Exception( $last_message );
					}
				}

				$this->save_settings_values(
					array(
						'identifier'         => $identifier,
						'application_key'    => $application_key,
						'application_secret' => $application_secret,
						'oauth_token'        => $api->get_oauth_token(),
						'oauth_token_secret' => $api->get_oauth_token_secret(),
					)
				);
				$template_params['is_close'] = true;
			} catch ( Forminator_Addon_Aweber_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
			}
		}

		return self::get_template( $template, $template_params );
	}

	/**
	 * Get AWeber Auth URL
	 *
	 * @since 1.1 AWeber Addon
	 *
	 * @param string $return_url
	 *
	 * @return string
	 */
	public function get_auth_url( $return_url = '' ) {
		$app_id = $this->get_app_id();

		$authorize_url = 'https://auth.aweber.com/1.0/oauth/authorize_app/' . trim( $app_id );

		if ( ! $return_url ) {
			$return_url = forminator_addon_integration_section_admin_url( $this, 'authorize', true );
		}
		$return_url = rawurlencode( $return_url );

		$auth_params = array(
			'oauth_callback' => $return_url, // un-official https://labs.aweber.com/getting_started/public#1.
		);

		/**
		 * Filter params used to authorize AWeber user
		 *
		 * @since 1.3
		 *
		 * @param array $auth_params
		 */
		$auth_params = apply_filters( 'forminator_addon_aweber_authorize_params', $auth_params );

		$authorize_url = add_query_arg( $auth_params, $authorize_url );

		return $authorize_url;
	}

	/**
	 * Get AWeber APP ID
	 *
	 * @see   https://labs.aweber.com/docs/authentication
	 *
	 * @since 1.0 AWeber Addon
	 *
	 * @return string;
	 */
	public function get_app_id() {
		$app_id = $this->_app_id;
		// check override by config constant.
		if ( defined( 'FORMINATOR_ADDON_AWEBER_APP_ID' ) && FORMINATOR_ADDON_AWEBER_APP_ID ) {
			$app_id = FORMINATOR_ADDON_AWEBER_APP_ID;
		}

		/**
		 * Filter APP ID used for API request(s) of AWeber
		 *
		 * @since 1.2
		 *
		 * @param string $app_id
		 */
		$app_id = apply_filters( 'forminator_addon_aweber_app_id', $app_id );

		return $app_id;
	}

	/**
	 * Get API Instance
	 *
	 * @since 1.0
	 *
	 * @param array|null $api_credentials
	 *
	 * @return Forminator_Addon_Aweber_Wp_Api
	 * @throws Forminator_Addon_Aweber_Wp_Api_Exception
	 */
	public function get_api( $api_credentials = null ) {
		if ( is_null( $api_credentials ) ) {
			$api_credentials      = array();
			$setting_values       = $this->get_settings_values();
			$api_credentials_keys = array(
				'application_key',
				'application_secret',
				'oauth_token',
				'oauth_token_secret',
			);

			foreach ( $api_credentials_keys as $api_credentials_key ) {
				$api_credentials[ $api_credentials_key ] = isset( $setting_values[ $api_credentials_key ] ) ? $setting_values[ $api_credentials_key ] : '';
			}
		}

		$_application_key    = isset( $api_credentials['application_key'] ) ? $api_credentials['application_key'] : '';
		$_application_secret = isset( $api_credentials['application_secret'] ) ? $api_credentials['application_secret'] : '';
		$_oauth_token        = isset( $api_credentials['oauth_token'] ) ? $api_credentials['oauth_token'] : '';
		$_oauth_token_secret = isset( $api_credentials['oauth_token_secret'] ) ? $api_credentials['oauth_token_secret'] : '';

		return new Forminator_Addon_Aweber_Wp_Api( $_application_key, $_application_secret, $_oauth_token, $_oauth_token_secret );
	}

	/**
	 * Validate Access Token
	 *
	 * @param $application_key
	 * @param $application_secret
	 * @param $request_token
	 * @param $token_secret
	 * @param $oauth_verifier
	 *
	 * @throws Forminator_Addon_Aweber_Wp_Api_Exception
	 * @throws Forminator_Addon_Aweber_Wp_Api_Not_Found_Exception
	 */
	public function validate_access_token( $application_key, $application_secret, $request_token, $token_secret, $oauth_verifier ) {
		//get access_token
		$api           = $this->get_api(
			array(
				'application_key'    => $application_key,
				'application_secret' => $application_secret,
				'oauth_token'        => $request_token,
				'oauth_token_secret' => $token_secret,
			)
		);
		$access_tokens = $api->get_access_token( $oauth_verifier );

		return $this->get_api(
			array(
				'application_key'    => $application_key,
				'application_secret' => $application_secret,
				'oauth_token'        => $access_tokens->oauth_token,
				'oauth_token_secret' => $access_tokens->oauth_token_secret,
			)
		);
	}

	/**
	 * Get validated account_id
	 *
	 * @return integer
	 * @throws Forminator_Addon_Aweber_Exception
	 * @throws Forminator_Addon_Aweber_Wp_Api_Exception
	 * @throws Forminator_Addon_Aweber_Wp_Api_Not_Found_Exception
	 */
	public function get_validated_account_id( $api ) {
		$accounts = $api->get_accounts();
		if ( ! isset( $accounts->entries ) ) {
			throw new Forminator_Addon_Aweber_Exception( __( 'Failed to get AWeber account information', 'forminator' ) );
		}

		$entries = $accounts->entries;
		if ( ! isset( $entries[0] ) ) {
			throw new Forminator_Addon_Aweber_Exception( __( 'Failed to get AWeber account information', 'forminator' ) );
		}

		$first_entry = $entries[0];
		$account_id  = $first_entry->id;

		/**
		 * Filter validated account_id
		 *
		 * @since 1.3
		 *
		 * @param integer                        $account_id
		 * @param object                         $accounts
		 * @param Forminator_Addon_Aweber_Wp_Api $api
		 */
		$account_id = apply_filters( 'forminator_addon_aweber_validated_account_id', $account_id, $accounts, $api );

		return $account_id;
	}

	/**
	 * set account_id on class if exist on settings
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		if ( is_array( $values ) && isset( $values['account_id'] ) ) {
			$this->_account_id = $values['account_id'];
		}

		return $values;
	}

	/**
	 * set account_id on class if exist on settings
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	public function before_save_settings_values( $values ) {
		if ( ! empty( $this->_account_id ) ) {
			$values['account_id'] = $this->_account_id;
		}

		return $values;
	}

	/**
	 * Get connected account id
	 *
	 * @return int
	 */
	public function get_account_id() {
		return $this->_account_id;
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
	 * Check if Aweber is connected with current quiz
	 *
	 * @since 1.0 Aweber Addon
	 *
	 * @param $quiz_id
	 *
	 * @return bool
	 */
	public function is_quiz_connected( $quiz_id ) {
		try {
			$quiz_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Aweber_Exception( __( ' AWeber is not connected', 'forminator' ) );
			}

			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Aweber_Quiz_Settings ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'Invalid Quiz Settings of AWeber', 'forminator' ) );
			}

			// Mark as active when there is at least one active connection.
			if ( false === $quiz_settings_instance->find_one_active_connection() ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'No active AWeber connection found in this quiz', 'forminator' ) );
			}

			$is_quiz_connected = true;

		} catch ( Forminator_Addon_Aweber_Exception $e ) {
			$is_quiz_connected = false;
		}

		/**
		 * Filter connected status of Aweber with the quiz
		 *
		 * @since 1.0
		 *
		 * @param bool                                       $is_quiz_connected
		 * @param int                                        $quiz_id                Current Quiz ID.
		 * @param Forminator_Addon_Aweber_Form_Settings|null $quiz_settings_instance Instance of quiz settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_aweber_is_form_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;
	}

	/**
	 * Flag for check if has lead form addon connected to a quiz
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @since 1.0 Aweber Addon
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
				throw new Forminator_Addon_Aweber_Exception( __( ' AWeber is not connected', 'forminator' ) );
			}

			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Aweber_Quiz_Settings ) {
				throw new Forminator_Addon_Aweber_Exception( __( 'Invalid Quiz Settings of AWeber', 'forminator' ) );
			}

			$quiz_settings = $quiz_settings_instance->get_quiz_settings();

			if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
				$is_quiz_connected = true;
			} else {
				$is_quiz_connected = false;
			}
		} catch ( Forminator_Addon_Aweber_Exception $e ) {
			$is_quiz_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of Aweber with the form
		 *
		 * @since 1.1
		 *
		 * @param bool $is_quiz_connected
		 * @param int $quiz_id Current Quiz ID.
		 * @param Forminator_Addon_Aweber_Quiz_Settings|null $quiz_settings_instance Instance of quiz settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_aweber_is_quiz_lead_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;

	}

	/**
	 * Allow multiple connection on one quiz
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return true;
	}
}
