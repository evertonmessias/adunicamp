<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_Page
 *
 * @since 1.0
 */
class Forminator_Poll_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Return module array
	 *
	 * @since 1.14.10
	 *
	 * @param $id
	 * @param $title
	 * @param $views
	 * @param $date
	 * @param $status
	 * @param name
	 *
	 * @return array
	 */
	protected static function module_array( $id, $title, $views, $date, $status, $model ) {
		return array(
			'id'              => $id,
			'title'           => $title,
			'entries'         => Forminator_Form_Entry_Model::count_entries( $id ),
			'last_entry_time' => forminator_get_latest_entry_time_by_form_id( $id ),
			'views'           => $views,
			'date'            => $date,
			'status'          => $status,
			'name'            => forminator_get_name_from_model( $model ),
		);
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_polls_bulk_actions',
			array(
				'clone-polls'        => __( 'Duplicate', 'forminator' ),
				'reset-views-polls'  => __( 'Reset Tracking Data', 'forminator' ),
				'delete-votes-polls' => __( 'Delete Votes', 'forminator' ),
				'delete-polls'       => __( 'Delete', 'forminator' ),
			)
		);
	}
}
