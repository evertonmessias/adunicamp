<?php
/**
 * Forminator Uninstall methods
 * Called when plugin is deleted
 *
 * @since 1.0.2
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Drop custom tables
 *
 * @since 1.0.2
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_drop_custom_tables( $db_prefix = 'wp_' ) {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_entry" );
	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_entry_meta" );
	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_views" );
}

/**
 * Clear custom posts
 *
 * @since 1.0.2
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_delete_custom_posts( $db_prefix = 'wp_' ) {
	global $wpdb;

	//Now we delete the custom posts
	$forms_sql        = "SELECT GROUP_CONCAT(`ID`) FROM {$db_prefix}posts WHERE `post_type` = %s";
	$delete_forms_sql = "DELETE FROM {$db_prefix}posts WHERE `post_type` = %s";

	$form_types       = array(
		'forminator_forms',
		'forminator_polls',
		'forminator_quizzes',
	);
	foreach ( $form_types as $type ) {
		$ids = $wpdb->get_var( $wpdb->prepare( $forms_sql, $type ) ); // WPCS: unprepared SQL ok. false positive
		if ( $ids ) {

			$array_ids = explode( ',', $ids );
			foreach ( $array_ids as $array_id ) {
				wp_cache_delete( $array_id, 'forminator_total_entries' );
			}

			$delete_form_meta_sql = "DELETE FROM {$db_prefix}postmeta WHERE `post_id` in($ids)";
			$wpdb->query( $delete_form_meta_sql ); // WPCS: unprepared SQL ok. false positive. no need to prepared since all param are not user defined
		}
		$wpdb->query( $wpdb->prepare( $delete_forms_sql, $type ) ); // WPCS: unprepared SQL ok. false positive
	}
}

/**
 * Delete custom options and addon options
 *
 * @since 1.0.2
 * @since 1.0.6 Delete privacy options
 * @since 1.14.10 Deletes all forminator options including the addons' options
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_delete_custom_options( $db_prefix = 'wp_' ) {
	global $wpdb;

	$forminator_options = $wpdb->get_results( "SELECT option_name FROM {$db_prefix}options WHERE option_name LIKE 'forminator_%'" );

	foreach( $forminator_options as $option ) {
		delete_option( $option->option_name );
	}
}

/**
 * Clear the module submissions cache data
 *
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_clear_module_submissions( $db_prefix = 'wp_' ) {
	global $wpdb;

	$max_entry_id_query = "SELECT MAX(`entry_id`) FROM {$db_prefix}frmt_form_entry";
	$max_entry_id       = $wpdb->get_var( $max_entry_id_query );

	if ( $max_entry_id && is_numeric( $max_entry_id ) && $max_entry_id > 0 ) {
		for ( $i = 1; $i <= $max_entry_id; $i ++ ) {
			wp_cache_delete( $i, 'Forminator_Form_Entry_Model' );
		}
	}

	wp_cache_delete( 'all_form_types', 'forminator_total_entries' );
	wp_cache_delete( 'custom-forms' . '_form_type', 'forminator_total_entries' );
	wp_cache_delete( 'poll' . '_form_type', 'forminator_total_entries' );
	wp_cache_delete( 'quizzes' . '_form_type', 'forminator_total_entries' );
}

/**
 * Remove forminator files in uploads folder
 */
function forminator_remove_upload_files() {
	$upload_dir = wp_upload_dir();
	$folder     = $upload_dir['basedir'] . '/forminator/';
	$recursive  = true;
	if ( ! class_exists( 'WP_Filesystem_Direct', false ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	}
	$filesystem = new WP_Filesystem_Direct( null );
	$filesystem->rmdir( $folder, $recursive );
}

$clear_data = get_option( 'forminator_uninstall_clear_data', false );

global $wpdb;
if ( $clear_data && ! is_multisite() ) {
	$db_prefix = $wpdb->prefix;

	forminator_delete_custom_options( $db_prefix );
	forminator_delete_custom_posts( $db_prefix );
	forminator_clear_module_submissions( $db_prefix );
	forminator_remove_upload_files();
	forminator_drop_custom_tables( $db_prefix );

} elseif ( $clear_data ) {
	$sites = get_sites();

	foreach ( $sites as $site ) {
		$blog_id = $site->blog_id;
		$db_prefix = $wpdb->get_blog_prefix( $blog_id );

		forminator_delete_custom_posts( $db_prefix );

		// Switch to blog before deleting options.
		switch_to_blog( $blog_id );
		forminator_delete_custom_options( $db_prefix );
		restore_current_blog();

		forminator_clear_module_submissions( $db_prefix );

		switch_to_blog( $blog_id );
		forminator_remove_upload_files();
		restore_current_blog();

		forminator_drop_custom_tables( $db_prefix );
	}
}
