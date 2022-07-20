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
class Forminator_Radio extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'radio';

	/**
	 * @var string
	 */
	public $type = 'radio';

	/**
	 * @var int
	 */
	public $position = 9;

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
	public $icon = 'sui-icon-element-radio';

	public $is_calculable = true;

	/**
	 * Forminator_SingleValue constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Radio', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'value_type'  => 'radio',
			'field_label' => __( 'Radio', 'forminator' ),
			'layout'      => 'vertical',
			'options'     => array(
				array(
					'label' => __( 'Option 1', 'forminator' ),
					'value' => 'one',
					'key'   => forminator_unique_key(),
				),
				array(
					'label' => __( 'Option 2', 'forminator' ),
					'value' => 'two',
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

		$i                = 1;
		$html             = '';
		$id               = self::get_property( 'element_id', $field );
		$name             = $id;
		$id               = 'forminator-field-' . $id;
		$required         = self::get_property( 'required', $field, false );
		$ariareq          = 'false';
		$options          = self::get_property( 'options', $field, array() );
		$value_type       = isset( $field['value_type'] ) ? trim( $field['value_type'] ) : 'multiselect';
		$post_value       = self::get_post_data( $name, false );
		$description      = self::get_property( 'description', $field, '' );
		$label            = esc_html( self::get_property( 'field_label', $field, '' ) );
		$design           = $this->get_form_style( $settings );
		$calc_enabled     = self::get_property( 'calculations', $field, false, 'bool' );
		$images_enabled   = self::get_property( 'enable_images', $field, false );
		$images_enabled   = filter_var( $images_enabled, FILTER_VALIDATE_BOOLEAN );
		$input_visibility = self::get_property( 'input_visibility', $field, 'true' );
		$input_visibility = filter_var( $input_visibility, FILTER_VALIDATE_BOOLEAN );

		$uniq_id = uniqid();

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		$html .= sprintf(
			'<div role="radiogroup" class="forminator-field" aria-labelledby="%s">',
			'forminator-radiogroup-' . $uniq_id . '-label'
		);

		if ( $label ) {
			if ( $required ) {
				$html .= sprintf(
					'<span id="%s" class="forminator-label">%s %s</span>',
					'forminator-radiogroup-' . $uniq_id . '-label',
					$label,
					forminator_get_required_icon()
				);
			} else {
				$html .= sprintf(
					'<span id="%s" class="forminator-label">%s</span>',
					'forminator-radiogroup-' . $uniq_id . '-label',
					$label
				);
			}
		}

		foreach ( $options as $option ) {
			$input_id          = $id . '-' . $i . '-' . $uniq_id;
			$value             = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( $option['value'] ) : esc_html( $option['label'] ) );
			$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
			$selected          = ( $value === $post_value || $option_default ) ? 'checked="checked"' : '';
			$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? $option['calculation'] : 0.0;
			$option_image_url  = array_key_exists( 'image', $option ) ? $option['image'] : '';
			$option_label      = sprintf(
				'<span class="forminator-radio-label">%s</span>',
				wp_kses(
					$option['label'],
					array(
						'a'      => array(
							'href'  => array(),
							'title' => array(),
						),
						'span'   => array(
							'class' => array(),
						),
						'b'      => array(),
						'i'      => array(),
						'br'     => array(),
						'em'     => array(),
						'strong' => array(),
					)
				)
			);
			$aria_label        = sprintf(
				'<span class="forminator-screen-reader-only">%s</span>',
				wp_kses(
					$option['label'],
					array(
						'a'      => array(
							'href'  => array(),
							'title' => array(),
						),
						'span'   => array(
							'class' => array(),
						),
						'b'      => array(),
						'i'      => array(),
						'br'     => array(),
						'em'     => array(),
						'strong' => array(),
					)
				)
			);

			if ( isset( $draft_value['value'] ) ) {

				if ( trim( $draft_value['value'] ) === trim( $value ) ) {
					$option_default = true;
				}

			} elseif ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$prefill = $this->get_prefill( $field, false );

				if ( $prefill === $value ) {
					$option_default = true;
				}
			}

			$class = 'forminator-radio';

			if ( $images_enabled && ! empty( $option_image_url ) ) {

				$class .= ' forminator-has_image';

				if ( $input_visibility ) {
					$class .= ' forminator-has_bullet';
				}
			}

			if ( 'horizontal' === self::get_property( 'layout', $field, '' ) ) {
				$class .= ' forminator-radio-inline';
			}

			$selected = $option_default ? 'checked="checked"' : '';

			$html .= '<label for="' . esc_attr( $input_id ) . '" class="' . esc_attr( $class ) . '" title="' . esc_attr( $option['label'] ) . '">';

				$html .= sprintf(
					'<input type="radio" name="%s" value="%s" id="%s" data-calculation="%s" %s />',
					$name,
					$value,
					$input_id,
					$calculation_value,
					$selected
				);

			if ( $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

				// Bullet + Label.
				$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
				$html .= $option_label;

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}
			} elseif ( ! $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}

				// Aria Label.
				$html .= $aria_label;

			} else {

				// Bullet + Label.
				$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
				$html .= $option_label;

			}

			$html .= '</label>';

			$i ++;

		}

			$html .= self::get_description( $description );

		$html .= '</div>';

		return apply_filters( 'forminator_field_single_markup', $html, $id, $required, $options, $value_type );
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

		if ( $is_required ) {
			$rules .= '"' . $this->get_id( $field ) . '": "required",';
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

		if ( $is_required ) {
			$required_message = self::get_property( 'required_message', $field, '' );
			$required_message = apply_filters(
				'forminator_single_field_required_validation_message',
				( ! empty( $required_message ) ? $required_message : __( 'This field is required. Please select a value.', 'forminator' ) ),
				$id,
				$field
			);
			$messages        .= '"' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
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
		$id = self::get_property( 'element_id', $field );
		if ( ! empty( $data ) && false === array_search( $data, array_column( $field['options'], 'value' ) ) ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_radio_field_nonexistent_validation_message',
				__( 'Selected value does not exist.', 'forminator' ),
				$id,
				$field
			);
		}
		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, '' );
			if ( empty( $data ) && '0' !== $data ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_single_field_required_validation_message',
					( ! empty( $required_message ) ? $required_message : __( 'This field is required. Please select a value.', 'forminator' ) ),
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
	 * Internal Calculable value
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

		$options = self::get_property( 'options', $field_settings, array() );

		// process as array.
		$submitted_field = array( $submitted_field );

		if ( ! is_array( $submitted_field ) ) {
			return $sums;
		}

		foreach ( $options as $option ) {
			$option_value      = isset( $option['value'] ) ? $option['value'] : ( isset( $option['label'] ) ? $option['label'] : '' );
			$calculation_value = isset( $option['calculation'] ) ? $option['calculation'] : 0.0;

			// strict array compare disabled to allow non-coercion type compare.
			if ( in_array( $option_value, $submitted_field ) ) {
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
		 * Filter formula being used on calculable value on radio field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_radio_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
