<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Calculation
 *
 * @since 1.7
 */
class Forminator_Calculation extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'calculation';

	/**
	 * @var string
	 */
	public $type = 'calculation';

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
	 * @var bool
	 */
	public $is_input = false;

	/**
	 * @var bool
	 */
	public $has_counter = false;

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-calculator';

	public $is_calculable = true;

	/**
	 * Forminator_Text constructor.
	 *
	 * @since 1.7
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Calculations', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.7
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_label' => __( 'Calculations', 'forminator' ),
		);
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.7
	 *
	 * @param $field
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function markup( $field, $settings = array() ) {

		$this->field         = $field;
		$this->form_settings = $settings;

		$html        = '';
		$wrapper     = array();
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = $id . '-field';
		$required    = self::get_property( 'required', $field, false );
		$value       = esc_html( self::get_post_data( $name, self::get_property( 'default_value', $field ) ) );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' );
		$design      = $this->get_form_style( $settings );
		$formula     = self::get_property( 'formula', $field, '', 'str' );
		$is_hidden   = self::get_property( 'hidden', $field, false, 'bool' );
		$suffix      = self::get_property( 'suffix', $field );
		$prefix      = self::get_property( 'prefix', $field );
		$precision   = self::get_calculable_precision( $field );
		$separator   = self::get_property( 'separators', $field, 'blank' );
		$separators  = $this->forminator_separators( $separator, $field );

		$point = ! empty( $precision ) ? $separators['point'] : '';

		$number_attr = array(
			'name'               => $name,
			'value'              => $value,
			'id'                 => $id,
			'class'              => 'forminator-calculation',
			'data-formula'       => $formula,
			'data-required'      => $required,
			'data-decimal-point' => $point,
			'data-precision'     => $precision,
			'data-is-hidden'     => $is_hidden,
			'disabled'           => 'disabled', // mark as disabled so this value won't send to backend later.
			'data-decimals'      => $precision,
			'data-inputmask'     => "'groupSeparator': '" . $separators['separator'] . "', 'radixPoint': '" . $point . "', 'digits': '" . $precision . "'",
		);

		if ( empty( $prefix ) && empty( $suffix ) ) {
			$number_attr['class'] .= ' forminator-input';
		}

		if ( ! empty( $prefix ) || ! empty( $suffix ) ) {
			$wrapper = array(
				'<div class="forminator-input forminator-input-with-prefix">',
				sprintf( '<span class="forminator-suffix">%s</span></div>', $suffix ),
				'',
				$prefix,
			);
		}

		$html .= '<div class="forminator-field">';

			$html .= self::create_input(
				$number_attr,
				$label,
				$description,
				$required,
				$design,
				$wrapper
			);

		$html .= '</div>';

		return apply_filters( 'forminator_field_calculation_markup', $html, $id, $required, $value );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.7
	 * @return string
	 */
	public function get_validation_rules() {
		return '';
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.7
	 * @return string
	 */
	public function get_validation_messages() {
		return '';
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.7
	 *
	 * @param array        $field
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_calculation_sanitize', $data, $field );
	}

	/**
	 * @since 1.7
	 * @inheritdoc
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$formula = self::get_property( 'formula', $field_settings, '', 'str' );

		/**
		 * Filter formula being used on calculable value of calculation field
		 *
		 * @since 1.7
		 *
		 * @param string $formula
		 * @param array  $submitted_data
		 * @param array  $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$formula = apply_filters( 'forminator_field_calculation_calculable_value', $formula, Forminator_CForm_Front_Action::$prepared_data, $field_settings );

		if ( empty( $formula ) ) {
			return 0.0;
		}

		return $formula;
	}

	/**
	 * Get default error message
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public static function default_error_message() {
		$message = __( 'Failed to calculate field.', 'forminator' );

		/**
		 * Filter default error message
		 *
		 * @since 1.7
		 *
		 * @param string $message
		 *
		 * @return string
		 */
		$message = apply_filters( 'forminator_field_calculation_default_error_message', $message );

		return $message;
	}
}
