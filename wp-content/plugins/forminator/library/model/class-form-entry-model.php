<?php

/**
 * Form Entry model
 * Base model for all form entries
 *
 * @since 1.0
 */
class Forminator_Form_Entry_Model {

	/**
	 * Entry id
	 *
	 * @var int
	 */
	public $entry_id = 0;

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public $entry_type;

	/**
	 * Form id
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Draft id
	 *
	 * @var string
	 */
	public $draft_id;

	/**
	 * Spam flag
	 *
	 * @var bool
	 */
	public $is_spam = false;

	/**
	 * Date created in sql format 0000-00-00 00:00:00
	 *
	 * @var string
	 */
	public $date_created_sql;

	/**
	 * Date created in sql format D M Y
	 *
	 * @var string
	 */
	public $date_created;

	/**
	 * Time created in sql format M D Y @ g:i A
	 *
	 * @var string
	 */
	public $time_created;

	/**
	 * Meta data
	 *
	 * @var array
	 */
	public $meta_data = array();

	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * The table meta name
	 *
	 * @var string
	 */
	protected $table_meta_name;

	/**
	 * Hold information about connected addons
	 *
	 * @since 1.1
	 * @var array
	 */
	private static $connected_addons = array();


	/**
	 * Initialize the Model
	 *
	 * @since 1.0
	 * @since 1.1 Add instantiate connected addons
	 * @since 1.2 Limit initiate addon only on custom-forms by default
	 */
	public function __construct( $entry_id = null ) {
		$this->table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$this->table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		/*
		 * @since 1.17.0
		 * draft_id could be used in place of the entry_id in the argument
		 * but we still need to get the entry_id which is used as the key for object cache
		*/
		if ( ! is_numeric( $entry_id ) && ctype_alnum( $entry_id ) ) {
			$entry_id = $this->get_entry_id_by_draft_id( $entry_id );
		}

		if ( is_numeric( $entry_id ) && $entry_id > 0 ) {
			$this->get( $entry_id );
			// get connected addons.
			if ( ! empty( $this->form_id ) ) {
				$entry_types = array(
					'custom-forms',
				);
				/**
				 * Filter Entry Types that can be connected with addons
				 *
				 * @since 1.2
				 *
				 * @param array $entry_types
				 */
				$entry_types = apply_filters( 'forminator_addon_entry_types', $entry_types );
				if ( ! empty( $this->entry_type ) && in_array( $this->entry_type, $entry_types, true ) ) {
					self::get_connected_addons( $this->form_id );
				}
			}
		}

	}

	/**
	 * Load entry by id
	 * After load set entry to cache
	 *
	 * @since 1.0
	 *
	 * @param int $entry_id - the entry id.
	 *
	 * @return bool|mixed
	 */
	public function get( $entry_id ) {
		global $wpdb;

		$cache_key          = get_class( $this );
		$entry_object_cache = wp_cache_get( $entry_id, $cache_key );

		if ( $entry_object_cache ) {
			$this->entry_id         = $entry_object_cache->entry_id;
			$this->entry_type       = $entry_object_cache->entry_type;
			$this->form_id          = $entry_object_cache->form_id;
			$this->is_spam          = $entry_object_cache->is_spam;
			$this->date_created_sql = $entry_object_cache->date_created_sql;
			$this->date_created     = $entry_object_cache->date_created;
			$this->time_created     = $entry_object_cache->time_created;
			$this->meta_data        = $entry_object_cache->meta_data;
			$this->draft_id         = $entry_object_cache->draft_id;

			return $entry_object_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT `entry_type`, `form_id`, `is_spam`, `date_created`, `draft_id` FROM {$table_name} WHERE `entry_id` = %d";
			$entry      = $wpdb->get_row( $wpdb->prepare( $sql, $entry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $entry ) {
				$this->entry_id         = $entry_id;
				$this->entry_type       = $entry->entry_type;
				$this->form_id          = $entry->form_id;
				$this->is_spam          = $entry->is_spam;
				$this->date_created_sql = $entry->date_created;
				$this->date_created     = date_i18n( 'j M Y', strtotime( $entry->date_created ) );
				$this->time_created     = date_i18n( 'M j, Y @ g:i A', strtotime( $entry->date_created ) );
				$this->draft_id     	= $entry->draft_id;
				$this->load_meta();
				wp_cache_set( $entry_id, $this, $cache_key );
			}
		}
	}

	/**
	 * Set fields
	 *
	 * @since 1.0
	 * @since 1.5 set meta_data values even entry_id is null for object reference in the future usage
	 *        entry_id has null value is the outcome of failed to save or prevent_store is enabled
	 *
	 * @param array  $meta_array {
	 *                           Array of data to be saved
	 * @param string $entry_date
	 *
	 * @type key - string the meta key
	 * @type value - string the meta value
	 * }
	 *
	 * @return bool - true or false
	 */
	public function set_fields( $meta_array, $entry_date = '' ) {
		global $wpdb;

		if ( $meta_array && ! is_array( $meta_array ) && ! empty( $meta_array ) ) {
			return false;
		}

		// probably prevent_store enabled.
		$prevent_store = ! $this->entry_id;

		if ( ! $prevent_store ) {
			// clear cache first.
			$cache_key = get_class( $this );
			wp_cache_delete( $this->entry_id, $cache_key );
		}
		foreach ( $meta_array as $meta ) {
			if ( ! isset( $meta['name'] ) || ! isset( $meta['value'] ) ) {
				continue;
			}
			$key   = wp_unslash( $meta['name'] );
			$value = wp_unslash( $meta['value'] );

			if ( ! $prevent_store ) {
				$meta_id = $wpdb->insert(
					$this->table_meta_name,
					array(
						'entry_id'     => $this->entry_id,
						'meta_key'     => $key,
						'meta_value'   => maybe_serialize( $value ),
						'date_created' => ! empty( $entry_date ) ? $entry_date : date_i18n( 'Y-m-d H:i:s' ),
					)
				);
			} else {
				$meta_id = $key;
			}

			/**
			 * Set Meta data for later usage
			 *
			 * @since 1.0.3
			 */
			if ( $meta_id ) {
				$this->meta_data[ $key ] = array(
					'id'    => $meta_id,
					'value' => $value,
				);
			}
		}

		return ! $prevent_store;
	}

	/**
	 * Load all meta data for entry
	 *
	 * @since 1.0
	 *
	 * @param object|bool $db - the WP_Db object.
	 */
	public function load_meta( $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$this->meta_data = array();
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql             = "SELECT `meta_id`, `meta_key`, `meta_value` FROM {$table_meta_name} WHERE `entry_id` = %d";
		$results         = $db->get_results( $db->prepare( $sql, $this->entry_id ) );
		foreach ( $results as $result ) {
			$this->meta_data[ $result->meta_key ] = array(
				'id'    => $result->meta_id,
				'value' => is_array( $result->meta_value ) ? array_map( 'maybe_unserialize', $result->meta_value ) : maybe_unserialize( $result->meta_value ),
			);
		}
	}

	/**
	 * Get Meta
	 *
	 * @since 1.0
	 *
	 * @param string      $meta_key      - the meta key.
	 * @param bool|object $default_value - the default value.
	 *
	 * @return bool|string
	 */
	public function get_meta( $meta_key, $default_value = false ) {
		if ( ! empty( $this->meta_data ) && isset( $this->meta_data[ $meta_key ] ) ) {
			return $this->meta_data[ $meta_key ]['value'];
		}

		return $this->get_grouped_meta( $meta_key, $default_value );
	}

	/**
	 * Get Grouped Meta
	 * Sometimes the meta prefix is same
	 *
	 * @since 1.0
	 *
	 * @param string      $meta_key      - the meta key.
	 * @param bool|object $default_value - the default value.
	 *
	 * @return bool|string
	 */
	public function get_grouped_meta( $meta_key, $default_value = false ) {
		if ( ! empty( $this->meta_data ) ) {
			$response     = '';
			$field_suffix = self::field_suffix();
			foreach ( $field_suffix as $suffix ) {
				if ( isset( $this->meta_data[ $meta_key . '-' . $suffix ] ) ) {
					$response .= $this->meta_data[ $meta_key . '-' . $suffix ]['value'] . ' ' . $suffix . ' , ';
				}
			}
			if ( ! empty( $response ) ) {
				return substr( trim( $response ), 0, - 1 );
			}
		}

		return $default_value;
	}

	/**
	 * Save entry
	 *
	 * @since 1.0
	 * @since 1.6.1 add $data_created arg
	 *
	 * @param string|null 	$data_created optional custom date created.
	 * @param int|null    	$entry_id
	 *
	 * @return bool
	 */
	public function save( $data_created = null, $entry_id = null, $previous_draft = null ) {
		global $wpdb;
		$this->delete_previous_draft( $previous_draft );

		if ( ! empty( $entry_id ) ) {
			$this->entry_id = $entry_id;

			return true;
		}

		if ( empty( $data_created ) ) {
			$data_created = date_i18n( 'Y-m-d H:i:s' );
		}

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'entry_type'   => $this->entry_type,
				'form_id'      => $this->form_id,
				'is_spam'      => $this->is_spam,
				'date_created' => $data_created,
				'draft_id' 	   => $this->draft_id,
			)
		);

