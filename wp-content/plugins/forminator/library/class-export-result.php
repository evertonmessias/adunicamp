<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Export_Result
 *
 * Export result data struct
 *
 * @since 1.5.4
 */
class Forminator_Export_Result {

	/**
	 * @var array
	 */
	public $data = array();

	/**
	 * @var int
	 */
	public $entries_count = 0;

	/**
	 * @var int
	 */
	public $new_entries_count = 0;

	/**
	 * @var Forminator_Base_Form_Model | null
	 */
	public $model = null;

	/**
	 * @var int
	 */
	public $latest_entry_id = 0;

	/**
	 * @var string
	 */
	public $file_path = '';

	/**
	 * @var string
	 */
	public $form_type = '';


	public function __construct() {
	}

	/**
	 * Parsing filters
	 *
	 * @since 1.5.4
	 */
	public function request_filters() {
		$data_range = Forminator_Core::sanitize_text_field( 'date_range' );
		$search     = Forminator_Core::sanitize_text_field( 'search' );
		$min_id     = Forminator_Core::sanitize_text_field( 'min_id' );
		$max_id     = Forminator_Core::sanitize_text_field( 'max_id' );
		$order_by   = Forminator_Core::sanitize_text_field( 'order_by' );
		$order      = Forminator_Core::sanitize_text_field( 'order' );

		$filters = array();
		if ( ! empty( $data_range ) ) {
			$date_ranges = explode( ' - ', $data_range );
			if ( is_array( $date_ranges ) && isset( $date_ranges[0] ) && isset( $date_ranges[1] ) ) {
				$date_ranges[0] = date( 'Y-m-d', strtotime( $date_ranges[0] ) );
				$date_ranges[1] = date( 'Y-m-d', strtotime( $date_ranges[1] ) );

				forminator_maybe_log( __METHOD__, $date_ranges );
				$filters['date_created'] = array( $date_ranges[0], $date_ranges[1] );
			}
		}
		if ( ! empty( $search ) ) {
			$filters['search'] = $search;
		}

		if ( ! empty( $min_id ) ) {
			$min_id = intval( $min_id );
			if ( $min_id > 0 ) {
				$filters['min_id'] = $min_id;
			}
		}

		if ( ! empty( $max_id ) ) {
			$max_id = intval( $max_id );
			if ( $max_id > 0 ) {
				$filters['max_id'] = $max_id;
			}
		}
		if ( $order_by ) {
			$filters['order_by'] = $order_by;
		}

		if ( $order ) {
			$filters['order'] = $order;
		}

		return $filters;
	}
}
