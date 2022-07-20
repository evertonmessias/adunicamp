<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Forminator_Custom_Forms extends Forminator_Module {

	/**
	 * Module instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Store template objects
	 *
	 * @var array
	 */
	public $templates = array();

	/**
	 * Return the plugin instance
	 *
	 * @since 1.0
	 * @return Forminator_Module
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 * @since 1.0.6 Include General Data Protecion
	 */
	public function init() {
		self::$instance = $this;

		$loader = new Forminator_Loader();

		$templates = $loader->load_files( 'library/modules/custom-forms/form-templates' );

		/**
		 * Filters the custom form templates list
		 */
		$this->templates = apply_filters( 'forminator_custom_form_templates', $templates );

		if ( ! class_exists( 'Forminator_General_Data_Protection' ) ) {
			include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-general-data-protection.php';
		}
		include_once dirname( __FILE__ ) . '/protection/general-data-protection.php';
		if ( class_exists( 'Forminator_CForm_General_Data_Protection' ) ) {
			new Forminator_CForm_General_Data_Protection();
		}
		if ( ! class_exists( 'Forminator_CForm_User_Data' ) ) {
			include_once __DIR__ . '/user/class-forminator-cform-user-data.php';
			new Forminator_CForm_User_Data();
		}

		add_action( 'forminator_update_version', array( __CLASS__, 'migrate_leads_forms' ), 10, 2 );
	}

	/**
	 * Load module Admin part
	 *
	 * @since 1.0
	 */
	public function load_admin() {
		if ( is_admin() ) {
			include_once dirname( __FILE__ ) . '/admin/admin-loader.php';

			new Forminator_Custom_Form_Admin();
		}
	}

	/**
	 * Load front part
	 *
	 * @since 1.0
	 */
	public function load_front() {

		include_once dirname( __FILE__ ) . '/front/front-action.php';
		include_once dirname( __FILE__ ) . '/front/front-render.php';
		include_once dirname( __FILE__ ) . '/front/front-user-login.php';
		include_once dirname( __FILE__ ) . '/front/front-user-registration.php';
		include_once dirname( __FILE__ ) . '/front/front-mail.php';
		include_once dirname( __FILE__ ) . '/front/front-assets.php';

		Forminator_CForm_Front_Action::get_instance();
		new Forminator_CForm_Front();

		add_action( 'wp_ajax_forminator_load_form', array( 'Forminator_CForm_Front', 'ajax_load_module' ) );
		add_action( 'wp_ajax_nopriv_forminator_load_form', array( 'Forminator_CForm_Front', 'ajax_load_module' ) );
	}

	/**
	 * Register CPT
	 *
	 * @since 1.0
	 */
	public function register_cpt() {
		$labels = array(
			'name'          => $this->get_option( 'name' ),
			'singular_name' => $this->get_option( 'singular_name' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => $this->get_option( 'description' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array(),
		);

		register_post_type( 'forminator_forms', $args );

		// Register custom post_status.
		add_action( 'init', array( __CLASS__, 'add_custom_post_status' ) );
	}

	/**
	 * Add custom post status for Leads forms
	 */
	public static function add_custom_post_status() {
		register_post_status(
			'leads',
			array(
				'public'                    => false,
				'internal'                  => true,
				'post_type'                 => array( 'forminator_forms' ),
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'exclude_from_search'       => true,
			)
		);
	}

	/**
	 * Migrate leads forms
	 *
	 * @param string $new_version New plugin version.
	 * @param string $old_version Old plugin version.
	 * @return null
	 */
	public static function migrate_leads_forms( $new_version, $old_version ) {
		if ( version_compare( $old_version, '1.14.7', '>' ) ) {
			return;
		}
		Forminator_Migration::migrate_leads_forms();
	}

	/**
	 * Module options
	 *
	 * @since 1.0
	 * @return array
	 */
	public function options() {
		return array(
			'name'          => __( 'Custom Forms', 'forminator' ),
			'singular_name' => __( 'Custom Form', 'forminator' ),
			'description'   => __( 'Custom forms, conditional forms, etc. Choose from our library of forms or create a new one from scratch.', 'forminator' ),
			'button_label'  => __( 'New Custom Form', 'forminator' ),
			'icon'          => '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="19" viewBox="0 0 13 19" preserveAspectRatio="none" class="wpmudev-icon wpmudev-i_cforms"><path fill-rule="evenodd" d="M12.916 17.035v-.228.228c0 .305-.105.563-.316.774-.21.21-.47.315-.774.315H1.174c-.305 0-.563-.105-.774-.316-.21-.212-.316-.47-.316-.775V2.287v.21-.227c0-.305.105-.566.316-.783.21-.216.47-.325.774-.325H4.25V.46c0-.095.032-.174.097-.238.064-.065.143-.097.237-.097h3.85c.093 0 .172.032.237.097.065.064.098.143.098.237v.702h3.058c.305 0 .563.11.774.325.21.217.316.478.316.783v14.765zm-2.074-.984V3.237H9.805v.668-.08.08c0 .106-.035.194-.106.264-.07.07-.16.105-.264.105H3.564c-.105 0-.193-.035-.263-.105-.07-.07-.105-.158-.105-.264v-.668H2.14V16.05h8.702zm-6.61-8.77v.544c0 .06-.023.114-.07.167-.047.054-.105.08-.176.08H3.44c-.07 0-.127-.026-.174-.08-.047-.052-.07-.107-.07-.166V7.28c0-.07.023-.13.07-.176.047-.047.105-.07.175-.07h.546c.07 0 .13.023.176.07.047.046.07.105.07.175zm5.573 0v.527c0 .07-.024.13-.07.184-.048.054-.106.08-.176.08H5.532c-.07 0-.13-.026-.176-.08-.046-.052-.07-.113-.07-.183V7.28c0-.07.024-.133.07-.185.047-.053.106-.08.176-.08H9.56c.07 0 .127.027.174.08.047.052.07.114.07.184zM4.232 9.353v.544c0 .07-.023.13-.07.176-.047.047-.105.07-.176.07H3.44c-.057 0-.113-.023-.166-.07-.052-.047-.08-.105-.08-.176v-.544c0-.07.028-.13.08-.176.053-.047.11-.07.167-.07h.546c.07 0 .13.023.176.07.047.047.07.105.07.176zm5.573 0v.544c0 .07-.024.13-.07.176-.048.047-.106.07-.176.07H5.532c-.07 0-.13-.023-.176-.07-.046-.047-.07-.105-.07-.176v-.544c0-.07.024-.13.07-.176.047-.047.106-.07.176-.07H9.56c.07 0 .127.023.174.07.047.047.07.105.07.176zm-5.573 2.074v.545c0 .07-.023.13-.07.175-.047.047-.105.07-.176.07H3.44c-.057 0-.113-.023-.166-.07-.052-.046-.08-.105-.08-.175v-.545c0-.06.028-.115.08-.167.053-.052.11-.078.167-.078h.546c.07 0 .13.026.176.08.047.05.07.107.07.166zm5.573 0v.545c0 .07-.024.13-.07.175-.048.047-.106.07-.176.07H5.532c-.07 0-.13-.023-.176-.07-.046-.046-.07-.105-.07-.175v-.545c0-.06.024-.115.07-.167.047-.052.106-.078.176-.078H9.56c.07 0 .127.026.174.08.047.05.07.107.07.166z"/></svg>',
		);
	}

	/**
	 * Order templates by priorty
	 *
	 * @since 1.0
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	public function order_templates( $b, $a ) {
		if ( isset( $a->options['priortiy'] ) && isset( $b->options['priortiy'] ) ) {
			if ( $a->options['priortiy'] === $b->options['priortiy'] ) {
				return 0;
			}

			return ( $a->options['priortiy'] < $b->options['priortiy'] ) ? +1 : -1;
		}
	}

	/**
	 * Get templates
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_templates() {
		array_multisort(
			array_map(
				function ( $template ) {
					return $template->options['priortiy'];
				},
				$this->templates
			),
			SORT_ASC,
			$this->templates
		);

		return $this->templates;
	}
}
