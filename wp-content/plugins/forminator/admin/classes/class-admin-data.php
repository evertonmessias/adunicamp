<?php

/**
 * Class Forminator_Admin_Data
 *
 * @since 1.0
 */
class Forminator_Admin_Data {

	public $core = null;

	public static $pages = null;

	/**
	 * Current Nonce
	 *
	 * @since 1.2
	 * @var string
	 */
	private $_nonce = '';

	/**
	 * Forminator_Admin_Data constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->core = Forminator::get_instance();
	}

	/**
	 * Combine Data and pass to JS
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_options_data() {
		$data              = $this->admin_js_defaults();
		$data              = apply_filters( 'forminator_data', $data );
		$data['fields']    = forminator_get_fields_sorted( 'position', SORT_ASC );
		$data['fieldsPro'] = forminator_get_pro_fields();

		return $data;
	}

	/**
	 * Generate nonce
	 *
	 * @since 1.2
	 */
	public function generate_nonce() {
		$this->_nonce = wp_create_nonce( 'forminator_load_google_fonts' );
	}

	/**
	 * Get current generated nonce
	 *
	 * @return string
	 * @since 1.2
	 */
	public function get_nonce() {
		return $this->_nonce;
	}

	/**
	 * Return published pages
	 *
	 * @return mixed
	 * @since 1.8
	 */
	public static function get_pages() {
		if ( ! is_null( self::$pages ) ) {
			return self::$pages;
		}

		global $wpdb;

		$sql         = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' ORDER BY post_title ASC";
		self::$pages = $wpdb->get_results( $sql );

		return self::$pages;
	}

