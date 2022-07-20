<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Core
 *
 * @since 1.0
 */
class Forminator_Core {

	/**
	 * @var Forminator_Admin
	 */
	public $admin;

	/**
	 * Store modules objects
	 *
	 * @var array
	 */
	public $modules = array();

	/**
	 * Store forms objects
	 *
	 * @var array
	 */
	public $forms = array();

	/**
	 * Store fields objects
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Store PRO fields
	 *
	 * @var array
	 */
	public $pro_fields = array();

	/**
	 * Store field objects
	 *
	 * @var array
	 */
	private static $field_objects = array();

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @since 1.0
	 * @return Forminator
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Core constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Include all necessary files.
		$this->includes();

		// First check if upgrade of data is needed.
		Forminator_Upgrade::init();

		if ( is_admin() ) {
			// Initialize admin core.
			$this->admin = new Forminator_Admin();

			new Forminator_Shortcode_Generator();

			add_action( 'wp_head', array( $this, 'localize_pointers' ) );
		}

		// Get enabled modules.
		$modules       = new Forminator_Modules();
		$this->modules = $modules->get_modules();

		// Get enabled fields.
		$fields       = new Forminator_Fields();
		$this->fields = $fields->get_fields();

		$this->set_field_objects();

		/**
		 * Filter Pro fields for promotion PRO version
		 *
		 * @since 1.13
		 * @param array $pro_fields Array of PRO fields e.g. [ 'field_type' => 'test', 'name' => 'test, 'icon' => 'sui-icon-pencil' ].
		 */
		$this->pro_fields = apply_filters( 'forminator_pro_fields', array() );

		// HACK: Add settings and entries page at the end of the list.
		if ( is_admin() ) {
			$this->admin->add_entries_page();
			$this->admin->add_addons_page();
			if ( Forminator::is_addons_feature_enabled() ) {
				$this->admin->add_integrations_page();
			}
			$this->admin->add_settings_page();

			if ( ! FORMINATOR_PRO ) {
				$this->admin->add_upgrade_page();
			}
		}

		// Protection management.
		Forminator_Protection::get_instance();

		// Export management.
		Forminator_Export::get_instance();

