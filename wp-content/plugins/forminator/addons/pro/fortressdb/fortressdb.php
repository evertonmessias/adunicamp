<?php

/**
 * Addon Name: FortressDB
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with FortressDB to get notified in real time.
 * Author: FortressDB
 * Author URI: https://fortressdb.com
 */

if ( class_exists( 'FortressDB' ) ) {
	return;
}

function forminator_addon_fortressdb_url() {
	return trailingslashit( forminator_plugin_url() . 'addons/pro/fortressdb' );
}

function forminator_addon_fortress_assets_url() {
	return trailingslashit( forminator_addon_fortressdb_url() . 'assets' );
}

function forminator_addon_fortress_dir() {
	return trailingslashit( dirname( __FILE__ ) );
}

require_once dirname( __FILE__ ) . '/class-fortressdb-forminator.php';
//require_once dirname( __FILE__ ) . '/class-fortressdb-forminator-form-settings.php';

try {
	Forminator_Addon_Loader::get_instance()->register( 'FortressDB_Forminator_Addon' );
} catch ( Exception $e ) {
	echo wp_kses_post( $e->getMessage() );
}
