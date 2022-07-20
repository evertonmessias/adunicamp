<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Module
 *
 * @since 1.0
 */
abstract class Forminator_Admin_Module {

	/**
	 * @var array
	 */
	public $pages = array();

	/**
	 * @var string
	 */
	public $page = '';

	/**
	 * @var string
	 */
	public $page_edit = '';

	/**
	 * @var string
	 */
	public $page_entries = '';

	/**
	 * @var string
	 */
	public $dir = '';

	/**
	 * Forminator_Admin_Module constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->init();

		$this->includes();

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_head', array( $this, 'hide_menu_pages' ) );

		add_action( 'wp_loaded', array( $this, 'create_module' ) );

		// admin-menu-editor compat.
		add_action( 'admin_menu_editor-menu_replaced', array( $this, 'hide_menu_pages' ) );

		add_filter( 'forminator_data', array( $this, 'add_js_defaults' ) );
		add_filter( 'forminator_l10n', array( $this, 'add_l10n_strings' ) );
		add_filter( 'submenu_file', array( $this, 'admin_submenu_file' ), 10, 2 );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init() {
		// Call init instead of __construct in modules.
	}

	/**
	 * Attach admin pages
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {}

	public function create_module() {}

	/**
	 * Hide pages from menu
	 *
	 * @since 1.0
	 */
	public function hide_menu_pages() {
		remove_submenu_page( 'forminator', $this->page_edit );
		remove_submenu_page( 'forminator', $this->page_entries );
	}

	/**
	 * Used to include files
	 *
	 * @since 1.0
	 */
	public function includes() {
		include_once $this->dir . '/admin-page-new.php';
		include_once $this->dir . '/admin-page-view.php';
		include_once $this->dir . '/admin-page-entries.php';
		include_once $this->dir . '/admin-renderer-entries.php';
	}

	/**
	 * Inject module options to JS
	 *
	 * @since 1.0
	 * @param $data
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		return $data;
	}

	/**
	 * Inject l10n strings to JS
	 *
	 * @param $strings
	 * @since 1.0
	 * @return mixed
	 */
	public function add_l10n_strings( $strings ) {
		return $strings;
	}

	/**
	 * Is the admin page being viewed in edit mode
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public static function is_edit() {
		return filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ) || filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	}

	/**
	 * Is the module admin dashboard page
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_admin_home() {
		global $plugin_page;

		return $this->page === $plugin_page;
	}

	/**
	 * Is the module admin new/edit page
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_admin_wizard() {
		global $plugin_page;

		// $plugin_page may not be set if we call the function too early, retrieve the page slug from GET.
		$page = filter_input( INPUT_GET, 'page' );
		if ( empty( $plugin_page ) && $page ) {
			$plugin_page = sanitize_text_field( $page );
		}

		return $this->page_edit === $plugin_page;
	}

	/**
	 * Highlight parent page in sidebar
	 *
	 * @deprecated 1.1 No longer used because this function override prohibited WordPress global of $plugin_page
	 * @since      1.0
	 *
	 * @param $file
	 *
	 * @return mixed
	 */
	public function highlight_admin_parent( $file ) {
		_deprecated_function( __METHOD__, '1.1', null );
		return $file;
	}

	/**
	 * Prepare settings
	 *
	 * @param array $original_settings Sent settings.
	 * @return array
	 */
	protected static function validate_settings( $original_settings ) {
		// Sanitize settings.
		$settings = forminator_sanitize_array_field( $original_settings );

		// Sanitize custom css.
		if ( isset( $original_settings['custom_css'] ) ) {
			$settings['custom_css'] = sanitize_textarea_field( $original_settings['custom_css'] );
		}

		// Sanitize admin email message.
		if ( isset( $original_settings['admin-email-editor'] ) ) {
			$settings['admin-email-editor'] = wp_kses_post( $original_settings['admin-email-editor'] );
		}

		// Sanitize quiz description.
		if ( isset( $original_settings['quiz_description'] ) ) {
			$settings['quiz_description'] = wp_kses_post( $original_settings['quiz_description'] );
		}

		if ( isset( $original_settings['social-share-message'] ) ) {
			$settings['social-share-message'] = forminator_sanitize_textarea( $original_settings['social-share-message'] );
		}

		if ( isset( $original_settings['msg_count'] ) ) {
			// Backup, we allow html here.
			$settings['msg_count'] = wp_kses_post( $original_settings['msg_count'] );
		}

		$settings = apply_filters( 'forminator_builder_data_settings_before_saving', $settings, $original_settings );

		return $settings;
	}

	/**
	 * Highlight submenu on admin page
	 *
	 * @since 1.1
	 *
	 * @param $submenu_file
	 * @param $parent_file
	 *
	 * @return string
	 */
	public function admin_submenu_file( $submenu_file, $parent_file ) {
		global $plugin_page;

		if ( 'forminator' !== $parent_file ) {
			return $submenu_file;
		}

		if ( $this->page_edit === $plugin_page || $this->page_entries === $plugin_page ) {
			$submenu_file = $this->page;
		}

		return $submenu_file;
	}
}
