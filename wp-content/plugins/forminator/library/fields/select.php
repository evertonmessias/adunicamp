<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_SingleValue
 *
 * @property  array field
 * @since 1.0
 */
class Forminator_Select extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'select';

	/**
	 * @var string
	 */
	public $type = 'select';

	/**
	 * @var int
	 */
	public $position = 11;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-element-select';

	public $is_calculable = true;

	/**
	 * Forminator_SingleValue constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Select', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'value_type'  => 'single',
			'field_label' => __( 'Select', 'forminator' ),
			'options'     => array(
				array(
					'label' => __( 'Option 1', 'forminator' ),
					'value' => 'one',
					'limit' => '',
					'key'   => forminator_unique_key(),
				),
				array(
					'label' => __( 'Option 2', 'forminator' ),
					'value' => 'two',
					'limit' => '',
					'key'   => forminator_unique_key(),
				),
			),
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

		$autofill_settings = array(
			'select' => array(
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

		$i             = 1;
		$html          = '';
		$id            = self::get_property( 'element_id', $field );
		$name          = $id;
		$uniq_id       = Forminator_CForm_Front::$uid;
		$id            = 'forminator-form-' . $settings['form_id'] . '__field--' . $id . '_' . $uniq_id;
		$required      = self::get_property( 'required', $field, false, 'bool' );
		$options       = self::get_property( 'options', $field, array() );
		$post_value    = self::get_post_data( $name, false );
		$description   = self::get_property( 'description', $field, '' );
		$label         = esc_html( self::get_property( 'field_label', $field, '' ) );
		$design        = $this->get_form_style( $settings );
		$field_type    = self::get_property( 'value_type', $field, '' );
		$search_status = self::get_property( 'search_status', $field, '' );
		$is_limit      = self::get_property( 'limit_status', $field, '' );
		$placeholder   = esc_html( self::get_property( 'placeholder', $field, '' ) );
		$calc_enabled  = self::get_property( 'calculations', $field, false, 'bool' );

		$html .= '<div class="forminator-field">';

		if ( $label ) {
			if ( $required ) {
				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s %s</label>',
					$id . '-label',
					esc_html( $label ),
					forminator_get_required_icon()
				);
			} else {
				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s</label>',
					$id . '-label',
					esc_html( $label )
				);
			}
		}

		if ( $required && empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Please select an option', 'forminator' );
		}

		if ( 'multiselect' === $field_type ) {
			$post_value  = self::get_post_data( $name, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
			$field_name  = $name;
			$name        = $name . '[]';
			$draft_value = isset( $draft_value['value'] ) ? array_map( 'trim', $draft_value['value'] ) : '';

			$html .= '<div class="forminator-multiselect">';

			$option_first_key = '';
			// Multi values.
			$default_arr = array();
			$default     = '';
			foreach ( $options as $key => $option ) {

				$value             = $option['value'] ? esc_html( strip_tags( $option['value'] ) ) : wp_kses_post( strip_tags( $option['label'] ) );
				$label             = $option['label'] ? wp_kses_post( strip_tags( $option['label'] ) ) : '';
				$limit             = ( isset( $option['limit'] ) && $option['limit'] ) ? esc_html( $option['limit'] ) : '';
				$input_id          = $id . '-' . $i;
				$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
				$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? $option['calculation'] : 0.0;
				if ( 0 === $key ) {
					$option_first_key = $value;
				}

				if ( ! empty( $draft_value ) ) {

					if ( in_array( trim( $value ), $draft_value ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$option_default = true;
					}
				} elseif ( $this->has_prefill( $field ) ) {
					// We have pre-fill parameter, use its value or $value.
					$prefill        = $this->get_prefill( $field, false );
					$prefill_values = explode( ',', $prefill );

					if ( in_array( $value, $prefill_values ) ) {
						$option_default = true;
					}
				}

				if ( isset( $is_limit ) && 'enable' === $is_limit && ! empty( $limit ) ) {
					$entries = Forminator_Form_Entry_Model::select_count_entries_by_meta_field(
						$settings['form_id'],
						$field_name,
						$value,
						$label,
						$field_type
					);

					if ( $limit <= $entries ) {
						continue;
					}
				}

				$selected = false;

				if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST !== $post_value ) {
					if ( is_array( $post_value ) ) {
						$selected = in_array( $value, $post_value ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					}
				} else {
					$selected = $option_default;
				}

				if ( $option_default ) {
					$default_arr[] = $value;
				}

				$selected    = $selected ? 'checked="checked"' : '';
				$extra_class = $selected ? ' forminator-is_checked' : '';

				$class = 'forminator-option' . $extra_class;

				$html .= sprintf( '<label for="%s" class="' . $class . '">', $input_id );

				$html .= sprintf(
					'<input type="checkbox" name="%s" value="%s" id="%s" data-calculation="%s" %s />',
					$name,
					$value,
					$input_id,
					$calculation_value,
					$selected
				);

				$html .= wp_kses_post( strip_tags( $option['label'] ) );

				$html .= '</label>';

				$i ++;
			}

			if ( ! empty( $default_arr ) ) {
				$default = json_encode( $default_arr, JSON_FORCE_OBJECT );
			}

			$html .= sprintf(
				"<input type='hidden' name='%s' class='%s' value='%s' />",
				$field_name . '-multiselect-default-values',
				'multiselect-default-values',
				$default
			);

			$html .= '</div>';

		} else {
			$options_markup = '';
			$default        = '';
			$search         = 'false';

			if ( 'enable' === $search_status ) {
				$search = 'true';
			}

			if ( ! empty( $placeholder ) ) {
				$options_markup = sprintf( '<option value="">%s</option>', $placeholder );
			}

			foreach ( $options as $key => $option ) {
				$value             = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( strip_tags( $option['value'] ) ) : '' );
				$label             = $option['label'] ? wp_kses_post( strip_tags( $option['label'] ) ) : '';
				$limit             = ( isset( $option['limit'] ) && $option['limit'] ) ? esc_html( $option['limit'] ) : '';
				$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
				$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? esc_html( $option['calculation'] ) : 0.0;

				if ( isset( $draft_value['value'] ) ) {

					if ( trim( $draft_value['value'] ) === trim( $value ) ) {
						$option_default = true;
					}
				} elseif ( $this->has_prefill( $field ) ) {
					// We have pre-fill parameter, use its value or $value.
					$prefill        = $this->get_prefill( $field, false );
					$prefill_values = explode( ',', $prefill );

					if ( in_array( $value, $prefill_values ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$option_default = true;
					}
				}

				if ( isset( $is_limit ) && 'enable' === $is_limit && ! empty( $limit ) ) {

					$entries = Forminator_Form_Entry_Model::select_count_entries_by_meta_field(
						$settings['form_id'],
						$name,
						$value,
						$label
					);

					if ( $limit <= $entries ) {
						continue;
					}
				}

				if ( $option_default ) {
					$default = $value;
				}

				$selected = ( $value === $post_value || $option_default ) ? 'selected="selected"' : '';

				$options_markup .= sprintf(
					'<option value="%s" %s data-calculation="%s">%s</option>',
					esc_html( $value ),
					$selected,
					esc_html( $calculation_value ),
					wp_kses_post( strip_tags( $option['label'] ) )
				);
			}

			$html .= sprintf(
				'<select id="%s" class="%s" data-required="%s" name="%s" data-default-value="%s" data-placeholder="%s" data-search="%s">',
				$id,
				'forminator-select--field forminator-select2', // class.
				$required,
				$name,
				$default,
				$placeholder,
				$search
			);

			$html .= $options_markup;

			$html .= sprintf( '</select>' );
		}

		$html .= self::get_description( $description );

		$html .= '</div>';

		return apply_filters( 'forminator_field_single_markup', $html, $id, $required, $options );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$rules       = '';
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );
		$field_type  = self::get_property( 'value_type', $field, '' );

		if ( $is_required && 'multiselect' !== $field_type ) {
			$rules .= '"' . $this->get_id( $field ) . '": "required",';
		}

		if ( $is_required && 'multiselect' === $field_type ) {
			$rules .= '"' . $this->get_id( $field ) . '[]": "required",';
		}

		return apply_filters( 'forminator_field_single_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$messages    = '';
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );
		$field_type  = self::get_property( 'value_type', $field, '' );

		if ( $is_required ) {
			$required_message = self::get_property( 'required_message', $field, __( 'This field is required. Please select a value.', 'forminator' ) );
			$required_message = apply_filters(
				'forminator_single_field_required_validation_message',
				$required_message,
				$id,
				$field
			);

			if ( 'multiselect' === $field_type ) {
				$messages .= '"' . $this->get_id( $field ) . '[]": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			} else {
				$messages .= '"' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}
		}

		return $messages;
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
		$select_type  = isset( $field['value_type'] ) ? $field['value_type'] : 'single';
		$id           = self::get_property( 'element_id', $field );
		$value_exists = true;

		if ( is_array( $data ) ) {
			foreach ( $data as $value ) {
				if ( false === array_search( $value, array_column( $field['options'], 'value' ) ) ) {
					$value_exists = false;
					break;
				}
			}
		} elseif ( ! empty( $data ) && false === array_search( $data, array_column( $field['options'], 'value' ) ) ) {
			$value_exists = false;
		}

		if ( ! $value_exists ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_select_field_nonexistent_validation_message',
				__( 'Selected value does not exist.', 'forminator' ),
				$id,
				$field
			);
		}

		if ( $this->is_required( $field ) ) {
			if ( ! isset( $data ) ||
				 ( 'single' === $select_type && ! strlen( $data ) ) ||
				 ( 'multiselect' === $select_type && empty( $data ) )
			) {
				$required_message                = self::get_property( 'required_message', $field, __( 'This field is required. Please select a value.', 'forminator' ) );
				$this->validation_message[ $id ] = apply_filters(
					'forminator_single_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
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

		return apply_filters( 'forminator_field_single_sanitize', $data, $field, $original_data );
	}

	/**
	 * Internal calculable value
	 *
	 * @since 1.7
	 *
	 * @param $submitted_field
	 * @param $field_settings
	 *
	 * @return float|string
	 */
	private static function calculable_value( $submitted_field, $field_settings ) {
		$enabled = self::get_property( 'calculations', $field_settings, false, 'bool' );
		if ( ! $enabled ) {
			return self::FIELD_NOT_CALCULABLE;
		}

		$sums = 0.0;

		$field_type = self::get_property( 'value_type', $field_settings, '' );
		$options    = self::get_property( 'options', $field_settings, array() );

		if ( 'multiselect' !== $field_type ) {
			// process as array.
			$submitted_field = array( $submitted_field );
		}
		if ( ! is_array( $submitted_field ) ) {
			return $sums;
		}

		foreach ( $options as $option ) {
			$option_value      = isset( $option['value'] ) ? $option['value'] : ( isset( $option['label'] ) ? $option['label'] : '' );
			$option_value      = trim( addslashes( $option_value ) );
			$calculation_value = isset( $option['calculation'] ) ? $option['calculation'] : 0.0;

			// strict array compare disabled to allow non-coercion type compare.
			if ( in_array( $option_value, $submitted_field, true ) ) {
				// this one is selected.
				$sums += floatval( $calculation_value );
			}
		}

		return floatval( $sums );
	}

	/**
	 * @since 1.7
	 * @inheritdoc
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$calculable_value = self::calculable_value( $submitted_field_data, $field_settings );
		/**
		 * Filter formula being used on calculable value on select field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_select_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
