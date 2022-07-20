<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_AJAX
 *
 * @since 1.0
 */
class Forminator_Admin_AJAX {

	/**
	 * Forminator_Admin_AJAX constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Handle close welcome box
		add_action( 'wp_ajax_forminator_dismiss_welcome', array( $this, 'dismiss_welcome' ) );
		add_action( 'wp_ajax_nopriv_forminator_dismiss_welcome', array( $this, 'dismiss_welcome' ) );

		// Handle load google fonts
		add_action( 'wp_ajax_forminator_load_google_fonts', array( $this, 'load_google_fonts' ) );

		// Handle load reCaptcha preview
		add_action( 'wp_ajax_forminator_load_recaptcha_preview', array( $this, 'load_recaptcha_preview' ) );
		add_action( 'wp_ajax_forminator_load_hcaptcha_preview', array( $this, 'load_hcaptcha_preview' ) );

		// Handle save settings
		add_action( 'wp_ajax_forminator_save_builder', array( $this, 'save_builder' ) );
		add_action( 'wp_ajax_forminator_save_poll', array( $this, 'save_poll_form' ) );
		add_action( 'wp_ajax_forminator_save_quiz_nowrong', array( $this, 'save_quiz' ) );
		add_action( 'wp_ajax_forminator_save_quiz_knowledge', array( $this, 'save_quiz' ) );
		add_action( 'wp_ajax_forminator_save_login', array( $this, 'save_login' ) );
		add_action( 'wp_ajax_forminator_save_register', array( $this, 'save_register' ) );

		// Handle settings popups.
		add_action( 'wp_ajax_forminator_load_captcha_popup', array( $this, 'load_captcha' ) );
		add_action( 'wp_ajax_forminator_save_captcha_popup', array( $this, 'save_captcha' ) );

		add_action( 'wp_ajax_forminator_load_currency_popup', array( $this, 'load_currency' ) );
		add_action( 'wp_ajax_forminator_save_currency_popup', array( $this, 'save_currency' ) );

		add_action( 'wp_ajax_forminator_load_pagination_entries_popup', array( $this, 'load_pagination_entries' ) );
		add_action( 'wp_ajax_forminator_save_pagination_entries_popup', array( $this, 'save_pagination_entries' ) );

		add_action( 'wp_ajax_forminator_load_pagination_listings_popup', array( $this, 'load_pagination_listings' ) );
		add_action( 'wp_ajax_forminator_save_pagination_listings_popup', array( $this, 'save_pagination_listings' ) );

		add_action( 'wp_ajax_forminator_load_email_settings_popup', array( $this, 'load_email_form' ) );

		add_action( 'wp_ajax_forminator_load_uninstall_settings_popup', array( $this, 'load_uninstall_form' ) );
		add_action( 'wp_ajax_forminator_save_uninstall_settings_popup', array( $this, 'save_uninstall_form' ) );

		add_action( 'wp_ajax_forminator_load_preview_cforms_popup', array( $this, 'preview_module' ) );
		add_action( 'wp_ajax_forminator_load_preview_polls_popup', array( $this, 'preview_module' ) );
		add_action( 'wp_ajax_forminator_load_preview_quizzes_popup', array( $this, 'preview_module' ) );

		// Handle exports popup
		add_action( 'wp_ajax_forminator_load_exports_popup', array( $this, 'load_exports' ) );
		add_action( 'wp_ajax_forminator_clear_exports_popup', array( $this, 'clear_exports' ) );

		// Handle search user email
		add_action( 'wp_ajax_forminator_builder_search_emails', array( $this, 'search_emails' ) );

		add_action( 'wp_ajax_forminator_load_privacy_settings_popup', array( $this, 'load_privacy_settings' ) );
		add_action( 'wp_ajax_forminator_save_privacy_settings_popup', array( $this, 'save_privacy_settings' ) );

		add_action( 'wp_ajax_forminator_load_export_form_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_form_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_form_popup', array( $this, 'save_import' ) );

		add_action( 'wp_ajax_forminator_load_import_form_cf7_popup', array( $this, 'load_import_form_cf7' ) );
		add_action( 'wp_ajax_forminator_save_import_form_cf7_popup', array( $this, 'save_import_form_cf7' ) );

		add_action( 'wp_ajax_forminator_load_import_form_ninja_popup', array( $this, 'load_import_form_ninja' ) );
		add_action( 'wp_ajax_forminator_save_import_form_ninja_popup', array( $this, 'save_import_form_ninja' ) );

		add_action( 'wp_ajax_forminator_load_import_form_gravity_popup', array( $this, 'load_import_form_gravity' ) );
		add_action( 'wp_ajax_forminator_save_import_form_gravity_popup', array( $this, 'save_import_form_gravity' ) );

		add_action( 'wp_ajax_forminator_load_export_poll_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_poll_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_poll_popup', array( $this, 'save_import' ) );

		add_action( 'wp_ajax_forminator_delete_poll_submissions', array( $this, 'delete_poll_submissions' ) );

		add_action( 'wp_ajax_forminator_load_export_quiz_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_quiz_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_quiz_popup', array( $this, 'save_import' ) );

		add_action( 'wp_ajax_forminator_save_accessibility_settings_popup', array( $this, 'save_accessibility_settings' ) );

		add_action( 'wp_ajax_forminator_save_dashboard_settings_popup', array( $this, 'save_dashboard_settings' ) );

		add_action( 'wp_ajax_forminator_stripe_settings_modal', array( $this, 'stripe_settings_modal' ) );
		add_action( 'wp_ajax_forminator_stripe_update_page', array( $this, 'stripe_update_page' ) );
		add_action( 'wp_ajax_forminator_disconnect_stripe', array( $this, 'stripe_disconnect' ) );

		add_action( 'wp_ajax_forminator_paypal_settings_modal', array( $this, 'paypal_settings_modal' ) );
		add_action( 'wp_ajax_forminator_paypal_update_page', array( $this, 'paypal_update_page' ) );
		add_action( 'wp_ajax_forminator_disconnect_paypal', array( $this, 'paypal_disconnect' ) );

		add_action( 'wp_ajax_forminator_save_payments_settings_popup', array( $this, 'save_payments' ) );
		add_action( 'wp_ajax_forminator_dismiss_notification', array( $this, 'dismiss_notice' ) );
		add_action( 'wp_ajax_forminator_dismiss_notice', array( $this, 'dismiss_admin_notice' ) );

		add_action( 'wp_ajax_forminator_later_notification', array( $this, 'later_notice' ) );

		add_action( 'wp_ajax_forminator_reset_tracking_data', array( $this, 'reset_tracking_data' ) );

		add_action( 'wp_ajax_forminator_save_appearance_preset', array( $this, 'save_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_create_appearance_preset', array( $this, 'create_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_apply_appearance_preset', array( $this, 'apply_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_delete_appearance_preset', array( $this, 'delete_appearance_preset' ) );

		add_action( 'wp_ajax_forminator_dismiss_prelaunch_subscriptions', array( $this, 'dismiss_prelaunch_subscriptions_notice' ) );

		add_action( 'wp_ajax_forminator_module_search', array( $this, 'module_search' ) );
		add_action( 'wp_ajax_forminator_load_addons_page_details_popup', array( $this, 'addons_page_details' ) );

		// Process ajax actions.
		$ajax_actions = array(
			'forminator_addons-install',
			'forminator_addons-activate',
			'forminator_addons-deactivate',
			'forminator_addons-delete',
			'forminator_addons-update',
		);
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'addons_page_actions' ) );
		}
	}

	/**
	 * Save quizzes
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function save_quiz() {
		if ( ! forminator_is_user_allowed() ) {
			wp_send_json_error( __( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_quiz' );

		$submitted_data = $this->get_post_data();

		$quiz_data = array();
		if ( ! empty( $_POST['data'] ) ) {
			$quiz_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		$id       = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$settings = isset( $quiz_data['settings'] ) ? $quiz_data['settings'] : array();
		$title    = isset( $submitted_data['quiz_title'] ) ? $submitted_data['quiz_title'] : $submitted_data['formName'];
		$status   = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version  = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template = new stdClass();

		$template->type = isset( $submitted_data['action'] ) ? $submitted_data['action'] : '';
		// Check if results exist
		if ( isset( $quiz_data['results'] ) && is_array( $quiz_data['results'] ) ) {
			$template->results = $quiz_data['results'];
		}

		// Check if answers exist
		if ( isset( $quiz_data['questions'] ) ) {
			$template->questions = $quiz_data['questions'];
		}

		if ( isset( $quiz_data['settings'] ) ) {
			$settings = $quiz_data['settings'];
		}
		$settings['version'] = $version;
		$template->settings  = $settings;

		if ( isset( $quiz_data['notifications'] ) ) {
			$template->notifications = $quiz_data['notifications'];
		}

		$id = Forminator_Quiz_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Save poll
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function save_poll_form() {
		if ( ! forminator_is_user_allowed() ) {
			wp_send_json_error( __( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_poll' );

		$submitted_data = $this->get_post_data();
		$poll_data      = array();
		if ( ! empty( $_POST['data'] ) ) {
			$poll_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		$settings = isset( $poll_data['settings'] ) ? $poll_data['settings'] : array();
		$id       = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title    = $submitted_data['formName'];
		$status   = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version  = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template = new stdClass();

		if ( isset( $poll_data['answers'] ) ) {
			$template->answers = $poll_data['answers'];
		}

		$settings['version'] = $version;
		$template->settings  = $settings;

		$id = Forminator_Poll_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Save custom form fields
	 *
	 * @since 1.2
	 */
	public function save_builder() {
		if ( ! forminator_is_user_allowed() ) {
			wp_send_json_error( __( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_builder_fields' );

		$submitted_data = $this->get_post_data();
		$form_data      = array();
		$fields         = array();
		$id             = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title          = $submitted_data['formName'];
		$status         = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version        = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template       = new stdClass();
		$action         = false;

		if ( ! empty( $_POST['data'] ) ) {
			$form_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Form_Model();
			$action     = 'create';

			if ( empty( $status ) ) {
				$status = Forminator_Form_Model::STATUS_PUBLISH;
			}
		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );
			$action     = 'update';

			if ( ! is_object( $form_model ) ) {
				wp_send_json_error( __( "Form model doesn't exist", 'forminator' ) );
			}

			if ( empty( $status ) ) {
				$status = $form_model->status;
			}

			//we need to empty fields cause we will send new data
			$form_model->clear_fields();
		}

		// Build the fields.
		if ( isset( $form_data ) ) {
			$fields = $form_data['wrappers'];
			unset( $form_data['wrappers'] );
		}
		$template->fields = $fields;

		if ( isset( $form_data['integrationConditions'] ) ) {
			$template->integration_conditions = $form_data['integrationConditions'];
		}

		if ( isset( $form_data['behaviorArray'] ) ) {
			$template->behaviors = $form_data['behaviorArray'];
		}

		if ( isset( $form_data['notifications'] ) ) {
			$template->notifications = $form_data['notifications'];
		}

		// Sanitize settings
		$settings            = $form_data['settings'];
		$settings['version'] = $version;
		$template->settings  = $settings;

		$id = Forminator_Custom_Form_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Save custom form settings
	 *
	 * @since 1.2
	 */
	public function save_builder_settings() {
		_deprecated_function( 'save_builder_settings', '1.6', 'save_builder' );
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_forminator' ) ) {
			return;
		}

		forminator_validate_ajax( 'forminator_save_builder_fields' );

		$submitted_data = $this->get_post_data();
		$id             = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title          = $submitted_data['formName'];
		$status         = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version        = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';

		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Form_Model();

			if ( empty( $status ) ) {
				$status = Forminator_Form_Model::STATUS_PUBLISH;
			}

		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );

			if ( ! is_object( $form_model ) ) {
				wp_send_json_error( __( "Form model doesn't exist", 'forminator' ) );
			}
			if ( empty( $status ) ) {
				$status = $form_model->status;
			}
		}
		$form_model->set_var_in_array( 'name', 'formName', $submitted_data, 'forminator_sanitize_field' );

		$settings = $submitted_data['data'];

		$settings['formName'] = $title;
		$settings['version']  = $version;
		$form_model->settings = $settings;

		// status.
		$form_model->status = $status;

		// Save data
		$id = $form_model->save();

		// add privacy settings to global option
		$override_privacy = false;
		if ( isset( $settings['enable-submissions-retention'] ) ) {
			$override_privacy = filter_var( $settings['enable-submissions-retention'], FILTER_VALIDATE_BOOLEAN );
		}
		$retention_number = null;
		$retention_unit   = null;
		if ( $override_privacy ) {
			$retention_number = 0;
			$retention_unit   = 'days';
			if ( isset( $settings['submissions-retention-number'] ) ) {
				$retention_number = (int) $settings['submissions-retention-number'];
			}
			if ( isset( $settings['submissions-retention-unit'] ) ) {
				$retention_unit = $settings['submissions-retention-unit'];
			}
		}

		forminator_update_form_submissions_retention( $id, $retention_number, $retention_unit );

		wp_send_json_success( $id );
	}

