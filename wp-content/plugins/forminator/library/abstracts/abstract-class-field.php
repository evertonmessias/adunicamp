<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Field
 *
 * @since 1.0
 * Abstract class for fields
 *
 * @since 1.0.5
 * @property array      form_settings
 * @property array      field
 * @property mixed|void autofill_settings
 * @property mixed|void advanced_settings
 * @property mixed|void markup
 */
abstract class Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = '';

	/**
	 * @var string
	 */
	public $category = '';

	/**
	 * @var array
	 */
	public $settings = array();

	/**
	 * @var array
	 */
	public $defaults = array();

	/**
	 * @var bool
	 */
	public $hide_advanced = false;

	/**
	 * @var int
	 */
	public $position = 99;

	/**
	 * @var bool
	 */
	public $is_input = false;

	/**
	 * @var bool
	 */
	public $has_counter = false;

	/**
	 * Check if the input data for field is valid
	 *
	 * @var bool
	 */
	public $is_valid = true;

	/**
	 * Validation message
	 *
	 * @var array
	 */
	public $validation_message = array();

	/**
	 * Activated Autofill Providers for this field based @see autofill_settings
	 *
	 * @since 1.0.5
	 * @var Forminator_Autofill_Provider_Abstract[]
	 */
	protected $activated_autofill_providers = array();

	/**
	 * Flag property value not exist
	 *
	 * Support backward compat, for non existent property from older forminator version
	 *
	 * @since 1.6
	 */
	const FIELD_PROPERTY_VALUE_NOT_EXIST = 'FORMINATOR_PROPERTY_VALUE_NOT_EXIST';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-element-radio';

	/**
	 * @var bool
	 */
	public $is_calculable = false;

	const FIELD_NOT_CALCULABLE = 'FIELD_NOT_CALCULABLE';

	public function __construct() {

		add_action( 'admin_init', array( &$this, 'admin_init_field' ) );
	}

	/**
	 * admin init field
	 *
	 * @since 1.7
	 */
	public function admin_init_field() {

		$this->settings          = apply_filters( "forminator_field_{$this->slug}_general_settings", array() );
		$this->autofill_settings = apply_filters( "forminator_field_{$this->slug}_autofill_settings", $this->autofill_settings() );
		$this->defaults          = apply_filters( "forminator_field_{$this->slug}_defaults", $this->defaults() );
		$this->position          = apply_filters( "forminator_field_{$this->slug}_position", $this->position );
		$this->is_calculable     = apply_filters( "forminator_field_{$this->slug}_is_calculable", $this->is_calculable );

	}

	/**
	 * Return field name
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Return field slug
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Return field settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Return field property
	 *
	 * @since 1.0
	 * @since 1.6 add $data_type, to cast it
	 *
	 * @param string $property
	 * @param array  $field
	 * @param string $fallback
	 * @param string $data_type data type to return.
	 *
	 * @return mixed
	 */

	public static function get_property( $property, $field, $fallback = '', $data_type = null ) {

		$property_value = $fallback;

		if ( isset( $field[ $property ] ) ) {
			$property_value = $field[ $property ];
		}

		if ( ! empty( $data_type ) ) {
			$property_value = forminator_var_type_cast( $property_value, $data_type );
		}

		if ( 'required_message' === $property ) {
			$property_value = wp_kses_post( $property_value );
		}

		return $property_value;
	}

	/**
	 * @since 1.0
	 *
	 * @param       $field
	 * @param array $settings
	 *
	 * @return mixed
	 */
	public function markup(
		/** @noinspection PhpUnusedParameterInspection */
		$field,
		$settings = array()
	) {
		return '';
	}

	/**
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array();
	}

	/**
	 * Return description
	 *
	 * @since 1.0
	 *
	 * @param string
	 *
	 * @return string
	 */
	public static function get_description( $description, $get_id = '' ) {
		$html         = '';
		$allowed_html = array();
		if ( ! empty( $description ) ) {
			$allowed_html = apply_filters(
				'forminator_field_description_allowed_html',
				array(
					'a'      => array(
						'href'   => true,
						'title'  => true,
						'target' => true,
						'rel'    => true,
					),
					'span'   => array(
						'class' => true,
					),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
				),
				$description,
				$get_id
			);

			$html .= sprintf(
				'<span class="forminator-description" aria-describedby="%s">%s</span>',
				$get_id,
				wp_kses( $description, $allowed_html )
			);
		}

		return apply_filters(
			'forminator_field_description',
			$html,
			$description,
			$get_id,
			$allowed_html
		);
	}

	/**
	 * Return new input field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr
	 *
	 * @param string $label
	 * @param string $description
	 * @param bool   $required
	 * @param string $design
	 * @param array  $wrapper_input
	 *
	 * @return mixed
	 */
	public static function create_input( $attr = array(), $label = '', $description = '', $required = false, $design = '', $wrapper_input = array() ) {

		$html = '';

		// Override value by the posted value.
		$value = isset( $attr['value'] ) ? $attr['value'] : false;

		if ( isset( $attr['name'] ) ) {
			$value = self::get_post_data( $attr['name'], $value );
		}

		$attr['value'] = $value;

		$markup = self::implode_attr( $attr );

		// Get field id.
		$get_id = $attr['id'];

		if ( $label ) {

			if ( $required ) {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s %s</label>',
					$get_id,
					esc_html( $label ),
					forminator_get_required_icon()
				);

			} else {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s</label>',
					$get_id,
					esc_html( $label )
				);

			}
		}

		if ( isset( $wrapper_input[0] ) ) {
			$html .= $wrapper_input[0];
		}

		if ( isset( $wrapper_input[2] ) && ! empty( $wrapper_input[2] ) ) {
			$html .= sprintf( '<label for="%s"><span class="forminator-icon-%s" aria-hidden="true"></span></label>', $get_id, $wrapper_input[2] );
		}

		if ( isset( $wrapper_input[3] ) && ! empty( $wrapper_input[3] ) ) {
			$html .= sprintf( '<span class="forminator-prefix">%s</span>', $wrapper_input[3] );
		}

			$html .= sprintf( '<input %s />', $markup );

		if ( isset( $wrapper_input[1] ) ) {
			$html .= $wrapper_input[1];
		}

		if ( ! empty( $description ) || '' !== $description ) {
			$html .= self::get_description( $description, $get_id );
		}

		return apply_filters( 'forminator_field_create_input', $html, $attr, $label, $description );
	}


	/**
	 * Return new textarea field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr
	 *
	 * @param string $label
	 * @param string $description
	 * @param bool   $required
	 * @param string $design
	 *
	 * @return mixed
	 */
	public static function create_textarea( $attr = array(), $label = '', $description = '', $required = false, $design = '' ) {

		$html    = '';
		$content = isset( $attr['content'] ) ? $attr['content'] : '';

		if ( isset( $attr['name'] ) ) {
			$content = self::get_post_data( $attr['name'], $content );
		}

		unset( $attr['content'] );

		$markup = self::implode_attr( $attr );

		if ( $label ) {

			if ( $required ) {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s %s</label>',
					$attr['id'],
					esc_html( $label ),
					forminator_get_required_icon()
				);

			} else {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s</label>',
					$attr['id'],
					esc_html( $label )
				);

			}
		}

		$html .= sprintf( '<textarea %s >%s</textarea>', $markup, $content );

		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description );
		}

		return apply_filters( 'forminator_field_create_textarea', $html, $attr, $label, $description );
	}

	/**
	 * Return wp_editor_field
	 *
	 * @since 1.0.2
	 *
	 * @param array  $attr
	 *
	 * @param string $label
	 * @param string $description
	 * @param bool   $required
	 *
	 * @return mixed
	 */
	public static function create_wp_editor( $attr = array(), $label = '', $description = '', $required = false, $default_height = '140' ) {
		$html = '';

		$content = isset( $attr['content'] ) ? $attr['content'] : '';
		if ( isset( $attr['name'] ) ) {
			$content = self::get_post_data( $attr['name'], $content );
		}
		unset( $attr['content'] );

		$editor_id = 'forminator-wp-editor-' . ( isset( $attr['id'] ) ? $attr['id'] : '' );
		if ( $label ) {

			if ( $required ) {

				$html .= '<div class="forminator-field--label">';
				$html .= sprintf( '<label for="%s" id="forminator-label-%s" class="forminator-label">%s %s</label>', $editor_id, $attr['id'], esc_html( $label ), forminator_get_required_icon() );
				$html .= '</div>';

			} else {

				$html .= '<div class="forminator-field--label">';
				$html .= sprintf( '<label for="%s" id="forminator-label-%s" class="forminator-label">%s</label>', $editor_id, $attr['id'], esc_html( $label ) );
				$html .= '</div>';

			}
		}

		$wp_editor_class = isset( $attr['class'] ) ? $attr['class'] : '';

		if ( $required ) {
			apply_filters( 'the_editor', array( __CLASS__, 'add_required_wp_editor' ) );
			$wp_editor_class .= ' do-validate forminator-wp-editor-required';
		}

		ob_start();
		wp_editor(
			$content,
			$editor_id,
			array(
				'textarea_name' => isset( $attr['name'] ) ? $attr['name'] : '',
				'media_buttons' => false,
				'editor_class'  => $wp_editor_class,
				'editor_height' => $default_height,
				// 'tinymce'     => array(
				// 'content_css' => '/forminator-{theme}-editor.min.css'
				// ),
			)
		);

		$html .= ob_get_clean();

		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description );
		}

		return apply_filters( 'forminator_field_create_wp_editor', $html, $attr, $label, $description );
	}

	/**
	 * Add Required attribute to wp_editor
	 *
	 * @since 1.0.2
	 *
	 * @param $editor_markup
	 *
	 * @return mixed
	 */
	public static function add_required_wp_editor( $editor_markup ) {
		if ( stripos( $editor_markup, 'forminator-wp-editor-required' ) !== false ) {
			// mark required.
			$editor_markup = str_replace( '<textarea', '<textarea required="true"', $editor_markup );
		}

		return $editor_markup;
	}

	/**
	 * Return new select field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr
	 * @param string $label
	 * @param array  $options
	 * @param string $value
	 *
	 * @param string $description
	 * @param bool   $required
	 *
	 * @return mixed
	 */
	public static function create_select( $attr = array(), $label = '', $options = array(), $value = '', $description = '', $required = false ) {

		$html = '';

		$markup = self::implode_attr( $attr );

		if ( isset( $attr['id'] ) ) {
			$get_id = $attr['id'];
		} else {
			$get_id = uniqid( 'forminator-select-' );
		}

		if ( self::get_post_data( $attr['name'], false ) ) {
			$value = self::get_post_data( $attr['name'] );
		}

		if ( $label ) {

			if ( $required ) {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s %s</label>',
					$get_id,
					esc_html( $label ),
					forminator_get_required_icon()
				);

			} else {

				$html .= sprintf(
					'<label for="%s" class="forminator-label">%s</label>',
					$get_id,
					esc_html( $label )
				);

			}
		}

		$markup .= ' data-default-value="' . esc_attr( $value ) . '"';

		$html .= sprintf( '<select %s>', $markup );

			$html .= self::populate_options_for_select( $options, $value );

		$html .= '</select>';

		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description, $get_id );
		}

		return apply_filters( 'forminator_field_create_select', $html, $attr, $label, $options, $value, $description );
	}

	/**
	 * Return new simple select field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr
	 * @param array  $options
	 * @param string $value
	 *
	 * @param string $description
	 *
	 * @return mixed
	 */
	public static function create_simple_select( $attr = array(), $options = array(), $value = '', $description = '' ) {

		_deprecated_function( 'create_simple_select', '1.6.1', 'create_select' );

		$html   = '';
		$markup = self::implode_attr( $attr );

		if ( self::get_post_data( $attr['name'], false ) ) {
			$value = self::get_post_data( $attr['name'] );
		}

		$html .= sprintf( '<select %s>', $markup );

			$html .= self::populate_options_for_select( $options, $value );

		$html .= '</select>';

		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description );
		}

		return apply_filters( 'forminator_field_create_simple_select', $html, $attr, $options, $value, $description );
	}

	/**
	 * Populate <options>s for <select>
	 *
	 * @since 1.5.2
	 *
	 * @param        $options
	 * @param string  $selected_value
	 *
	 * @return string
	 */
	public static function populate_options_for_select( $options, $selected_value = '' ) {
		$html = '';
		foreach ( $options as $option ) {
			$selected = '';

			if ( isset( $option['value'] ) && is_array( $option['value'] ) ) {
				$populated_optgroup_options = self::populate_options_for_select( $option['value'], $selected_value );
				$html                      .= sprintf( '<optgroup label="%s">%s</optgroup>', $option['label'], $populated_optgroup_options );
			} else {
				if ( '' === $selected_value && '' === $option['value']
						|| '' !== $selected_value && $option['value'] == $selected_value // phpcs:ignore -- loose comparison ok : possible compare '01' and '1'.
						|| ! empty( $option['selected'] ) ) {
					$selected = 'selected="selected"';
				}
				$html .= sprintf( '<option value="%s" %s>%s</option>', esc_html( $option['value'] ), $selected, esc_html( $option['label'] ) );
			}
		}

		return $html;
	}

	/**
	 * Create file upload
	 *
	 * @since 1.0
	 *
	 * @param string      $id
	 * @param string      $name
	 * @param        $description
	 * @param bool        $required
	 *
	 * @param        $design
	 * @param        $file_type
	 * @param        $form_id
	 * @param        $upload_attr
	 *
	 * @return string $html
	 */
	public static function create_file_upload( $id, $name, $description, $required, $design, $file_type = 'single', $form_id = 0, $upload_attr = array() ) {

		$html      = '';
		$style     = '';
		$field_id  = $id;
		$id        = 'forminator-field-' . $field_id;
		$button_id = 'forminator-field-' . $field_id . '_button';
		$mainclass = 'forminator-file-upload';
		$class     = 'forminator-input-file';

		if ( $required ) {
			$class .= '-required do-validate';
		}

		if ( 'multiple' === $file_type ) {
			$mainclass = 'forminator-multi-upload';
			$class    .= ' ' . $id . '-' . $form_id;

		}
		$upload_data = self::implode_attr( $upload_attr );

		$html .= sprintf( '<div class="%s %s" data-element="%s">', $mainclass, $style, $field_id );

			$html .= sprintf( '<input type="file" name="%s" id="%s" class="%s" %s>', $name, $id, $class, $upload_data );

		if ( 'none' === $design ) {

			$html .= sprintf( '<button class="forminator-upload--remove" style="display: none;">%s</button>', __( 'Remove', 'forminator' ) );

		} else {

			if ( 'multiple' === $file_type ) {

				$html .= '<div class="forminator-multi-upload-message" aria-hidden="true">';

					$html .= '<span class="forminator-icon-upload" aria-hidden="true"></span>';

					$html     .= '<p>';
						$html .= sprintf( esc_html__( 'Drag and Drop (or) %1$sChoose Files%2$s', 'forminator' ), '<a class="forminator-upload-file--' . $id . '" href="javascript:void(0)">', '</a>' );
					$html     .= '</p>';

				$html .= '</div>';

			} else {

				$html .= sprintf( '<button id="%s" class="forminator-button forminator-button-upload" data-id="%s">', $button_id, $id );

				if ( 'material' === $design ) {

					$html .= sprintf(
						'<span>%s</span>',
						__( 'Choose File', 'forminator' )
					);

					$html .= '<span aria-hidden="true"></span>';

				} else {
					$html .= __( 'Choose File', 'forminator' );
				}

					$html .= '</button>';

					$html .= sprintf(
						'<span data-empty-text="%s">%s</span>',
						__( 'No file chosen', 'forminator' ),
						__( 'No file chosen', 'forminator' )
					);

				$html .= '<button class="forminator-button-delete" style="display: none;">';

					$html .= '<i class="forminator-icon-close" aria-hidden="true"></i>';

					$html .= sprintf(
						'<span class="forminator-screen-reader-only">%s</span>',
						__( 'Delete uploaded file', 'forminator' )
					);

				$html .= '</button>';
			}
		}

		$html .= '</div>';

		// Check if description is not empty and append it.
		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description, $id );
		}

		return apply_filters( 'forminator_field_create_file_upload', $html, $id, $name, $required );

	}

	/**
	 * Return string from array
	 *
	 * @since 1.0
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function implode_attr( $args ) {
		$data = array();

		foreach ( $args as $key => $value ) {
			$data[] = $key . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $data );
	}

	/**
	 * Validate data
	 *
	 * @since 1.0
	 *
	 * @param array        $field
	 * @param array|string $data - the data to be validated.
	 */
	public function validate( $field, $data ) {
	}

	/**
	 * Validate field data
	 *
	 * @param array        $field_array Field settings.
	 * @param array|string $field_data - the data to be validated.
	 * @return array       validated field data.
	 */
	public function validate_entry( $field_array, $field_data ) {
		// Validate data when its available.
		if ( $this->is_available( $field_array ) ) {

			/**
			 * @since 1.0.5
			 * Mayble re autofill, when autofill not editable, it should return autofill value
			 */
			$field_data = $this->maybe_autofill( $field_array, $field_data, Forminator_CForm_Front_Action::$module_settings );
			$this->validate( $field_array, $field_data );
		}

		return $field_data;
	}

	/**
	 * Check if entry is valid for the field
	 *
	 * @since 1.0
	 * @return bool|array true on valid, or array validation messages on invalid
	 */
	public function is_valid_entry() {
		$this->is_valid = empty( $this->validation_message );
		if ( ! $this->is_valid ) {
			foreach ( $this->validation_message as $field_name => $error ) {
				Forminator_CForm_Front_Action::$submit_errors[][ $field_name ] = $error;
			}
			return $this->validation_message;
		}

		return $this->is_valid;
	}

	/**
	 * Check if field has input limit
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function has_limit( $field ) {
		if ( isset( $field['limit'] ) && intval( $field['limit'] ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if field is required
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_required( $field ) {
		$required = self::get_property( 'required', $field, false );
		$required = filter_var( $required, FILTER_VALIDATE_BOOLEAN );

		return $required;
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @since 1.0
	 *
	 * @param $field
	 * @param $extra_conditions payment plan conditions.
	 *
	 * @return bool
	 */
	public static function is_hidden( $field, $extra_conditions = array() ) {
		if ( ! empty( $extra_conditions ) ) {
			$conditions       = isset( $extra_conditions['conditions'] ) ? $extra_conditions['conditions'] : array();
			$condition_rule   = isset( $extra_conditions['condition_rule'] ) ? $extra_conditions['condition_rule'] : 'all';
			$condition_action = 'show';
		} else {
			$conditions       = self::get_property( 'conditions', $field, array() );
			$condition_action = self::get_property( 'condition_action', $field, 'show' );
			$condition_rule   = self::get_property( 'condition_rule', $field, 'all' );
		}
		// empty conditions.
		if ( empty( $conditions ) ) {
			return false;
		}

		$condition_fulfilled = 0;
		$conditions_count    = 0;

		foreach ( $conditions as $condition ) {
			$element_id = $condition['element_id'];
			// Increase conditions count.
			$conditions_count ++;

			if ( in_array( $element_id, Forminator_CForm_Front_Action::$hidden_fields, true ) ) {
				$is_condition_fulfilled = false;
			} else {
				$is_condition_fulfilled = self::is_condition_matched( $condition );
			}

			if ( $is_condition_fulfilled ) {
				$condition_fulfilled ++;
			} elseif ( 'all' === $condition_rule ) {
				// if condition rule is ALL and at least one condition isn't matched - we don't need to check others.
				break;
			}

			// Check parent conditions only if the current condition is matched.
			if ( $is_condition_fulfilled && Forminator_Front_Action::$module_object ) {
				$parent_field  = Forminator_Front_Action::$module_object->get_field( $element_id );
				$parent_hidden = self::is_hidden( $parent_field );

				if ( $parent_hidden ) {
					$condition_fulfilled--;
					if ( 'all' === $condition_rule ) {
						break;
					}
				}
			}
			// There is no sense to continue checking if at least 1 condition is matched for ANY condition rule.
			if ( 'any' === $condition_rule && $condition_fulfilled ) {
				break;
			}
		}

		$all_matched = ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( $conditions_count === $condition_fulfilled && 'all' === $condition_rule );

		// initialized as hidden
		if ( 'show' === $condition_action ) {
			return ! $all_matched;
		} else {
			// initialized as shown
			return $all_matched;
		}
	}

	/**
	 * Check if passed condition is matched
	 *
	 * @param array $condition Current condition.
	 * @return bool
	 */
	public static function is_condition_matched( $condition ) {
		$form_data = Forminator_CForm_Front_Action::$prepared_data;

		$form_id = $form_data['form_id'];

		// empty conditions.
		if ( empty( $condition ) ) {
			return false;
		}

		$element_id  = $condition['element_id'];
		$field_value = isset( $form_data[ $element_id ] ) ? $form_data[ $element_id ] : '';

		if ( stripos( $element_id, 'upload-' ) !== false && ! isset( $form_data[ $element_id ] ) && isset( $form_data['forminator-multifile-hidden'] ) ) {
			$form_upload_data = json_decode( stripslashes( $form_data['forminator-multifile-hidden'] ), true );
			if ( $form_upload_data && isset( $form_upload_data[ $element_id ] ) ) {
				$field_value = $form_upload_data[ $element_id ];
			}
		}

		// If date field is dropdown type
		if (
			stripos( $element_id, 'date-' ) !== false &&
			isset( $form_data[ $element_id . '-month' ] ) &&
			isset( $form_data[ $element_id . '-day' ] ) &&
			isset( $form_data[ $element_id . '-year' ] )
		) {
			$field_value       = $form_data[ $element_id . '-year' ] . '-' . $form_data[ $element_id . '-month' ] . '-' . $form_data[ $element_id . '-day' ];
			$date_format       = Forminator_API::get_form_field( $form_id, $element_id, false )->date_format;
			$normalized_format = new Forminator_Date();
			$normalized_format = $normalized_format->normalize_date_format( $date_format );
			$date              = date_create_from_format( 'Y-m-d', $field_value );
			$field_value       = date_format( $date, $normalized_format );
		}

		if ( stripos( $element_id, 'signature-' ) !== false ) {
			// We have signature field.
			$is_condition_fulfilled = false;
			$signature_id           = 'field-' . $element_id;

			if ( isset( $form_data[ $signature_id ] ) ) {
				$signature_data = 'ctlSignature' . $form_data[ $signature_id ] . '_data';

				if ( isset( $form_data[ $signature_data ] ) ) {
					$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $signature_data ], $condition );
				}
			}
		} elseif ( stripos( $element_id, 'calculation-' ) !== false || stripos( $element_id, 'stripe-' ) !== false ) {
			$is_condition_fulfilled = false;
			if ( isset( Forminator_CForm_Front_Action::$prepared_data[ $element_id ] ) ) {
				// Condition's value is saved as a string value.
				$is_condition_fulfilled = self::is_condition_fulfilled( (string) Forminator_CForm_Front_Action::$prepared_data[ $element_id ], $condition );
			}
		} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
			$is_condition_fulfilled = self::is_condition_fulfilled( $field_value, $condition );
		} else {
			$is_condition_fulfilled = self::is_condition_fulfilled( $field_value, $condition, $form_id );
		}

		return $is_condition_fulfilled;
	}

	/**
	 * Check if Form Field value fullfilled the condition
	 *
	 * @since 1.0
	 *
	 * @param $form_field_value
	 * @param $condition
	 * @param $form_id null
	 *
	 * @return bool
	 */
	public static function is_condition_fulfilled( $form_field_value, $condition, $form_id = null ) {
		if ( is_array( $form_field_value ) ) {
			$form_field_value = forminator_trim_array( $form_field_value );
		} else {
			$form_field_value = wp_unslash( trim( $form_field_value ) );
		}

		$form_field_value = wp_unslash( $form_field_value );
		$condition_value  = trim( $condition['value'] );

		if ( is_string( $form_field_value ) ) {
			$form_field_value = strtolower( $form_field_value );
		}
		if ( is_string( $condition_value ) ) {
			$condition_value = strtolower( $condition_value );
		}

		switch ( $condition['rule'] ) {
			case 'is':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1
					return in_array( $condition_value, $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
				if ( is_numeric( $condition_value ) ) {
					return ( (int) $form_field_value === (int) $condition_value );
				}

				return ( $form_field_value === $condition_value );
			case 'is_not':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1
					return ! in_array( $condition_value, $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}

				return ( $form_field_value !== $condition_value );
			case 'is_great':
				if ( ! is_numeric( $condition_value ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value > $condition_value;
			case 'is_less':
				if ( ! is_numeric( $condition_value ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value < $condition_value;
			case 'contains':
				return ( stripos( $form_field_value, $condition_value ) === false ? false : true );
			case 'starts':
				return ( stripos( $form_field_value, $condition_value ) === 0 ? true : false );
			case 'ends':
				return ( substr( $form_field_value, - strlen( $condition_value ) ) === $condition_value );

			case 'day_is':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$day = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'D' );
					return strtolower( $day ) === $condition_value;
				}

				return false;
			case 'day_is_not':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$day = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'D' );
					return strtolower( $day ) !== $condition_value;
				}

				return false;
			case 'month_is':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$month = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'M' );
					return strtolower( $month ) === $condition_value;
				}

				return false;
			case 'month_is_not':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$month = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'M' );
					return strtolower( $month ) !== $condition_value;
				}

				return false;
			case 'is_before':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'j F Y' );
					return strtotime( $date ) < strtotime( $condition_value );
				}

				return false;
			case 'is_after':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'j F Y' );
					return strtotime( $date ) > strtotime( $condition_value );
				}

				return false;
			case 'is_before_n_or_more_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					return strtotime( $date ) <= strtotime( '-' . $condition_value . ' days' );
				}

				return false;
			// date_is_less_than_n_days_before_current_date.
			case 'is_before_less_than_n_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date         = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					$rule_date    = strtotime( '-' . $condition_value . ' days' );
					$current_date = strtotime( 'today' );
					return $rule_date < strtotime( $date ) && strtotime( $date ) <= $current_date;
				}

				return false;
			case 'is_after_n_or_more_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					return strtotime( $date ) >= strtotime( '+' . $condition_value . ' days' );
				}

				return false;
			// date_is_less_than_n_days_after_current_date.
			case 'is_after_less_than_n_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date         = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					$rule_date    = strtotime( '+' . $condition_value . ' days' );
					$current_date = strtotime( 'today' );
					return $rule_date > strtotime( $date ) && strtotime( $date ) >= $current_date;
				}

				return false;

			case 'is_correct':
				return $form_field_value ? true : false;
			case 'is_incorrect':
				return ! $form_field_value ? true : false;
			case 'is_final_result':
				return $form_field_value === $condition['element_id'];
			case 'is_not_final_result':
				return $form_field_value !== $condition['element_id'];
			default:
				return false;
		}
	}

	/**
	 * Get the day or month from given date
	 *
	 * @since 1.14
	 *
	 * @param $form_field_value
	 * @param $element_id
	 * @param $form_id
	 * @param $format
	 *
	 * @return string|bool
	 */
	public static function get_day_or_month( $form_field_value, $element_id, $form_id, $format ) {

		if ( empty( $form_field_value ) ) {
			return false;
		}
		$date_format       = Forminator_API::get_form_field( $form_id, $element_id, false )->date_format;
		$normalized_format = new Forminator_Date();
		$normalized_format = $normalized_format->normalize_date_format( $date_format );
		$date              = date_create_from_format( $normalized_format, $form_field_value );

		if ( false === $date ) {
			$date = date_create_from_format( 'Y/m/d', $form_field_value );
		}

		if ( 'D' === $format ) {
			// Day format is based on fields' visibility day format.
			return substr( date_format( $date, $format ), 0, -1 );
		} else {
			return date_format( $date, $format );
		}
	}

	/**
	 * Return field ID
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return string
	 */
	public function get_id( $field ) {
		return self::get_property( 'element_id', $field );
	}

	/**
	 * Field validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		return '';
	}

	/**
	 * Field validation messages
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		return '';
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
	public function sanitize(
		/** @noinspection PhpUnusedParameterInspection */
		$field,
		$data
	) {
		return $data;
	}

	public function sanitize_value( $value ) {
		return htmlspecialchars( $value, ENT_COMPAT );
	}

	/**
	 * Check if field is available
	 * Override it for field that needs dependencies
	 * Example : `captcha` that needs `captcha_key` to be displayed properly
	 *
	 * @see   Forminator_Captcha::is_available()
	 *
	 * @since 1.0.3
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_available(
		/** @noinspection PhpUnusedParameterInspection */
		$field
	) {
		return true;
	}

	/**
	 * Return form style
	 *
	 * @since 1.0.3
	 *
	 * @param $settings
	 *
	 * @return string|bool
	 */
	public function get_form_style( $settings ) {
		if ( isset( $settings['form-style'] ) ) {
			return $settings['form-style'];
		}

		return false;
	}

	/**
	 * Return value stored in $_POST, or the fallback value
	 *
	 * @since 1.1
	 *
	 * @param string $id
	 * @param mixed  $fallback
	 *
	 * @return mixed value of $_POST[$id] or $fallback when unavailable
	 */
	public static function get_post_data( $id, $fallback = '' ) {
		if ( isset( $_POST[ $id ] ) ) {
			$post_data = Forminator_Core::sanitize_array( $_POST[ $id ], $id );
		}
		if ( ! empty( $post_data ) ) {
			return self::get_post_data_sanitize( $post_data, $fallback );
		}

		return $fallback;
	}

	/**
	 * Return sanitized $_POST value, or the fallback value
	 *
	 * @since 1.6.3
	 *
	 * @param string $data
	 * @param mixed  $fallback
	 *
	 * @return mixed value of $_POST[$id] or $fallback when unavailable
	 */
	public static function get_post_data_sanitize( $data, $fallback ) {
		if ( is_array( $data ) ) {
			$escaped = array();

			foreach ( $data as $key => $value ) {
				$escaped[ $key ] = self::get_post_data_sanitize( $value, '' );
			}

			return $escaped;
		}

		return esc_html( $data );
	}

	/**
	 * Abstraction of autofill settings
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Get Autofill setting as paired ['element_id' => $setting]
	 *
	 * @since 1.0.5
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public static function get_autofill_setting( $settings ) {

		// Autofill not enabled
		if ( ! self::is_autofill_enabled( $settings ) ) {
			return array();
		}

		if ( ! empty( $settings['fields-autofill'] ) ) {
			// build to array key
			$fields_autofill      = $settings['fields-autofill'];
			$fields_autofill_pair = array();
			if ( ! is_array( $fields_autofill ) ) {
				return array();
			}

			foreach ( $fields_autofill as $field_autofill ) {
				if ( empty( $field_autofill['element_id'] ) ) {
					continue;
				}
				$fields_autofill_pair[ $field_autofill['element_id'] ] = $field_autofill;
			}

			return $fields_autofill_pair;
		}

		return array();
	}

	/**
	 * Check if autofill enabled on this form
	 *
	 * @since 1.0.5
	 *
	 * @param $settings
	 *
	 * @return bool
	 */
	public static function is_autofill_enabled( $settings ) {
		return isset( $settings['use-autofill'] ) ? forminator_var_type_cast( $settings['use-autofill'], 'bool' ) : false;
	}

	/**
	 * Restore value of the POST fields if its not editable, so we ensure its not modified by anykind
	 * Happens before this POST fields getting validated, so autofill-ed value will be getting validated too
	 *
	 * @since 1.0.5
	 *
	 * @param $field_array
	 * @param $field_data
	 * @param $settings
	 *
	 * @return array|mixed|string
	 */
	public function maybe_autofill( $field_array, $field_data, $settings ) {
		if ( isset( $settings['form-type'] ) && in_array( $settings['form-type'], array( 'registration', 'login' ), true ) ) {
			return $field_data;
		}

		$autofill_settings = self::get_autofill_setting( $settings );

		if ( empty( $autofill_settings ) ) {
			return $field_data;
		}

		$element_id = self::get_property( 'element_id', $field_array );
		if ( is_array( $field_data ) ) {
			foreach ( $field_data as $element_id_suffix => $field_datum ) {
				$element_id                = $element_id . '-' . $element_id_suffix;
				$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );
				if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
					// refill with autofill provider.
					$field_data[ $element_id_suffix ] = $this->maybe_replace_to_autofill_value( $field_datum, $element_autofill_settings );
				}
			}
		} else {
			$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );

			if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
				// refill with autofill provider.
				$field_data = $this->maybe_replace_to_autofill_value( $field_data, $element_autofill_settings );
			}
		}

		return $field_data;
	}


	/**
	 * Get autofill as markup attributes to used later
	 *
	 * @since   1.0.5
	 *
	 * @example {
	 *  'value' => [] // VALUE.
	 *   'readonly' => 'readonly'
	 * }
	 *
	 * @param       $element_id
	 *
	 * @return array
	 */
	public function get_element_autofill_markup_attr( $element_id ) {
		$settings = $this->form_settings;
		if ( ! self::is_autofill_enabled( $settings ) ) {
			return array();
		}

		$autofill_settings = self::get_autofill_setting( $settings );

		if ( empty( $autofill_settings ) ) {
			return array();
		}

		$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );
		$value                     = $this->maybe_replace_to_autofill_value( '', $element_autofill_settings );

		// only return value when its autofilled.
		if ( ! empty( $value ) ) {
			$markup_attr = array(
				'value' => $value,
			);
			// only disable if value is not empty.
			if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
				$markup_attr['readonly'] = 'readonly';
			}

			return $markup_attr;
		}

		return array();
	}

	/**
	 * Get element autofill value if all requirement(s) fulfilled
	 * - Autofill Provider activated
	 *
	 * @param $element_value
	 * @param $element_autofill_settings
	 *
	 * @return mixed|string
	 */
	public function maybe_replace_to_autofill_value( $element_value, $element_autofill_settings ) {
		if ( ! empty( $element_autofill_settings['provider'] ) ) {
			$attribute_provider = $element_autofill_settings['provider'];
			$provider_parts     = explode( '.', $attribute_provider );
			if ( ! empty( $provider_parts[1] ) ) {
				$provider_slug     = $provider_parts[0];
				$provider_instance = forminator_autofill_init_provider( $provider_slug );
				if ( $provider_instance ) {
					$element_value = $provider_instance->fill( $provider_parts[1] );
				}
			}
		}

		return $element_value;
	}

	/**
	 * Get individial element autofill setting
	 *
	 * @since 1.0.5
	 *
	 * @param $element_id
	 * @param $autofill_settings
	 *
	 * @return array
	 */
	public static function get_element_autofill_settings( $element_id, $autofill_settings ) {
		$autofill_element_settings = array();

		if ( isset( $autofill_settings[ $element_id ] ) && is_array( $autofill_settings[ $element_id ] ) ) {
			$autofill_element_settings = $autofill_settings[ $element_id ];
		}

		return $autofill_element_settings;
	}

	/**
	 * Check if an element is editable when autofill enabled
	 *
	 * @param $element_autofill_settings
	 *
	 * @return bool
	 */
	public static function element_autofill_is_editable( $element_autofill_settings ) {
		if ( isset( $element_autofill_settings['is_editable'] ) && 'yes' === $element_autofill_settings['is_editable'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get required message for multiple name field
	 *
	 * @param $id
	 * @param $field
	 * @param $property
	 * @param $slug
	 * @param $fallback
	 *
	 * @return string
	 */
	protected function get_field_multiple_required_message( $id, $field, $property, $slug, $fallback ) {
		// backward compat *_required_message.
		$required_message = self::get_property( $property, $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST, 'string' );
		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $required_message ) {
			$required_message = $fallback;
		}

		$required_message = wp_kses_post( $required_message );

		$required_message = apply_filters( "forminator_{$this->slug}_field_{$slug}_required_validation_message", $required_message, $id, $field );

		return $required_message;
	}

	/**
	 * Get calculable value
	 *
	 * @since 1.7
	 *
	 * @param array|mixed $submitted_field_data
	 * @param array       $field_settings
	 *
	 * @return float|string
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$field_slug       = $field_settings['type'];
		$calculable_value = 0.0;

		/**
		 * Filter formula being used on calculable value on abstract level
		 * this hook can be used on un-implemented calculation field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$calculable_value = apply_filters( "forminator_field_{$field_slug}_calculable_value", $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}

	/**
	 *
	 * Get calculable precision
	 *
	 * @since 1.7
	 *
	 * @param $field_settings
	 *
	 * @return int
	 */
	public static function get_calculable_precision( $field_settings ) {
		$precision = self::get_property( 'precision', $field_settings, 2, 'num' );

		/**
		 * Filter formula being used on calculable value on abstract level
		 * this hook can be used on un-implemented calculation field
		 *
		 * @param int   $precision
		 * @param array $submitted_data
		 * @param array $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$precision = apply_filters( 'forminator_field_calculable_precision', $precision, Forminator_CForm_Front_Action::$prepared_data, $field_settings );

		return $precision;
	}

	/**
	 * Return if field has pre-fill value filled
	 *
	 * @since 1.10
	 *
	 * @param $field
	 * @return bool
	 */
	public function has_prefill( $field, $prefix = false ) {
		if ( $prefix ) {
			$prefix = $prefix . '_';
		}

		$prefill = self::get_property( $prefix . 'prefill', $field, false );

		if ( $prefill ) {
			return true;
		}

		return false;
	}

	/**
	 * Get pre-fill value if set, else return $default
	 *
	 * @since 1.10
	 *
	 * @param $field
	 * @param $default
	 * @return mixed
	 */
	public function get_prefill( $field, $default, $prefix = false ) {
		if ( $prefix ) {
			$prefix = $prefix . '_';
		}

		$prefill = self::get_property( $prefix . 'prefill', $field, false );

		if ( isset( $_REQUEST[ $prefill ] ) ) {
			if ( 'textarea' === $field['type'] ) {
				$return = rawurldecode( wp_kses_post( wp_unslash( $_REQUEST[ $prefill ] ) ) );
			} else {
				$return = rawurldecode( esc_html( wp_unslash( $_REQUEST[ $prefill ] ) ) );
			}

			if ( $return ) {
				return $return;
			}
		}

		return $default;
	}

	/**
	 * Replace object value from prefill
	 *
	 * @since 1.10
	 *
	 * @param $field
	 * @param $attributes
	 * @param $prefix
	 * @param bool       $default
	 * @return mixed
	 */
	public function replace_from_prefill( $field, $attributes, $prefix, $default = false ) {
		if ( $this->has_prefill( $field, $prefix ) ) {
			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $default, $prefix );

			$attributes['value'] = esc_html( $value );
		}

		return $attributes;
	}

	/**
	 * Get TinyMCE arguments for js on front-end
	 *
	 * @param string $id Editor ID.
	 * @return string
	 */
	public static function get_tinymce_args( $id ) {
		$args = "{
			tinymce: {
				wpautop  : true,
				theme    : 'modern',
				skin     : 'lightgray',
				language : 'en',
				formats  : {
					alignleft  : [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'left' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'alignleft' }
					],
					aligncenter: [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'center' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'aligncenter' }
					],
					alignright : [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'right' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'alignright' }
					],
					strikethrough: { inline: 'del' }
				},
				relative_urls       : false,
				remove_script_host  : false,
				convert_urls        : false,
				browser_spellcheck  : true,
				fix_list_elements   : true,
				entities            : '38,amp,60,lt,62,gt',
				entity_encoding     : 'raw',
				keep_styles         : false,
				paste_webkit_styles : 'font-weight font-style color',
				preview_styles      : 'font-family font-size font-weight font-style text-decoration text-transform',
				tabfocus_elements   : ':prev,:next',
                plugins    : 'charmap,hr,media,paste,tabfocus,textcolor,fullscreen,wptextpattern,lists,wordpress,wpeditimage,wpgallery,link,wplink,wpdialogs,wpview'," // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
				. "
				resize     : 'vertical',
				menubar    : false,
				indent     : false,
				toolbar1   : 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,fullscreen,wp_adv',
				toolbar2   : 'strikethrough,underline,hr,forecolor,pastetext,alignjustify,removeformat,charmap,outdent,indent,undo,redo,wp_help',
				toolbar3   : '',
				toolbar4   : '',
				body_class : 'id post-type-post post-status-publish post-format-standard',
				wpeditimage_disable_captions: false,
				wpeditimage_html5_captions  : true

			},
			quicktags: true,
		}";

		/**
		 * Filter TinyMCE arguments for js on front-end.
		 *
		 * @since 1.1
		 *
		 * @param string $args TinyMCE arguments.
		 * @param string $id   Editor ID.
		 */
		$args = apply_filters( 'forminator_tinymce_args', $args, $id );

		return $args;
	}

	/**
	 * Number separators
	 *
	 * @param $separator
	 * @param $field
	 *
	 * @return array
	 */
	public static function forminator_separators( $separator, $field ) {

		$separators = array(
			'blank'       => array(
				'point'     => '.',
				'separator' => '',
			),
			'comma_dot'   => array(
				'point'     => '.',
				'separator' => ',',
			),
			'dot_comma'   => array(
				'point'     => ',',
				'separator' => '.',
			),
			'space_comma' => array(
				'point'     => ',',
				'separator' => ' ',
			),
		);
		if ( 'custom' === $separator ) {
			$data_point     = self::get_property( 'decimal-separators', $field, '.' );
			$data_separator = self::get_property( 'thousand-separators', $field, ',' );
		} else {
			$data_point     = $separators[ $separator ]['point'];
			$data_separator = $separators[ $separator ]['separator'];
		}

		return array(
			'separator' => $data_separator,
			'point'     => $data_point,
		);
	}

	/**
	 * Number formatting
	 *
	 * @param $field
	 * @param $number
	 *
	 * @return string
	 */
	public static function forminator_number_formatting( $field, $number ) {
		$default_precision = 2;
		if ( isset( $field['type'] ) && 'number' === $field['type'] ) {
			$default_precision = 0;
		}
		$precision  = self::get_property( 'precision', $field, $default_precision, 'num' );
		$separator  = self::get_property( 'separators', $field, 'blank' );
		$separators = self::forminator_separators( $separator, $field );
		$data_value = (float) str_replace( $separators['point'], '.', $number );
		$formatted  = number_format( $data_value, $precision, $separators['point'], $separators['separator'] );

		if ( ! empty( $field['prefix'] ) || ! empty( $field['suffix'] ) ) {
			// Prefix.
			$formatted = ( ! empty( $field['prefix'] ) ? $field['prefix'] . ' ' : '' ) . $formatted;
			// Suffix.
			$formatted = $formatted . ( ! empty( $field['suffix'] ) ? ' ' . $field['suffix'] : '' );
		} elseif ( ! empty( $field['currency'] ) ) {
			$formatted .= ' ' . $field['currency'];
		}

		return $formatted;
	}

	/**
	 * Replace number formatting
	 *
	 * @param $field
	 * @param $number
	 *
	 * @return string
	 */
	public static function forminator_replace_number( $field, $number ) {
		$separator  = self::get_property( 'separators', $field, 'blank' );
		$separators = self::forminator_separators( $separator, $field );

		return str_replace( $separators['point'], '.', $number );
	}

}
