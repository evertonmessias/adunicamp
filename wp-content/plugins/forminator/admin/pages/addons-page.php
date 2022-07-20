<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Addons_Page
 *
 * @since 1.15
 */
class Forminator_Addons_Page extends Forminator_Admin_Page {

	/**
	 * fetch Add-ons data
	 *
	 * @return array
	 */
	public function get_addons_data() {
		$project_data = array();
		if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			$dash         = WPMUDEV_Dashboard::instance();
			$project_info = array(
				array( 'pid' => 3953609 ),
			);

			foreach ( $project_info as $pkey => $project ) {
				$project_data[] = $dash::$site->get_project_info( $project['pid'] );
			}
		}

		return $project_data;
	}


	/**
	 * Get addons by action
	 *
	 * @return array
	 */
	public function get_addons_by_action() {
		$update   = array();
		$projects = $this->get_addons_data();
		if ( ! empty( $projects ) ) {
			foreach ( $projects as $project ) {
				if ( ! empty( $project ) ) {
					if ( $project->has_update ) {
						$update[] = $project;
					}
				}
			}
		}

		if ( empty( $projects ) && ! FORMINATOR_PRO && ! class_exists( 'WPMUDEV_Dashboard' ) ) {
			$projects = Forminator_Admin_Addons_page::forminator_get_static_addon();
		}

		$response['all']    = $projects;
		$response['update'] = $update;

		return $response;
	}
}
