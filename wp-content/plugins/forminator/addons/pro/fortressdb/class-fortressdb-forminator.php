<?php
/**
 * Class FortressDB_Forminator_Addon
 * FortressDB Addon Main Class
 *
 * @since 1.0 FortressDB Addon
 */
final class FortressDB_Forminator_Addon extends Forminator_Addon_Abstract {
	/**
	 * @var self|null
	 */
	private static $_instance = null;
	protected $_slug = 'forminatorfortressdb';
	protected $_version = '1.0';
	protected $_min_forminator_version = '1.1';
	protected $_short_title = 'FortressDB';
	protected $_title = 'FortressDB';
	protected $_url = 'https://wpmudev.com';
	protected $_full_path = __FILE__;
	protected $_form_settings = 'FortressDB_Forminator_Addon_Form_Settings';
	protected $_form_hooks = 'FortressDB_Forminator_Addon_Form_Hooks';
	/**
	 * @var FortressDB_Wp_Api
	 */
	private $api = null;
	/**
	 * @var FortressDB_Form_Parser
	 */
	private $parser = null;
	private $_token = '';
	private $_update_form_settings_error_message = '';

	/**
	 * @var array
	 */
	private $feed = array();

	/**
	 * Forminator_Addon_FortressDB_Form_Settings constructor.
	 *
	 * @throws Exception
	 *
	 * @since 1.0 FortressDB Addon
	 */
	public function __construct() {
		$this->_icon  = forminator_addon_fortress_assets_url() . 'images/fortressdb.png';
		$this->_image = forminator_addon_fortress_assets_url() . 'images/fortressdb.png';
	}

	/**
	 * Get Instance
	 *
	 * @return self|null
	 * @throws Exception
	 * @since 1.0 FortressDB Addon
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Setting apier Addon
	 *
	 * @since 1.0 FortressDB Addon
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'setup_connect' ),
				'is_completed' => array( $this, 'is_connected' ),
			),
		);
	}

	/**
	 * Override settings available,
	 *
	 * @return bool
	 * @since 1.0 FortressDB Addon
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Override on is_connected
	 *
	 * @return bool
	 * @since 1.0 FortressDB Addon
	 *
	 */
	public function is_connected() {
		return false;
	}

	/**
	 * Connect FortressDB
	 *
	 * @since 1.0 Fortress Addon
	 *
	 * @param     $submitted_data
	 * @param int $form_id
	 *
	 * @return array
	 */
	public function setup_connect( $submitted_data, $form_id = 0 ) {
		ob_start();
		?>

		<div class="forminator-integration-popup__header">

			<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
				<?php
				/* translators: ... */
				echo esc_html( sprintf( __( 'Connect %1$s', 'forminator' ), 'FortressDB' ) );
				?>
			</h3>

		</div>

		<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;">
			<?php esc_html_e( 'Install the FortressDB plugin and sign-in to your FortressDB account to automatically connect it to Forminator. If you do not have an account, you can create a new free account within the FortressDB plugin.', 'forminator' ); ?>
		</p>

		<?php
		$html = ob_get_clean();

		$buttons = array();

		$fortress_install_url = is_multisite() ?
                                network_admin_url( 'plugin-install.php?s=fortressdb&tab=search&type=term' ) :
                                admin_url( 'plugin-install.php?s=fortressdb&tab=search&type=term' );

		$buttons['close'] = array(
			'markup' => self::get_link_markup( $fortress_install_url, esc_html__( 'Install FortressDB', 'forminator' ), '_self', 'forminator-addon-close forminator-integration-popup__close' ),
		);

		$has_errors = false;

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'nopadding'  => true,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Check if FortressDB is connected with current form
	 *
	 * @since 1.0 Fortress Addon
	 *
	 * @param $form_id
	 *
	 * @return bool
	 */
	public function is_form_connected( $form_id ) {
		return false;
	}
}