		// Post meta box.
		add_action( 'init', array( &$this, 'post_field_meta_box' ) );
	}

	/**
	 * Set field objects
	 */
	private function set_field_objects() {
		if ( self::$field_objects ) {
			return;
		}
		foreach ( $this->fields as $field_object ) {
			self::$field_objects[ $field_object->slug ] = $field_object;
		}
	}

	/**
	 * Get field object by field type
	 *
	 * @param string $type Field type.
	 * @return object
	 */
	public static function get_field_object( $type ) {
		$object = isset( self::$field_objects[ $type ] ) ? self::$field_objects[ $type ] : null;

		return $object;
	}

	/**
	 * Get field types
	 *
	 * @return array
	 */
	public static function get_field_types() {
		$types = array_keys( self::$field_objects );

		return $types;
	}

	/**
	 * Get field type based on $element_id
	 *
	 * @param $element_id Field slug.
	 * @return array
	 */
	public static function get_field_type( $element_id ) {
		$field_type = '';
		$parts      = explode( '-', $element_id );
		// all avail fields on library.
		$field_types = Forminator_Core::get_field_types();

		if ( in_array( $parts[0], $field_types, true ) ) {
			$field_type = $parts[0];
		}

		return $field_type;
	}

	/**
	 * Includes
	 *
	 * @since 1.0
	 */
	private function includes() {
		// Abstracts.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-field.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-form-result.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-form-template.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-front-action.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-mail.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-payment-gateway.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-spam-protection.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-user.php';

		// Classes.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-loader.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-modules.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-form-fields.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-database-tables.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-upgrade.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-geo.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-protection.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-shortcode-generator.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-export-result.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-export.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-render-form.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-assets-enqueue.php';
		/* @noinspection PhpIncludeInspection */

		if ( version_compare( PHP_VERSION, '5.3.0', 'ge' ) && file_exists( forminator_plugin_dir() . 'library/gateways/class-paypal-express.php' ) ) {
			include_once forminator_plugin_dir() . 'library/gateways/class-paypal-express.php';
		}

		if ( version_compare( PHP_VERSION, '5.6.0', 'ge' ) && file_exists( forminator_plugin_dir() . 'library/gateways/class-stripe.php' ) ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/gateways/class-stripe.php';
		}

		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-widget.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-captcha-verification.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-migration.php';

		// Models.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-entry-model.php';

		// Helpers.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-core.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-importer.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-modules.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-forms.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-fields.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-google-fonts.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-mail.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-currency.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-autofill.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-calculator.php';

		if ( version_compare( PHP_VERSION, '5.6.0', 'ge' ) ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/helpers/helper-payment.php';
		}

		// Model.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-base-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-custom-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-field-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-poll-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-quiz-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-views-model.php';
		if ( is_admin() ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-view-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-module-edit-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-module.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-import-mediator.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/classes/class-admin.php';
			/* @noinspection PhpIncludeInspection */
			if ( ! class_exists( 'WP_List_Table' ) ) {
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';// added.
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/screen.php';// added.
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/template.php';
			}
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/model/class-form-entries-list-table.php';
		}

		if ( Forminator::is_internal_page_cache_support_enabled() ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/class-page-cache.php';
		}
	}

	/**
	 * Start creating meta box for the posts
	 *
	 * @since 1.0
	 */
	public function post_field_meta_box() {
		add_action( 'add_meta_boxes', array( $this, 'setup_post_meta_box' ) );
	}

	/**
	 * Setup the meta box
	 *
	 * @since 1.0
	 */
	public function setup_post_meta_box() {
		global $post;
		if ( is_object( $post ) ) {
			$is_forminator_meta = get_post_meta( $post->ID, '_has_forminator_meta' );
			if ( $is_forminator_meta ) {
				add_meta_box(
					'forminator-post-meta-box',
					__( 'Post Custom Data', 'forminator' ),
					array( $this, 'render_post_meta_box' ),
					$post->post_type,
					'normal',
					'default'
				);
			}
		}
	}

	public function localize_pointers() {
		?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		</script>
		<?php
	}

	/**
	 * Render Meta box
	 *
	 * @since 1.0
	 */
	public function render_post_meta_box( $post ) {
		$meta_values = get_post_custom( $post->ID );
		?>
		<table class="widefat">
			<tbody>
			<?php
			foreach ( $meta_values as $key => $value ) {
				if ( '_' === $key[0] ) {
					continue;
				}
				$value = $value[0];
				?>
				<tr>
					<th><?php echo esc_html( $key ); ?></th>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Sanitize
	 *
	 * @param string $key POST key.
	 * @param mixed  $default_value Default value.
	 * @return type
	 */
	public static function sanitize_text_field( $key, $default_value = '' ) {
		if ( ! empty( $_POST[ $key ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		} elseif ( ! empty( $_GET[ $key ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
		} else {
			$value = $default_value;
		}

		return $value;
	}

	/**
     * Recursively sanitize data
     *
	 * @param array  $data Data.
	 * @param string $current_key Current key.
	 *
	 * @return array|string
	 */
	public static function sanitize_array( $data, $current_key = '' ) {
		$data = wp_unslash( $data );
		$skipped_keys = array( 'preview_data' );
		// TODO: Should skip fields that has its own sanitize function
		if (
			in_array( $current_key, $skipped_keys, true ) ||
			0 === strpos( $current_key, 'url-' )
		) {
			return $data;
		}

		$allow_html = array(
			'variations',
			'question_description',
			'thankyou-message',
			'user-email-editor',
			'admin-email-editor',
			'quiz_description',
			'question_description',
			'email-editor',
			'email-editor-method-email',
			'email-editor-method-manual',
            'msg_count',
			'confirm-password-description',
			'description',
            'consent_description',
            'hc_invisible_notice',
            'options_bulk_editor',
            'label',
            'value',
            'importable',
            'sc_message',
		);
		if (
			in_array( $current_key, $allow_html, true ) ||
			0 === strpos( $current_key, 'html-' ) ||
			0 === strpos( $current_key, 'textarea-' ) ||
			false !== strpos( $current_key, '-post-title' ) ||
			false !== strpos( $current_key, '-post-content' ) ||
			false !== strpos( $current_key, '-post-excerpt' )
		) {
			return trim( wp_kses_post( $data ) );
		}
		if ( 'custom_css' === $current_key ) {
			return sanitize_textarea_field( $data );
		}

		if ( ! is_array( $data ) ) {
			return sanitize_text_field( $data );
		} else {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::sanitize_array( $value, $key );
			}

			return $data;
		}
	}
}
