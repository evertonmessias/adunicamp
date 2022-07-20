<?php

require_once dirname( __FILE__ ) . '/class-forminator-addon-hubspot-exception.php';
require_once dirname( __FILE__ ) . '/lib/class-forminator-addon-hubspot-wp-api.php';

/**
 * Class Forminator_Addon_Hubspot
 * HubSpot Addon Main Class
 *
 * @since 1.0 HubSpot Addon
 */
final class Forminator_Addon_Hubspot extends Forminator_Addon_Abstract {

	/**
	 * @var self|null
	 */
	private static $_instance = null;

	protected $_slug = 'hubspot';
	protected $_version = FORMINATOR_ADDON_HUBSPOT_VERSION;
	protected $_min_forminator_version = '1.1';
	protected $_short_title = 'HubSpot';
	protected $_title = 'HubSpot';
	protected $_url = 'https://wpmudev.com';
	protected $_full_path = __FILE__;

	protected $_form_settings = 'Forminator_Addon_Hubspot_Form_Settings';
	protected $_form_hooks = 'Forminator_Addon_Hubspot_Form_Hooks';

	protected $_quiz_settings = 'Forminator_Addon_Hubspot_Quiz_Settings';
	protected $_quiz_hooks = 'Forminator_Addon_Hubspot_Quiz_Hooks';

	private $_token = '';

	private $_auth_error_message = '';

	const TARGET_TYPE_PUBLIC_CHANNEL = 'public_channel';
	const TARGET_TYPE_PRIVATE_CHANNEL = 'private_channel';
	const TARGET_TYPE_DIRECT_MESSAGE = 'direct_message';

	protected $_position = 4;

	/**
	 * Forminator_Addon_Hubspot constructor.
	 *
	 * @since 1.0 HubSpot Addon
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description                = __( 'Get awesome by your form.', 'forminator' );
		$this->_activation_error_message   = __( 'Sorry but we failed to activate HubSpot Integration, don\'t hesitate to contact us', 'forminator' );
		$this->_deactivation_error_message = __( 'Sorry but we failed to deactivate HubSpot Integration, please try again', 'forminator' );

		$this->_update_settings_error_message = __(
			'Sorry, we failed to update settings, please check your form and try again',
			'forminator'
		);

		$this->_icon     = forminator_addon_hubspot_assets_url() . 'icons/hubspot.png';
		$this->_icon_x2  = forminator_addon_hubspot_assets_url() . 'icons/hubspot@2x.png';
		$this->_image    = forminator_addon_hubspot_assets_url() . 'img/hubspot.png';
		$this->_image_x2 = forminator_addon_hubspot_assets_url() . 'img/hubspot@2x.png';

		$this->is_multi_global = true;

		$this->global_id_for_new_integrations = uniqid( '', true );

		add_filter(
			'forminator_addon_hubspot_api_request_headers',
			array(
				$this,
				'default_filter_api_headers',
			),
			1,
			4
		);
		add_action( 'wp_ajax_forminator_hubspot_support_request', array( $this, 'hubspot_support_request' ) );

		add_action( 'forminator_after_activated_addons_removed', array( $this, 'clear_db' ), 10, 2 );
	}

	/**
	 * Clear Database
	 *
	 * @param string $slug Slug.
	 * @param object $addon Addon.
	 */
	public function clear_db( $slug, $addon ) {
		if ( $this->_slug === $slug ) {
			$api = $addon->get_api( 'any_random_token' );
			$api->clear_db();
		}
	}

	/**
	 * Get Instance
	 *
	 * @since 1.0 HubSpot Addon
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
	 * @since 1.0 HubSpot Addon
	 *
	 * @return bool
	 */
	public function is_connected() {
		try {
			// check if its active.
			if ( ! $this->is_active() ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'HubSpot is not active', 'forminator' ) );
			}

			// if user completed api setup.
			$is_connected = false;

