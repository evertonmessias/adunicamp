<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Date
 *
 * @since 1.0
 */
class Forminator_Date extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'date';

	/**
	 * @var int
	 */
	public $position = 12;

	/**
	 * @var string
	 */
	public $type = 'date';

	/**
	 * @var string
	 */
	public $options = array();

	/**
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-calendar';

	/**
	 * Forminator_Date constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Datepicker', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_type'        => 'picker',
			'date_format'       => 'mm/dd/yy',
			'default_date'      => 'none',
			'field_label'       => __( 'Date', 'forminator' ),
			'placeholder'       => __( 'Choose Date', 'forminator' ),
			'icon'              => 'true',
			'day_label'         => __( 'Day', 'forminator' ),
			'day_placeholder'   => __( 'E.g. 01', 'forminator' ),
			'month_label'       => __( 'Month', 'forminator' ),
			'month_placeholder' => __( 'E.g. 01', 'forminator' ),
			'year_label'        => __( 'Year', 'forminator' ),
			'year_placeholder'  => __( 'E.g. 2000', 'forminator' ),
			'restrict_message'  => __( 'Please select one of the available dates.', 'forminator' ),
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		$providers = apply_filters( 'forminator_field_' . $this->slug . '_autofill', array(), $this->slug );

		// TODO: support for multiple field date.
		$autofill_settings = array(
			'date' => array(
				'values' => forminator_build_autofill_providers( $providers ),
			),
		);

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param $field
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function markup( $field, $settings = array(), $draft_value = null ) {

		$this->field = $field;

		$html            = '';
		$design          = $this->get_form_style( $settings );
		$id              = self::get_property( 'element_id', $field );
		$name            = $id;
		$required        = self::get_property( 'required', $field, false );
		$placeholder     = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$label           = $this->sanitize_value( self::get_property( 'field_label', $field ) );
		$description     = $this->sanitize_value( self::get_property( 'description', $field ) );
		$type            = trim( self::get_property( 'field_type', $field ) );
		$has_icon        = self::get_property( 'icon', $field, false, 'bool' );
		$has_icon        = filter_var( $has_icon, FILTER_VALIDATE_BOOLEAN );
		$date_format     = esc_html( self::get_property( 'date_format', $field, 'm/d/Y' ) );
		$start_date_type = self::get_property( 'start-date', $field, '' );
		$end_date_type   = self::get_property( 'end-date', $field, '' );
		$start_of_week   = self::get_property( 'start_of_week', $field, get_option( 'start_of_week' ) );
		$disabled_dates  = self::get_property( 'disabled-dates', $field, array() );
		$disabled_range  = self::get_property( 'disable-date-range', $field, array() );
		$uniq_id		 = '_' . Forminator_CForm_Front::$uid;

		if ( false !== strpos( $date_format, '-' ) ) {
			$sep = '-';
		} elseif ( false !== strpos( $date_format, '.' ) ) {
			$sep = '.';
		} else {
			$sep = '/';
		}
		$formats = explode( $sep, $date_format );

		$min_year = esc_html( self::get_property( 'min_year', $field, date( 'Y' ) - 100 ) );
		$max_year = esc_html( self::get_property( 'max_year', $field, date( 'Y' ) + 100 ) );

		$prefill         = false;
		$is_prefil_valid = false;

		// Check if Pre-fill parameter used.
		if ( $this->has_prefill( $field ) ) {
			// We have pre-fill parameter, use its value or $value.
			$prefill = $this->get_prefill( $field, $prefill );
		}

		$parsed_date = self::parse_date( $prefill, $date_format );
		if ( $parsed_date && $this->check_date( $parsed_date['month'], $parsed_date['day'], $parsed_date['year'] ) ) {
			$is_prefil_valid = true;
		}

		if ( 'picker' === $type ) {
			$html .= '<div class="forminator-field">';
		}

		if ( 'picker' === $type ) {

			$restrict      = array();
			$restrict_type = self::get_property( 'howto-restrict', $field );

			if ( 'week' === $restrict_type ) {

				$days = forminator_week_days();
				$i    = 0;

				foreach ( $days as $k => $day ) {

					if ( ! self::get_property( $k, $field ) ) {
						$restrict[] = $i;
					}

					$i ++;
				}
			} elseif ( 'custom' === $restrict_type ) {
				$dates         = self::get_property( 'date_multiple', $field );
				$default_value = '';
				$default_date  = esc_html( self::get_property( 'default_date', $field, false ) );

				if ( ! empty( $dates ) ) {
					foreach ( $dates as $k => $date ) {
						$restrict[] = $date['value'];
					}
				}

				if ( 'custom' === $default_date ) {
					$default_date_value = esc_html( self::get_property( 'date', $field, '' ) );
					$default_value      = $default_date_value;
				}
			}

			$default_value = '';
			$default_date  = self::get_property( 'default_date', $field, false );

			if ( 'today' === $default_date ) {
				$datepicker_format = $this->normalize_date_format( $date_format );
				$default_value     = current_time( $datepicker_format );
			}

			if ( 'custom' === $default_date ) {
				$default_date_value = self::get_property( 'date', $field, '' );
				$default_value      = $default_date_value;
			}

			if ( $is_prefil_valid ) {
				$default_value = $prefill;
			}

			$past_dates = self::get_property( 'past_dates', $field );
			$past_dates = '' !== $default_value && 'disable' === $past_dates ? 'disable' : 'enable';

			$start_date       = '';
			$end_date         = '';
			$start_date_field = '';
			$end_date_field   = '';
			$start_offset     = '';
			$end_offset       = '';
			if ( ! empty( $start_date_type ) ) {
				if ( 'specific' === $start_date_type ) {
					$start_date = self::get_property( 'start-specific-date', $field, '' );
				} else {
					$start_offset_operator = self::get_property( 'start-offset-operator', $field, '+' );
					$start_offset_value    = self::get_property( 'start-offset-value', $field, '0' );
					$start_offset_duration = self::get_property( 'start-offset-duration', $field, 'days' );
					if ( 'today' === $start_date_type ) {
						$start_date = date_i18n( 'Y-m-d', strtotime( $start_offset_operator . $start_offset_value . ' ' . $start_offset_duration, current_time( 'U' ) ) );
					} else {
						$start_date_field = $start_date_type;
						$start_offset     = $start_offset_operator . '_' . $start_offset_value . '_' . $start_offset_duration;
					}
				}
			}
			if ( ! empty( $end_date_type ) ) {
				if ( 'specific' === $end_date_type ) {
					$end_date = self::get_property( 'end-specific-date', $field, '' );
				} else {
					$end_offset_operator = self::get_property( 'end-offset-operator', $field, '+' );
					$end_offset_value    = self::get_property( 'end-offset-value', $field, '0' );
					$end_offset_duration = self::get_property( 'end-offset-duration', $field, 'days' );
					if ( 'today' === $end_date_type ) {
						$end_date = date_i18n( 'Y-m-d', strtotime( $end_offset_operator . $end_offset_value . ' ' . $end_offset_duration, current_time( 'U' ) ) );
					} else {
						$end_date_field = $end_date_type;
						$end_offset     = $end_offset_operator . '_' . $end_offset_value . '_' . $end_offset_duration;
					}
				}
			}

			$icon_markup = array(
				'<div class="forminator-input-with-icon">',
				'</div>',
				'calendar',
			);

			if ( ! empty( $start_date ) ) {
				$min_year = date( 'Y', strtotime( $start_date ) );
			}

			if ( ! empty( $end_date ) ) {
				$max_year = date( 'Y', strtotime( $end_date ) );
			}

			if ( isset( $draft_value['value'] ) ) {
				$default_value = $draft_value['value'];
			}

			$html .= self::create_input(
				array(
					'autocomplete'       => 'off',
					'type'               => 'text',
					'size'               => 1,
					'name'               => $name,
					'value'              => $default_value,
					'placeholder'        => $placeholder,
					'id'                 => 'forminator-field-' . $id . '-picker-' . $uniq_id,
					'class'              => 'forminator-input forminator-datepicker',
					'data-required'      => $required,
					'data-format'        => $date_format,
					'data-restrict-type' => $restrict_type,
					'data-restrict'      => implode( ',', $restrict ),
					'data-start-year'    => $min_year,
					'data-end-year'      => $max_year,
					'data-past-dates'    => $past_dates,
					'data-start-of-week' => $start_of_week,
					'data-start-date'    => $start_date,
					'data-end-date'      => $end_date,
					'data-start-field'   => $start_date_field,
					'data-end-field'     => $end_date_field,
					'data-start-offset'  => $start_offset,
					'data-end-offset'    => $end_offset,
					'data-disable-date'  => implode( ',', $disabled_dates ),
					'data-disable-range' => implode( ',', $disabled_range ),
				),
				$label,
				$description,
				$required,
				$design,
				$has_icon ? $icon_markup : ''
			);

		} elseif ( 'select' === $type ) {

			if ( ! empty( $label ) ) {

				if ( $required ) {

					$html .= sprintf(
						'<label for="%s" class="forminator-label">%s %s</label>',
						'forminator-field-' . $id,
						$label,
						forminator_get_required_icon()
					);

				} else {

					$html .= sprintf(
						'<label for="%s" class="forminator-label">%s</label>',
						'forminator-field-' . $id,
						$label
					);

				}

				// Mark day, month and year required markup as false.
				$required = false;

			}

			$default_date       = esc_html( self::get_property( 'default_date', $field, false ) );
			$default_date_value = esc_html( self::get_property( 'date', $field, '' ) );

			if ( $is_prefil_valid ) {
				$default_value = $prefill;
			}

			if ( isset( $draft_value['value'] ) ) {
				$parsed_date = $draft_value['value'];
				$day   = $parsed_date['day'];
				$month = $parsed_date['month'];
				$year  = $parsed_date['year'];
			} elseif ( $is_prefil_valid ) {
				$day   = $parsed_date['day'];
				$month = $parsed_date['month'];
				$year  = $parsed_date['year'];
			} elseif ( 'today' === $default_date ) {
				list( $day, $month, $year ) = explode( ' ', current_time( 'j n Y' ) );
			} elseif ( 'custom' === $default_date && ! empty( $default_date_value ) ) {
				$day   = date( 'j', strtotime( $default_date_value ) );
				$month = date( 'n', strtotime( $default_date_value ) );
				$year  = date( 'Y', strtotime( $default_date_value ) );
			} else {
				$day   = '';
				$month = '';
				$year  = date( 'Y' );
			}

			// START: Row.
			$html .= '<div class="forminator-date-select">';

			$html .= '<div class="forminator-row" data-multiple="true">';

			foreach ( $formats as $format ) {

				switch ( $format ) {

					case 'dd':
						$day_id = $id . '-day';
						$html  .= '<div id="' . $day_id . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$day_data = array(
							'name'        => $id . '-day',
							'id'          => 'forminator-form-' . $settings['form_id'] . '__field--' . $id . '-day' . $uniq_id,
							'class'       => 'forminator-select2',
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'day_label', $field );

							if ( ! empty( $label ) ) {
								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$day_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_select(
								$day_data,
								false,
								$this->get_day(),
								$day
							);

						} else {

							$html .= self::create_select(
								$day_data,
								$this->sanitize_value( self::get_property( 'day_label', $field ) ),
								$this->get_day(),
								$day
							);
						}

						$html .= '</div>';

						$html .= '</div>';

						break;

					case 'mm':
						$month_id = $id . '-month';
						$html    .= '<div id="' . $month_id . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$month_data = array(
							'name'        => $id . '-month',
							'id'          => 'forminator-form-' . $settings['form_id'] . '__field--' . $id . '-month' . $uniq_id,
							'class'       => 'forminator-select2',
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'month_label', $field );

							if ( ! empty( $label ) ) {
								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$month_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_select(
								$month_data,
								false,
								$this->get_months(),
								$month
							);

						} else {

							$html .= self::create_select(
								$month_data,
								$this->sanitize_value( self::get_property( 'month_label', $field ) ),
								$this->get_months(),
								$month
							);
						}

						$html .= '</div>';

						$html .= '</div>';

						break;

					case 'yy':
						$year_id = $id . '-year';
						$html   .= '<div id="' . $year_id . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$year_data = array(
							'name'        => $id . '-year',
							'id'          => 'forminator-form-' . $settings['form_id'] . '__field--' . $id . '-year' . $uniq_id,
							'class'       => 'forminator-select2',
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'year_label', $field );

							if ( ! empty( $label ) ) {

								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$year_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_select(
								$year_data,
								false,
								$this->get_years( $min_year, $max_year ),
								$year
							);

						} else {

							$html .= self::create_select(
								$year_data,
								$this->sanitize_value( self::get_property( 'year_label', $field ) ),
								$this->get_years( $min_year, $max_year ),
								$year
							);
						}

						$html .= '</div>';

						$html .= '</div>';

						break;
					default:
						break;
				}
			}

			$html .= '</div>';

			// END: Row.
			$html .= '</div>';

			$html .= self::get_description( $description, 'forminator-field-' . $id );

		} elseif ( 'input' === $type ) {
			$day_value = $month_value = $year_value = '';

			if ( isset( $draft_value['value'] ) ) {
				$parsed_date = $draft_value['value'];
				$day_value   = $parsed_date['day'];
				$month_value = $parsed_date['month'];
				$year_value  = $parsed_date['year'];
			} elseif ( $is_prefil_valid ) {
				$day_value   = $parsed_date['day'];
				$month_value = $parsed_date['month'];
				$year_value  = $parsed_date['year'];
			}

			if ( ! empty( $label ) ) {

				if ( $required ) {

					$html .= sprintf(
						'<label for="%s" class="forminator-label">%s %s</label>',
						'forminator-field-' . $id,
						esc_html( $label ),
						forminator_get_required_icon()
					);

				} else {

					$html .= sprintf(
						'<label for="%s" class="forminator-label">%s</label>',
						'forminator-field-' . $id,
						esc_html( $label )
					);
				}
			}

			// START: Row.
			$html .= '<div class="forminator-date-input">';

			$html .= '<div class="forminator-row" data-multiple="true">';

			foreach ( $formats as $format ) {

				switch ( $format ) {

					case 'dd':
						$day   = $id . '-day';
						$html .= '<div id="' . $day . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$day_data = array(
							'type'        => 'number',
							'min'         => 1,
							'max'         => 31,
							'name'        => $id . '-day',
							'value'       => esc_attr( $day_value ),
							'placeholder' => $this->sanitize_value( self::get_property( 'day_placeholder', $field ) ),
							'id'          => 'forminator-field-' . $id . '-day',
							'class'       => 'forminator-input',
							'data-field'  => 'day',
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'day_label', $field );

							if ( ! empty( $label ) ) {

								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$day_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_input(
								$day_data,
								false,
								'',
								$required,
								$design
							);

						} else {

							$html .= self::create_input(
								$day_data,
								$this->sanitize_value( self::get_property( 'day_label', $field ) ),
								'',
								$required,
								$design
							);
						}

						$html .= '</div>';

						$html .= '</div>';

						break;

					case 'mm':
						$month = $id . '-month';
						$html .= '<div id="' . $month . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$month_data = array(
							'type'        => 'number',
							'min'         => 1,
							'max'         => 12,
							'name'        => $id . '-month',
							'value'       => esc_attr( $month_value ),
							'placeholder' => $this->sanitize_value( self::get_property( 'month_placeholder', $field ) ),
							'id'          => 'forminator-field-' . $id . '-month',
							'class'       => 'forminator-input',
							'data-field'  => 'month',
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'month_label', $field );

							if ( ! empty( $label ) ) {

								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$month_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_input(
								$month_data,
								false,
								'',
								$required,
								$design
							);
						} else {
							$html .= self::create_input(
								$month_data,
								$this->sanitize_value( self::get_property( 'month_label', $field ) ),
								'',
								$required,
								$design
							);
						}

						$html .= '</div>';

						$html .= '</div>';

						break;

					case 'yy':
						$year  = $id . '-year';
						$html .= '<div id="' . $year . '" class="forminator-col">';

						$html .= '<div class="forminator-field">';

						$year_data = array(
							'type'        => 'number',
							'min'         => 1,
							'name'        => $id . '-year',
							'placeholder' => $this->sanitize_value( self::get_property( 'year_placeholder', $field ) ),
							'id'          => 'forminator-field-' . $id . '-year',
							'class'       => 'forminator-input',
							'data-field'  => 'year',
							'value'       => esc_attr( $year_value ),
							'data-format' => $date_format,
							'data-parent' => $id,
						);

						if ( $required ) {

							$label = self::get_property( 'year_label', $field );

							if ( ! empty( $label ) ) {
								$html .= sprintf(
									'<label for="%s" class="forminator-label">%s %s</label>',
									$year_data['id'],
									$this->sanitize_value( $label ),
									'<span class="forminator-required">*</span>'
								);
							}

							$html .= self::create_input(
								$year_data,
								false,
								'',
								$required,
								$design
							);

						} else {

							$html .= self::create_input(
								$year_data,
								$this->sanitize_value( self::get_property( 'year_label', $field ) ),
								'',
								$required,
								$design
							);
						}

						$html .= '</div>';

						$html .= '</div>';
						break;

					default:
						break;
				}
			}

			$html .= '</div>';

			// END: Row.
			$html .= '</div>';

			$html .= self::get_description( $description, 'forminator-field-' . $id );
		}

		if ( 'picker' === $type ) {
			$html .= '</div>';
		}

		return apply_filters( 'forminator_field_date_markup', $html, $field, $this );
	}

	/**
	 * Return modified date format
	 *
	 * @since 1.7.0.1
	 *
	 * @param string $date_format
	 *
	 * @return string
	 */
	public function normalize_date_format( $date_format ) {
		$date_format = str_replace( 'dd', 'd', $date_format );
		$date_format = str_replace( 'mm', 'm', $date_format );
		$date_format = str_replace( 'yy', 'Y', $date_format );

		return $date_format;
	}

	/**
	 * Return all years between two dates
	 *
	 * @since 1.0
	 *
	 * @param string $min_year
	 * @param string $max_year
	 *
	 * @return array
	 */
	public function get_years( $min_year = '', $max_year = '' ) {
		$array = array();
		$year  = intval( date( 'Y' ) );
		$end   = empty( $min_year ) ? $year - 100 : intval( $min_year ) - 1;
		$start = empty( $max_year ) ? $year + 100 : intval( $max_year );
		for ( $i = $start; $i > $end; $i -- ) {
			$array[] = array(
				'label' => $i,
				'value' => $i,
			);
		}

		array_unshift(
			$array,
			array(
				'label' => esc_html__( 'Select Year', 'forminator' ),
				'value' => '',
			)
		);

		return apply_filters( 'forminator_field_date_get_years', $array, $min_year, $max_year, $year, $start, $end, $this );
	}

	/**
	 * Return monts
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_months() {
		$array = array();
		for ( $i = 1; $i < 13; $i ++ ) {
			$array[] = array(
				'label' => $i,
				'value' => $i,
			);
		}

		array_unshift(
			$array,
			array(
				'label' => esc_html__( 'Select month', 'forminator' ),
				'value' => '',
			)
		);

		return apply_filters( 'forminator_field_date_get_months', $array, $this );
	}

	/**
	 * Return days
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_day() {
		$array = array();
		for ( $i = 1; $i < 32; $i ++ ) {
			$array[] = array(
				'label' => $i,
				'value' => $i,
			);
		}
		array_unshift(
			$array,
			array(
				'label' => esc_html__( 'Select day', 'forminator' ),
				'value' => '',
			)
		);

		return apply_filters( 'forminator_field_date_get_day', $array, $this );
	}

	/**
	 * Parse date
	 *
	 * @since 1.0
	 *
	 * @param string|array $date - the date to be parsed.
	 * @param string       $format - the data format.
	 *
	 * @return array
	 */
	public static function parse_date( $date, $format = 'yy-mm-dd' ) {
		$date_info = array(
			'year'  => 0,
			'month' => 0,
			'day'   => 0,
		);

		$position = substr( $format, 0, 8 );

		if ( is_array( $date ) ) {

			switch ( $position ) {
				case 'mm/dd/yy':
				case 'mm-dd-yy':
				case 'mm.dd.yy':
					$date_info['month'] = isset( $date['month'] ) ? $date['month'] : 0;
					$date_info['day']   = isset( $date['day'] ) ? $date['day'] : 0;
					$date_info['year']  = isset( $date['year'] ) ? $date['year'] : 0;
					break;
				case 'dd/mm/yy':
				case 'dd-mm-yy':
				case 'dd.mm.yy':
					$date_info['day']   = isset( $date['day'] ) ? $date['day'] : 0;
					$date_info['month'] = isset( $date['month'] ) ? $date['month'] : 0;
					$date_info['year']  = isset( $date['year'] ) ? $date['year'] : 0;
					break;
				case 'yy-mm-dd':
				case 'yy/mm/dd':
				case 'yy.mm.dd':
					$date_info['year']  = isset( $date['year'] ) ? $date['year'] : 0;
					$date_info['month'] = isset( $date['month'] ) ? $date['month'] : 0;
					$date_info['day']   = isset( $date['day'] ) ? $date['day'] : 0;
					break;

				default:
					break;
			}

			return apply_filters( 'forminator_field_date_parse_dates', $date_info, $date, $format );
		}

		$date = preg_replace( '|[/\.]|', '-', $date );
		if ( 'mm/dd/yy' === $position || 'mm-dd-yy' === $position || 'mm.dd.yy' === $position ) {
			if ( preg_match( '/^(\d{1,2})-(\d{1,2})-(\d{1,4})$/', $date, $matches ) ) {
				$date_info['month'] = $matches[1];
				$date_info['day']   = $matches[2];
				$date_info['year']  = $matches[3];
			}
		} elseif ( 'dd/mm/yy' === $position || 'dd-mm-yy' === $position || 'dd.mm.yy' === $position ) {
			if ( preg_match( '/^(\d{1,2})-(\d{1,2})-(\d{1,4})$/', $date, $matches ) ) {
				$date_info['day']   = $matches[1];
				$date_info['month'] = $matches[2];
				$date_info['year']  = $matches[3];
			}
		} elseif ( 'yy-mm-dd' === $position || 'yy/mm/dd' === $position || 'yy.mm.dd' === $position ) {
			if ( preg_match( '/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/', $date, $matches ) ) {
				$date_info['year']  = $matches[1];
				$date_info['month'] = $matches[2];
				$date_info['day']   = $matches[3];
			}
		}

		return apply_filters( 'forminator_field_date_parse_dates', $date_info, $date, $format );
	}

	/**
	 * Check data
	 *
	 * @since 1.0
	 *
	 * @param int $month - the month.
	 * @param int $day - the day.
	 * @param int $year - the year.
	 *
	 * @return bool
	 */
	public function check_date( $month, $day, $year ) {
		if ( empty( $month ) || ! is_numeric( $month ) || empty( $day ) || ! is_numeric( $day )
			 || empty( $year )
			 || ! is_numeric( $year )
			 || 4 !== strlen( $year ) ) {
			return false;
		}

		return checkdate( $month, $day, $year );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$type        = trim( self::get_property( 'field_type', $field ) );
		$date_format = self::get_property( 'date_format', $field );
		$rules       = '';

		if ( 'picker' === $type ) {
			$rules .= '"' . $this->get_id( $field ) . '": {' . "\n";
			if ( $this->is_required( $field ) ) {
				$rules .= '"required": true,';
			}

			$rules .= '"dateformat": "' . $date_format . '",';
			$rules .= '},' . "\n";
		} else {
			if ( $this->is_required( $field ) ) {
				$rules .= '"' . $this->get_id( $field ) . '-day": "required",';
				$rules .= '"' . $this->get_id( $field ) . '-month": "required",';
				$rules .= '"' . $this->get_id( $field ) . '-year": "required",';
			}
		}

		return apply_filters( 'forminator_field_date_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field                       = $this->field;
		$type                        = trim( self::get_property( 'field_type', $field ) );
		$date_format                 = self::get_property( 'date_format', $field );
		$required_validation_message = self::get_property( 'required_message', $field, '' );
		$month_label                 = self::get_property( 'month_label', $field, '' );
		$day_label                   = self::get_property( 'day_label', $field, '' );
		$year_label                  = self::get_property( 'year_label', $field, '' );

		if ( empty( $required_validation_message ) ) {
			if ( 'picker' === $type ) {
				$required_validation_message = __( 'This field is required.', 'forminator' );
			} else {
				$required_validation_message = ' ' . __( 'field is required.', 'forminator' );
			}
		}

		$messages = '';
		if ( 'picker' === $type ) {
			$messages = '"' . $this->get_id( $field ) . '": {' . "\n";
			if ( $this->is_required( $field ) ) {
				$required_validation_message = apply_filters(
					'forminator_field_date_required_validation_message',
					$required_validation_message,
					$field,
					$type,
					$date_format,
					$this
				);
				$messages                   .= '"required": "' . forminator_addcslashes( $required_validation_message ) . '",' . "\n";
			}

			$format_validation_message = apply_filters(
				'forminator_field_date_format_validation_message',
				__( 'Not valid date', 'forminator' ),
				$field,
				$type,
				$date_format,
				$this
			);

			$messages .= '"dateformat": "' . forminator_addcslashes( $format_validation_message ) . '",' . "\n";
			$messages .= '},' . "\n";
		} else {
			if ( $this->is_required( $field ) ) {
				$day_validation_message = apply_filters(
					'forminator_field_date_day_validation_message',
					$required_validation_message,
					$field,
					$type,
					$date_format,
					$this
				);
				$messages               = '"' . $this->get_id( $field ) . '-day": "<strong>' . $day_label . '</strong> ' . forminator_addcslashes( $day_validation_message ) . '",' . "\n";

				$month_validation_message = apply_filters(
					'forminator_field_date_month_validation_message',
					$required_validation_message,
					$field,
					$type,
					$date_format,
					$this
				);
				$messages                .= '"' . $this->get_id( $field ) . '-month": "<strong>' . $month_label . '</strong> ' . forminator_addcslashes( $month_validation_message ) . '",' . "\n";

				$year_validation_message = apply_filters(
					'forminator_field_date_year_validation_message',
					$required_validation_message,
					$field,
					$type,
					$date_format,
					$this
				);
				$messages               .= '"' . $this->get_id( $field ) . '-year": "<strong>' . $year_label . '</strong> ' . forminator_addcslashes( $year_validation_message ) . '",' . "\n";
			}
		}

		return apply_filters( 'forminator_field_date_validation_message', $messages, $field, $type, $date_format, $this );
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 *
	 * @param array        $field
	 * @param array|string $data
	 */
	public function validate( $field, $data ) {
		$id              = self::get_property( 'element_id', $field );
		$start_date_type = self::get_property( 'start-date', $field, '' );
		$end_date_type   = self::get_property( 'end-date', $field, '' );
		$date_type       = self::get_property( 'field_type', $field, 'picker' );
		$disabled_dates  = self::get_property( 'disabled-dates', $field, array() );
		$disabled_range  = self::get_property( 'disable-date-range', $field, array() );
		$restrict_type   = self::get_property( 'howto-restrict', $field );

		if ( $this->is_required( $field ) ) {
			$required_validation_message = self::get_property( 'required_message', $field, __( 'This field is required. Please enter a valid date.', 'forminator' ) );
			if ( empty( $data ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_date_required_field_validation_message',
					$required_validation_message,
					$id,
					$data,
					$this
				);

				return;
			} elseif ( 'picker' !== $date_type ) {
				$month = $data['month'];
				$day   = $data['day'];
				$year  = $data['year'];

				if ( empty( $month ) ) {
					$month_id = $id . '-month';

					$this->validation_message[ $month_id ] = apply_filters(
						'forminator_field_date_required_field_validation_message',
						__( 'Month field is required.', 'forminator' ),
						$month_id,
						$data,
						$this
					);
				}

				if ( empty( $day ) ) {
					$day_id = $id . '-day';

					$this->validation_message[ $day_id ] = apply_filters(
						'forminator_field_date_required_field_validation_message',
						__( 'Day field is required.', 'forminator' ),
						$day_id,
						$data,
						$this
					);
				}

				if ( empty( $year ) ) {
					$year_id = $id . '-year';

					$this->validation_message[ $year_id ] = apply_filters(
						'forminator_field_date_required_field_validation_message',
						__( 'Year field is required.', 'forminator' ),
						$year_id,
						$data,
						$this
					);
				}
			}
		}

		if ( empty( $data ) ) {
			return;
		}

		if ( isset( $data['year'] ) && isset( $data['day'] ) && isset( $data['month'] )
				&& empty( $data['year'] ) && empty( $data['day'] ) && empty( $data['month'] ) ) {
			return;
		}

		// Always! (we dont have validate flag on builder) validate date_format.
		$date_format = self::get_property( 'date_format', $field );
		$date        = self::parse_date( $data, $date_format );
		$month = $date['month'];
		$day   = $date['day'];
		$year  = $date['year'];

		// strtotime does not recognize all of our date formats so we need to convert all dates to 1 accepted format before processing.
		if ( 'Y-m-d' !== datepicker_default_format( $date_format ) && ! is_array( $data ) ) {
			$format_date = date_create_from_format( datepicker_default_format( $date_format ), $data );
			if ( $format_date ) {
				$data = date_format( $format_date, 'Y-m-d' );
			} else {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_date_valid_date_validation_message',
					__( 'Please enter a valid date.', 'forminator' ),
					$id,
					$data,
					$date_format,
					$this
				);
				return;
			}
		}

		if (
			'select' !== $date_type &&
			! $this->check_date( $date['month'], $date['day'], $date['year'] )
		) {


			$year_id = $id . '-year';
			if ( strlen( $date['year'] ) !== 4 && 'picker' !== $date_type ) {

				$this->validation_message[ $year_id ] = apply_filters(
					'forminator_field_date_valid_year_validation_message',
					__( 'Year field is invalid.', 'forminator' ),
					$year_id,
					$data,
					$date_format,
					$this
				);

			} else {

				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_date_valid_date_validation_message',
					__( 'Please enter a valid date.', 'forminator' ),
					$id,
					$data,
					$date_format,
					$this
				);

			}

		} else {

			if ( 'select' === $date_type ) {

				// For year limits.
				$min_year = self::get_property( 'min_year', $field, date( 'Y' ) - 100 );
				$max_year = self::get_property( 'max_year', $field, date( 'Y' ) + 100 );
				$year     = intval( $date['year'] );
				if ( ! empty( $min_year ) && ! empty( $max_year ) ) {
					if ( $year < $min_year || $year > $max_year ) {
						$this->validation_message[ $id ] = apply_filters(
							'forminator_field_date_valid_maxmin_year_validation_message',
							__( 'Please select a valid year.', 'forminator' )
						);
					}
				} else {
					if ( ! empty( $min_year ) ) {
						if ( $year < $min_year ) {
							$this->validation_message[ $id ] = apply_filters(
								'forminator_field_date_valid_maxmin_year_validation_message',
								__( 'Please select a valid year.', 'forminator' )
							);
						}
					}
					if ( ! empty( $max_year ) ) {
						if ( $year > $max_year ) {
							$this->validation_message[ $id ] = apply_filters(
								'forminator_field_date_valid_maxmin_year_validation_message',
								__( 'Please select a valid year.', 'forminator' )
							);
						}
					}
				}

				/*
				 * For empty day or month.
				 * We are allowing year to be filled because it defaults to current year.
				 * In front-action.php (get_fields_info), we will remove the date submission data if only year has value coz its the default value.
				*/
				if (
					( ! empty( $date['month'] ) || ! empty( $date['day'] ) ) &&
					! $this->check_date( $date['month'], $date['day'], $date['year'] )
				) {

					if ( empty( $month ) ) {
						$month_id = $id . '-month';

						$this->validation_message[ $month_id ] = apply_filters(
							'forminator_field_date_required_field_validation_message',
							__( 'Month field is invalid.', 'forminator' ),
							$month_id,
							$data,
							$this
						);
					}

					if ( empty( $day ) ) {
						$day_id = $id . '-day';

						$this->validation_message[ $day_id ] = apply_filters(
							'forminator_field_date_required_field_validation_message',
							__( 'Day field is invalid.', 'forminator' ),
							$day_id,
							$data,
							$this
						);
					}

					if ( empty( $year ) ) {
						$year_id = $id . '-year';

						$this->validation_message[ $year_id ] = apply_filters(
							'forminator_field_date_required_field_validation_message',
							__( 'Year field is invalid.', 'forminator' ),
							$year_id,
							$data,
							$this
						);
					}
				}
			}

			if ( 'picker' === $date_type ) {

				$custom_form   = forminator_get_model_from_id( Forminator_Front_Action::$module_id );
				$selected_date = preg_replace( '/(\d+)\D+(\d+)\D+(\d+)/', '$1/$2/$3', $data );

				if ( 'week' === $restrict_type ) {
					$restrict = array();
					$days     = forminator_week_days();

					foreach ( $days as $k => $day ) {

						if ( ! self::get_property( $k, $field ) ) {
							$restrict[] = $k;
						}
					}
					if ( ! empty( $restrict ) ) {
						$current_day = date( 'l', strtotime( $selected_date ) );
						if ( in_array( strtolower( $current_day ), $restrict, true ) ) {
							$this->validation_message[ $id ] = apply_filters(
								'forminator_field_date_valid_between_date_validation_message',
								self::get_property( 'restrict_message', $field, __( 'Please select one of the available dates.', 'forminator' ) )
							);
						}
					}
				}
				if ( ! empty( $start_date_type ) ) {
					if ( 'specific' === $start_date_type ) {
						$start_date = self::get_property( 'start-specific-date', $field, '' );
					} else {
						$start_offset_operator = self::get_property( 'start-offset-operator', $field, '+' );
						$start_offset_value    = self::get_property( 'start-offset-value', $field, '0' );
						$start_offset_duration = self::get_property( 'start-offset-duration', $field, 'days' );
						if ( 'today' === $start_date_type ) {
							$start_date = date_i18n( 'Y-m-d', strtotime( $start_offset_operator . $start_offset_value . ' ' . $start_offset_duration, current_time( 'U' ) ) );
						} else {
							$start_date_value = isset( Forminator_CForm_Front_Action::$prepared_data[ $start_date_type ] )
								? Forminator_CForm_Front_Action::$prepared_data[ $start_date_type ] : '';
							$start_date       = '';
							if ( ! empty( $start_date_value ) ) {
								$start_date_field  = $custom_form->get_field( $start_date_type, true );
								$start_date_format = self::get_property( 'date_format', $start_date_field );
								$start_date_format = date_create_from_format( datepicker_default_format( $start_date_format ), $start_date_value );
								$start_date_value  = date_format( $start_date_format, 'Y-m-d' );
								$start_date        = date_i18n( 'Y-m-d', strtotime( $start_date_value . ' ' . $start_offset_operator . $start_offset_value . ' ' . $start_offset_duration ) );
							}
						}
					}
					if ( ! empty( $start_date ) && strtotime( $selected_date ) < strtotime( $start_date ) ) {
						$this->validation_message[ $id ] = apply_filters(
							'forminator_field_date_valid_between_date_validation_message',
							self::get_property( 'restrict_message', $field, __( 'Please select one of the available dates.', 'forminator' ) )
						);
					}
				}
				if ( ! empty( $end_date_type ) ) {
					if ( 'specific' === $end_date_type ) {
						$end_date = self::get_property( 'end-specific-date', $field, '' );
					} else {
						$end_offset_operator = self::get_property( 'end-offset-operator', $field, '+' );
						$end_offset_value    = self::get_property( 'end-offset-value', $field, '0' );
						$end_offset_duration = self::get_property( 'end-offset-duration', $field, 'days' );
						if ( 'today' === $end_date_type ) {
							$end_date = date_i18n( 'Y-m-d', strtotime( $end_offset_operator . $end_offset_value . ' ' . $end_offset_duration, current_time( 'U' ) ) );
						} else {
							$end_date_value = isset( Forminator_CForm_Front_Action::$prepared_data[ $end_date_type ] )
								? Forminator_CForm_Front_Action::$prepared_data[ $end_date_type ] : '';
							$end_date       = '';
							if ( ! empty( $end_date_value ) ) {
								$end_date_field  = $custom_form->get_field( $end_date_type, true );
								$end_date_format = self::get_property( 'date_format', $end_date_field );
								$end_date_format = date_create_from_format( datepicker_default_format( $end_date_format ), $end_date_value );
								$end_date_value  = date_format( $end_date_format, 'Y-m-d' );
								$end_date        = date_i18n( 'Y-m-d', strtotime( $end_date_value . ' ' . $end_offset_operator . $end_offset_value . ' ' . $end_offset_duration ) );
							}
						}
					}
					if ( ! empty( $end_date ) && strtotime( $selected_date ) > strtotime( $end_date ) ) {
						$this->validation_message[ $id ] = apply_filters(
							'forminator_field_date_valid_between_date_validation_message',
							self::get_property( 'restrict_message', $field, __( 'Please select one of the available dates.', 'forminator' ) )
						);
					}
				}

				// Change selected date format to the disabled-date format which is m/d/Y
				$selected_date_format 	 = date_create_from_format( 'Y/m/d', $selected_date );
				$formatted_selected_date = date_format( $selected_date_format, 'm/d/Y' );
				if ( ! empty( $disabled_dates ) && in_array( $formatted_selected_date, $disabled_dates, true ) ) {
					$this->validation_message[ $id ] = apply_filters(
						'forminator_field_date_valid_disabled_validation_message',
						self::get_property( 'restrict_message', $field, __( 'Please select one of the available dates.', 'forminator' ) )
					);
				}

				if ( ! empty( $disabled_range ) ) {
					$has_range = true;
					foreach ( $disabled_range as $range ) {
						$range_arr = array_map( 'trim', explode( '-', $range ) );
						if ( ! empty( $range_arr ) ) {
							$start_date = isset( $range_arr[0] ) ? $range_arr[0] : '';
							$end_date   = isset( $range_arr[1] ) ? $range_arr[1] : '';
							if ( strtotime( $selected_date ) >= strtotime( $start_date ) && strtotime( $selected_date ) <= strtotime( $end_date ) ) {
								$has_range = false;
								continue;
							}
						}
					}
					if ( ! $has_range ) {
						$this->validation_message[ $id ] = apply_filters(
							'forminator_field_date_valid_disabled_validation_message',
							self::get_property( 'restrict_message', $field, __( 'Please select one of the available dates.', 'forminator' ) )
						);
					}
				}
			}
		}
	}


	/**
	 * Sanitize data
	 *
	 * @since 1.0.2
	 *
	 * @param array        $field
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		if ( is_array( $data ) ) {
			$data = forminator_sanitize_array_field( $data );
		} else {
			$data = forminator_sanitize_field( $data );
		}

		return apply_filters( 'forminator_field_date_sanitize', $data, $field, $original_data );
	}
}
