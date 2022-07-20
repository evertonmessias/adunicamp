<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Upgrade
 *
 * Handle any installation upgrade or install tasks
 */
class Forminator_Upgrade {

	/**
	 * Initialise data before plugin is fully loaded
	 *
	 * @since 1.0
	 */
	public static function init() {
		/**
		 * Initialize the plugin data
		 */
		$old_version = get_option( 'forminator_version', false );
		if ( $old_version ) {
			$version_changed = version_compare( $old_version, FORMINATOR_VERSION, 'lt' );

			if ( $version_changed ) {
				update_option( 'forminator_version_upgraded', true );
			}
		} else {
			$version_changed = true;
		}
		if ( $version_changed ) {
			// Update tables if required.
			Forminator_Database_Tables::install_database_tables();

			add_action( 'admin_init', array( __CLASS__, 'flush_rewrite' ) );

			// Update version.
			update_option( 'forminator_version', FORMINATOR_VERSION );

			add_action(
				'forminator_loaded',
				function() use ( $old_version ) {
					/**
					 * Triggered when Forminator version is updated
					 *
					 * @param string FORMINATOR_VERSION New plugin version
					 * @param string $old_version Old plugin version.
					 */
					do_action( 'forminator_update_version', FORMINATOR_VERSION, $old_version );
				}
			);
		}

		// cleanup ip address on views.
		self::cleanup_views_ip_address();
	}

	public static function flush_rewrite() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clean up up address on views
	 *
	 * @since 1.5.4
	 * @return bool
	 */
	public static function cleanup_views_ip_address() {
		if ( defined( 'FORMINATOR_VIEWS_ENABLE_TRACK_IP' ) && FORMINATOR_VIEWS_ENABLE_TRACK_IP ) {
			return false;
		}
		$views = new Forminator_Form_Views_Model();
		$views->maybe_cleanup_ip_address();

		return true;
	}
}
