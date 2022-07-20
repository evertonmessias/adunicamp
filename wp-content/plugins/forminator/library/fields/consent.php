<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Consent
 *
 * @since 1.0.5
 */
class Forminator_Consent extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'consent';

	/**
	 * @var string
	 */
	public $type = 'consent';

	/**
	 * @var int
	 */
	public $position = 21;

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
	public $icon = 'sui-icon-gdpr';

	/**
	 * Forminator_Consent constructor.
	 *
	 * @since 1.0.5
	 */

	public function __construct() {
		parent::__construct();

		$this->name = __( 'Consent', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0.5
	 * @return array
	 */
	public function defaults() {

		$privacy_url = get_privacy_policy_url();
		$privacy_url = ! empty( $privacy_url ) ? $privacy_url : '#';

		return array(
			'required'            => 'true',
			'field_label'         => 'Consent',
			'consent_description' => sprintf( __( 'Yes, I agree with the <a href="%s" target="_blank">privacy policy</a> and <a href="#" target="_blank">terms and conditions</a>.', 'forminator' ), esc_url( $privacy_url ) ),
			'required_message'    => __( 'This field is required. Please check it.', 'forminator' ),
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
		// Unsupported Autofill.
		$autofill_settings = array();

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0.5
	 *
	 * @param $field
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function markup( $field, $settings = array() ) {

		$this->field = $field;

		$html        = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$form_id     = isset( $settings['form_id'] ) ? $settings['form_id'] : false;
		$description = wp_kses_post( forminator_replace_variables( self::get_property( 'consent_description', $field ), $form_id ) );
		$id          = 'forminator-field-' . $id . '-' . uniqid();
		$label       = esc_html( self::get_property( 'field_label', $field ) );
		$required    = self::get_property( 'required', $field, true );
		$ariareq     = $required ? 'true' : 'false';

		$html .= '<div class="forminator-field">';

		if ( $label ) {

			$html .= sprintf(
				'<label for="%s" class="forminator-label">%s %s</label>',
				$id,
				$label,
				$required ? forminator_get_required_icon() : ''
			);
		}

			$html .= '<div class="forminator-checkbox__wrapper">';

				$html .= sprintf( '<label for="%s" class="forminator-checkbox" style="margin: 0;" aria-labelledby="%s__label">', $id, $id );

					$html .= sprintf(
						'<input type="checkbox" name="%1$s" value="%3$s" id="%2$s" data-required="%4$s" aria-required="%4$s" />',
						$name,
						$id,
						esc_html__( 'checked', 'forminator' ),
						$ariareq
					);

					$html .= '<span class="forminator-checkbox-box" aria-hidden="true"></span>';

				$html .= '</label>';

				$html .= sprintf( '<div id="%s__label" class="forminator-checkbox__label">%s</div>', $id, $description );

			$html .= '</div>';

		$html .= '</div>';

		return apply_filters( 'forminator_field_consent_markup', $html, $id, $description );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_validation_rules() {
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );
		$rules       = $is_required ? '"' . $this->get_id( $field ) . '":{"required":true},' : '';

		return apply_filters( 'forminator_field_consent_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_validation_messages() {
		$field            = $this->field;
		$id               = $this->get_id( $field );
		$is_required      = $this->is_required( $field );
		$required_message = self::get_property( 'required_message', $field, '' );
		$required_message = apply_filters(
			'forminator_consent_field_required_validation_message',
			( ! empty( $required_message ) ? $required_message : __( 'This field is required. Please check it.', 'forminator' ) ),
			$id,
			$field
		);
		$messages         = $is_required
						   ? '"' . $this->get_id( $field ) . '": {"required":"' . forminator_addcslashes( $required_message ) . '"},' . "\n"
						   : '';

		return $messages;
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.15.3
	 *
	 * @param array        $field
	 * @param array|string $data
	 */
	public function validate( $field, $data ) {
		// value of consent checkbox is `string` *checked*.
		$id = $this->get_id( $field );
		if ( $this->is_required( $field ) && ( empty( $data ) || __( 'checked', 'forminator' ) !== $data ) ) {
			$required_message                = self::get_property( 'required_message', $field, '' );
			$this->validation_message[ $id ] = apply_filters(
				'forminator_consent_field_required_validation_message',
				( ! empty( $required_message ) ? $required_message : __( 'This field is required. Please check it.', 'forminator' ) ),
				$id,
				$field
			);
		}
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.0.5
	 *
	 * @param array        $field
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_consent_sanitize', $data, $field, $original_data );
	}
}