	/**
	 * Apply Appearance Preset ajax.
	 */
	public function apply_appearance_preset() {
		forminator_validate_ajax( 'forminator_apply_preset' );

		$preset_id = filter_input( INPUT_POST, 'preset_id' );
		$ids       = filter_input( INPUT_POST, 'ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		$edit_form = filter_input( INPUT_POST, 'edit_form', FILTER_VALIDATE_BOOLEAN );
		$count     = 0;

		$new_settings = Forminator_Settings_Page::get_preset( $preset_id );
		if ( $ids ) {
			foreach ( $ids as $form_id ) {
				$form_model = Forminator_Base_Form_Model::get_model( $form_id );
				if ( ! $form_model ) {
					continue;
				}

				$form_model->settings = self::merge_appearance_settings( $form_model->settings, $new_settings );

				if ( $edit_form ) {
					$settings     = filter_input( INPUT_POST, 'settings' );
					$old_settings = json_decode( wp_unslash( $settings ), true );
					if ( $old_settings ) {
						$form_model->settings = self::merge_appearance_settings( $old_settings, $new_settings );
					}
					wp_send_json_success( $form_model->settings );
				}

				// Save data.
				$form_model->save();

				// Regenerare module css file.
				Forminator_Render_Form::regenerate_css_file( $form_id );

				$count++;
			}
		}

		if ( $edit_form ) {
			wp_send_json_error( __( 'Something went wrong.', 'forminator' ) );
		}

		$success = sprintf( __( 'Preset successfully applied to %d form(s).', 'forminator' ), $count );
		wp_send_json_success( $success );
	}

	/**
	 * Merge Appearance settings
	 *
	 * @param array $settings Current Settings.
	 * @param array $new_settings New Appearance settings.
	 * @return array
	 */
	private function merge_appearance_settings( $settings, $new_settings ) {
		$appearance_settings = Forminator_Settings_Page::only_appearance_settings( $settings );
		$rest_settings       = array();
		foreach ( $settings as $key => $val ) {
			if ( ! array_key_exists( $key, $appearance_settings ) ) {
				$rest_settings[ $key ] = $val;
			}
		}
		$new_settings = array_merge( $rest_settings, $new_settings );

		return $new_settings;
	}

	/**
	 * Create Appearance Preset ajax.
	 */
	public function create_appearance_preset() {
		forminator_validate_ajax( 'forminator_create_preset' );

		$id       = uniqid();
		$settings = array();
		$form_id  = filter_input( INPUT_POST, 'form_id' );
		$name     = filter_input( INPUT_POST, 'name' );

		if ( empty( $name ) ) {
			wp_send_json_error( __( 'Preset Name is empty.', 'forminator' ) );
		}

		if ( ! empty( $form_id ) ) {
			$form     = Forminator_Base_Form_Model::get_model( $form_id );
			$settings = $form->settings;
		}

		// Update preset list.
		Forminator_Settings_Page::save_preset_list( $id, $name );

		// Save preset.
		self::save_preset( $id, $settings );

		wp_send_json_success( $id );
	}

	/**
	 * Delete Appearance Preset ajax.
	 */
	public function delete_appearance_preset() {
		forminator_validate_ajax( 'forminator_appearance_preset' );

		$id = filter_input( INPUT_POST, 'preset_id' );
		if ( empty( $id ) ) {
			wp_send_json_error( __( 'Preset ID is empty.', 'forminator' ) );
		}
		if ( 'default' === $id ) {
			wp_send_json_error( __( 'Preset ID is incorrect.', 'forminator' ) );
		}

		// Delete preset from list.
		$key          = 'forminator_appearance_presets';
		$preset_names = get_option( $key, array() );
		unset( $preset_names[ $id ] );
		update_option( $key, $preset_names );

		// Delete preset.
		delete_option( 'forminator_appearance_preset_' . $id );

		wp_send_json_success();
	}

	/**
	 * Save Appearance Preset ajax.
	 */
	public function save_appearance_preset() {
		forminator_validate_ajax( 'forminator_appearance_preset' );

		$id = filter_input( INPUT_POST, 'presetId' );
		if ( ! $id ) {
			wp_send_json_error( esc_attr__( "Appearance preset id doesn't exist", 'forminator' ) );
		}

		$settings = array();
		if ( ! empty( $_POST['settings'] ) ) {
			$settings = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['settings'] ), true ) );
		}

		self::save_preset( $id, $settings );

		wp_send_json_success( esc_attr__( 'The preset has been successfully updated.', 'forminator' ) );
	}

	/**
	 * Save Appearance Preset
	 *
	 * @param string $id ID.
	 * @param array  $settings Settings.
	 */
	private static function save_preset( $id, $settings ) {
		update_option( 'forminator_appearance_preset_' . $id, $settings );
	}

	/**
	 * Load existing custom field keys
	 *
	 * @return string JSON
	 * @since 1.0
	 */
	public function load_existing_cfields() {

		forminator_validate_ajax( 'forminator_load_existing_cfields' );

		$keys = array();
		$html = '';

		foreach ( $keys as $key ) {
			$html .= "<option value='$key'>$key</option>";
		}

		wp_send_json_success( $html );
	}

	/**
	 * Dismiss welcome message
	 *
	 * @since 1.0
	 */
	public function dismiss_welcome() {
		forminator_validate_ajax( 'forminator_dismiss_welcome' );
		update_option( 'forminator_welcome_dismissed', true );
		wp_send_json_success();
	}

	/**
	 * Load Google Fonts
	 *
	 * @since 1.0
	 */
	public function load_fonts() {
		forminator_validate_ajax( 'forminator_load_fonts' );
		_deprecated_function( 'load_fonts', '1.0.5', 'load_google_fonts' );
		wp_send_json_error( array() );
	}


	/**
	 * Load google fonts
	 *
	 * @since 1.0.5
	 */
	public function load_google_fonts() {
		forminator_validate_ajax( 'forminator_load_google_fonts' );

		$is_object = isset( $_POST['data']['isObject'] ) ? sanitize_text_field( $_POST['data']['isObject'] ) : false;

		$fonts = forminator_get_font_families( $is_object );
		wp_send_json_success( $fonts );
	}

	/**
	 * Load reCaptcha settings
	 *
	 * @since 1.0
	 */
	public function load_captcha() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_captcha' );

		$html = forminator_template( 'settings/popup/edit-captcha-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save reCaptcha popup data
	 *
	 * @since 1.0
	 */
	public function save_captcha() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_popup_captcha' );

		update_option( 'forminator_captcha_key', Forminator_Core::sanitize_text_field( 'v2_captcha_key' ) );
		update_option( 'forminator_captcha_secret', Forminator_Core::sanitize_text_field( 'v2_captcha_secret' ) );

		update_option( 'forminator_v2_invisible_captcha_key', Forminator_Core::sanitize_text_field( 'v2_invisible_captcha_key' ) );
		update_option( 'forminator_v2_invisible_captcha_secret', Forminator_Core::sanitize_text_field( 'v2_invisible_captcha_secret' ) );

		update_option( 'forminator_v3_captcha_key', Forminator_Core::sanitize_text_field( 'v3_captcha_key' ) );
		update_option( 'forminator_v3_captcha_secret', Forminator_Core::sanitize_text_field( 'v3_captcha_secret' ) );

		update_option( "forminator_hcaptcha_key", sanitize_text_field( $_POST['hcaptcha_key'] ) );
		update_option( "forminator_hcaptcha_secret", sanitize_text_field( $_POST['hcaptcha_secret'] ) );
		update_option( "forminator_captcha_tab_saved", sanitize_text_field( $_POST['captcha_tab_saved'] ) );
		// update_option( "forminator_hcaptcha_noconflict", sanitize_text_field( $_POST['hcaptcha_noconflict'] ) );

		update_option( 'forminator_captcha_language', Forminator_Core::sanitize_text_field( 'captcha_language' ) );

		wp_send_json_success();
	}

	/**
	 * Load currency modal
	 *
	 * @since 1.0
	 */
	public function load_currency() {
		forminator_validate_ajax( 'forminator_popup_currency' );

		$html = forminator_template( 'settings/popup/edit-currency-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save reCaptcha popup data
	 *
	 * @since 1.0
	 */
	public function save_currency() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_popup_currency' );

		update_option( 'forminator_currency', Forminator_Core::sanitize_text_field( 'currency' ) );

		wp_send_json_success();
	}

	/**
	 * Load entries pagination modal
	 *
	 * @since 1.0.2
	 */
	public function load_pagination_entries() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_pagination_entries' );

		$html = forminator_template( 'settings/popup/edit-pagination-entries-content' );

		wp_send_json_success( $html );
	}

	/*
	 * Load reCaptcha preview
	 *
	 * @since 1.5.4
	 */
	public function load_recaptcha_preview() {
		$site_language = get_locale();
		$language      = get_option( 'forminator_captcha_language', '' );
		$language      = ! empty( $language ) ? $language : $site_language;

		$captcha = Forminator_Core::sanitize_text_field( 'captcha' );

		if ( 'v2-invisible' === $captcha ) {
			$captcha_key  = get_option( 'forminator_v2_invisible_captcha_key', '' );
			$captcha_size = 'invisible';
			$onload       = 'forminator_render_admin_captcha_v2_invisible';
		} elseif ( 'v3' === $captcha ) {
			$captcha_key  = get_option( 'forminator_v3_captcha_key', '' );
			$captcha_size = 'invisible';
			$onload       = 'forminator_render_admin_captcha_v3';
		} else {
			$captcha_key  = get_option( 'forminator_captcha_key', '' );
			$captcha_size = 'normal';
			$onload       = 'forminator_render_admin_captcha_v2';
		}
		$html = '';

		if ( ! empty( $captcha_key ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$html .= '<script src="https://www.google.com/recaptcha/api.js?hl=' . $language . '&render=explicit&onload=' . $onload . '" async defer></script>';
			$html .= '<div class="forminator-g-recaptcha-' . $captcha . '" data-sitekey="' . $captcha_key . '" data-theme="light" data-size="' . $captcha_size . '"></div>';

		} else {
			$html .= '<div role="alert" class="sui-notice sui-active" style="display: block; text-align: left;" aria-live="assertive">';
				$html .= '<div class="sui-notice-content">';
					$html .= '<div class="sui-notice-message">';
						$html .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';
						$html .= '<p>' . esc_html__( 'You have to first save your credentials to load the reCAPTCHA . ', 'forminator' ) . '</p>';
					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';
		}

		wp_send_json_success( $html );
	}

	/*
	 * Load hCaptcha preview
	 *
	 * @since 1.15.?
	 */
	public function load_hcaptcha_preview() {
		$site_language = get_locale();
		$language      = get_option( 'forminator_captcha_language', '' );
		$language      = ! empty( $language ) ? $language : $site_language;

		// $captcha = sanitize_text_field( $_POST['captcha'] );// phpcs:ignore -- data without nonce verification

		$hcaptcha_key  	 = get_option( 'forminator_hcaptcha_key', '' );
		$hcaptcha_secret = get_option( 'forminator_hcaptcha_secret', '' );
		$onload			 = 'forminator_render_admin_hcaptcha';

		if ( ! empty( $hcaptcha_key ) && ! empty( $hcaptcha_secret ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			// recaptchacompat=off seems to fix problems with hcaptcha container loading recaptcha instead of hcaptcha in the admin
			$html  = '<script src="https://js.hcaptcha.com/1/api.js?hl=' . $language . '&onload=' . $onload . '&render=explicit&recaptchacompat=off" async defer></script>';
			$html .= '<div class="forminator-hcaptcha h-captcha" data-sitekey="' . esc_attr( $hcaptcha_key ) . '"></div>';
		} else {
			$html  = '<div class="sui-notice" style="margin: 10px 0;"><p>' . esc_html__( 'Save your API keys to load the hCAPTCHA preview.', 'forminator' ) . '</p></div>';
		}

		wp_send_json_success( $html );
	}

	/**
	 * Load listings pagination modal
	 *
	 * @since 1.0.2
	 */
	public function load_pagination_listings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_pagination_listings' );

		$html = forminator_template( 'settings/popup/edit-pagination-listings-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save listings pagination popup data
	 *
	 * @since 1.0.2
	 */
	public function save_pagination_listings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_popup_pagination_listings' );

		$pagination = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 0 < $pagination ) {

			update_option( 'forminator_pagination_listings', $pagination );
			wp_send_json_success();

		} else {

			wp_send_json_error( __( 'Limit per page can not be less than one.', 'forminator' ) );

		}

	}

	/**
	 * Load the email settings form
	 *
	 * @since 1.1
	 */
	public function load_email_form() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_load_popup_email_settings' );

		$html = forminator_template( 'settings/popup/edit-email-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Load the uninstall form
	 *
	 * @since 1.0.2
	 */
	public function load_uninstall_form() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_uninstall_form' );

		$html = forminator_template( 'settings/popup/edit-uninstall-content' );

		wp_send_json_success( $html );
	}


	/**
	 * Save listings pagination popup data
	 *
	 * @since 1.0.2
	 */
	public function save_uninstall_form() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_popup_uninstall_settings' );

		$delete_uninstall = Forminator_Core::sanitize_text_field( 'delete_uninstall', false );
		$delete_uninstall = filter_var( $delete_uninstall, FILTER_VALIDATE_BOOLEAN );

		update_option( 'forminator_uninstall_clear_data', $delete_uninstall );
		wp_send_json_success();

	}

	/**
	 * Preview module
	 *
	 * @since 1.0
	 */
	public function preview_module() {
		$current_action = current_action();

		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_preview_', '_popup' ), '', $current_action );
		if ( 'cforms' === $slug ) {
			$slug = 'form';
		} elseif ( 'polls' === $slug ) {
			$slug = 'poll';
		} elseif ( 'quizzes' === $slug ) {
			$slug = 'quiz';
		}

		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_preview_' . $slug );

		$preview_data = false;
		// force -1 for preview.
		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$form_id = $form_id ? $form_id : -1;

		// Check if preview data set
		if ( ! empty( $_POST['data'] ) ) {
			$data = Forminator_Core::sanitize_array( $_POST['data'], 'data' );

			if ( ! is_array( $data ) ) {
				$data = json_decode( $data, true );
			}
			$function     = 'forminator_data_to_model_' . $slug;
			$preview_data = $function( $data );
		}

		$html = forminator_preview( $form_id, $slug, true, $preview_data );

		wp_send_json_success( $html );
	}

	/**
	 * Load list of exports
	 *
	 * @since 1.0
	 */
	public function load_exports() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_load_exports' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		if ( $form_id ) {
			$args = array(
				'form_id' => $form_id,
			);
			$html = forminator_template( 'settings/popup/exports-content', $args );
			wp_send_json_success( $html );
		} else {
			wp_send_json_error( __( 'Not valid module ID provided.', 'forminator' ) );
		}
	}

	/**
	 * Clear list of exports
	 *
	 * @since 1.0
	 */
	public function clear_exports() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_clear_exports' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		if ( ! $form_id ) {
			wp_send_json_error( __( 'No ID was provided.', 'forminator' ) );
		}

		$was_cleared = delete_export_logs( $form_id );

		if ( $was_cleared ) {
			wp_send_json_success( __( 'Exports cleared.', 'forminator' ) );
		} else {
			wp_send_json_error( __( "Exports couldn't be cleared.", 'forminator' ) );
		}
	}

	/**
	 * Search Emails
	 *
	 * @since 1.0.3
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function search_emails() {
		forminator_validate_ajax( 'forminator_search_emails' );
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_forminator' ) ) {
			wp_send_json_error( array() );
		}

		$submitted_data = $this->get_post_data();

		// TODO : add ajax validate here and js admin too.
		$admin_email  = ! empty( $submitted_data['admin_email'] ) ? true : false;
		$search_email = ! empty( $submitted_data['q'] ) ? $submitted_data['q'] : false;

		// return admin_email when requested.
		if ( $admin_email ) {
			wp_send_json_success( get_option( 'admin_email' ) );
		}

		if ( ! $search_email ) {
			wp_send_json_success( array() );
		}

		$args = array(
			'search'  => '*' . $search_email . '*',
			'number'  => 10,
			'orderby' => 'user_login',
			'order'   => 'ASC',
		);

		/**
		 * Filter args to be passed on to get_users
		 *
		 * @param array $args
		 * @param string $search_email string to search.
		 *
		 * @see   get_users()
		 *
		 * @since 1.2
		 *
		 */
		$args = apply_filters( 'forminator_builder_search_emails_args', $args, $search_email );

		$users = get_users( $args );
		$data  = array();
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				$data[] = array(
					'id'           => $user->user_email,
					'text'         => $user->user_email,
					'display_name' => $user->display_name,
				);
			}
		}

		/**
		 * Filter returned data when builder search emails
		 *
		 * @param array $data
		 * @param array $users search result of get_users.
		 * @param array $args current query args passed to get_users.
		 * @param string $search_email string to search.
		 *
		 * @since 1.2
		 *
		 */
		$data = apply_filters( 'forminator_builder_search_emails_data', $data, $users, $args, $search_email );

		wp_send_json_success( $data );
	}

	/**
	 * Get superglobal POST data
	 *
	 * @param string $nonce_action action to validate.
	 * @param array $sanitize_callbacks {
	 *                                   custom sanitize options, its assoc array
	 *                                   'field_name_1' => 'function_to_call_1' function will called with `call_user_func_array`,
	 *                                   'field_name_2' => 'function_to_call_2',
	 *                                   }
	 *
	 * @return array
	 * @since 1.1
	 *
	 */
	protected function get_post_data( $nonce_action = '', $sanitize_callbacks = array() ) {
		// do nonce / caps check when requested.
		if ( ! empty( $nonce_action ) ) {
			// it will wp_send_json_error.
			forminator_validate_ajax( $nonce_action );
		}

		$post_data = Forminator_Core::sanitize_array( $_POST );

		// do some additional sanitize.
		foreach ( $sanitize_callbacks as $field => $sanitize_func ) {
			if ( isset( $post_data[ $field ] ) ) {
				if ( is_callable( $sanitize_func ) ) {
					$post_data[ $field ] = call_user_func_array( array( $sanitize_func ), array( $post_data[ $field ] ) );
				}
			}
		}

		// do some validation

		return $post_data;
	}

	/*
	 * Load Privacy Settings
	 *
	 * @since 1.0.6
	 */
	public function load_privacy_settings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_privacy_settings' );

		$html = forminator_template( 'settings/popup/edit-privacy-settings' );

		wp_send_json_success( $html );
	}

	/**
	 * Save Privacy Settings
	 *
	 * @since 1.0.6
	 */
	public function save_privacy_settings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_privacy_settings' );
		$post_data = Forminator_Core::sanitize_array( $_POST );

		/**
		 * CUSTOM FORMS
		 */
		// Account Erasure Requests
		if ( isset( $post_data['erase_form_submissions'] ) ) {
			$enable_erasure_request_erase_form_submissions = filter_var( $post_data['erase_form_submissions'], FILTER_VALIDATE_BOOLEAN );
			update_option( 'forminator_enable_erasure_request_erase_form_submissions', $enable_erasure_request_erase_form_submissions );
		}
		// Account Erasure Requests.

		// Submissions Retention.
		$cform_retain_forever = filter_var( $post_data['form_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_submission_forever', $cform_retain_forever );
		if ( $cform_retain_forever ) {
			$post_data['form_retain_submission_number'] = 0;
		}
		if ( isset( $post_data['form_retain_submission_number'] ) ) {
			$submissions_retention_number = intval( $post_data['form_retain_submission_number'] );
			if ( $submissions_retention_number < 0 ) {
				$submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_submissions_interval_number', $submissions_retention_number );
		}
		update_option( 'forminator_retain_submissions_interval_unit', $post_data['form_retain_submission_unit'] );
		// Submissions Retention

		// IP Retention
		$cform_retain_ip_forever = filter_var( $post_data['form_retain_ip_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_ip_forever', $cform_retain_ip_forever );
		if ( $cform_retain_ip_forever ) {
			$post_data['form_retain_ip_number'] = 0;
		}
		if ( isset( $post_data['form_retain_ip_number'] ) ) {
			$cform_ip_retention_number = intval( $post_data['form_retain_ip_number'] );
			if ( $cform_ip_retention_number < 0 ) {
				$cform_ip_retention_number = 0;
			}
			update_option( 'forminator_retain_ip_interval_number', $cform_ip_retention_number );
		}
		update_option( 'forminator_retain_ip_interval_unit', $post_data['form_retain_ip_unit'] );
		// IP Retention.

		/**
		 * POLLS
		 */
		// Submissions Retention
		$poll_retain_submissions_forever = filter_var( $post_data['poll_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'poll_retain_submission_forever', $poll_retain_submissions_forever );
		if ( $poll_retain_submissions_forever ) {
			$post_data['poll_retain_submission_number'] = 0;
		}
		// Polls
		if ( isset( $post_data['poll_retain_submission_number'] ) ) {
			$poll_submissions_retention_number = intval( $post_data['poll_retain_submission_number'] );
			if ( $poll_submissions_retention_number < 0 ) {
				$poll_submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_poll_submissions_interval_number', $poll_submissions_retention_number );
		}
		update_option( 'forminator_retain_poll_submissions_interval_unit', $post_data['poll_retain_submission_unit'] );
		// Submissions Retention

		// IP Retention
		$poll_retain_ip_forever = filter_var( $post_data['poll_retain_ip_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_poll_forever', $poll_retain_ip_forever );
		if ( $poll_retain_ip_forever ) {
			$post_data['poll_retain_ip_number'] = 0;
		}
		if ( isset( $post_data['poll_retain_ip_number'] ) ) {
			$votes_retention_number = intval( $post_data['poll_retain_ip_number'] );
			if ( $votes_retention_number < 0 ) {
				$votes_retention_number = 0;
			}
			update_option( 'forminator_retain_votes_interval_number', $votes_retention_number );
		}
		update_option( 'forminator_retain_votes_interval_unit', $post_data['poll_retain_ip_unit'] );
		// IP Retention

		/**
		 * QUIZ
		 */
		// Submissions Retention
		$quiz_retain_submissions_forever = filter_var( $post_data['quiz_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'quiz_retain_submission_forever', $quiz_retain_submissions_forever );
		if ( $quiz_retain_submissions_forever ) {
			$post_data['quiz_retain_submission_number'] = 0;
		}
		if ( isset( $post_data['quiz_retain_submission_number'] ) ) {
			$quiz_submissions_retention_number = intval( $post_data['quiz_retain_submission_number'] );
			if ( $quiz_submissions_retention_number < 0 ) {
				$quiz_submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_quiz_submissions_interval_number', $quiz_submissions_retention_number );
		}
		update_option( 'forminator_retain_quiz_submissions_interval_unit', $post_data['quiz_retain_submission_unit'] );
		// Submissions Retention

		wp_send_json_success();
	}

	/**
	 * AJAX Reset tracking data
	 */
	public function reset_tracking_data() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_reset_tracking_data' );

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( ! $id ) {
			wp_send_json_error( __( 'Required id parameter is not set.', 'forminator' ) );
		}

		Forminator_Admin_Module_Edit_Page::reset_module_views( $id );

		wp_send_json_success( __( 'Tracking Data has been reset successfully.', 'forminator' ) );
	}

	/**
	 * Execute Import Form
	 *
	 * @since 1.5
	 */
	public function save_import() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_error( __( 'Import Export Feature disabled.', 'forminator' ) );
		}
		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_save_import_', '_popup' ), '', $current_action );
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_import_' . $slug );

		$importable = json_decode( html_entity_decode( wp_unslash( $_POST[ 'importable'] ) ), true );

		$import_data = Forminator_Core::sanitize_array( $importable );

		// hook custom data here.
		$import_data = apply_filters( 'forminator_' . $slug . '_import_data', $import_data );

		try {
			if ( empty( $_POST['importable'] ) ) {
				throw new Exception( __( 'Import text can not be empty.', 'forminator' ) );
			}

			if ( empty( $import_data ) || ! is_array( $import_data ) ) {
				throw new Exception( __( 'Oops, looks like we found an issue. Import text can not include whitespace or special characters.', 'forminator' ) );
			}

			if ( ! isset( $import_data['type'] ) || $slug !== $import_data['type'] ) {
				throw new Exception( __( 'Oops, looks like we found an issue. Import text can not include whitespace or special characters.', 'forminator' ) );
			}

			$class = 'Forminator_' . forminator_get_prefix( $slug, '', true ) . '_Model';
			$model = $class::create_from_import_data( $import_data );

			if ( is_wp_error( $model ) ) {
				throw new Exception( $model->get_error_message() );
			}

			if ( ! $model instanceof Forminator_Base_Form_Model ) {
				throw new Exception( __( 'Failed to import module, please make sure import text is valid, and try again.', 'forminator' ) );
			}

			$return_url = admin_url( 'admin.php?page=forminator-' . forminator_get_prefix( $slug, 'c' ) );

			wp_send_json_success(
				array(
					'id'  => $model->id,
					'url' => $return_url,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}


	/**
	 * Get instance of thirdparty importer class
	 *
	 * @since 1.5
	 */
	public function importers( $type ) {

		$class = '';

		switch ( $type ) {
			case 'cf7':
				if ( class_exists( 'Forminator_Admin_Import_CF7' ) ) {
					$class = new Forminator_Admin_Import_CF7();
				}
				break;
			case 'ninja':
				if ( class_exists( 'Forminator_Admin_Import_Ninja' ) ) {
					$class = new Forminator_Admin_Import_Ninja();
				}
				break;
			case 'gravity':
				if ( class_exists( 'Forminator_Admin_Import_Gravity' ) ) {
					return new Forminator_Admin_Import_Gravity();
				}
				break;
			default:
				break;
		}

		return $class;
	}


	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_cf7() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_cf7' );

		$html = forminator_template( 'custom-form/popup/import-cf7' );

		wp_send_json_success( $html );
	}


	/**
	 * Execute Contact Form 7 Import Form
	 *
	 * @since 1.5
	 */
	public function save_import_form_cf7() {
		global $wpdb, $wpcf7_shortcode_manager;

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			wp_send_json_error( __( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_cf7' );

		$post_data  = $this->get_post_data();
		$importable = ( isset( $post_data['cf7_forms'] ) ? $post_data['cf7_forms'] : '' );
		$importer   = $this->importers( 'cf7' );
		if ( ! empty( $importer ) ) :
			if ( ! empty( $importable ) ) {
				if ( 'specific' === $importable ) {
					$forms = isset( $post_data['cf7-form-id'] ) ? $post_data['cf7-form-id'] : array();
				} else {
					$forms = forminator_list_thirdparty_contact_forms( 'cf7' );
				}
				if ( ! empty( $forms ) ) {
					foreach ( $forms as $key => $value ) {
						$values   = 'specific' === $importable ? $value : $value->ID;
						$imported = $importer->import_form( $values, $post_data );

						if ( 'fail' === $imported['type'] ) {

							$error = $imported['message'];
						}
					}
					if ( ! empty( $error ) ) {
						wp_send_json_error( $error );
					}

					wp_send_json_success( $imported );
				}
			} else {
				wp_send_json_error( __( 'Can\'t find form to import', 'forminator' ) );
			}
		endif;

		wp_send_json_error( __( 'Could not import the forms. Check if the selected form plugin is active', 'forminator' ) );

	}


	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_ninja() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_ninjaforms' );

		$html = forminator_template( 'custom-form/popup/import-ninjaforms' );

		wp_send_json_success( $html );
	}

	/**
	 * Execute Ninjaforms Import Form Save
	 *
	 * @since 1.5
	 */
	public function save_import_form_ninja() {

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			wp_send_json_error( __( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_ninja' );

		$importable = Forminator_Core::sanitize_text_field( 'ninjaforms' );
		$importer   = ( ! empty( $this->importers( 'ninja' ) ) ? $this->importers( 'ninja' ) : '' );

		if ( ! empty( $importer ) ) :
			if ( 'all' !== $importable && '' !== $importable ) {

				$importable = absint( $importable );
				$imported   = $importer->import_form( $importable );

				if ( 'fail' === $imported['type'] ) {
					wp_send_json_error( $imported['message'] );
				}

				wp_send_json_success( $imported );

			} elseif ( '' !== $importable ) {
				$forms = forminator_list_thirdparty_contact_forms( 'ninjaforms' );

				foreach ( $forms as $key => $value ) {
					$imported = $importer->import_form( $value->get_id() );

					if ( 'fail' === $imported['type'] ) {
						$error = $imported['message'];
					}
				}

				if ( ! empty( $error ) ) {
					wp_send_json_error( $error );
				}

				wp_send_json_success( $imported );
			}
		endif;

		wp_send_json_error( __( 'Could not import the forms. Check if the selected form plugin is active', 'forminator' ) );
	}

	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_gravity() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_gravityforms' );

		$html = forminator_template( 'custom-form/popup/import-gravityforms' );

		wp_send_json_success( $html );
	}

	/**
	 * Execute Ninjaforms Import Form Save
	 *
	 * @since 1.5
	 */
	public function save_import_form_gravity() {

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			wp_send_json_error( __( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_gravity' );

		$importable = Forminator_Core::sanitize_text_field( 'gravityforms' );
		$importer   = ( ! empty( $this->importers( 'gravity' ) ) ? $this->importers( 'gravity' ) : '' );

		if ( ! empty( $importer ) ) :
			if ( 'all' !== $importable && '' !== $importable ) {

				$importable = absint( $importable );
				$imported   = $importer->import_form( $importable );

				if ( 'fail' === $imported['type'] ) {
					wp_send_json_error( $imported['message'] );
				}

				wp_send_json_success( $imported );

			} elseif ( '' !== $importable ) {
				$forms = forminator_list_thirdparty_contact_forms( 'gravityforms' );

				foreach ( $forms as $key => $value ) {
					$imported = $importer->import_form( $value['id'] );

					if ( 'fail' === $imported['type'] ) {
						$error = $imported['message'];
					}
				}

				if ( ! empty( $error ) ) {
					wp_send_json_error( $error );
				}

				wp_send_json_success( $imported );
			}
		endif;

	}

	/**
	 * Load Export Module Popup
	 *
	 * @since 1.5
	 */
	public function load_export() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_success( '' );
		}
		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_export_', '_popup' ), '', $current_action );
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_export_' . $slug );

		$html = forminator_template( 'common/popup/export', array( 'slug' => $slug ) );

		wp_send_json_success( $html );
	}

	/**
	 * Load Import Popup
	 *
	 * @since 1.5
	 */
	public function load_import() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_success( '' );
		}

		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_import_', '_popup' ), '', $current_action );
		// Validate nonce
		forminator_validate_ajax( 'forminator_popup_import_' . $slug );

		$html = forminator_template( 'common/popup/import', array( 'slug' => $slug ) );

		wp_send_json_success( $html );
	}

	/**
	 * Save pagination data
	 *
	 * @since 1.6
	 */
	public function save_pagination() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_popup_pagination' );

		$pagination         = filter_input( INPUT_POST, 'pagination_entries', FILTER_VALIDATE_INT );
		$pagination_listing = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 1 > $pagination || 1 > $pagination_listing ) {
			wp_send_json_error( __( 'Limit per page can not be less than one.', 'forminator' ) );
		}

		update_option( 'forminator_pagination_entries', $pagination );
		update_option( 'forminator_pagination_listings', $pagination_listing );
		wp_send_json_success();

	}

	/**
	 * Save accessibility_settings
	 *
	 * @since 1.6.1
	 */
	public function save_accessibility_settings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_accessibility_settings' );

		$enable_accessibility = filter_input( INPUT_POST, 'enable_accessibility', FILTER_VALIDATE_BOOLEAN );

		update_option( 'forminator_enable_accessibility', $enable_accessibility );
		wp_send_json_success();
	}

	/**
	 * Save dashboard
	 *
	 * @since 1.6.3
	 */
	public function save_dashboard_settings() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_save_dashboard_settings' );

		$dashboard_settings = forminator_get_dashboard_settings();
		$widgets            = array( 'forms', 'polls', 'quizzes' );

		$num_recents = filter_input( INPUT_POST, 'num_recent', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		$num_recents = is_array( $num_recents ) ? $num_recents : array();
		$publisheds  = filter_input( INPUT_POST, 'published', FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_ARRAY );
		$publisheds  = is_array( $publisheds ) ? $publisheds : array();
		$drafts      = filter_input( INPUT_POST, 'draft', FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_ARRAY );
		$drafts      = is_array( $drafts ) ? $drafts : array();

		// value based settings
		foreach ( $num_recents as $widget => $value ) {
			if ( ! isset( $dashboard_settings[ $widget ] ) ) {
				$dashboard_settings[ $widget ] = array();
			}
			// at least 0.
			if ( $value >= 0 ) {
				$dashboard_settings[ $widget ]['num_recent'] = $value;
			}
		}

		// bool based settings aka checkboxes.
		foreach ( $widgets as $widget ) {
			if ( ! isset( $dashboard_settings[ $widget ] ) ) {
				$dashboard_settings[ $widget ] = array();
			}

			// default enabled, handle when not exist = false.
			if ( ! isset( $publisheds[ $widget ] ) ) {
				$dashboard_settings[ $widget ]['published'] = false;
			}
			if ( ! isset( $drafts[ $widget ] ) ) {
				$dashboard_settings[ $widget ]['draft'] = false;
			}
		}

		update_option( 'forminator_dashboard_settings', $dashboard_settings );
		$sender_email = filter_input( INPUT_POST, 'sender_email', FILTER_SANITIZE_EMAIL );
		if ( $sender_email ) {
			update_option( 'forminator_sender_email_address', $sender_email );
		}
		$sender_name = Forminator_Core::sanitize_text_field( 'sender_name' );
		if ( $sender_name ) {
			update_option( 'forminator_sender_name', $sender_name );
		}

		$pagination         = filter_input( INPUT_POST, 'pagination_entries', FILTER_VALIDATE_INT );
		$pagination_listing = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 1 > $pagination || 1 > $pagination_listing ) {
			wp_send_json_error( __( 'Limit per page can not be less than one.', 'forminator' ) );
		}

		update_option( 'forminator_pagination_entries', $pagination );
		update_option( 'forminator_pagination_listings', $pagination_listing );

		$editor_settings = Forminator_Core::sanitize_text_field( 'editor_settings', false );
		update_option( 'forminator_editor_settings', $editor_settings );

		wp_send_json_success();
	}

	/**
	 * Disconnect stripe
	 *
	 * @since 1.7
	 */
	public function stripe_disconnect() {
		// Validate nonce
		forminator_validate_ajax( 'forminatorSettingsRequest' );

		if ( class_exists( 'Forminator_Gateway_Stripe' ) ) {
			Forminator_Gateway_Stripe::store_settings( array() );
		}
		$data['notification'] = array(
			'type'     => 'success',
			'text'     => __( 'Stripe account disconnected successfully.', 'forminator' ),
			'duration' => '4000',
		);
		$file                 = forminator_plugin_dir() . 'admin/views/settings/payments/section-stripe.php';

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $file;
		$data['html'] = ob_get_clean();

		wp_send_json_success( $data );
	}

	/**
	 * Disconnect PayPal
	 *
	 * @since 1.7
	 */
	public function paypal_disconnect() {
		// Validate nonce.
		forminator_validate_ajax( 'forminatorSettingsRequest' );

		if ( class_exists( 'Forminator_PayPal_Express' ) ) {
			Forminator_PayPal_Express::store_settings( array() );
		}
		$data['notification'] = array(
			'type'     => 'success',
			'text'     => __( 'PayPal account disconnected successfully.', 'forminator' ),
			'duration' => '4000',
		);
		$file                 = forminator_plugin_dir() . 'admin/views/settings/payments/section-paypal.php';

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $file;
		$data['html'] = ob_get_clean();

		wp_send_json_success( $data );
	}

	/**
	 * Handle stripe settings
	 *
	 * @since 1.7
	 */
	public function stripe_update_page() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_stripe_settings_modal' );

		$file = forminator_plugin_dir() . 'admin/views/settings/payments/section-stripe.php';

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $file;
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 * Handle PayPal settings
	 *
	 * @since 1.7
	 */
	public function paypal_update_page() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_paypal_settings_modal' );

		$file = forminator_plugin_dir() . 'admin/views/settings/payments/section-paypal.php';

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $file;
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 * Handle stripe settings
	 *
	 * @since 1.7
	 */
	public function stripe_settings_modal() {
		if ( ! class_exists( 'Forminator_Gateway_Stripe' ) ) {
			return false;
		}

		// Validate nonce
		forminator_validate_ajax( 'forminator_stripe_settings_modal' );

		$data = array();

		$post_data          = Forminator_Core::sanitize_array( $_POST );
		$is_connect_request = isset( $post_data['connect'] ) ? $post_data['connect'] : false;
		$template_vars      = array();
		try {
			$stripe = new Forminator_Gateway_Stripe();

			$test_key         = isset( $post_data['test_key'] ) ? $post_data['test_key'] : $stripe->get_test_key();
			$test_secret      = isset( $post_data['test_secret'] ) ? $post_data['test_secret'] : $stripe->get_test_secret();
			$live_key         = isset( $post_data['live_key'] ) ? $post_data['live_key'] : $stripe->get_live_key();
			$live_secret      = isset( $post_data['live_secret'] ) ? $post_data['live_secret'] : $stripe->get_live_secret();
			$default_currency = $stripe->get_default_currency();

			$template_vars['test_key']    = $test_key;
			$template_vars['test_secret'] = $test_secret;
			$template_vars['live_key']    = $live_key;
			$template_vars['live_secret'] = $live_secret;

			if ( ! empty( $is_connect_request ) ) {
				if ( empty( $test_key ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_TEST_KEY_EXCEPTION
					);
				}
				if ( empty( $test_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_TEST_SECRET_EXCEPTION
					);
				}

				Forminator_Gateway_Stripe::validate_keys( $test_key, $test_secret, Forminator_Gateway_Stripe::INVALID_TEST_SECRET_EXCEPTION );

				if ( empty( $live_key ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_LIVE_KEY_EXCEPTION
					);
				}
				if ( empty( $live_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_LIVE_SECRET_EXCEPTION
					);
				}

				Forminator_Gateway_Stripe::validate_keys( $live_key, $live_secret, Forminator_Gateway_Stripe::INVALID_LIVE_SECRET_EXCEPTION );

				Forminator_Gateway_Stripe::store_settings(
					array(
						'test_key'         => $test_key,
						'test_secret'      => $test_secret,
						'live_key'         => $live_key,
						'live_secret'      => $live_secret,
						'default_currency' => $default_currency,
					)
				);

				$data['notification'] = array(
					'type'     => 'success',
					'text'     => __( 'Stripe account connected successfully. You can now add the Stripe field to your forms and start collecting payments.', 'forminator' ),
					'duration' => '4000',
				);

			}
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage(), $e->getTrace() );
			$template_vars['error_message'] = $e->getMessage();

			if ( Forminator_Gateway_Stripe::EMPTY_TEST_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['test_key_error'] = __( 'Please input test publishable key' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_TEST_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['test_secret_error'] = __( 'Please input test secret key' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_LIVE_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['live_key_error'] = __( 'Please input live publishable key' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = __( 'Please input live secret key' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_TEST_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['test_secret_error'] = __( 'You\'ve entered an invalid test secret key' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = __( 'You\'ve entered an invalid live secret key' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_TEST_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['test_key_error'] = __( 'You\'ve entered an invalid test publishable key' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_LIVE_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['live_key_error'] = __( 'You\'ve entered an invalid live publishable key' );
			}
		}

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include forminator_plugin_dir() . 'admin/views/settings/payments/stripe.php';
		$html = ob_get_clean();

		$data['html'] = $html;

		$data['buttons'] = array();

		$data['buttons']['connect']['markup'] = '<div class="sui-actions-right">' .
		                                        '<button class="sui-button forminator-stripe-connect" type="button" data-nonce="' . wp_create_nonce( 'forminator_stripe_settings_modal' ) . '">' .
		                                        '<span class="sui-loading-text">' . esc_html__( 'Connect', 'forminator' ) . '</span>' .
		                                        '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' .
		                                        '</button>' .
		                                        '</div>';

		wp_send_json_success( $data );
	}

	/**
	 * Handle PayPal settings
	 *
	 * @since 1.7.1
	 */
	public function paypal_settings_modal() {
		// Validate nonce
		forminator_validate_ajax( 'forminator_paypal_settings_modal' );

		$data = array();

		$post_data          = Forminator_Core::sanitize_array( $_POST );
		$is_connect_request = isset( $post_data['connect'] ) ? $post_data['connect'] : false;
		$template_vars      = array();

		try {
			$paypal = new Forminator_PayPal_Express();

			$sandbox_id       = isset( $post_data['sandbox_id'] ) ? $post_data['sandbox_id'] : $paypal->get_sandbox_id();
			$sandbox_secret   = isset( $post_data['sandbox_secret'] ) ? $post_data['sandbox_secret'] : $paypal->get_sandbox_secret();
			$live_id          = isset( $post_data['live_id'] ) ? $post_data['live_id'] : $paypal->get_live_id();
			$live_secret      = isset( $post_data['live_secret'] ) ? $post_data['live_secret'] : $paypal->get_live_secret();
			$default_currency = $paypal->get_default_currency();

			$template_vars['sandbox_id']     = $sandbox_id;
			$template_vars['sandbox_secret'] = $sandbox_secret;
			$template_vars['live_id']        = $live_id;
			$template_vars['live_secret']    = $live_secret;

			if ( ! empty( $is_connect_request ) ) {
				if ( empty( $sandbox_id ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_SANDBOX_ID_EXCEPTION
					);
				}
				if ( empty( $sandbox_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_SANDBOX_SECRET_EXCEPTION
					);
				}

				$paypal->validate_id( 'sandbox', $sandbox_id, $sandbox_secret, Forminator_PayPal_Express::INVALID_SANDBOX_SECRET_EXCEPTION );

				if ( empty( $live_id ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_LIVE_ID_EXCEPTION
					);
				}
				if ( empty( $live_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_LIVE_SECRET_EXCEPTION
					);
				}

				$paypal->validate_id( 'live', $live_id, $live_secret, Forminator_PayPal_Express::INVALID_LIVE_SECRET_EXCEPTION );

				Forminator_PayPal_Express::store_settings(
					array(
						'sandbox_id'     => $sandbox_id,
						'sandbox_secret' => $sandbox_secret,
						'live_id'        => $live_id,
						'live_secret'    => $live_secret,
						'currency'       => $default_currency,
					)
				);

				$data['notification'] = array(
					'type'     => 'success',
					'text'     => __( 'PayPal account connected successfully. You can now add the PayPal field to your forms and start collecting payments.', 'forminator' ),
					'duration' => '4000',
				);

			}
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage(), $e->getTrace() );
			$template_vars['error_message'] = $e->getMessage();

			if ( Forminator_PayPal_Express::EMPTY_SANDBOX_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_id_error'] = __( 'Please input sandbox client id' );
			}
			if ( Forminator_PayPal_Express::EMPTY_SANDBOX_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_secret_error'] = __( 'Please input sandbox secret key' );
			}
			if ( Forminator_PayPal_Express::EMPTY_LIVE_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['live_id_error'] = __( 'Please input live client id' );
			}
			if ( Forminator_PayPal_Express::EMPTY_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = __( 'Please input live secret key' );
			}
			if ( Forminator_PayPal_Express::INVALID_SANDBOX_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_secret_error'] = __( 'You\'ve entered an invalid sandbox secret key' );
			}
			if ( Forminator_PayPal_Express::INVALID_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = __( 'You\'ve entered an invalid live secret key' );
			}
			if ( Forminator_PayPal_Express::INVALID_SANDBOX_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_id_error'] = __( 'You\'ve entered an invalid sandbox client id' );
			}
			if ( Forminator_PayPal_Express::INVALID_LIVE_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['live_id_error'] = __( 'You\'ve entered an invalid live client id' );
			}
		}

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include forminator_plugin_dir() . 'admin/views/settings/payments/paypal.php';
		$html = ob_get_clean();

		$data['html'] = $html;

		$data['buttons'] = array();

		$data['buttons']['connect']['markup'] = '<div class="sui-actions-right">' .
		                                        '<button class="sui-button forminator-paypal-connect" type="button" data-nonce="' . wp_create_nonce( 'forminator_paypal_settings_modal' ) . '">' .
		                                        '<span class="sui-loading-text">' . esc_html__( 'Connect', 'forminator' ) . '</span>' .
		                                        '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' .
		                                        '</button>' .
		                                        '</div>';

		wp_send_json_success( $data );
	}

	/**
	 * Dismiss notice. Use dismiss_admin_notice method instead
	 *
	 * @since 1.9
	 */
	public function dismiss_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notification' );

		$notification_name = Forminator_Core::sanitize_text_field( 'prop' );
		$input_value       = Forminator_Core::sanitize_text_field( 'value' );

		if ( ! empty( $input_value ) ) {
			update_option( $notification_name, $input_value );
		} else {
			update_option( $notification_name, true );
		}

		$version_upgraded = get_option( 'forminator_version_upgraded', false );

		if ( $version_upgraded ) {
			update_option( 'forminator_version_upgraded', false );
		}

		wp_send_json_success();
	}

	/**
	 * Dismiss admin notice.
	 */
	public function dismiss_admin_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notice' );

		$slug      = Forminator_Core::sanitize_text_field( 'slug' );
		$user_id   = get_current_user_id();
		$dismissed = get_user_meta( $user_id, 'frmt_dismissed_messages', true );
		if ( ! $dismissed || ! is_array( $dismissed ) ) {
			$dismissed = array();
		}
		$dismissed[] = $slug;
		update_user_meta( $user_id, 'frmt_dismissed_messages', $dismissed );

		wp_send_json_success();
	}

	/**
	 * Dismiss notice
	 *
	 * @since 1.9
	 */
	public function later_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notification' );

		$notification_name = Forminator_Core::sanitize_text_field( 'prop' );
		$form_id           = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );

		update_post_meta( $form_id, $notification_name, true );

		wp_send_json_success();
	}

	/**
	 * Save general payments settings
	 *
	 * @since 1.7
	 */
	public function save_payments() {
		forminator_validate_ajax( 'forminator_save_payments_settings' );

		// stripe.
		$default_currency = filter_input( INPUT_POST, 'stripe-default-currency' );
		if ( $default_currency ) {

			try {
				$stripe = new Forminator_Gateway_Stripe();

				$test_key    = $stripe->get_test_key();
				$test_secret = $stripe->get_test_secret();
				$live_key    = $stripe->get_live_key();
				$live_secret = $stripe->get_live_secret();

				Forminator_Gateway_Stripe::store_settings(
					array(
						'test_key'         => $test_key,
						'test_secret'      => $test_secret,
						'live_key'         => $live_key,
						'live_secret'      => $live_secret,
						'default_currency' => $default_currency,
					)
				);

			} catch ( Forminator_Gateway_Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		// paypal.
		$pp_default_currency = Forminator_Core::sanitize_text_field( 'paypal-default-currency' );
		if ( $pp_default_currency ) {
			$default_currency = $pp_default_currency;

			try {
				$paypal = new Forminator_PayPal_Express();

				$sandbox_id     = $paypal->get_sandbox_id();
				$sandbox_secret = $paypal->get_sandbox_secret();
				$live_id        = $paypal->get_live_id();
				$live_secret    = $paypal->get_live_secret();

				Forminator_PayPal_Express::store_settings(
					array(
						'sandbox_id'     => $sandbox_id,
						'sandbox_secret' => $sandbox_secret,
						'live_id'        => $live_id,
						'live_secret'    => $live_secret,
						'currency'       => $default_currency,
					)
				);

			} catch ( Forminator_Gateway_Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		wp_send_json_success();

	}

	/**
	 * Delete all poll submission
	 *
	 * @since 1.7.2
	 */
	public function delete_poll_submissions() {
		forminator_validate_ajax( 'forminatorPollEntries' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( $form_id ) {

			Forminator_Form_Entry_Model::delete_by_form( $form_id );

			$file = forminator_plugin_dir() . 'admin/views/common/entries/content-none.php';

			ob_start();
			/** @noinspection PhpIncludeInspection */
			include $file;
			$html = ob_get_clean();

			$data['html']         = $html;
			$data['notification'] = array(
				'type'     => 'success',
				'text'     => __( 'All the submissions deleted successfully.', 'forminator' ),
				'duration' => '4000',
			);
			wp_send_json_success( $data );
		} else {
			$data['notification'] = array(
				'type'     => 'error',
				'text'     => __( 'Submission delete failed.', 'forminator' ),
				'duration' => '4000',
			);
			wp_send_json_error( $data );
		}
	}

	/**
	 * Dismiss Subscriptions Prelaunch notice
	 *
	 * @since 1.14.10
	 */
	public function dismiss_prelaunch_subscriptions_notice() {
		forminator_validate_ajax( 'forminator_dismiss_prelaunch_subscriptions' );

		$slug = Forminator_Core::sanitize_text_field( 'prop' );

		$dismissed_messages          = get_user_meta( get_current_user_id(), 'frmt_dismissed_messages', true );
		$dismissed_messages          = empty( $dismissed_messages ) ? array() : $dismissed_messages;
		$dismissed_messages[ $slug ] = true;

		update_user_meta( get_current_user_id(), 'frmt_dismissed_messages', $dismissed_messages );
	}

	/**
	 * Module search for all types
	 *
	 * @since 1.14.12
	 */
	public function module_search() {
		forminator_validate_ajax( 'forminator-nonce-search-module' );
		$html    = '';
		$keyword = Forminator_Core::sanitize_text_field( 'search_keyword' );
		$modules = Forminator_Admin_Module_Edit_Page::get_searched_modules( $keyword );

		ob_start();
		Forminator_Admin_Module_Edit_Page::show_modules(
			$modules,
			Forminator_Core::sanitize_text_field( 'module_slug' ),
			Forminator_Core::sanitize_text_field( 'preview_dialog' ),
			Forminator_Core::sanitize_text_field( 'preview_title' ),
			Forminator_Core::sanitize_text_field( 'export_dialog' ),
			Forminator_Core::sanitize_text_field( 'post_type' ),
			Forminator_Core::sanitize_text_field( 'soon' ),
			Forminator_Core::sanitize_text_field( 'sql_month_start_date' ),
			Forminator_Core::sanitize_text_field( 'wizard_page' ),
			$keyword
		);
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 * Addons page details
	 */
	public function addons_page_details() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_addons_page_details' );

		$pid = filter_input( INPUT_POST, 'id' );
		if ( ! $pid ) {
			wp_send_json_error();
		}

		$file = forminator_plugin_dir() . 'admin/views/addons/addon-details.php';

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $file;
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	public function addons_page_actions() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_addons_actions' );

		$action = Forminator_Core::sanitize_text_field( 'action' );
		if ( ! $action ) {
			wp_send_json_error(
				array( 'message' => __( 'Required field missing', 'forminator' ) )
			);
		}

		$action = str_replace( 'forminator_', '', $action );

		Forminator_Admin_Addons_page::get_instance()->addons_action_ajax( $action );

		// When the addons_action_ajax function did not send a response assume error.
		wp_send_json_error(
			array( 'message' => __( 'Unexpected action, we could not handle it.', 'forminator' ) )
		);

	}
}
