<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_General_Data_Protection
 *
 * What it does :
 * - Hook WordPress Export Personal Data
 * - Hook WordPress Erase Personal Data
 * - Data Retention
 * - WordPress Policy Page
 *
 * @since 1.0.6
 */
abstract class Forminator_General_Data_Protection {

	/**
	 * Friendly name used
	 *
	 * @var string
	 */
	protected $name;

	protected $cron_cleanup_interval;

	protected $exporters = array();
	protected $erasers   = array();

	public function __construct( $name, $cron_cleanup_interval = 'hourly' ) {
		$this->name                  = $name;
		$this->cron_cleanup_interval = $cron_cleanup_interval;
		$this->init();
	}

	protected function init() {
		add_action( 'admin_init', array( $this, 'add_privacy_message' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ), 10 );

		// for data removal / anonymize data.
		if ( ! wp_next_scheduled( 'forminator_general_data_protection_cleanup' ) ) {
			wp_schedule_event( time(), $this->get_cron_cleanup_interval(), 'forminator_general_data_protection_cleanup' );
		}

		add_action( 'forminator_general_data_protection_cleanup', array( $this, 'personal_data_cleanup' ) );

	}

	/**
	 * Add Privacy Messages
	 */
	public function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = $this->get_privacy_message();
			if ( ! empty( $content ) ) {
				wp_add_privacy_policy_content( $this->name, $this->get_privacy_message() );
			}
		}
	}

	/**
	 * Privacy Message
	 *
	 * @return string
	 */
	public function get_privacy_message() {
		return '';
	}

	/**
	 * Get retain time
	 *
	 * @param int    $retain_number Unit amount.
	 * @param string $retain_unit Unit.
	 * @return boolean|string
	 */
	public function get_retain_time( $retain_number, $retain_unit ) {

		if ( empty( $retain_number ) || $retain_number <= 0 ) {
			return false;
		}

		$possible_units = array(
			'days',
			'weeks',
			'months',
			'years',
		);

		if ( ! in_array( $retain_unit, $possible_units, true ) ) {
			return false;
		}

		$retain_time = strtotime( '-' . $retain_number . ' ' . $retain_unit, current_time( 'timestamp' ) );
		$retain_time = date_i18n( 'Y-m-d H:i:s', $retain_time );

		return $retain_time;
	}

	/**
	 * Anon Entry model IP
	 *
	 * @since 1.0.6
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 */
	protected function anonymize_entry_model( Forminator_Form_Entry_Model $entry_model ) {
		if ( isset( $entry_model->meta_data['_forminator_user_ip'] ) ) {
			$meta_id    = $entry_model->meta_data['_forminator_user_ip']['id'];
			$meta_value = $entry_model->meta_data['_forminator_user_ip']['value'];

			if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
				$anon_value = wp_privacy_anonymize_data( 'ip', $meta_value );
			} else {
				$anon_value = '';
			}

			if ( $anon_value !== $meta_value ) {
				$entry_model->update_meta( $meta_id, '_forminator_user_ip', $anon_value );
			}
		}
	}

	/**
	 * Delete older entries
	 *
	 * @param int    $module_id
	 * @param string $retain_time
	 */
	protected function delete_older_entries( $module_id, $retain_time, $is_draft = false ) {
		$entry_ids = Forminator_Form_Entry_Model::get_older_entry_ids( $retain_time, '', $module_id, $is_draft );
		foreach ( $entry_ids as $entry_id ) {
			Forminator_Form_Entry_Model::delete_by_entry( $entry_id );
		}
	}

	/**
	 * Append registered exporters to wp exporter
	 *
	 * @param array $exporters
	 *
	 * @return array
	 */
	public function register_exporters( $exporters = array() ) {
		foreach ( $this->exporters as $id => $exporter ) {
			$exporters[ $id ] = $exporter;
		}

		return $exporters;
	}

	/**
	 * Append registered eraser to wp eraser
	 *
	 * @param array $erasers
	 *
	 * @return array
	 */
	public function register_erasers( $erasers = array() ) {
		foreach ( $this->erasers as $id => $eraser ) {
			$erasers[ $id ] = $eraser;
		}

		return $erasers;
	}

	public function add_exporter( $id, $name, $callback ) {
		$this->exporters[ $id ] = array(
			'exporter_friendly_name' => $name,
			'callback'               => $callback,
		);

		return $this->exporters;
	}

	public function add_eraser( $id, $name, $callback ) {
		$this->erasers[ $id ] = array(
			'eraser_friendly_name' => $name,
			'callback'             => $callback,
		);

		return $this->erasers;
	}

	/**
	 * Get Interval
	 *
	 * @return string
	 */
	public function get_cron_cleanup_interval() {
		$cron_cleanup_interval = $this->cron_cleanup_interval;

		/**
		 * Filter interval to be used for cleanup process
		 *
		 * @since  1.0.6
		 *
		 * @params string $cron_cleanup_interval interval in string (daily,hourly, etc).
		 */
		$cron_cleanup_interval = apply_filters( 'forminator_general_data_cleanup_interval', $cron_cleanup_interval );

		return $cron_cleanup_interval;
	}

	/**
	 * Cleanup personal data
	 *
	 * @return bool
	 */
	public function personal_data_cleanup() {
		return false;
	}
}