	/**
	 * Default Admin properties
	 *
	 * @return array
	 * @since 1.0
	 */
	public function admin_js_defaults() {
		// Generate addon nonce.
		Forminator_Addon_Admin_Ajax::get_instance()->generate_nonce();
		$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

		return array(
			'ajaxUrl'                        => forminator_ajax_url(),
			'adminUrl'                       => admin_url(),
			'akismetEnabled'                 => is_plugin_active( 'akismet/akismet.php' ),
			'application'                    => '',
			'is_touch'                       => wp_is_mobile(),
			'dashboardUrl'                   => menu_page_url( 'forminator', false ),
			'formEditUrl'                    => menu_page_url( 'forminator-cform-wizard', false ),
			'noWrongEditUrl'                 => menu_page_url( 'forminator-nowrong-wizard', false ),
			'knowledgeEditUrl'               => menu_page_url( 'forminator-knowledge-wizard', false ),
			'pollEditUrl'                    => menu_page_url( 'forminator-poll-wizard', false ),
			'settingsUrl'                    => menu_page_url( 'forminator-settings', false ),
			'integrationsUrl'                => menu_page_url( 'forminator-integrations', false ),
			'hasCaptcha'                     => forminator_has_captcha_settings(),
			'hasV2Captcha'                   => forminator_has_v2_captcha_settings(),
			'hasV2InvisibleCaptcha'          => forminator_has_v2_invisible_captcha_settings(),
			'hasV3Captcha'                   => forminator_has_v3_captcha_settings(),
			'hasHCaptcha'                   => forminator_has_hcaptcha_settings(),
			'hasStripe'                      => forminator_has_stripe_connected(),
			'formNonce'                      => $this->get_nonce(),
			'resetTrackingDataNonce'         => wp_create_nonce( 'forminator_reset_tracking_data' ),
			'previewNonce'                   => wp_create_nonce( 'forminator_load_module' ),
			'searchNonce'                    => wp_create_nonce( 'forminator_search_emails' ),
			'gFontNonce'                     => wp_create_nonce( 'forminator_load_google_fonts' ),
			'dismissNonce'                   => wp_create_nonce( 'forminator_dismiss_notification' ),
			'formProcessNonce'               => wp_create_nonce( 'forminator_form_request' ),
			'formExportNonce'                => wp_create_nonce( 'forminator_popup_export_form' ),
			'pollProcessNonce'               => wp_create_nonce( 'forminator_poll_request' ),
			'pollExportNonce'                => wp_create_nonce( 'forminator_popup_export_poll' ),
			'quizProcessNonce'               => wp_create_nonce( 'forminator_quiz_request' ),
			'quizExportNonce'                => wp_create_nonce( 'forminator_popup_export_quiz' ),
			'cloneNonce'                     => wp_create_nonce( 'forminator-nonce-clone-' . $id ),
			'addons_enabled'                 => Forminator::is_addons_feature_enabled(),
			'pluginUrl'                      => forminator_plugin_url(),
			'imagesUrl'                      => forminator_plugin_url() . 'assets/images',
			'addonNonce'                     => Forminator_Addon_Admin_Ajax::get_instance()->get_nonce(),
			'countries'                      => forminator_get_countries_list(),
			'userList'                       => forminator_list_users(),
			'variables'                      => forminator_get_vars(),
			'variablesForHiddenField'		 => forminator_get_vars( true ),
			'payment_variables'              => forminator_get_payment_vars(),
			'maxUpload'                      => forminator_get_max_upload(),
			'captchaLangs'                   => forminator_get_captcha_languages(),
			'erasure'                        => get_option( 'forminator_enable_erasure_request_erase_form_submissions', false ),
			'retain_number'                  => get_option( 'forminator_retain_submissions_interval_number', 0 ),
			'retain_unit'                    => get_option( 'forminator_retain_submissions_interval_unit', 'days' ),
			'poll_ip_retain_number'          => get_option( 'forminator_retain_votes_interval_number', 0 ),
			'poll_ip_retain_unit'            => get_option( 'forminator_retain_votes_interval_unit', 'days' ),
			'submissions_ip_retain_number'   => get_option( 'forminator_retain_poll_submissions_interval_number', 0 ),
			'submissions_ip_retain_unit'     => get_option( 'forminator_retain_poll_submissions_interval_unit', 'days' ),
			'submissions_quiz_retain_number' => get_option( 'forminator_retain_quiz_submissions_interval_number', 0 ),
			'submissions_quiz_retain_unit'   => get_option( 'forminator_retain_quiz_submissions_interval_unit', 'days' ),
			'skip_pro_notice'                => get_option( 'forminator_skip_pro_notice', false ),
			'fileExts'                       => forminator_get_ext_types(),
			'version'                        => FORMINATOR_VERSION,
			'showDocLink'                    => forminator_is_show_documentation_link(),
			'showBranding'                   => forminator_is_show_branding(),
			'currencies'                     => forminator_currency_list(),
			'ppCurrencies'                   => forminator_pp_currency_list(),
			'postTypeList'                   => forminator_post_type_list(),
			'postCategories'                 => forminator_post_categories(),
			'isPro'                          => FORMINATOR_PRO,
			'userRoles'                      => get_editable_roles(),
			'pages'                          => self::get_pages(),
			'hasPayPal'                      => forminator_has_paypal_settings(),
			'pollAnswerColors'               => forminator_get_poll_chart_colors(),
			'isMainSite'                     => forminator_is_main_site(),
			'isSubdomainNetwork'             => forminator_is_subdomain_network(),
			'showFieldSettings'              => get_option( 'forminator_editor_settings', 'true' ),
			'hasStripePro'                   => defined( 'FORMINATOR_STRIPE_ADDON' ) && class_exists( 'Forminator_Stripe_Addon' ),
			'stripeForms'                    => $this->get_forms_by_field_type( 'stripe' ),
			'paypalForms'                    => $this->get_forms_by_field_type( 'paypal' ),
		);
	}

	/**
	 * Get form by field
	 *
	 * @param $type
	 *
	 * @return array
	 */
	public function get_forms_by_field_type( $type ) {
		$field_forms = array();
		$forms       = Forminator_Form_Model::model()->get_models( 99 );
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				if ( ! empty( $form->fields ) ) {
					foreach ( $form->fields as $f => $field ) {
						$field_array = $field->to_formatted_array();
						$field_type  = isset( $field_array['type'] ) ? $field_array['type'] : '';
						if ( $type === $field_type ) {
							$field_forms[ $form->id ] = isset( $form->settings['formName'] ) ? $form->settings['formName'] : '';
						}
					}
				}
			}
		}

		return $field_forms;
	}
}
