<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_Page
 *
 * @since 1.0
 */
class Forminator_CForm_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_cform_bulk_actions',
			array(
				'publish-forms'        => __( 'Publish', 'forminator' ),
				'draft-forms'          => __( 'Unpublish', 'forminator' ),
				'clone-forms'          => __( 'Duplicate', 'forminator' ),
				'reset-views-forms'    => __( 'Reset Tracking Data', 'forminator' ),
				'apply-preset-forms'   => __( 'Apply Appearance Preset', 'forminator' ),
				'delete-entries-forms' => __( 'Delete Submissions', 'forminator' ),
				'delete-forms'         => __( 'Delete', 'forminator' ),
			)
		);
	}

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
		);
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.6.1
	 *
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		// for preview.
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = '4.0.3';

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;
		wp_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput.
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput.
	}
}