			$setting_values = $this->get_settings_values();
			// if user completed api setup.
			if ( isset( $setting_values['token'] ) && ! empty( $setting_values['token'] ) ) {
				$is_connected = true;
			}
		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			$is_connected = false;
		}

		/**
		 * Filter connected status of HubSpot
		 *
		 * @since 1.0
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_hubspot_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Check if HubSpot is connected with current form
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $form_id
	 *
	 * @return bool
	 */
	public function is_form_connected( $form_id ) {
		try {
			$form_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'HubSpot is not connected', 'forminator' ) );
			}

			$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
			if ( ! $form_settings_instance instanceof Forminator_Addon_Hubspot_Form_Settings ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'Invalid Form Settings of HubSpot', 'forminator' ) );
			}

			// Mark as active when there is at least one active connection.
			if ( false === $form_settings_instance->find_one_active_connection() ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'No active HubSpot connection found in this form', 'forminator' ) );
			}

			$is_form_connected = true;

		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			$is_form_connected = false;
		}

		/**
		 * Filter connected status of HubSpot with the form
		 *
		 * @since 1.0
		 *
		 * @param bool $is_form_connected
		 * @param int $form_id Current Form ID.
		 * @param Forminator_Addon_Hubspot_Form_Settings|null $form_settings_instance Instance of form settings, or null when unavailable.
		 *
		 */
		$is_form_connected = apply_filters( 'forminator_addon_hubspot_is_form_connected', $is_form_connected, $form_id, $form_settings_instance );

		return $is_form_connected;
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 HubSpot Addon
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag show full log on entries
	 *
	 * @since 1.0 HubSpot Addon
	 * @return bool
	 */
	public static function is_show_full_log() {
		$show_full_log = false;
		if ( defined( 'FORMINATOR_ADDON_HUBSPOT_SHOW_FULL_LOG' ) && FORMINATOR_ADDON_HUBSPOT_SHOW_FULL_LOG ) {
			$show_full_log = true;
		}

		/**
		 * Filter Flag show full log on entries
		 *
		 * @since  1.2
		 *
		 * @params bool $show_full_log
		 */
		$show_full_log = apply_filters( 'forminator_addon_hubspot_show_full_log', $show_full_log );

		return $show_full_log;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 HubSpot Addon
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 HubSpot Addon
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
	 * @return array
	 * @throws Forminator_Addon_Hubspot_Wp_Api_Exception
	 * @throws Forminator_Addon_Hubspot_Wp_Api_Not_Found_Exception
	 */
	public function authorize_access() {

		$template = forminator_addon_hubspot_dir() . 'views/settings/authorize.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect forminator-integration-popup__close' ),
			);

			$setting_values  = $this->get_settings_values();
			$template_params = array(
				'auth_url' => $this->get_auth_url(),
				'token'    => $this->_token,
				'user'     => isset( $setting_values['user'] ) ? $setting_values['user'] : '',
			);
		} else {
			// Force save empty settings.
			$template_params = array(
				'auth_url' => $this->get_auth_url(),
				'token'    => '',
				'user'     => '',
			);
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Wait Authorize Access wizard
	 *
	 * @since 1.0 HubSpot Addon
	 * @return array
	 */
	public function wait_authorize_access() {
		$template         = forminator_addon_hubspot_dir() . 'views/settings/wait-authorize.php';
		$template_success = forminator_addon_hubspot_dir() . 'views/settings/success-authorize.php';
		$template_error   = forminator_addon_hubspot_dir() . 'views/settings/error-authorize.php';

		$buttons = array();

		$is_poll = true;

		$setting_values = $this->get_settings_values();

		$template_params = array(
			'token'    => $this->_token,
			'auth_url' => $this->get_auth_url(),
			'user'     => isset( $setting_values['user'] ) ? $setting_values['user'] : '',
		);

		$has_errors = false;

		if ( $this->_token ) {
			$buttons['close'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Close', 'forminator' ), 'forminator-addon-connect forminator-addon-close forminator-integration-popup__close' ),
			);
			$is_poll          = false;

			$template = $template_success;
		} elseif ( $this->_auth_error_message ) {
			$template_params['error_message'] = $this->_auth_error_message;
			$is_poll                          = false;
			$has_errors                       = true;

			// reset err msg.
			if ( $this->_auth_error_message ) {
				unset( $setting_values['auth_error_message'] );
				$this->save_settings_values( $setting_values );
				$this->_auth_error_message = '';
			}

			$template = $template_error;
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	public function is_authorized( $submitted_data ) {
		$setting_values = $this->get_settings_values();

		// check api_key and and api_url set up.
		return isset( $setting_values['token'] ) && ! empty( $setting_values['token'] );
	}

	/**
	 * Get Access Token
	 *
	 * @since 1.0 HubSpot Addon
	 * @return string
	 */
	public function get_client_access_token() {
		$settings_values = $this->get_settings_values();
		$token           = '';
		if ( isset( $settings_values ['token'] ) ) {
			$token = $settings_values ['token'];
		}

		/**
		 * Filter access_token used
		 *
		 * @since 1.2
		 *
		 * @param string $token
		 */
		$token = apply_filters( 'forminator_addon_hubspot_client_access_token', $token );

		return $token;
	}

	/**
	 * Register a page for redirect url of HubSpot auth
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * Flag if delete member on delete entry enabled
	 *
	 * Default is `true`,
	 * which can be changed via `FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER` constant
	 *
	 * @return bool
	 */
	public static function is_enable_delete_member() {
		if ( defined( 'FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER' ) && FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Auth Url
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$base_authorize_url = Forminator_Addon_Hubspot_Wp_Api::AUTHORIZE_URL;
		$client_id          = Forminator_Addon_Hubspot_Wp_Api::CLIENT_ID;
		$redirect_url       = rawurlencode( forminator_addon_integration_section_admin_url( $this, 'authorize', false ) );
		$scopes             = Forminator_Addon_Hubspot_Wp_Api::$oauth_scopes;

		/**
		 * Filter OAuth Scopes
		 *
		 * @since 1.3
		 *
		 * @param array $scopes
		 */
		$scopes = apply_filters( 'forminator_addon_hubspot_oauth_scopes', $scopes );

		$auth_url = add_query_arg(
			array(
				'client_id'    => $client_id,
				'scope'        => rawurlencode( $scopes ),
				'redirect_uri' => $redirect_url,
			),
			$base_authorize_url
		);

		/**
		 * Filter HubSpot Auth Url
		 *
		 * @since 1.3
		 *
		 * @param string $auth_url
		 * @param string $base_authorize_url
		 * @param string $client_id
		 * @param array $scopes
		 * @param string $redirect_url
		 */
		$auth_url = apply_filters( 'forminator_addon_hubspot_auth_url', $auth_url, $base_authorize_url, $client_id, $scopes, $redirect_url );

		return $auth_url;
	}

	/**
	 * HubSpot Authorize Page
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $query_args
	 *
	 * @return string
	 */
	public function authorize_page_callback( $query_args ) {
		$settings        = $this->get_settings_values();
		$template        = forminator_addon_hubspot_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['code'] ) ) {
			try {
				$code       = $query_args['code'];
				$token      = '';
				$identifier = ! empty( $query_args['identifier'] ) ? $query_args['identifier'] : '';

				$this->multi_global_id = ! empty( $query_args['global_id'] ) ? $query_args['global_id'] : uniqid( '', true );

				// prefer new instance.
				$api           = Forminator_Addon_Hubspot_Wp_Api::get_instance( uniqid(), $this->multi_global_id );
				$redirect_uri  = forminator_addon_integration_section_admin_url( $this, 'authorize', false, $identifier );
				$args          = array(
					'code'         => $code,
					'redirect_uri' => $redirect_uri,
				);
				$token_request = $api->get_access_token( $args );

				if ( isset( $token_request->access_token ) ) {
					$token = $token_request->access_token;
				}

				if ( empty( $token ) ) {
					throw new Forminator_Addon_Hubspot_Exception( __( 'Failed to get token', 'forminator' ) );
				}

				if ( ! $this->is_active() ) {
					$activated = Forminator_Addon_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Addon_Loader::get_instance()->get_last_error_message();
						throw new Forminator_Addon_Hubspot_Exception( $last_message );
					}
				}
				$user = $api->get_access_token_information();

				$settings['token']        = $token;
				$settings['user']         = $user;
				$settings['identifier']   = $identifier;
				$settings['re-authorize'] = 'ticket';
				$this->save_settings_values( $settings );
				$template_params['is_close'] = true;
			} catch ( Exception $e ) {
				// catch all exception.
				$template_params['error_message'] = $e->getMessage();
			}
		} else {
			$template_params['error_message'] = __( 'Failed to get authorization code.', 'forminator' );
			// todo : translate $query_args[error].
			$settings['auth_error_message'] = $template_params['error_message'];
			$this->save_settings_values( $settings );
			$template_params['is_close'] = true;
		}

		return self::get_template( $template, $template_params );
	}

	/**
	 * Get API Instance
	 *
	 * @param null $access_token
	 *
	 * @return Forminator_Addon_Hubspot_Wp_Api|null
	 * @throws Forminator_Addon_Hubspot_Wp_Api_Exception
	 */
	public function get_api( $access_token = null ) {
		if ( is_null( $access_token ) ) {
			$access_token = $this->get_client_access_token();
		}

		$api = Forminator_Addon_Hubspot_Wp_Api::get_instance( $access_token, $this->multi_global_id );
		return $api;
	}

	/**
	 * Before get Setting Values
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $values
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		if ( isset( $values['token'] ) ) {
			$this->_token = $values['token'];
		}

		if ( isset( $values['auth_error_message'] ) ) {
			$this->_auth_error_message = $values['auth_error_message'];
		}

		return $values;
	}

	/**
	 * Default filter for header
	 *
	 * its add / change Authorization header
	 * - on get access token it uses Basic realm of encoded client id and secret
	 * - on web API request it uses Bearer realm of access token which default of @see Forminator_Addon_Hubspot_Wp_Api
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $headers
	 * @param $verb
	 * @param $path
	 * @param $args
	 *
	 * @return array
	 */
	public function default_filter_api_headers( $headers, $verb, $path, $args ) {
		if ( false !== stripos( $path, 'oauth.access' ) ) {
			$encoded_auth             = base64_encode( Forminator_Addon_Hubspot_Wp_Api::CLIENT_ID . ':' . Forminator_Addon_Hubspot_Wp_Api::CLIENT_SECRET ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . $encoded_auth;
			unset( $headers['Content-Type'] );
		}

		return $headers;
	}

	/**
	 * Support Request Ajax
	 *
	 * @throws Exception
	 */
	public function hubspot_support_request() {
		$status   = array();
		$pipeline = Forminator_Core::sanitize_text_field( 'value' );
		try {
			$api              = $this->get_api();
			$pipeline_request = $api->get_pipeline();
			if ( empty( $pipeline_request ) ) {
				throw new Exception( __( 'Pipeline can not be empty.', 'forminator' ) );
			}
			if ( ! empty( $pipeline_request->results ) ) {
				foreach ( $pipeline_request->results as $key => $data ) {
					if ( isset( $data->pipelineId ) && $pipeline === $data->pipelineId ) {
						foreach ( $data->stages as $stages => $stage ) {
							if ( isset( $stage->stageId ) && isset( $stage->label ) ) {
								$status[ $stage->stageId ] = $stage->label;
							}
						}
					}
				}
			}
			wp_send_json_success( $status );
		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Check if HubSpot is connected with current quiz
	 *
	 * @since 1.0 HubSpot Addon
	 *
	 * @param $quiz_id
	 *
	 * @return bool
	 */
	public function is_quiz_connected( $quiz_id ) {
		try {
			$quiz_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'HubSpot is not connected', 'forminator' ) );
			}

			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Hubspot_Quiz_Settings ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'Invalid Quiz Settings of HubSpot', 'forminator' ) );
			}

			// Mark as active when there is at least one active connection.
			if ( false === $quiz_settings_instance->find_one_active_connection() ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'No active HubSpot connection found in this quiz', 'forminator' ) );
			}

			$is_quiz_connected = true;

		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			$is_quiz_connected = false;
		}

		/**
		 * Filter connected status of HubSpot with the form
		 *
		 * @since 1.0
		 *
		 * @param bool $is_quiz_connected
		 * @param int $quiz_id Current Quiz ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings|null $quiz_settings_instance Instance of quiz settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_hubspot_is_form_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;
	}

	/**
	 * Flag for check if has lead form addon connected to a quiz
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @since 1.0 Hubspot Addon
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
				throw new Forminator_Addon_Hubspot_Exception( __( 'HubSpot is not connected', 'forminator' ) );
			}

			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
			if ( ! $quiz_settings_instance instanceof Forminator_Addon_Hubspot_Quiz_Settings ) {
				throw new Forminator_Addon_Hubspot_Exception( __( 'Invalid Quiz Settings of HubSpot', 'forminator' ) );
			}

			$quiz_settings = $quiz_settings_instance->get_quiz_settings();

			if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
				$is_quiz_connected = true;
			} else {
				$is_quiz_connected = false;
			}
		} catch ( Forminator_Addon_Hubspot_Exception $e ) {
			$is_quiz_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of Hubspot with the form
		 *
		 * @since 1.1
		 *
		 * @param bool $is_quiz_connected
		 * @param int $quiz_id Current Form ID.
		 * @param Forminator_Addon_Hubspot_Quiz_Settings|null $quiz_settings_instance Instance of quiz settings, or null when unavailable.
		 *
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_hubspot_is_quiz_lead_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

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