		if ( ! $result ) {
			return false;
		}
		wp_cache_delete( $this->form_id, 'forminator_total_entries' );
		wp_cache_delete( 'all_form_types', 'forminator_total_entries' );
		wp_cache_delete( $this->entry_type . '_form_type', 'forminator_total_entries' );
		$this->entry_id = (int) $wpdb->insert_id;

		return true;
	}

	/**
	 * Delete entry with meta
	 *
	 * @since 1.0
	 */
	public function delete() {
		self::delete_by_entry( $this->entry_id );
	}

	/**
	 * Field suffix
	 * Some fields are grouped and have the same suffix
	 *
	 * @since 1.0
	 * @return array
	 */
	public static function field_suffix() {
		return apply_filters(
			'forminator_field_suffix',
			array(
				'hours',
				'minutes',
				'ampm',
				'street_address',
				'address_line',
				'city',
				'state',
				'zip',
				'country',
				'year',
				'day',
				'month',
				'prefix',
				'first-name',
				'middle-name',
				'last-name',
				'post-title',
				'post-content',
				'post-excerpt',
				'post-image',
				'post-category',
				'post-tags',
				'product-id',
				'product-quantity',
			)
		);
	}

	/**
	 * Field suffix label
	 * Displayable label for suffix
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public static function translate_suffix( $suffix ) {
		$translated_suffix = $suffix;
		$field_suffixes    = self::field_suffix();
		$default_label_map = array(
			'hours'            => esc_html__( 'Hour', 'forminator' ),
			'minutes'          => esc_html__( 'Minute', 'forminator' ),
			'ampm'             => esc_html__( 'AM/PM', 'forminator' ),
			'country'          => esc_html__( 'Country', 'forminator' ),
			'city'             => esc_html__( 'City', 'forminator' ),
			'state'            => esc_html__( 'State', 'forminator' ),
			'zip'              => esc_html__( 'Zip', 'forminator' ),
			'street_address'   => esc_html__( 'Street Address', 'forminator' ),
			'address_line'     => esc_html__( 'Address Line 2', 'forminator' ),
			'year'             => esc_html__( 'Year', 'forminator' ),
			'day'              => esc_html__( 'Day', 'forminator' ),
			'month'            => esc_html__( 'Month', 'forminator' ),
			'prefix'           => esc_html__( 'Prefix', 'forminator' ),
			'first-name'       => esc_html__( 'First Name', 'forminator' ),
			'middle-name'      => esc_html__( 'Middle Name', 'forminator' ),
			'last-name'        => esc_html__( 'Last Name', 'forminator' ),
			'post-title'       => esc_html__( 'Post Title', 'forminator' ),
			'post-content'     => esc_html__( 'Post Content', 'forminator' ),
			'post-excerpt'     => esc_html__( 'Post Excerpt', 'forminator' ),
			'post-image'       => esc_html__( 'Post Image', 'forminator' ),
			'post-category'    => esc_html__( 'Post Category', 'forminator' ),
			'post-tags'        => esc_html__( 'Post Tags', 'forminator' ),
			'product-id'       => esc_html__( 'Product ID', 'forminator' ),
			'product-quantity' => esc_html__( 'Product Quantity', 'forminator' ),
		);

		// could be filtered out field_suffix.
		if ( in_array( $suffix, $field_suffixes, true ) && isset( $default_label_map[ $suffix ] ) ) {
			$translated_suffix = $default_label_map[ $suffix ];
		}

		/**
		 * Translatable suffix
		 *
		 * @param string $translated_suffix
		 * @param string $suffix            original suffix.
		 * @param array  $default_label_map default translated suffix.
		 *
		 * @since 1.0.5
		 */
		return apply_filters( 'forminator_translate_suffix', $translated_suffix, $suffix, $default_label_map );
	}

	/**
	 * Ignored fields
	 * Fields not saved or shown
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function ignored_fields() {
		return apply_filters( 'forminator_entry_ignored_fields', array( 'html', 'page-break', 'captcha', 'section' ) );
	}

	/**
	 * List entries
	 *
	 * @since 1.0
	 *
	 * @param int $form_id  - the form id.
	 * @param int $per_page - results per page.
	 * @param int $page     - the current page. Defaults to 0.
	 *
	 * @return Forminator_Form_Entry_Model[]
	 */
	public static function list_entries( $form_id, $per_page, $page = 0 ) {
		global $wpdb;
		$entries    = array();
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `form_id` = %d AND `is_spam` = 0 ORDER BY `entry_id` DESC LIMIT %d, %d ";
		$results    = $wpdb->get_results( $wpdb->prepare( $sql, $form_id, $page, $per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
			}
		}

		return $entries;
	}

	/**
	 * Return if form has live payment entry
	 *
	 * @since 1.10
	 *
	 * @param $form_id - the form id.
	 *
	 * @return mixed
	 */
	public static function has_live_payment( $form_id ) {
		global $wpdb;

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		$sql = "SELECT count(1) > 0
			FROM {$table_name} m
			LEFT JOIN {$entry_table_name} e
			ON (m.entry_id = e.entry_id)
			WHERE e.form_id = %d
			AND ( m.meta_key = 'stripe-1' || m.meta_key = 'paypal-1' )
			AND m.meta_value LIKE '%4:\"mode\";s:4:\"live\"%'
			LIMIT 1";

		$count = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // WPCS: unprepared SQL ok. false positive

		return $count;
	}

	/**
	 * Get all entries
	 *
	 * @since 1.0
	 *
	 * @param int $form_id - the form id.
	 * @param int $filters
	 *
	 * @return Forminator_Form_Entry_Model[]
	 */
	public static function get_entries( $form_id ) {
		global $wpdb;
		$entries    = array();
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `form_id` = %d AND `is_spam` = 0 ORDER BY `entry_id` DESC";
		$results    = $wpdb->get_results( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
			}
		}

		return $entries;
	}

	/**
	 * Get entries with filters
	 *
	 * @since 1.10
	 *
	 * @param int   $form_id - the form id.
	 * @param array $filters
	 *
	 * @return Forminator_Form_Entry_Model[]
	 */
	public static function get_filter_entries( $form_id, $filters ) {
		global $wpdb;
		$entries                 = array();
		$where                   = 'entries.`form_id` = %d AND entries.`is_spam` = 0';
		$table_name              = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entries_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		if ( isset( $filters['date_created'] ) ) {
			$date_created = $filters['date_created'];
			if ( is_array( $date_created ) && isset( $date_created[0] ) && isset( $date_created[1] ) ) {
				$date_created[1] = $date_created[1] . ' 23:59:00';
				$where          .= $wpdb->prepare( ' AND ( entries.date_created >= %s AND entries.date_created <= %s )', $date_created[0], $date_created[1] );
			}
		}

		if ( isset( $filters['user_status'] ) ) {
			$where .= ' AND metas.meta_key="activation_key"';
		}

		if ( isset( $filters['search'] ) ) {
			$where .= $wpdb->prepare( ' AND metas.meta_value LIKE %s', '%' . $wpdb->esc_like( $filters['search'] ) . '%' );
		}

		if ( isset( $filters['min_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id >= %d', $filters['min_id'] );
		}

		if ( isset( $filters['max_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id <= %d', $filters['max_id'] );
		}
		$order_by = 'ORDER BY entries.entry_id';
		if ( isset( $filters['order_by'] ) ) {
			$order_by = 'ORDER BY ' . $filters['order_by']; // unesacaped.
		}
		$order = 'DESC';
		if ( isset( $filters['order'] ) ) {
			$order = $filters['order'];
		}

		// group.
		$group_by = 'GROUP BY entries.entry_id';

		$sql     = "SELECT entries.`entry_id` FROM {$table_name} entries
						INNER JOIN {$entries_meta_table_name} AS metas
    					ON (entries.entry_id = metas.entry_id)
 						WHERE {$where} {$group_by} {$order_by} {$order}";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
			}
		}

		return $entries;
	}

	/**
	 * Group count of Entries with extra selected
	 *
	 * @since   1.0.5
	 *
	 * @example = [
	 *  'FIELDS_WITH_EXTRA_ELEMENT_ID' => [
	 *      'META_VALUE-1' => COUNT
	 *      'META_VALUE-2' => COUNT
	 * ],
	 * 'answer-3' => [
	 *      'javascript is the best' => 8
	 *      'php is the best' => 7
	 * ],
	 * ]
	 *
	 * @param $form_id
	 * @param $fields_element_id_with_extra
	 *
	 * @return array|null|object
	 */
	public static function count_polls_with_extra( $form_id, $fields_element_id_with_extra ) {
		global $wpdb;

		$polls_with_extras = array();

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		foreach ( $fields_element_id_with_extra as $field_element_id_with_extra ) {
			$sql       = "SELECT m.entry_id AS entry_id
							FROM {$table_name} m
							LEFT JOIN {$entry_table_name} e
							ON (m.entry_id = e.entry_id)
							WHERE e.form_id = %d
							AND m.meta_key = %s
							GROUP BY m.entry_id";
			$sql       = $wpdb->prepare( $sql, $form_id, $field_element_id_with_extra ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( ! empty( $entry_ids ) ) {
				$entry_id_placeholders = implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) );

				$sql = "SELECT m.meta_value AS meta_value, COUNT(1) votes
							FROM {$table_name} m
							WHERE m.entry_id IN ({$entry_id_placeholders})
							AND m.meta_key = 'extra'
							GROUP BY m.meta_value ORDER BY votes DESC";
				$sql = $wpdb->prepare( $sql, $entry_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$votes = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$polls_with_extras[ $field_element_id_with_extra ] = array();
				foreach ( $votes as $vote ) {
					$polls_with_extras[ $field_element_id_with_extra ][ $vote['meta_value'] ] = $vote['votes'];
				}
			}
		}

		return $polls_with_extras;
	}

	/**
	 * Count entries by form
	 *
	 * @since 1.0
	 *
	 * @param int $form_id - the form id.
	 *
	 * @return int - total entries
	 */
	public static function count_entries( $form_id, $db = false, $include_draft = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$cache_key     = 'forminator_total_entries';
		$entries_cache = wp_cache_get( $form_id, $cache_key );
		$where 		   = '';

		if ( $entries_cache ) {
			return $entries_cache;
		} else {
			/*
			 * On Submissions page we include drafts in the entries count
			 * but in specific form submissions count (in admin.php?page=forminator-cform)
			 * we dont count the drafts to prevent affecting conversion rate where we only count complete submissions
			 */
			if ( ! $include_draft ) {
				$where = ' AND ( `draft_id` IS NULL OR `draft_id` = "" )';
			}

			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `form_id` = %d AND `is_spam` = 0 {$where}";
			$entries    = $db->get_var( $db->prepare( $sql, $form_id ) );
			if ( $entries ) {
				wp_cache_set( $form_id, $entries, $cache_key );

				return $entries;
			}
		}

		return 0;
	}

	/**
	 * Count lead entries by form
	 *
	 * @since 1.14
	 *
	 * @param int $form_id - the form id.
	 *
	 * @return int - total entries
	 */
	public static function count_leads( $form_id ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT count(DISTINCT e.`entry_id`) FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.meta_key = 'skip_form' AND m.meta_value = '0' AND e.`is_spam` = 0";

		$entries = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entries ) {
			return $entries;
		}

		return 0;
	}


	/**
	 * Count entries by form
	 *
	 * @since 1.0
	 * @deprecated
	 *
	 * @param int $form_id - the form id.
	 *
	 * @return int - total entries
	 */
	public static function count_entries_by_form_and_field( $form_id ) {
		_deprecated_function( 'count_entries_by_form_and_field', '1.0.5' );
		global $wpdb;
		$field            = '';
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT count(m.`meta_id`) FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND e.`is_spam` = 0";
		$entries          = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $field ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entries ) {
			return $entries;
		}

		return 0;
	}

	/**
	 * Map Polls Entries with its votes
	 *
	 * @since   1.0.5
	 *
	 * @example {
	 *  'ELEMENT_ID' => 'NUMBER'
	 *  'answer-1' = 9
	 * }
	 *
	 * @param       $form_id
	 * @param array   $fields
	 *
	 * @return array
	 */
	public static function map_polls_entries( $form_id, $fields ) {
		global $wpdb;
		$map_entries      = array();
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Make sure $form_id is always number.
		if ( ! is_numeric( $form_id ) ) {
			$form_id = 0;
		}

		$element_ids = array();
		foreach ( $fields as $field ) {
			$element_id    = (string) $field['element_id'];
			$element_ids[] = $element_id;
			$title         = sanitize_title( $field['title'] );

			// First, escape the link for use in a LIKE statement.
			$new_element_id_format = $wpdb->esc_like( 'answer-' );
			// Add wildcards.
			$new_element_id_format = $new_element_id_format . '%';

			// find old format entries of this field.
			$sql
				= "SELECT count(1) FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key NOT LIKE '{$new_element_id_format}' AND m.meta_value = '1' AND m.meta_key = '{$title}' LIMIT 1";

			// todo : it can not be prepared by $wpdb->prepare since element_id because of `LIKE` query.
			$old_format_entries = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// old format exist.
			if ( $old_format_entries ) {
				// update old format entries if avail.
				self::maybe_update_poll_entries_meta_key_to_element_id( $form_id, $title, $element_id );
			}
		}

		if ( ! empty( $element_ids ) ) {
			$element_ids_placeholders = implode( ', ', array_fill( 0, count( $element_ids ), '%s' ) );

			$sql
				= "SELECT m.meta_key as element_id, count(1) as votes
					FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key IN ({$element_ids_placeholders}) GROUP BY m.meta_key";

			$sql = $wpdb->prepare( $sql, $element_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $results as $result ) {
				$map_entries[ $result['element_id'] ] = $result['votes'];
			}
		}

		return $map_entries;
	}

	/**
	 * Map Polls Entries to be used for export
	 * Pretty much @see Form_Entry_Model::map_polls_entries(), but returning what's required for the export.
	 *
	 * @since   1.6
	 *
	 * @example {
	 *  'meta_id' => 'values'
	 *  '2' = [
	 *        'date_created' = '1999-12-31 23:59:59',
	 *        'meta_key'       = 'answer-1',
	 *        'is_spam'       = '0',
	 *    ]
	 * }
	 *
	 * @param       $form_id
	 * @param array   $fields
	 *
	 * @return array
	 */
	public static function map_polls_entries_for_export( $form_id, $fields ) {
		global $wpdb;
		$map_entries      = array();
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Make sure $form_id is always number.
		if ( ! is_numeric( $form_id ) ) {
			$form_id = 0;
		}

		$element_ids = array();
		foreach ( $fields as $field ) {
			$element_id    = (string) $field['element_id'];
			$element_ids[] = $element_id;
			$title         = sanitize_title( $field['title'] );

			// First, escape the link for use in a LIKE statement.
			$new_element_id_format = $wpdb->esc_like( 'answer-' );
			// Add wildcards.
			$new_element_id_format = $new_element_id_format . '%';

			// find old format entries of this field.
			$sql
				= "SELECT count(1) FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key NOT LIKE '{$new_element_id_format}' AND m.meta_value = '1' AND m.meta_key = '{$title}' LIMIT 1";

			// todo : it can not be prepared by $wpdb->prepare since element_id because of `LIKE` query.
			$old_format_entries = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// old format exist.
			if ( $old_format_entries ) {
				// update old format entries if avail.
				self::maybe_update_poll_entries_meta_key_to_element_id( $form_id, $title, $element_id );
			}
		}

		if ( ! empty( $element_ids ) ) {
			$element_ids_placeholders = implode( ', ', array_fill( 0, count( $element_ids ), '%s' ) );

			$sql
				= "SELECT m.meta_id, m.meta_key, m.meta_value, m.date_created, e.is_spam, m.entry_id
					FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key IN ({$element_ids_placeholders})";

			$sql = $wpdb->prepare( $sql, $element_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $results as $result ) {
				$map_entries[ $result['meta_id'] ]['entry_id']     = $result['entry_id'];
				$map_entries[ $result['meta_id'] ]['is_spam']      = $result['is_spam'];
				$map_entries[ $result['meta_id'] ]['date_created'] = $result['date_created'];
				$map_entries[ $result['meta_id'] ]['meta_key']     = $result['meta_key'];
				$map_entries[ $result['meta_id'] ]['meta_value']   = $result['meta_value'];
			}
		}

		return $map_entries;
	}

	/**
	 * Update poll entries meta_key to its element_id
	 *
	 * @since 1.0.5
	 *
	 * @param $form_id
	 * @param $old_meta_key
	 * @param $element_id
	 */
	public static function maybe_update_poll_entries_meta_key_to_element_id( $form_id, $old_meta_key, $element_id ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		// find entries that using old format.
		$sql = "SELECT entry_id FROM {$entry_table_name} where form_id = %d";

		$sql       = $wpdb->prepare( $sql, $form_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! empty( $entry_ids ) && count( $entry_ids ) > 0 ) {
			$entry_ids = implode( ', ', $entry_ids );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET meta_key = %s, meta_value = %s WHERE entry_id IN (%s) AND meta_key = %s AND meta_value = '1'",
					$table_name,
					$element_id,
					$entry_ids,
					$old_meta_key,
					$old_meta_key
				)
			);
		}
	}

	/**
	 * Get entry date by ip and form
	 *
	 * @since 1.0
	 *
	 * @param int    $form_id - the form id.
	 * @param string $ip      -  the user ip.
	 *
	 * @return string|bool
	 */
	public static function get_entry_date_by_ip_and_form( $form_id, $ip ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT m.`date_created` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s order by m.`meta_id` desc limit 0,1";
		$entry_date       = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entry_date ) {
			return $entry_date;
		}

		return false;
	}

	/**
	 * Get last entry by IP and form
	 *
	 * @since 1.0
	 *
	 * @param int    $form_id - the form id.
	 * @param string $ip      -  the user ip.
	 *
	 * @return string|bool
	 */
	public static function get_last_entry_by_ip_and_form( $form_id, $ip ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT m.`entry_id` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s order by m.`meta_id` desc limit 0,1";
		$entry_id         = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entry_id ) {
			return $entry_id;
		}

		return false;
	}

	/**
	 * Get entry date by ip and form
	 *
	 * @since 1.0
	 *
	 * @param int    $form_id  - the form id.
	 * @param string $ip       -  the user ip.
	 * @param int    $entry_id - the entry id.
	 * @param string $interval - the mysql interval. Eg (INTERVAL 1 HOUR).
	 *
	 * @return string|bool
	 */
	public static function check_entry_date_by_ip_and_form( $form_id, $ip, $entry_id, $interval = '' ) {
		global $wpdb;
		$current_date     = date_i18n( 'Y-m-d H:i:s' );
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$interval         = esc_sql( $interval );
		$sql              =
			"SELECT m.`meta_id` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s AND m.`entry_id` = %d AND DATE_ADD(m.`date_created`, {$interval}) < %s order by m.`meta_id` desc limit 0,1";
		$entry            = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip, $entry_id, $current_date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entry ) {
			return $entry;
		}

		return false;
	}

	/**
	 * Bulk delete form entries
	 *
	 * @since 1.0
	 *
	 * @param int                                        $form_id - the form id.
	 * @param bool|object - the WP_Object optional param
	 */
	public static function delete_by_form( $form_id, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT GROUP_CONCAT(`entry_id`) FROM {$table_name} WHERE `form_id` = %d";
		$entries    = $db->get_var( $db->prepare( $sql, $form_id ) );

		if ( $entries ) {
			self::delete_by_entrys( $form_id, $entries, $db );
		}
	}

	/**
	 * Delete by string of comma separated entry ids
	 *
	 * @since 1.0
	 * @since 1.1 Add init addons and Add hooks `forminator_before_delete_entry`
	 *
	 * @param           $form_id
	 * @param           $entries
	 * @param bool|wpdb $db
	 *
	 * @return bool
	 */
	public static function delete_by_entrys( $form_id, $entries, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}

		if ( empty( $form_id ) || empty( $entries ) ) {
			return false;
		}

		$form_id = (int) $form_id;

		$table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		forminator_maybe_log( 'delete_by_entrys', $form_id, $entries );
		$entries_to_array = explode( ',', $entries );
		forminator_maybe_log( 'delete_by_entrys', $form_id, $entries_to_array );

		$valid_entries_to_delete = array();
		if ( ! empty( $entries_to_array ) && is_array( $entries_to_array ) ) {
			foreach ( $entries_to_array as $entry_id ) {
				$entry_id    = (int) $entry_id;
				$entry_model = new Forminator_Form_Entry_Model( $entry_id );
				// validate : entry must be exist on requested $form_id.
				if ( $form_id === (int) $entry_model->form_id ) {
					$valid_entries_to_delete[] = $entry_id;
					self::attach_addons_on_before_delete_entry( $form_id, $entry_model );
					self::entry_delete_upload_files( $form_id, $entry_model );
				}
			}
		}

		if ( empty( $valid_entries_to_delete ) ) {
			return false;
		}

		// modify $entries with $valid_entries_to_delete.
		$entries = implode( ', ', $valid_entries_to_delete );
		/**
		 * Fires just before an entry getting deleted
		 *
		 * @since 1.1
		 *
		 * @param int $form_id  Current Form ID.
		 * @param int $entry_id Current Entry ID to be deleted.
		 */
		do_action_ref_array( 'forminator_before_delete_entries', array( $form_id, $entries ) );

		$sql = "DELETE FROM {$table_meta_name} WHERE `entry_id` IN ($entries)";
		$db->query( $sql );

		$sql = "DELETE FROM {$table_name} WHERE `entry_id` IN ($entries)";
		$db->query( $sql );

		wp_cache_delete( $form_id, 'forminator_total_entries' );
		wp_cache_delete( 'all_form_types', 'forminator_total_entries' );

		$model = forminator_get_model_from_id( $form_id );
		if ( is_object( $model ) ) {
			wp_cache_delete( $model->get_entry_type() . '_form_type', 'forminator_total_entries' );
		}
	}


	/**
	 * Delete by entry
	 *
	 * @since 1.0
	 * @since 1.1 Add init addons and Add hooks `forminator_before_delete_entry`
	 *
	 * @param int                                        $entry_id - the entry id.
	 * @param bool|object - the WP_Object optional param
	 */
	public static function delete_by_entry( $entry_id ) {
		global $wpdb;

		$table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$cache_key       = 'Forminator_Form_Entry_Model';
		$entry_model     = new Forminator_Form_Entry_Model( $entry_id );

		$form_id  = (int) $entry_model->form_id;
		$entry_id = (int) $entry_id;
		forminator_maybe_log( 'forminator_before_delete_entry', $form_id, $entry_id );
		/**
		 * Fires just before an entry getting deleted
		 *
		 * @since 1.1
		 *
		 * @param int $form_id  Current Form ID.
		 * @param int $entry_id Current Entry ID to be deleted.
		 */
		do_action_ref_array( 'forminator_before_delete_entry', array( $form_id, $entry_id ) );
		self::attach_addons_on_before_delete_entry( $form_id, $entry_model );
		self::entry_delete_upload_files( $form_id, $entry_model );

		$sql = "DELETE FROM {$table_meta_name} WHERE `entry_id` = %d";
		$wpdb->query( $wpdb->prepare( $sql, $entry_id ) );

		$sql = "DELETE FROM {$table_name} WHERE `entry_id` = %d";
		$wpdb->query( $wpdb->prepare( $sql, $entry_id ) );

		wp_cache_delete( $entry_id, $cache_key );
		wp_cache_delete( $form_id, 'forminator_total_entries' );
		wp_cache_delete( 'all_form_types', 'forminator_total_entries' );
		wp_cache_delete( $entry_model->entry_type . '_form_type', 'forminator_total_entries' );
	}

	/**
	 *  delete files from upload folder
	 *
	 * @since 1.7
	 *
	 * @param                             $form_id
	 * @param Forminator_Form_Entry_Model $entry_model
	 */
	public static function entry_delete_upload_files( $form_id, $entry_model ) {
		$custom_form     = Forminator_Base_Form_Model::get_model( $form_id );
		$submission_file = 'delete';
		if ( is_object( $custom_form ) ) {
			$settings        = $custom_form->settings;
			$submission_file = isset( $settings['submission-file'] ) ? $settings['submission-file'] : 'delete';
		}
		if ( 'delete' === $submission_file ) {
			foreach ( $entry_model->meta_data as $meta_data ) {
				$meta_value = $meta_data['value'];
				if ( is_array( $meta_value ) && isset( $meta_value['file'] ) ) {
					$file_path = is_array( $meta_value['file']['file_path'] ) ? $meta_value['file']['file_path'] : array( $meta_value['file']['file_path'] );
					if ( ! empty( $file_path ) ) {
						foreach ( $file_path as $path ) {
							if ( ! empty( $path ) && file_exists( $path ) ) {
								wp_delete_file( $path );
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Convert meta value to string
	 * Useful on displaying metadata without PHP warning on conversion
	 *
	 * @since 1.0.5
	 *
	 * @param      $field_type
	 * @param      $meta_value
	 * @param bool       $allow_html
	 * @param int        $truncate truncate returned string (usefull if display container is limited).
	 *
	 * @return string
	 */
	public static function meta_value_to_string( $field_type, $meta_value, $allow_html = false, $truncate = PHP_INT_MAX ) {
		switch ( $field_type ) {
			case 'postdata':
				$string_value = self::postdata_to_string( $meta_value, $allow_html, $truncate );
				break;
			case 'time':
				if ( ! isset( $meta_value['hours'] ) || ! isset( $meta_value['minutes'] ) ) {
					$string_value = '';
				} else {
					$string_value = sprintf( '%02d', $meta_value['hours'] ) . ':' . sprintf( '%02d', $meta_value['minutes'] ) . ' ' . ( isset( $meta_value ['ampm'] ) ? $meta_value['ampm'] : '' );
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'date':
				if ( ! isset( $meta_value['year'] ) || ! isset( $meta_value['month'] ) || ! isset( $meta_value['day'] ) ) {
					// is it date picker?
					if ( ! empty( $meta_value ) && is_string( $meta_value ) ) {
						$string_value = $meta_value;
					} else {
						$string_value = '';
					}
				} else {
					if ( empty( $meta_value['year'] ) || empty( $meta_value['month'] ) || empty( $meta_value['day'] ) ) {
						$string_value = '';
					} else {
						$date_value = $meta_value['year'] . '/' . sprintf( '%02d', $meta_value['month'] ) . '/' . sprintf( '%02d', $meta_value['day'] );
						if ( isset( $meta_value['format'] ) && ! empty( $meta_value['format'] ) ) {
							$string_value = date_i18n( $meta_value['format'], strtotime( $date_value ) );
						} else {
							$string_value = date_i18n( get_option( 'date_format' ), strtotime( $date_value ) );
						}
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'email':
				if ( ! empty( $meta_value ) ) {
					$string_value = $meta_value;
					// truncate.
					if ( $allow_html ) {
						// make link.
						$email = $string_value;
						// truncate.
						if ( strlen( $email ) > $truncate ) {
							$email = substr( $email, 0, $truncate ) . '...';
						}
						$string_value = '<a href="mailto:' . $email . '" target="_blank" rel="noopener noreferrer" title="' . __( 'Send Email', 'forminator' ) . '">' . $email . '</a>';
					} else {
						// truncate url.
						if ( strlen( $string_value ) > $truncate ) {
							$string_value = substr( $string_value, 0, $truncate ) . '...';
						}
					}
				} else {
					$string_value = '';
				}

				break;
			case 'url':
				if ( ! empty( $meta_value ) ) {
					$string_value = $meta_value;
					// truncate.
					if ( $allow_html ) {
						// make link.
						$website = esc_url( $string_value );
						// truncate.
						if ( strlen( $website ) > $truncate ) {
							$website = substr( $website, 0, $truncate ) . '...';
						}
						$string_value = '<a href="' . $website . '" target="_blank" rel="noopener noreferrer" title="' . __( 'View Website', 'forminator' ) . '">' . $website . '</a>';
					} else {
						// truncate url.
						if ( strlen( $string_value ) > $truncate ) {
							$string_value = substr( $string_value, 0, $truncate ) . '...';
						}
					}
				} else {
					$string_value = '';
				}

				break;
			case 'upload':
				$file = '';
				if ( isset( $meta_value['file'] ) ) {
					$file = $meta_value['file'];
				}
				if ( ! empty( $file ) && is_array( $file ) && isset( $file['file_url'] ) && ! empty( $file['file_url'] ) ) {
					if ( $allow_html ) {
						// make link.
						$string_value = '';
						$upload_count = 0;
						$file_values  = is_array( $file['file_url'] ) ? $file['file_url'] : array( $file['file_url'] );
						foreach ( $file_values as $file_value ) {
							$url       = $file_value;
							$file_name = basename( $url );
							$file_name = ! empty( $file_name ) ? $file_name : __( '(no filename)', 'forminator' );
							// truncate.
							if ( strlen( $file_name ) > $truncate ) {
								$file_name = substr( $file_name, 0, $truncate ) . '...';
							}

							$upload_count ++;
							if ( $upload_count > 1 ) {
								$string_value .= ', ';
							}

							$string_value .= '<a href="' . $url . '" rel="noopener noreferrer" target="_blank" title="' . __( 'View File', 'forminator' ) . '">' . $file_name . '</a>';
						}
					} else {
						// truncate url.
						$string_value = is_array( $file['file_url'] ) ? implode( '<br/>', $file['file_url'] ) : $file['file_url'];
						if ( strlen( $string_value ) > $truncate ) {
							$string_value = substr( $string_value, 0, $truncate ) . '...';
						}
					}
				} else {
					$string_value = '';
				}
				break;
			case 'checkbox':
				if ( is_array( $meta_value ) ) {
					$string_value = implode( ', ', $meta_value );
				} elseif ( is_string( $meta_value ) ) {
					$string_value = $meta_value;
				} else {
					$string_value = '';
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'calculation':
				if ( ! is_array( $meta_value ) ) {
					$string_value = '0.0';
				} else {
					if ( ! empty( $meta_value['error'] ) ) {
						$string_value = $meta_value['error'];
					} else {
						if ( isset( $meta_value['formatting_result'] ) ) {
							$result = $meta_value['formatting_result'];
						} else {
							$result = $meta_value['result'];
						}
						if ( ! isset( $result ) ) {
							$string_value = '0.0';
						} else {
							if ( is_infinite( floatval( $result ) ) ) {
								$string_value = 'INF';
							} else {
								$string_value = (string) $result;
							}
						}
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'stripe':
				// In case stripe requested without mapper, we return transaction_id.
				$string_value = '';
				if ( is_array( $meta_value ) && isset( $meta_value['transaction_id'] ) ) {
					if ( ! empty( $meta_value['transaction_id'] ) ) {
						$string_value = $meta_value['transaction_id'];
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}

				/**
				 * Filter string value of Stripe meta entry
				 *
				 * @since 1.7
				 *
				 * @param string  $string_value
				 * @param array   $meta_value
				 * @param boolean $allow_html
				 * @param int     $truncate
				 *
				 * @return string
				 */
				$string_value = apply_filters( 'forminator_entry_stripe_meta_value_to_string', $string_value, $meta_value, $allow_html, $truncate );
				break;
			case 'password':
				// Hide value for login/template forms.
				$string_value = '*****';
				break;
			default:
				// base flattener.
				// implode on array.
				if ( is_array( $meta_value ) ) {
					$string_value = json_encode( $meta_value );
				} else {
					// or juggling to string.
					$string_value = (string) $meta_value;
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
		}

		/**
		 * Filter string value of meta entry
		 *
		 * @since 1.7
		 *
		 * @param string  $string_value
		 * @param string  $field_type
		 * @param array   $meta_value
		 * @param boolean $allow_html
		 * @param int     $truncate
		 *
		 * @return string
		 */
		$string_value = apply_filters( 'forminator_entry_meta_value_to_string', $string_value, $field_type, $meta_value, $allow_html, $truncate );

		return $string_value;
	}

	/**
	 * Process postdata meta value
	 *
	 * @since 1.17.0
	 *
	 * @param array $meta_value
	 *
	 * @return string
	 */
	public static function postdata_to_string( $meta_value, $allow_html = false, $truncate = PHP_INT_MAX ) {
		if ( empty( $meta_value ) ) {
			$string_value = '';
		} elseif ( is_string( $meta_value ) ) {
			$string_value = $meta_value;
		} elseif ( ! empty( $meta_value['postdata'] ) ) {
			$post_id = $meta_value['postdata'];

			// Title.
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$url = get_edit_post_link( $post_id, 'link' );
			} else {
				// is not logged in.
				$url = get_home_url();
			}

			// Title make link.
			$title 	  	  = get_the_title( $post_id );
			$post_content = isset( $meta_value['value']['post-content'] ) ? $meta_value['value']['post-content'] : '';
			$post_excerpt = isset( $meta_value['value']['post-excerpt'] ) ? $meta_value['value']['post-excerpt'] : '';
			$category 	  = isset( $meta_value['value']['category'] ) 	  ? $meta_value['value']['category'] : [];
			$tags 	  	  = isset( $meta_value['value']['post_tag'] ) 	  ? $meta_value['value']['post_tag'] : [];
			$post_custom  = isset( $meta_value['value']['post-custom'] )  ? $meta_value['value']['post-custom'] : '';

			if ( $allow_html ) {
				$post_image = ! empty( $meta_value['value']['post-image'] ) && ! empty( $meta_value['value']['post-image']['attachment_id'] )
					? $meta_value['value']['post-image']['attachment_id']
					: '';

				$string_value = self::get_postdata_title( $title, 'allow_html', $truncate, $url  );
				$string_value .= self::get_postdata_content( $post_content, true, $truncate );
				$string_value .= self::get_postdata_excerpt( $post_excerpt, true, $truncate );
				$string_value .= self::get_postdata_categories( $category, true );
				$string_value .= self::get_postdata_tags( $tags, true );
				$string_value .= self::get_postdata_image( $post_image, true );
				$string_value .= self::get_postdata_customfields( $post_custom, true );

			} else {
				$post_image = ! empty( $meta_value['value']['post-image'] ) && ! empty( $meta_value['value']['post-image']['uploaded_file'] )
					? $meta_value['value']['post-image']['uploaded_file'][0]
					: '';

				$string_value = self::get_postdata_title( $title, 'no_html', $truncate  );
				$string_value .= self::get_postdata_content( $post_content, false, $truncate );
				$string_value .= self::get_postdata_excerpt( $post_excerpt, false, $truncate );
				$string_value .= self::get_postdata_categories( $category, false );
				$string_value .= self::get_postdata_tags( $tags, false );
				$string_value .= self::get_postdata_image( $post_image, false );
				$string_value .= self::get_postdata_customfields( $post_custom, false, $truncate );

				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
			}
		} else {
			//  Draft postdata values
			$title 	  	  = isset( $meta_value['post-title'] ) 	 ? $meta_value['post-title'] : '';
			$post_content = isset( $meta_value['post-content'] ) ? $meta_value['post-content'] : '';
			$post_excerpt = isset( $meta_value['post-excerpt'] ) ? $meta_value['post-excerpt'] : '';
			$category 	  = isset( $meta_value['category'] ) 	 ? $meta_value['category'] : [];
			$tags 		  = isset( $meta_value['post_tag'] ) 	 ? $meta_value['post_tag'] : [];

			$string_value = self::get_postdata_title( $title, 'draft', $truncate  );
			$string_value .= self::get_postdata_content( $post_content, true, $truncate );
			$string_value .= self::get_postdata_excerpt( $post_excerpt, true, $truncate );
			$string_value .= self::get_postdata_categories( $category, true );
			$string_value .= self::get_postdata_tags( $tags, true );
			// Custom fields are not being saved in drafts but automatically created after full submission
		}

		return $string_value;
	}

	/**
	 * Get the postdata title depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string	$value
	 * @param string	$type		allow_html, no_html, draft
	 * @param int		$truncate	PHP_INT_MAX
	 * @param string	$url
	 */
	public static function get_postdata_title( $value, $type = 'allow_html', $truncate = PHP_INT_MAX, $url = '' ) {
		if ( empty( $value ) ) {
			return;
		}

		$title = ! empty( $value ) ? $value : __( '(no title)', 'forminator' );
		$title = forminator_truncate_text( wp_kses_post( $title ), $truncate );
		$label = esc_html__( 'Title', 'forminator' );

		if ( 'no_html' !== $type ) {
			$value  = '<b>' . $label . ':</b> ';
		}

		if ( 'allow_html' === $type ) {
			$value .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Edit Post', 'forminator' ) . '">' . $title . '</a>';
		} elseif ( 'draft' === $type ) {
			$value .= $title;
		} else {
			$value  = $label . ': ';
			$value .= $title . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata content depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string	$value
	 * @param bool		$allow_html
	 */
	public static function get_postdata_content( $value = '', $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return '';
		}

		$post_content = forminator_truncate_text( $value, $truncate );
		$label = esc_html__( 'Content', 'forminator' );

		if ( $allow_html ) {
			$value = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';
			$value .= wp_kses_post( $post_content, 'post' );
		} else {
			$post_content  = strip_tags( $post_content );
			$value = $label . ': ';
			$value .= $post_content . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata excerpt depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string	$value
	 * @param bool		$allow_html
	 */
	public static function get_postdata_excerpt( $value = '', $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_excerpt = forminator_truncate_text( $value, $truncate );
		$label = esc_html__( 'Excerpt', 'forminator' );

		if ( $allow_html ) {
			$value = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';
			$value .= wp_strip_all_tags( $post_excerpt );
		} else {
			$post_excerpt = strip_tags( $post_excerpt );
			$value = $label . ': ';
			$value .= $post_excerpt . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata categories depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string/array	$value
	 * @param bool			$allow_html
	 */
	public static function get_postdata_categories( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_category	= $value;
		$the_categories = '';
		$countegories	= 0;
		$single_label	= esc_html__( 'Category', 'forminator' );

		if ( is_array( $post_category ) ) {
			foreach( $post_category as $category ) {
				$categories[] = get_the_category_by_ID( $category );
			}

			$countegories  = count( $categories );
			$the_categories = implode( ', ', $categories );
		} else {
			$the_categories = get_the_category_by_ID( $post_category );
		}

		if ( $allow_html ) {
			$value = '<hr>';

			if ( is_array( $post_category ) ) {
				$value .= '<b>' . esc_html( _n( 'Category', 'Categories', $countegories, 'forminator' ) ) . ':</b> ';
			} else {
				$value .= '<b>' . $single_label . ':</b> ';
			}

			$value .= $the_categories;
		} else {
			$value .= $single_label . ': ';
			$value .= $the_categories . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata tags depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string/array	$value
	 * @param bool			$allow_html
	 */
	public static function get_postdata_tags( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_tags	= $value;
		$the_tags = '';
		$tags_count	= 0;

		$term_args   = array(
			'taxonomy'         => 'post_tag',
			'term_taxonomy_id' => $post_tags,
			'hide_empty'       => false,
			'fields'           => 'names',
		);
		$term_query  = new WP_Term_Query( $term_args );

		if ( ! empty( $tags = $term_query->terms ) ) {
			$tags_count = count( $tags );
			$the_tags .= implode( ', ', $tags );
		}

		$label = esc_html( _n( 'Tag', 'Tags', $tags_count, 'forminator' ) );

		if ( $allow_html ) {
			$value = '<hr>';
			$value .= '<b>' . $label . ':</b> ';

			$value .= $the_tags;
		} else {
			$value .= $label;
			$value .= $the_tags . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata featured image depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string	$value
	 * @param bool		$allow_html
	 */
	public static function get_postdata_image( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$label = esc_html__( 'Featured image', 'forminator' ) . ': ';

		if ( $allow_html ) {
			$post_image_id = $value;
			$value = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';
			$value .= wp_get_attachment_image( $post_image_id, array( 100, 100 ) );
		} else {
			$post_image_url = $value;
			$value = $label . ': ';
			$value .= $post_image_url;
		}

		return $value;
	}

	/**
	 * Get the postdata Custom fields depending on context
	 *
	 * @since 1.17.0
	 *
	 * @param string	$value
	 * @param bool		$allow_html
	 */
	public static function get_postdata_customfields( $value = array(), $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return '';
		}

		$post_custom = $value;
		$label 		 = esc_html__( 'Custom fields', 'forminator' );

		if ( $allow_html ) {
			$value = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';

			$value .= '<ul class="' . esc_attr( 'bulleted' ) . '">';

			foreach ( $post_custom as $field ) {
				if ( ! empty( $field['value'] ) ) {
					$value .= '<li>';
					$value .= esc_html( $field['key'] ) . ': ';
					$value .= esc_html( $field['value'] );
					$value .= '</li>';
				}
			}

			$value .= '</ul>';
		} else {
			$value = $label . ': ';

			foreach ( $post_custom as $index => $field ) {
				if ( ! empty( $field['value'] ) ) {
					0 === $index ?: $value .= ', ';
					$value                 .= esc_html( $field['key'] ) . ' = ';
					$value                 .= esc_html( $field['value'] );
				}
			}

			$value = forminator_truncate_text( $value, $truncate );
		}

		return $value;
	}

	/**
	 * Count all entries for all form_type
	 */
	public static function count_all_entries() {
		global $wpdb;
		$cache_key     = 'forminator_total_entries';
		$entries_cache = wp_cache_get( 'all_form_types', $cache_key );

		if ( $entries_cache ) {
			return $entries_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `is_spam` = %d";
			$entries    = $wpdb->get_var( $wpdb->prepare( $sql, 0 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $entries ) {
				wp_cache_set( 'all_form_types', $entries, $cache_key );

				return $entries;
			}
		}

		return 0;
	}

	/**
	 * Count all entries for the selected entry type
	 *
	 * @since 1.5.4
	 *
	 * @param string $entry_type
	 *
	 * @return int
	 */
	public static function count_all_entries_by_type( $entry_type = 'custom-forms' ) {
		$available_entry_types = array(
			'custom-forms',
			'quizzes',
			'poll',
		);

		if ( ! in_array( $entry_type, $available_entry_types, true ) ) {
			return null;
		}

		global $wpdb;
		$cache_key     = 'forminator_total_entries';
		$entries_cache = wp_cache_get( $entry_type . '_form_type', $cache_key );

		if ( $entries_cache ) {

			return $entries_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `entry_type` = %s AND `is_spam` = %d";
			$entries    = $wpdb->get_var( $wpdb->prepare( $sql, $entry_type, 0 ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $entries ) {
				wp_cache_set( $entry_type . '_form_type', $entries, $cache_key );

				return $entries;
			}
		}

		return 0;

	}

	/**
	 * Get Latest Entry
	 *
	 * @param string $entry_type
	 *
	 * @return Forminator_Form_Entry_Model|null
	 */
	public static function get_latest_entry( $entry_type = 'custom-forms' ) {
		if ( 'form' === $entry_type ) {
			$entry_type = 'custom-forms';
		} elseif ( 'quiz' === $entry_type ) {
			$entry_type = 'quizzes';
		}
		$available_entry_types = array(
			'custom-forms',
			'quizzes',
			'poll',
			'all',
		);

		if ( ! in_array( $entry_type, $available_entry_types, true ) ) {
			return null;
		}

		global $wpdb;
		$entry      = null;
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		if ( 'all' !== $entry_type ) {
			$sql = "SELECT `entry_id` FROM {$table_name} WHERE `entry_type` = %s AND `is_spam` = 0 ORDER BY `date_created` DESC";
			$sql = $wpdb->prepare( $sql, $entry_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$sql = "SELECT `entry_id` FROM {$table_name} WHERE `is_spam` = 0 ORDER BY `date_created` DESC";
		}
		$entry_id = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $entry_id ) ) {
			$entry = new Forminator_Form_Entry_Model( $entry_id );
		}

		return $entry;
	}

	/**
	 * Get Latest Entry by form_id
	 *
	 * @param $form_id
	 *
	 * @return Forminator_Form_Entry_Model|null
	 */
	public static function get_latest_entry_by_form_id( $form_id ) {

		global $wpdb;
		$entry      = null;
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `form_id` = %d AND `is_spam` = 0 ORDER BY `date_created` DESC";
		$entry_id   = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $entry_id ) ) {
			$entry = new Forminator_Form_Entry_Model( $entry_id );
		}

		return $entry;
	}

	/**
	 * Get Connected Addons for form_id, avoid overhead for checking connected addons many times
	 *
	 * @since 1.1
	 *
	 * @param $module_id
	 *
	 * @return array|Forminator_Addon_Abstract[]
	 */
	public static function get_connected_addons( $module_id, $module_slug = 'form' ) {
		if ( ! isset( self::$connected_addons[ $module_id ] ) ) {
			self::$connected_addons[ $module_id ] = array();

			$connected_addons = forminator_get_addons_instance_connected_with_module( $module_id, $module_slug );

			foreach ( $connected_addons as $connected_addon ) {
				try {
					$method = "get_addon_{$module_slug}_hooks";
					if ( ! method_exists( $connected_addon, $method ) ) {
						throw new Exception( 'Method ' . $method . ' doesn\'t exist.' );
					}
					$module_hooks = $connected_addon->$method( $module_id );
					if ( $module_hooks instanceof Forminator_Addon_Hooks_Abstract ) {
						self::$connected_addons[ $module_id ][] = $connected_addon;
					}
				} catch ( Exception $e ) {
					forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to get_addon_module_hooks', $e->getMessage() );
				}
			}
		}

		return self::$connected_addons[ $module_id ];
	}

	/**
	 * Attach hooks for delete entry on connected addons
	 *
	 * @since 1.1
	 *
	 * @param                             $form_id
	 * @param Forminator_Form_Entry_Model $entry_model
	 */
	public static function attach_addons_on_before_delete_entry( $form_id, Forminator_Form_Entry_Model $entry_model ) {
		$module_slug = 'form';
		if ( ! empty( $entry_model->entry_type ) && 'poll' === $entry_model->entry_type ) {
			$module_slug = 'poll';
		} elseif ( ! empty( $entry_model->entry_type ) && 'quizzes' === $entry_model->entry_type ) {
			$module_slug = 'quiz';
		}
		$connected_addons = self::get_connected_addons( $form_id, $module_slug );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$method = "get_addon_{$module_slug}_hooks";
				if ( ! method_exists( $connected_addon, $method ) ) {
					throw new Exception( 'Method ' . $method . ' doesn\'t exist.' );
				}
				$module_hooks    = $connected_addon->$method( $form_id );
				$addon_meta_data = forminator_find_addon_meta_data_from_entry_model( $connected_addon, $entry_model );
				$module_hooks->on_before_delete_entry( $entry_model, $addon_meta_data );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_before_delete_entry', $e->getMessage() );
			}
		}
	}

	/*
	 * Get entries by email
	 *
	 * @since 1.0.6
	 *
	 * @param $email
	 *
	 * @return array
	 */
	public static function get_custom_form_entry_ids_by_email( $email ) {
		global $wpdb;
		$meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql             = "SELECT m.entry_id AS entry_id
							FROM {$meta_table_name} m
							WHERE (m.meta_key LIKE %s OR m.meta_key LIKE %s)
							AND m.meta_value = %s
							GROUP BY m.entry_id";

		$sql       = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->esc_like( 'email-' ) . '%',
			$wpdb->esc_like( 'text-' ) . '%',
			$email
		);
		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Get entries older than $date_created
	 *
	 * @since 1.0.6
	 *
	 * @param $date_created
	 * @param $entry_type
	 * @param $id
	 *
	 * @return array
	 */
	public static function get_older_entry_ids( $date_created, $entry_type = '', $id = 0, $is_draft = false ) {
		global $wpdb;
		$where = '';
		if ( $entry_type ) {
			$where .= $wpdb->prepare( ' AND e.entry_type = %s', $entry_type );
		}
		if ( $id ) {
			$where .= $wpdb->prepare( ' AND e.form_id = %d', $id );
		}

		// wpdb prepare needs something to substitute or else it will throw an error
		if ( ! $is_draft ) {
			$where .= $wpdb->prepare( ' AND ( e.draft_id IS NULL OR e.draft_id = %s )', '' );
		} else {
			$where .= $wpdb->prepare( ' AND e.draft_id IS NOT NULL AND e.draft_id != %s', '' );
		}

		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.date_created < %s {$where}";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$date_created
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created
	 *
	 * @since 1.5.3
	 *
	 * @param $entry_type
	 * @param $date_created
	 *
	 * @return array
	 */
	public static function get_newer_entry_ids( $entry_type, $date_created ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.entry_type = %s
							AND e.date_created > %s";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$entry_type,
			$date_created
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created
	 *
	 * @since 1.5.3
	 *
	 * @param $entry_type
	 *
	 * @return array|object
	 */
	public static function get_most_entry( $entry_type ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT form_id, count(*) entry_count
							FROM {$entry_table_name}
							WHERE entry_type = %s
							GROUP BY form_id ORDER BY entry_count DESC LIMIT 1";
		$sql              = $wpdb->prepare(
			$sql,
			$entry_type
		);
		$most_entry       = $wpdb->get_row( $sql );

		return $most_entry;
	}

	/**
	 * Get entries newer than $date_created of form_id
	 *
	 * @since 1.5.3
	 *
	 * @param $form_id
	 * @param $date_created
	 *
	 * @return array
	 */
	public static function get_newer_entry_ids_of_form_id( $form_id, $date_created ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.form_id = %d
							AND e.date_created > %s";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			$date_created
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created of form_id grouped by date_created Day
	 *
	 * @since 1.5.3
	 *
	 * @param $form_id
	 * @param $date_created
	 *
	 * @return array
	 */
	public static function get_form_latest_entries_count_grouped_by_day( $form_id, $date_created ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT COUNT(e.entry_id) AS entries_amount,
						  	DATE(e.date_created) AS date_created
							FROM {$entry_table_name} e
							WHERE e.form_id = %d
							AND e.date_created > %s
							GROUP BY DATE(e.date_created)
							ORDER BY e.date_created DESC";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			$date_created
		);

		$entry_ids = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created of form_id grouped by date_created Day
	 *
	 * @since 1.14
	 *
	 * @param $form_id
	 * @param $date_created
	 *
	 * @return array
	 */
	public static function get_form_latest_lead_entries_count_grouped_by_day( $form_id, $date_created ) {
		global $wpdb;
		$entry_table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entry_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql                   = "SELECT COUNT(e.entry_id) AS entries_amount,
						  	DATE(e.date_created) AS date_created
							FROM {$entry_table_name} e
							LEFT JOIN {$entry_meta_table_name} m
							ON(e.`entry_id` = m.`entry_id`)
							WHERE e.form_id = %d
							AND e.date_created > %s
							AND m.meta_key = 'skip_form'
							AND m.meta_value = ''
							GROUP BY DATE(e.date_created)
							ORDER BY e.date_created DESC";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			$date_created
		);

		$entry_ids = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	/**
	 * Update Meta
	 *
	 * @since 1.0.6
	 * @since 1.5 : add optional `$date_updated` and `$date_created` arguments
	 *
	 * @param             $meta_id
	 * @param string      $meta_key      - the meta key.
	 * @param bool|object $default_value - the default value.
	 * @param string      $date_updated
	 * @param string      $date_created
	 *
	 * @return bool|string
	 */
	public function update_meta( $meta_id, $meta_key, $default_value = false, $date_updated = '', $date_created = '' ) {
		global $wpdb;

		$updated_meta = array(
			'entry_id'   => $this->entry_id,
			'meta_key'   => $meta_key,
			'meta_value' => $default_value,
		);

		if ( ! empty( $date_updated ) ) {
			$updated_meta['date_updated'] = $date_updated;
		}

		if ( ! empty( $date_created ) ) {
			$updated_meta['date_created'] = $date_created;
		}

		$wpdb->update(
			$this->table_meta_name,
			$updated_meta,
			array(
				'meta_id' => $meta_id,
			)
		);
		$cache_key = get_class( $this );
		wp_cache_delete( $this->entry_id, $cache_key );
		$this->get( $this->entry_id );
	}

	/**
	 * Custom Query entries
	 *
	 * @since 1.5.4
	 *
	 * @param array $args
	 * @param int   $count pass by reference for get count.
	 *
	 * @return Forminator_Form_Entry_Model[]
	 */
	public static function query_entries( $args, &$count ) {
		global $wpdb;

		/**
		 * $args
		 * [
		 *  form_id => X,
		 *  date_created=> array(),
		 *  search = '',
		 *  min_id =>
		 *  max_id =>
		 *  orderby => 'x',
		 *  order => 'DESC',
		 *  per_page => '10'
		 *  offset => 0
		 * ]
		 */

		if ( ! isset( $args['per_page'] ) ) {
			$args['per_page'] = 10;
		}

		if ( ! isset( $args['offset'] ) ) {
			$args['offset'] = 0;
		}

		if ( ! isset( $args['order'] ) ) {
			$args['order'] = 'DESC';
		}

		$entries_table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entries_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		$entries = array();

		// Building where.
		$where = 'WHERE 1=1';
		// exclude Addon meta.
		$where .= $wpdb->prepare( ' AND metas.meta_key NOT LIKE %s', $wpdb->esc_like( 'forminator_addon_' ) . '%' );

		if ( isset( $args['form_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.form_id = %d', $args['form_id'] );
		}

		if ( isset( $args['is_spam'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.is_spam = %s', $args['is_spam'] );
		}

		if ( isset( $args['date_created'] ) ) {
			$date_created = $args['date_created'];
			if ( is_array( $date_created ) && isset( $date_created[0] ) && isset( $date_created[1] ) ) {
				// hack to before nextday.
				// https://app.asana.com/0/385581670491499/864371485201331/f.
				$date_created[1] = $date_created[1] . ' 23:59:00';
				$where          .= $wpdb->prepare( ' AND ( entries.date_created >= %s AND entries.date_created <= %s )', $date_created[0], $date_created[1] );
			}
		}

		if ( isset( $args['user_status'] ) ) {
			require_once __DIR__ . '/../modules/custom-forms/user/class-forminator-cform-user-signups.php';
			Forminator_CForm_User_Signups::prep_signups_functionality();
			$ids = $wpdb->get_col( "SELECT entries.entry_id FROM {$entries_meta_table_name} entries INNER JOIN {$wpdb->base_prefix}signups AS `signups` ON (entries.meta_value = `signups`.activation_key) WHERE entries.meta_key='activation_key' AND `signups`.active = 0" );
			$not = 'approved' === $args['user_status'] ? ' NOT' : '';
			if ( $ids ) {
				$where .= " AND entries.entry_id {$not} IN(" . implode( ', ', $ids ) . ')';
			}
		}

		if ( isset( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND metas.meta_value LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' );
		}

		if ( isset( $args['min_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id >= %d', $args['min_id'] );
		}

		if ( isset( $args['max_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id <= %d', $args['max_id'] );
		}

		if ( isset( $args['entry_status'] ) && 'completed' === $args['entry_status'] ) {
			$where .= $wpdb->prepare( ' AND ( entries.draft_id IS NULL OR entries.draft_id = %s )', '' );
		}

		if ( isset( $args['entry_status'] ) && 'draft' === $args['entry_status'] ) {
			$where .= $wpdb->prepare( ' AND entries.draft_id IS NOT NULL AND entries.draft_id != %s', '' );
		}

		/**
		 * Filter where query to be used on query-ing entries
		 *
		 * @since 1.5.4
		 *
		 * @param string $where
		 * @param array  $args
		 */
		$where = apply_filters( 'forminator_query_entries_where', $where, $args );

		// group.
		$group_by = 'GROUP BY entries.entry_id';

		/**
		 * Filter GROUP BY query to be used on query-ing entries
		 *
		 * @since 1.5.4
		 *
		 * @param string $group_by
		 * @param array  $args
		 */
		$group_by = apply_filters( 'forminator_query_entries_group_by', $group_by, $args );

		// order.
		$order_by = 'ORDER BY entries.entry_id';
		if ( isset( $args['order_by'] ) ) {
			$order_by = 'ORDER BY ' . $args['order_by']; // unesacaped.
		}

		/**
		 * Filter ORDER BY query to be used on query-ing entries
		 *
		 * @since 1.5.4
		 *
		 * @param string $order_by
		 * @param array  $args
		 */
		$order_by = apply_filters( 'forminator_query_entries_order_by', $order_by, $args );

		$order = $args['order'];

		/**
		 * Filter order (DESC/ASC) query to be used on query-ing entries
		 *
		 * @since 1.5.4
		 *
		 * @param string $order
		 * @param array  $args
		 */
		$order = apply_filters( 'forminator_query_entries_order', $order, $args );

		// limit.
		$limit = $wpdb->prepare( 'LIMIT %d, %d', $args['offset'], $args['per_page'] );

		/**
		 * Filter LIMIT query to be used on query-ing entries
		 *
		 * @since 1.5.4
		 *
		 * @param string $order
		 * @param array  $args
		 */
		$limit = apply_filters( 'forminator_query_entries_limit', $limit, $args );

		// sql count.
		$sql_count
			= "SELECT count(DISTINCT entries.entry_id) as total_entries
				FROM
  				{$entries_table_name} AS entries
  				INNER JOIN {$entries_meta_table_name} AS metas
    			ON (entries.entry_id = metas.entry_id)
    			{$where}
    			";

		$sql_count = apply_filters( 'forminator_query_entries_sql_count', $sql_count, $args );
		$count     = intval( $wpdb->get_var( $sql_count ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $count > 0 ) {
			// sql.
			$sql
				= "SELECT entries.entry_id AS entry_id
				FROM
  				{$entries_table_name} AS entries
  				INNER JOIN {$entries_meta_table_name} AS metas
    			ON (entries.entry_id = metas.entry_id)
    			{$where}
    			{$group_by}
    			{$order_by} {$order}
    			{$limit}
    			";

			$sql     = apply_filters( 'forminator_query_entries_sql', $sql, $args );
			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $results as $result ) {
				$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
			}
		}

		return $entries;
	}

	/**
	 * Count entries of form select key and value
	 *
	 * @since 1.7
	 *
	 * @param int    $form_id - the form id.
	 * @param string $field_name - the field name.
	 * @param string $field_value - the field value.
	 * @param string $type - type.
	 *
	 * @return int - total entries
	 */
	public static function select_count_entries_by_meta_field( $form_id, $field_name, $field_value, $field_label, $type = 'select' ) {
		global $wpdb;

		if ( 'select' === $type ) {
			$condition = $wpdb->prepare( " AND ( m.`meta_value` = '%s' OR m.`meta_value` = '%s' )", $field_value, $field_label );
		} else {
			// todo: change this condition to check if it's multiple one - do this, otherwise do the previous code block.
			$condition = $wpdb->prepare(
				" AND ( m.`meta_value` LIKE '%s' OR m.`meta_value` LIKE '%s' )",
				'%' . $field_value . '%',
				'%' . $field_label . '%'
			);
		}

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT count(m.`meta_id`) FROM {$table_name} m
								LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`)
								WHERE e.`form_id` = %d
								AND m.`meta_key` = '%s'
								{$condition}
								AND ( e.draft_id IS NULL OR e.draft_id = '' )
								AND e.`is_spam` = 0";
		$entries          = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $field_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $entries ) {
			return $entries;
		}

		return 0;
	}

	/**
	 * Load entry by draft_id
	 *
	 * @since 1.17.0
	 *
	 * @param int $draft_id - the draft id.
	 *
	 * @return bool|mixed
	 */
	public function get_entry_id_by_draft_id( $draft_id ) {
		global $wpdb;

		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `draft_id` = %s";
		$entry      = $wpdb->get_row( $wpdb->prepare( $sql, $draft_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_object( $entry ) ? $entry->entry_id : null;
	}

	/**
	 * Delete previous draft
	 *
	 * @since 1.17.0
	 *
	 * @param string $previous_draft - draft ID of previously saved draft.
	 *
	 */
	public function delete_previous_draft( $previous_draft ) {
		if ( ! is_null( $previous_draft ) ) {
			$entry_id = $this->get_entry_id_by_draft_id( $previous_draft );
			self::delete_by_entry( $entry_id );
		}
	}
}
