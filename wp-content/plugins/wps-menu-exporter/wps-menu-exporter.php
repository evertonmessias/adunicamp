<?php
/*
Plugin Name: WPS Menu Exporter
Description: WPS Menu Exporter permet d'exporter les menus WordPress.
Donate link: https://www.paypal.me/donateWPServeur
Author: WPServeur, NicolasKulka, wpformation, benoti
Author URI: https://wpserveur.net
Version: 1.3.5
Requires at least: 4.2
Tested up to: 6.0
Domain Path: languages
Text Domain: wps-menu-exporter
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPS_MENU_EXPORTER_VERSION', '1.3.5' );
define( 'WPS_MENU_EXPORTER_FOLDER', 'wps-menu-exporter' );
define( 'WPS_MENU_EXPORTER_BASENAME', plugin_basename( __FILE__ ) );

define( 'WPS_MENU_EXPORTER_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_MENU_EXPORTER_DIR', plugin_dir_path( __FILE__ ) );

require_once WPS_MENU_EXPORTER_DIR . 'autoload.php';

if ( ! function_exists( 'plugins_loaded_wps_menu_exporter_plugin' ) ) {
	add_action( 'plugins_loaded', 'plugins_loaded_wps_menu_exporter_plugin' );
	function plugins_loaded_wps_menu_exporter_plugin() {
		\WPS\WPS_Menu_Exporter\Plugin::get_instance();

		load_plugin_textdomain( 'wps-menu-exporter', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );
	}
}