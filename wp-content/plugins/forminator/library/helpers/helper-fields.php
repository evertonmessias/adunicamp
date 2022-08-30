<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Return custom form
 *
 * @since 1.0
 * @return mixed
 */
function forminator_form( $id, $is_preview = false, $hidden = true ) {
	$view = new Forminator_CForm_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return custom form
 *
 * @since 1.0
 * @return mixed
 */
function forminator_poll( $id, $is_preview = false, $hidden = true ) {
	$view = new Forminator_Poll_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return custom form
 *
 * @since 1.0
 * @return mixed
 */
function forminator_quiz( $id, $is_preview = false, $hidden = true ) {
	$view = new Forminator_QForm_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return module
 *
 * @since 1.0
 * @return mixed
 */
function forminator_preview( $id, $type, $ajax = false, $data = false ) {
	$class = 'Forminator_CForm_Front';
	if ( 'poll' === $type ) {
		$class = 'Forminator_Poll_Front';
	} elseif ( 'quiz' === $type ) {
		$class = 'Forminator_QForm_Front';
	}
	$view = new $class();
	$data = forminator_stripslashes_deep( $data );

	return $view->render_shortcode(
		array(
			'id'           => $id,
			'is_preview'   => $ajax,
			'preview_data' => $data,
		)
	);
}

/**
 * Return stripslashed string or array
 *
 * @since 1.0
 * @return mixed
 */
function forminator_stripslashes_deep( $val ) {
	$val = is_array( $val ) ? array_map( 'stripslashes_deep', $val ) : stripslashes( $val );

	return $val;
}

/**
 * Sanitize field
 *
 * @since 1.0.2
 *
 * @param $field
 *
 * @return array|string
 */
function forminator_sanitize_field( &$field, $key = null ) {
	if ( 'question_description' === $key ) {
		return wp_kses_post( $field );
	}
	// If array map all fields.
	if ( is_array( $field ) ) {
		array_walk( $field, 'forminator_sanitize_field' );
		return $field;
	}

	return sanitize_text_field( $field );
}

/**
 * Forminator sanitation for an array
 *
 * @param $fields
 *
 * @return mixed
 */
function forminator_sanitize_array_field( $fields ) {
	foreach ( $fields as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = forminator_sanitize_array_field( $value );
		} else {
			if ( 'consent_description' === $key
				 || 'variations' === $key
			     || 'hc_invisible_notice' === $key
			     || 'sc_message' === $key
			) {
				$value = wp_kses_post( $value );
			} elseif ( 'card_description' === $key
					   || 'gdpr_text' === $key
					   || 'message' === $key
			) {
				$value = sanitize_textarea_field( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}
	}

	return $fields;
}

/**
 * Forminator decode HTML entity
 *
 * @param $fields
 *
 * @return mixed
 */
function forminator_decode_html_entity( $fields ) {
	foreach ( $fields as &$value ) {
		if ( is_array( $value ) ) {
			$value = forminator_decode_html_entity( $value );
		} else {
			$value = wp_specialchars_decode( $value );
		}
	}

	return $fields;
}

/**
 * Sanitize text area
 *
 * @param $field
 *
 * @return string
 */
function forminator_sanitize_textarea( $field ) {

	return sanitize_textarea_field( $field );
}

/**
 * Return the array of ALL fields objects
 *
 * @since 1.0
 * @return mixed
 */
function forminator_get_fields() {
	$forminator = Forminator_Core::get_instance();

	return $forminator->fields;
}

/**
 * Return the array of PRO fields for promotion PRO version
 *
 * @return array
 */
function forminator_get_pro_fields() {
	$forminator = Forminator_Core::get_instance();

	return $forminator->pro_fields;
}

/**
 * Return all existing custom fields
 *
 * @since      1.0
 * @deprecated 1.5.4
 * @return mixed
 */
function forminator_get_existing_cfields() {
	_deprecated_function( 'forminator_get_existing_cfields', '1.5.4' );

	return array();
}

/**
 * Convert array to array compatible with field values
 *
 * @since 1.0
 *
 * @param      $array
 * @param bool $replace_value
 *
 * @return array
 */
function forminator_to_field_array( $array, $replace_value = false ) {
	$field_array = array();

	if ( ! empty( $array ) ) {
		foreach ( $array as $key => $value ) {
			// Use value instead of key.
			if ( $replace_value ) {
				$field_array[] = array(
					'value' => $value,
					'label' => $value,
				);
			} else {
				$field_array[] = array(
					'value' => $key,
					'label' => $value,
				);
			}
		}
	}

	return $field_array;
}

/**
 * Return max upload limit from server
 *
 * @since 1.6
 * @return int Mb
 */
function forminator_get_max_upload() {
	$max_upload = wp_max_upload_size();

	return (int) ( $max_upload / 1000000 ); // convert to mb;.
}

/**
 * Return users list
 *
 * @since 1.6
 * @return array
 */
function forminator_list_users() {
	$users_list = array();
	$users      = get_users(
		array(
			'role__in' => array( 'administrator', 'editor', 'author' ),
			'fields'   => array( 'ID', 'display_name' ),
		)
	);
	foreach ( $users as $user ) {
		$users_list[] = array(
			'value' => $user->ID,
			'label' => ucfirst( $user->display_name ),
		);
	}

	return apply_filters( 'forminator_postdata_users_list', $users_list );
}

/**
 * Return post type list
 *
 * @since 1.7
 * @return array
 */
function forminator_post_type_list() {
	$post_type_list = array();
	$post_types     = get_post_types( array( 'public' => true ), 'objects' );

	unset( $post_types['attachment'] );
	unset( $post_types['revision'] );
	unset( $post_types['nav_menu_item'] );
	unset( $post_types['custom_css'] );
	unset( $post_types['customize_changeset'] );
	unset( $post_types['oembed_cache'] );
	unset( $post_types['user_request'] );
	unset( $post_types['wp_block'] );

	foreach ( $post_types as $post ) {
		$post_type_list[] = array(
			'value' => $post->name,
			'label' => ucfirst( $post->label ),
		);
	}

	return apply_filters( 'forminator_postdata_post_type_list', $post_type_list );
}

/**
 * Return post type Categories
 *
 * @since 1.7
 *
 * @param $type
 *
 * @return array
 */
function forminator_post_categories( $type = '' ) {
	$categories = array();
	$category   = array();
	$post_types = forminator_post_type_list();

	foreach ( $post_types as $post ) {
		$post_type  = $post['value'];
		$categories = get_object_taxonomies( $post_type, 'objects' );

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( 'post_format' !== $cat->name ) {
					$category[ $post_type ][] = array(
						'value'    => $cat->name,
						'label'    => ucfirst( $cat->label ),
						'singular' => $cat->labels->singular_name,
					);
				}
			}
		}
		$categories = $category;
	}
	if ( ! empty( $type ) ) {
		$category_list = isset( $categories[ $type ] ) ? $categories[ $type ] : array();
	} else {
		$category_list = $categories;
	}
	return apply_filters( 'forminator_postdata_post_categories', $category_list );
}

/**
 * Return vars
 *
 * @since 1.0
 * @since 1.5 add `user_id`
 *
 * @param bool $add_query	@since 1.15.6
 *
 * @return mixed
 */
function forminator_get_vars( $add_query = false ) {
	$vars_list = array(
		'user_ip'      	  => esc_html__( 'User IP Address', 'forminator' ),
		'date_mdy'     	  => esc_html__( 'Date (mm/dd/yyyy)', 'forminator' ),
		'date_dmy'     	  => esc_html__( 'Date (dd/mm/yyyy)', 'forminator' ),
		'submission_id'   => esc_html__( 'Submission ID', 'forminator' ),
		'submission_time' => esc_html__( 'Submission Time (hh:mm:ss am/pm, timezone)', 'forminator' ),
		'embed_id'     	  => esc_html__( 'Embed Post/Page ID', 'forminator' ),
		'embed_title'  	  => esc_html__( 'Embed Post/Page Title', 'forminator' ),
		'embed_url'    	  => esc_html__( 'Embed URL', 'forminator' ),
		'user_agent'   	  => esc_html__( 'HTTP User Agent', 'forminator' ),
		'refer_url'    	  => esc_html__( 'HTTP Refer URL', 'forminator' ),
		'user_id'      	  => esc_html__( 'User ID', 'forminator' ),
		'user_name'    	  => esc_html__( 'User Display Name', 'forminator' ),
		'user_email'   	  => esc_html__( 'User Email', 'forminator' ),
		'user_login'   	  => esc_html__( 'User Login', 'forminator' ),
		'custom_value' 	  => esc_html__( 'Custom Value', 'forminator' ),
	);

	if ( $add_query ) {
		$vars_list['query'] = esc_html__( 'Query Parameter', 'forminator' );
	}

	/**
	 * Filter forminator var list
	 *
	 * @see   forminator_replace_variables()
	 *
	 * @since 1.0
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_vars_list', $vars_list );
}

/**
 * Return Stripe vars
 *
 * @since 1.7
 * @return mixed
 */
function forminator_get_payment_vars() {
	$vars_list = array(
		'payment_mode'     => esc_html__( 'Payment Mode', 'forminator' ),
		'payment_status'   => esc_html__( 'Payment Status', 'forminator' ),
		'payment_amount'   => esc_html__( 'Payment Amount', 'forminator' ),
		'payment_currency' => esc_html__( 'Payment Currency', 'forminator' ),
		'transaction_id'   => esc_html__( 'Transaction ID', 'forminator' ),
	);

	/**
	 * Filter forminator Stripe var list
	 *
	 * @since 1.7
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_stripe_vars_list', $vars_list );
}

/**
 * Return required icon
 *
 * @since 1.0
 * @return string
 */
function forminator_get_required_icon() {
	return '<span class="forminator-required">*</span>';
}

/**
 * Return week days
 *
 * @since 1.0
 * @return array
 */
function forminator_week_days() {
	return apply_filters(
		'forminator_week_days',
		array(
			'sunday'    => __( 'Sunday', 'forminator' ),
			'monday'    => __( 'Monday', 'forminator' ),
			'tuesday'   => __( 'Tuesday', 'forminator' ),
			'wednesday' => __( 'Wednesday', 'forminator' ),
			'thursday'  => __( 'Thursday', 'forminator' ),
			'friday'    => __( 'Friday', 'forminator' ),
			'saturday'  => __( 'Saturday', 'forminator' ),
		)
	);
}

/**
 * Return name prefixes
 *
 * @since 1.0
 * @return array
 */
function forminator_get_name_prefixes() {
	return apply_filters(
		'forminator_name_prefixes',
		array(
			'Mr'   => __( 'Mr.', 'forminator' ),
			'Mrs'  => __( 'Mrs.', 'forminator' ),
			'Ms'   => __( 'Ms.', 'forminator' ),
			'Mx'   => __( 'Mx.', 'forminator' ),
			'Miss' => __( 'Miss', 'forminator' ),
			'Dr'   => __( 'Dr.', 'forminator' ),
			'Prof' => __( 'Prof.', 'forminator' ),
		)
	);
}

/**
 * Return field id by string
 *
 * @since 1.0
 *
 * @param $string
 *
 * @return mixed
 */
function forminator_clear_field_id( $string ) {
	$string = str_replace( '{', '', $string );
	$string = str_replace( '}', '', $string );

	return $string;
}

/**
 * Return filtered editor content with form data
 *
 * @since 1.0
 * @param bool $get_labels Optional. Set true for getting labels instead of values for select, radio and checkbox.
 * @return mixed
 */
function forminator_replace_form_data( $content, Forminator_Form_Model $custom_form = null, Forminator_Form_Entry_Model $entry = null, $get_labels = false, $urlencode = false ) {
	$data             = Forminator_CForm_Front_Action::$prepared_data;
	$matches          = array();
	$field_types      = Forminator_Core::get_field_types();
	$original_content = $content;

	$content = forminator_replace_form_payment_data( $content, $custom_form, $entry );

	$randomed_field_pattern  = 'field-\d+-\d+';
	$increment_field_pattern = sprintf( '(%s)-\d+', implode( '|', $field_types ) );
	$pattern                 = '/\{((' . $randomed_field_pattern . ')|(' . $increment_field_pattern . '))(\-[A-Za-z-_]+)?\}/';
	$print_value             = ! empty( $custom_form->settings['print_value'] )
			? filter_var( $custom_form->settings['print_value'], FILTER_VALIDATE_BOOLEAN ) : false;
	// Find all field ID's.
	if ( preg_match_all( $pattern, $content, $matches ) ) {
		if ( ! isset( $matches[0] ) || ! is_array( $matches[0] ) ) {
			return $content;
		}
		foreach ( $matches[0] as $match ) {
			$element_id = forminator_clear_field_id( $match );
			$value      = '';

			// For HTML field we get the relevant field label instead of field value for select, radio and checkboxes and for them themselves.
			if ( $get_labels && ! $print_value && ( strpos( $element_id, 'radio' ) === 0
					|| strpos( $element_id, 'select' ) === 0
					|| strpos( $element_id, 'checkbox' ) === 0
					) ) {
				$field_value = isset( $data[ $element_id ] ) ? $data[ $element_id ] : null;
				if ( ! is_null( $field_value ) ) {
					$form_fields   = $custom_form ? $custom_form->get_fields() : array();
					$fields_slugs  = wp_list_pluck( $form_fields, 'slug' );
					$field_key     = array_search( $element_id, $fields_slugs, true );
					$field_options = false !== $field_key && ! empty( $form_fields[ $field_key ]->raw['options'] )
							? wp_list_pluck( $form_fields[ $field_key ]->options, 'label', 'value' )
							: array();

					$selected_values = is_array( $field_value ) ? $field_value : array( $field_value );
					$value           = implode( ', ', array_keys( array_intersect( array_flip( $field_options ), array_map( 'stripslashes', $selected_values ) ) ) );
				}
			} elseif ( ( strpos( $element_id, 'postdata' ) !== false
						|| strpos( $element_id, 'upload' ) !== false
						|| strpos( $element_id, 'html' ) !== false
						|| strpos( $element_id, 'section' ) !== false
						|| strpos( $element_id, 'signature' ) !== false )
					&& $custom_form && $entry ) {
				$value = forminator_get_field_from_form_entry( $element_id, $custom_form, $entry );

				if ( strpos( $element_id, 'html' ) !== false ) {
					$value = forminator_replace_form_data( $value, $custom_form, $entry, $get_labels );
				}
			} elseif ( isset( $data[ $element_id ] ) ) {

				if ( strpos( $element_id, 'number' ) !== false ) {
					$field = $custom_form->get_field( $element_id, true );
					$value = Forminator_Field::forminator_number_formatting( $field, $data[ $element_id ] );
				} elseif (
					false !== stripos( $element_id, 'time' ) &&
					( false !== stripos( $element_id, '-hours' ) || false !== stripos( $element_id, '-minutes' ) )
				) {
					$value = str_pad( $data[ $element_id ], 2, '0', STR_PAD_LEFT );
				} else {
					$value = $data[ $element_id ];
				}
			} else {
				// element with suffixes, etc.
				// use submitted `data` since its possible to disable DB storage,.
				// causing Forminator_Form_Entry_Model = nothing.
				// and cant be used as reference.

				// DATE.
				if ( false !== stripos( $element_id, 'date' ) ) {
					$day_element_id    = $element_id . '-day';
					$month_element_id  = $element_id . '-month';
					$year_element_id   = $element_id . '-year';
					$format_element_id = $element_id . '-format';

					if ( isset( $data[ $day_element_id ] ) && isset( $data[ $month_element_id ] ) && isset( $data[ $year_element_id ] ) ) {
						$meta_value = array(
							'day'    => $data[ $day_element_id ],
							'month'  => $data[ $month_element_id ],
							'year'   => $data[ $year_element_id ],
							'format' => $data[ $format_element_id ],
						);
						$value      = Forminator_Form_Entry_Model::meta_value_to_string( 'date', $meta_value, true );
					}
				}
			}

			// If array, convert it to string.
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			if ( $urlencode ) {
				$value = rawurlencode( $value );
			}

			$content = str_replace( $match, $value, $content );
		}
	}

	return apply_filters( 'forminator_replace_form_data', $content, $data, $original_content );
}

/**
 * Format custom form data variables to html formatted
 *
 * @since 1.0.3
 *
 * @param string                      $content
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 * @param array                       $excluded
 *
 * @return mixed
 */
function forminator_replace_custom_form_data( $content, Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, $excluded = array() ) {
	$custom_form_datas = array(
		'{all_fields}'            => 'forminator_get_formatted_form_entry',
		'{all_non_empty_fields}'  => 'forminator_get_formatted_form_non_empty_entry',
		'{form_name}'             => 'forminator_get_formatted_form_name',
		'{submission_id}'         => 'forminator_get_submission_id',
		'{submission_url}'        => 'forminator_get_submission_url',
		'{account_approval_link}' => 'forminator_get_account_approval_link',
		'{username}'              => 'forminator_get_formatted_username',
		'{line_break}'            => 'forminator_get_formatted_line_break',
	);

	foreach ( $custom_form_datas as $custom_form_data => $function ) {
		if ( in_array( $custom_form_data, $excluded, true ) ) {
			continue;
		}
		if ( strpos( $content, $custom_form_data ) !== false ) {
			if ( is_callable( $function ) ) {
				$replacer = call_user_func( $function, $custom_form, $entry );
				$content  = str_replace( $custom_form_data, $replacer, $content );
			}
		}
	}

	return apply_filters( 'forminator_replace_custom_form_data', $content, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry, $excluded, $custom_form_datas );
}

/**
 * Get Html Formatted of form entry for email notification
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 * @param boolean                     $exclude_empty [Optional] Exclude empty form entry.
 *
 * @return string
 */
function forminator_get_formatted_form_entry( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, $exclude_empty = false ) {
	$ignored_field_types = Forminator_Form_Entry_Model::ignored_fields();

	/**
	 * Filter form fields before displaying
	 *
	 * @since 1.11
	 *
	 * @param array $form_fields
	 * @param Forminator_Form_Model $custom_form
	 *
	 * @return array
	 */
	$form_fields = apply_filters( 'forminator_custom_form_before_form_fields', $custom_form->get_fields(), $custom_form, Forminator_CForm_Front_Action::$prepared_data );
	if ( is_null( $form_fields ) ) {
		$form_fields = array();
	}
	$html = '<br/><ol>';

	foreach ( $form_fields as $form_field ) {
		$field_array    = $form_field->to_formatted_array();
		$field_type     = $field_array['type'];
		$field_id       = Forminator_Field::get_property( 'element_id', $field_array );

		if ( in_array( $field_id, Forminator_CForm_Front_Action::$hidden_fields, true ) ) {
			continue;
		}

		if ( 'section' === $field_type ) {
			$value = $form_field->__get( 'section_title' );
			if ( ! empty( $value ) ) {
				$html .= '</ol>';
				$html .= '<h4><b>' . $value . '</b></h4>';
				$html .= '<ol>';
			}
		} elseif ( 'html' === $field_type ) {
			$label   = $form_field->__get( 'field_label' );
			$value   = $form_field->__get( 'variations' );
			$content = forminator_replace_form_data( $value, $custom_form, $entry, true );
			$content = forminator_replace_variables( $content, $custom_form->id );
			$content = forminator_replace_custom_form_data( $content, $custom_form, $entry );
			$html   .= '</ol>';
			if ( ! empty( $label ) ) {
				$html .= '<h4><b>' . $label . '</b></h4>';
			}
			$html .= $content;
			$html .= '<ol>';
		} elseif ( in_array( $field_type, $ignored_field_types, true ) ) {
			continue;
		} else {
			$slug = $form_field->slug;
			if ( strpos( $slug, 'radio' ) !== false
					|| strpos( $slug, 'select' ) !== false
					|| strpos( $slug, 'checkbox' ) !== false
					) {
				$value = forminator_replace_form_data( '{' . $form_field->slug . '}', $custom_form, $entry, true );
			} else {
				$value = render_entry( $entry, $slug, $field_array, '', $exclude_empty );
			}
			/**
			 * Filter value of a field that is not saved in DB
			 */
			$value = apply_filters( 'forminator_custom_form_after_render_value', $value, $custom_form, $form_field->slug, Forminator_CForm_Front_Action::$prepared_data );
			if ( ! $exclude_empty || ! empty( $value ) ) {
				$html .= '<li>';
				$label = $form_field->get_label_for_entry();

				if ( ! empty( $label ) ) {
					$html .= '<b>' . $label . '</b><br/>';
				}
				if ( isset( $value ) && '' !== $value ) {
					$html .= $value . '<br/>';
				}
				$html .= '</li>';
			}
		}
	}
	$html .= '</ol><br/>';

	$filter_name = $exclude_empty ? 'forminator_get_formatted_form_non_empty_entry' : 'forminator_get_formatted_form_entry';

	return apply_filters( $filter_name, $html, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry, $ignored_field_types );
}

/**
 * Get Html Formatted of form entry
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_formatted_form_non_empty_entry( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	return forminator_get_formatted_form_entry( $custom_form, $entry, true );
}

/**
 * Get field from registered entries
 *
 * @since 1.0.5
 *
 * @param                               $element_id
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_field_from_form_entry( $element_id, Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	$form_fields = $custom_form->get_fields();
	if ( is_null( $form_fields ) ) {
		$form_fields = array();
	}
	foreach ( $form_fields as $form_field ) {
		/** @var  Forminator_Form_Field_Model $form_field */
		if ( $form_field->slug !== $element_id ) {
			continue;
		}
		$field_type = $form_field->__get( 'type' );
		if ( 'section' === $field_type ) {
			$value = $form_field->__get( 'section_title' );
		} elseif ( 'html' === $field_type ) {
			$variations = $form_field->__get( 'variations' );
			$value      = forminator_replace_variables( $variations, $custom_form->id );
		} else {
			$value = render_entry( $entry, $form_field->slug );
		}

		return $value;
	}
}

/**
 * Get Html Formatted of form name
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_formatted_form_name( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	return esc_html( forminator_get_form_name( $custom_form->id ) );
}

/**
 * Get Submission ID
 *
 * @since 1.1
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_submission_id( Forminator_Form_Model $custom_form, $entry = null ) {
	return is_object( $entry ) && isset( $entry->entry_id ) ? esc_html( $entry->entry_id ) : 0;
}

/**
 * Get referer url
 *
 * @since ?
 *
 * @param string $embed_url
 * @return string
 */
function forminator_get_referer_url( $embed_url = '' ) {
	$referer_url = '';
	if ( isset( $_REQUEST['extra']['referer_url'] ) ) {
		$referer_url = sanitize_text_field( $_REQUEST['extra']['referer_url'] );
	} elseif ( isset( $_REQUEST['referer_url'] ) ) {
		$referer_url = sanitize_text_field( $_REQUEST['referer_url'] );
	} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$referer_url = $_SERVER['HTTP_REFERER'];
	}

	if ( $referer_url === '' ) {
		$referer_url = $embed_url;
	}

	return $referer_url;
}

/*
 * Get Submission URL
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model $custom_form
 * @param Forminator_Form_Entry_Model  $entry
 *
 * @return string
 */
function forminator_get_submission_url( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	return '<a href="' . esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=forminator_forms&form_id=' . $entry->form_id . '&entry_id=' . $entry->entry_id ) ) . '">' . esc_html__( 'here', 'forminator' ) . '</a>';
}

/**
 * Get account approval link
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_account_approval_link( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	$key = $entry->get_meta( 'activation_key', '' );
	if ( ! empty( $key ) ) {
		$key = esc_url(
			add_query_arg(
				array(
					'page' => 'account_activation',
					'key'  => $key,
				),
				home_url( '/' )
			)
		);
		$key = '<a href="' . $key . '" target="_blank">' . $key . '</a>';
	}

	return '<p>' . $key . '</p>';
}

/**
 * Get username from registration form
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model       $custom_form
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return string
 */
function forminator_get_formatted_username( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	$username = '';
	if ( isset( $custom_form->settings['registration-username-field'] ) && ! empty( $custom_form->settings['registration-username-field'] ) ) {
		$username = $custom_form->settings['registration-username-field'];
		if ( ! empty( Forminator_CForm_Front_Action::$prepared_data[ $username ] ) ) {
			$username = '<b>' . Forminator_CForm_Front_Action::$prepared_data[ $username ] . '</b>';
		}
	}

	return $username;
}

/**
 * Get line break
 *
 * @since 1.11
 *
 * @return string
 */
function forminator_get_formatted_line_break() {
	return '&nbsp;<br/>';
}

/**
 * Return filtered editor content with replaced variables
 *
 * @since 1.0
 * @since 1.0.6 add `{form_id}` handle
 *
 * @param $content
 * @param $id
 *
 * @return string
 */
function forminator_replace_variables( $content, $id = false, $entry = null ) {
	$content_before_replacement = $content;

	// If we have no variables, skip.
	if ( strpos( $content, '{' ) !== false ) {
		$embed_url = ! empty( Forminator_CForm_Front_Action::$prepared_data['current_url'] )
				? Forminator_CForm_Front_Action::$prepared_data['current_url']
				: forminator_get_current_url();
		$post_id   = null;
		if ( ! empty( Forminator_CForm_Front_Action::$prepared_data['page_id'] ) ) {
			$post_id = Forminator_CForm_Front_Action::$prepared_data['page_id'];
		} elseif ( $embed_url ) {
			$post_id = url_to_postid( $embed_url );
		}
		$refer_url = forminator_get_referer_url( $embed_url );

		$variables = array(
			// Handle User IP Address variable.
			'{user_ip}'            => forminator_user_ip(),
			// Handle Date (mm/dd/yyyy) variable.
			'{date_mdy}'           => date_i18n( 'm/d/Y', forminator_local_timestamp(), true ),
			// Handle Date (dd/mm/yyyy) variable.
			'{date_dmy}'           => date_i18n( 'd/m/Y', forminator_local_timestamp(), true ),
			// Submission ID.
			'{submission_id}'	   => forminator_get_submission_id( new Forminator_Form_Model(), $entry ),
			// Submission time.
			'{submission_time}'    => date_i18n( 'g:i:s a, T', forminator_local_timestamp(), true ),
			// Handle Embed Post/Page ID variable.
			'{embed_id}'           => forminator_get_post_data( 'ID', $post_id ),
			// Handle Embed Post/Page Title variable.
			'{embed_title}'        => forminator_get_post_data( 'post_title', $post_id ),
			// Handle Embed URL variable.
			'{embed_url}'          => $embed_url,
			// Handle HTTP User Agent variable.
			// some browser not sending HTTP_USER_AGENT or some servers probably stripped this value.
			'{user_agent}'         => isset( $_SERVER['HTTP_USER_AGENT'] ) ? esc_html( $_SERVER['HTTP_USER_AGENT'] ) : '',
			// Handle site url variable.
			'{site_url}'           => site_url(),
			// Handle HTTP Refer URL variable.
			'{refer_url}'          => $refer_url,
			'{http_refer}'         => $refer_url,
			// Handle User ID variable.
			'{user_id}'            => forminator_get_user_data( 'ID' ),
			// Handle User Display Name variable.
			'{user_name}'          => forminator_get_user_data( 'display_name' ),
			// Handle User Email variable.
			'{user_email}'         => forminator_get_user_data( 'user_email' ),
			// Handle User Login variable.
			'{user_login}'         => forminator_get_user_data( 'user_login' ),
			// Handle Submissions number.
			'{submissions_number}' => Forminator_Form_Entry_Model::count_entries( $id ),
			// Handle site title variable.
			'{site_title}'         => get_bloginfo( 'name' ),
		);
		// Handle form_name data.
		if ( strpos( $content, '{form_name}' ) !== false ) {
			$variables['{form_name}'] = ( false !== $id ) ? esc_html( forminator_get_form_name( $id ) ) : '';
		}

		// handle form_id.
		if ( $id ) {
			$variables['{form_id}'] = $id;
		}

		$content = str_replace( array_keys( $variables ), array_values( $variables ), $content );
	}

	return apply_filters( 'forminator_replace_variables', $content, $content_before_replacement );
}

/**
 * Render entry
 * Used in email notifications
 * TODO: refactor this
 *
 * @since 1.0
 *
 * @param object $item        - the entry.
 * @param string $column_name - the column name.
 *
 * @param null   $field       @since 1.0.5, optional Forminator_Form_Field_Model.
 * @param string $type
 *
 * @return string
 */
function render_entry( $item, $column_name, $field = null, $type = '', $remove_empty = false ) {
	$data = $item->get_meta( $column_name, '' );

	$is_calculation = false;
	if ( stripos( $column_name, 'calculation' ) !== false ) {
		$is_calculation = true;
	}

	if ( $is_calculation && $data ) {
		return Forminator_Form_Entry_Model::meta_value_to_string( 'calculation', $data, true );
	}

	if ( $data || '0' === $data ) {
		$currency_symbol = forminator_get_currency_symbol();
		if ( is_array( $data ) ) {
			if ( 'non_empty' === $type ) {
				$data = array_filter( $data );
			}
			if ( stripos( $column_name, 'time' ) !== false && 1 === count( $data ) && isset( $data['ampm'] ) ) {
				$data = array();
			}
			$output       = '';
			$product_cost = 0;
			$is_product   = false;
			$countries    = forminator_get_countries_list();

			if ( ! empty( $data ) ) {
				foreach ( $data as $key => $value ) {
					if ( is_array( $value ) ) {
						if ( 'file' === $key && isset( $value['file_url'] ) ) {
							$file_urls = is_array( $value['file_url'] ) ? $value['file_url'] : array( $value['file_url'] );
							$files_count = count( $file_urls );
							foreach ( $file_urls as $index => $file_url ) {
								$file_name = basename( $file_url );
								$file_name = "<a href='" . esc_url( $file_url ) . "' target='_blank' rel='noreferrer' title='" . __( 'View File', 'forminator' ) . "'>$file_name</a>";
								$output   .= $file_name;
								$output   .= $index < $files_count - 1 ? '<br/>' : '';
							}
						}
					} else {
						if ( ! is_int( $key ) ) {
							if ( 'postdata' === $key ) {
								// possible empty when postdata not required.
								if ( ! empty( $value ) ) {

                                    $post_id = $data['postdata'];
                                    $url = get_edit_post_link( $post_id, 'link' );

                                    // Title
                                    $title = get_the_title( $post_id );
                                    $title = ! empty( $title ) ? $title : __( '(no title)', 'forminator' );

                                    $output .= '<ul>';

                                        $output .= '<li>';
                                        $output .= '<b>' . esc_html__( 'Title', 'forminator' ) . ':</b> ';
                                        $output .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Edit Post', 'forminator' ) . '">'
                                                        . $title .
                                                    '</a>';
                                        $output .= '</li>';

                                        // Content
                                        if ( ! empty( $data['value']['post-content'] ) ) {
                                            $post_content  = $data['value']['post-content'];
                                            $output .= '<li>';
                                            $output .= '<b>' . esc_html__( 'Content', 'forminator' ) . ':</b><br>';
                                            $output .= wp_kses( $post_content, 'post' );
                                            $output .= '</li>';
                                        }

                                        // Excerpt
                                        if ( ! empty( $data['value']['post-excerpt'] ) ) {
                                            $post_excerpt = $data['value']['post-excerpt'];
                                            $output .= '<li>';
                                            $output .= '<b>' . esc_html__( 'Excerpt', 'forminator' ) . ':</b><br>';
                                            $output .= wp_strip_all_tags( $post_excerpt );
                                            $output .= '</li>';
                                        }

                                        // Category
                                        if ( ! empty( $data['value']['category'] ) ) {
                                            $post_category = $data['value']['category'];
                                            $post_category = get_the_category_by_ID( $post_category );
                                            // In case of deleted categories.
                                            if ( ! empty( $post_category ) ) {
                                                $output .= '<li>';
                                                $output .= '<b>' . esc_html__( 'Category', 'forminator' ) . ':</b> ';
                                                $output .= $post_category;
                                                $output .= '</li>';
                                            }
                                        }

                                        // Tags
                                        if ( ! empty( $data['value']['post_tag'] ) ) {
                                            $post_tag_id = $data['value']['post_tag'];
                                            $term_args = array(
                                                'taxonomy'          => 'post_tag',
                                                'term_taxonomy_id'  => $post_tag_id,
                                                'hide_empty'        => false,
                                                'fields'            => 'names',
                                            );
                                            $term_query = new WP_Term_Query( $term_args );

                                            // In case of deleted tags.
                                            if ( ! empty( $tag = $term_query->terms ) ) {
                                                $output .= '<li>';
                                                $output .= '<b>' . esc_html__( 'Tag', 'forminator' ) . ':</b> ';
                                                $output .= $tag[0];
                                                $output .= '</li>';
                                            }
                                        }

                                        // Featured Image
                                        if ( ! empty( $data['value']['post-image'] ) && ! empty( $data['value']['post-image']['attachment_id'] ) ) {
                                            $post_image_id = $data['value']['post-image']['attachment_id'];
                                            $output .= '<li>';
                                            $output .= '<b>' . esc_html__( 'Featured image', 'forminator' ) . ':</b><br>';
                                            $output .= wp_get_attachment_image( $post_image_id, array( 100, 100 ) );
                                            $output .= '</li>';
                                        }

                                        // Custom fields
                                        if ( ! empty( $data['value']['post-custom'] ) ) {
                                            $post_custom   = $data['value']['post-custom'];
                                            $output .= '<li>';
                                                $output .= '<b>' . esc_html__( 'Custom fields', 'forminator' ) . ':</b><br>';
                                                $output .= '<ul class="' . esc_attr( 'bulleted' ) . '">';
                                                    foreach ( $post_custom as $field ) {
                                                        if ( ! empty( $field['value'] ) ) {
                                                            $output .= '<li>';
                                                            $output .= esc_html( $field['key'] ) . ': ';
                                                            $output .= esc_html( $field['value'] );
                                                            $output .= '</li>';
                                                        }
                                                    }
                                                $output .= '</ul>';
                                            $output .= '</li>';
                                        }

                                    $output .= '</ul>';

								} else {
                                    $output = ' ';
								}
							} else {
								if ( is_string( $key ) ) {
									if ( 'product-id' === $key || 'product-quantity' === $key ) {
										if ( 0 === $product_cost ) {
											$product_cost = $value;
										} else {
											$product_cost = $product_cost * $value;
										}
										$is_product = true;
									} else {

										$key_slug = $key;

										if ( in_array( $key, Forminator_Form_Entry_Model::field_suffix(), true ) ) {
											$key = Forminator_Form_Entry_Model::translate_suffix( $key );
										} else {
											$key = strtolower( $key );
											$key = ucfirst( str_replace( array( '-', '_' ), ' ', $key ) );
										}

										// Name labels
										if ( 'prefix' === $key_slug && ! empty( $field[ 'prefix_label' ] ) ) {
											$key = $field[ 'prefix_label' ];
										}
										if ( 'first-name' === $key_slug && ! empty( $field[ 'fname_label' ] ) ) {
											$key = $field[ 'fname_label' ];
										}
										if ( 'middle-name' === $key_slug && ! empty( $field[ 'mname_label' ] ) ) {
											$key = $field[ 'mname_label' ];
										}
										if ( 'last-name' === $key_slug && ! empty( $field[ 'lname_label' ] ) ) {
											$key = $field[ 'lname_label' ];
										}

										// Address labels
										if ( 'street_address' === $key_slug && ! empty( $field[ 'street_address_label' ] ) ) {
											$key = $field[ 'street_address_label' ];
										}
										if ( 'address_line' === $key_slug && ! empty( $field[ 'address_line_label' ] ) ) {
											$key = $field[ 'address_line_label' ];
										}
										if ( 'city' === $key_slug && ! empty( $field[ 'address_city_label' ] ) ) {
											$key = $field[ 'address_city_label' ];
										}
										if ( 'state' === $key_slug && ! empty( $field[ 'address_state_label' ] ) ) {
											$key = $field[ 'address_state_label' ];
										}
										if ( 'zip' === $key_slug && ! empty( $field[ 'address_zip_label' ] ) ) {
											$key = $field[ 'address_zip_label' ];
										}
										if ( 'country' === $key_slug ) {

											if ( ! empty( $field[ 'address_country_label' ] ) ) {
												$key = $field[ 'address_country_label' ];
											}
											if ( isset( $countries[ $value ] ) ) {
												$value = $countries[ $value ];
											}
										}

										if ( $remove_empty && empty( $value ) ) {
											$output .= '';
										} else {
											$output .= sprintf( __( '<strong>%1$s : </strong> %2$s', 'forminator' ), esc_html( $key ), esc_html( $value ) ) . "<br/> ";
										}
									}
								}
							}
						}
					}
				}
			}
			if ( $is_product ) {
				/* translators: ... */
				$output = sprintf( __( '<strong>Total</strong> %s', 'forminator' ), $currency_symbol . '' . $product_cost );
			} else {
				if ( ! empty( $output ) ) {
					if (
						isset( $column_name ) &&
						(
							false !== strpos( $column_name, 'name' ) ||
							false !== strpos( $column_name, 'address' ) ||
							false !== strpos( $column_name, 'upload' ) ||
							false !== strpos( $column_name, 'postdata' ) ||
							false !== strpos( $column_name, 'signature' )
						)
					) {
						$output = trim( $output );

					} elseif ( false !== strpos( $column_name, 'date' ) && 'select' === $field['field_type'] ) {
						$meta_value = array(
							'day'    => $data['day'],
							'month'  => $data['month'],
							'year'   => $data['year'],
							'format' => $data['format'],
						);

						$output	= Forminator_Form_Entry_Model::meta_value_to_string( 'date', $meta_value, true ) . '<br/>';
						$output .= sprintf( esc_html__( 'Format%s %s %s', 'forminator' ), ':', $data['format'], '<br/>' );

					} elseif ( false !== strpos( $column_name, 'time' ) ) {
						$output	= Forminator_Form_Entry_Model::meta_value_to_string( 'time', $data, true ) . '<br/>';

					} else {
						$output = substr( trim( $output ), 0, - 1 );

					}
				} else {
					$output = implode( ', ', $data );
				}
			}

			return $output;
		} else {
			if ( stripos( $column_name, 'currency' ) !== false || stripos( $column_name, 'number' ) !== false ) {
				$data = Forminator_Field::forminator_number_formatting( $field, $data );
			}

			return $data;
		}
	}

	return '';
}

/**
 * Return countries list
 *
 * @since 1.0
 * @return array
 */
function forminator_get_countries_list() {
	return apply_filters(
		'forminator_countries_list',
		array(
			'AF' => esc_html__( 'Afghanistan', 'forminator' ),
			'AL' => esc_html__( 'Albania', 'forminator' ),
			'DZ' => esc_html__( 'Algeria', 'forminator' ),
			'AS' => esc_html__( 'American Samoa', 'forminator' ),
			'AD' => esc_html__( 'Andorra', 'forminator' ),
			'AO' => esc_html__( 'Angola', 'forminator' ),
			'AI' => esc_html__( 'Anguilla', 'forminator' ),
			'AQ' => esc_html__( 'Antarctica', 'forminator' ),
			'AG' => esc_html__( 'Antigua and Barbuda', 'forminator' ),
			'AR' => esc_html__( 'Argentina', 'forminator' ),
			'AM' => esc_html__( 'Armenia', 'forminator' ),
			'AU' => esc_html__( 'Australia', 'forminator' ),
			'AW' => esc_html__( 'Aruba', 'forminator' ),
			'AT' => esc_html__( 'Austria', 'forminator' ),
			'AZ' => esc_html__( 'Azerbaijan', 'forminator' ),
			'BS' => esc_html__( 'Bahamas', 'forminator' ),
			'BH' => esc_html__( 'Bahrain', 'forminator' ),
			'BD' => esc_html__( 'Bangladesh', 'forminator' ),
			'BB' => esc_html__( 'Barbados', 'forminator' ),
			'BY' => esc_html__( 'Belarus', 'forminator' ),
			'BE' => esc_html__( 'Belgium', 'forminator' ),
			'BZ' => esc_html__( 'Belize', 'forminator' ),
			'BJ' => esc_html__( 'Benin', 'forminator' ),
			'BM' => esc_html__( 'Bermuda', 'forminator' ),
			'BT' => esc_html__( 'Bhutan', 'forminator' ),
			'BO' => esc_html__( 'Bolivia', 'forminator' ),
			'BA' => esc_html__( 'Bosnia and Herzegovina', 'forminator' ),
			'BW' => esc_html__( 'Botswana', 'forminator' ),
			'BV' => esc_html__( 'Bouvet Island', 'forminator' ),
			'BR' => esc_html__( 'Brazil', 'forminator' ),
			'IO' => esc_html__( 'British Indian Ocean Territory', 'forminator' ),
			'BN' => esc_html__( 'Brunei', 'forminator' ),
			'BG' => esc_html__( 'Bulgaria', 'forminator' ),
			'BF' => esc_html__( 'Burkina Faso', 'forminator' ),
			'BI' => esc_html__( 'Burundi', 'forminator' ),
			'KH' => esc_html__( 'Cambodia', 'forminator' ),
			'CM' => esc_html__( 'Cameroon', 'forminator' ),
			'CA' => esc_html__( 'Canada', 'forminator' ),
			'CV' => esc_html__( 'Cabo Verde', 'forminator' ),
			'KY' => esc_html__( 'Cayman Islands', 'forminator' ),
			'CF' => esc_html__( 'Central African Republic', 'forminator' ),
			'TD' => esc_html__( 'Chad', 'forminator' ),
			'CL' => esc_html__( 'Chile', 'forminator' ),
			'CN' => html_entity_decode( esc_html__( "China, People's Republic of", 'forminator' ), ENT_QUOTES ),
			'CX' => esc_html__( 'Christmas Island', 'forminator' ),
			'CC' => esc_html__( 'Cocos Islands', 'forminator' ),
			'CO' => esc_html__( 'Colombia', 'forminator' ),
			'KM' => esc_html__( 'Comoros', 'forminator' ),
			'CD' => esc_html__( 'Congo, Democratic Republic of the', 'forminator' ),
			'CG' => esc_html__( 'Congo, Republic of the', 'forminator' ),
			'CK' => esc_html__( 'Cook Islands', 'forminator' ),
			'CR' => esc_html__( 'Costa Rica', 'forminator' ),
			'CI' => html_entity_decode( esc_html__( "Côte d'Ivoire", 'forminator'  ), ENT_QUOTES ),
			'HR' => esc_html__( 'Croatia', 'forminator' ),
			'CU' => esc_html__( 'Cuba', 'forminator' ),
			'CW' => esc_html__( 'Curaçao', 'forminator' ),
			'CY' => esc_html__( 'Cyprus', 'forminator' ),
			'CZ' => esc_html__( 'Czech Republic', 'forminator' ),
			'DK' => esc_html__( 'Denmark', 'forminator' ),
			'DJ' => esc_html__( 'Djibouti', 'forminator' ),
			'DM' => esc_html__( 'Dominica', 'forminator' ),
			'DO' => esc_html__( 'Dominican Republic', 'forminator' ),
			'TL' => esc_html__( 'East Timor', 'forminator' ),
			'EC' => esc_html__( 'Ecuador', 'forminator' ),
			'EG' => esc_html__( 'Egypt', 'forminator' ),
			'SV' => esc_html__( 'El Salvador', 'forminator' ),
			'GQ' => esc_html__( 'Equatorial Guinea', 'forminator' ),
			'ER' => esc_html__( 'Eritrea', 'forminator' ),
			'EE' => esc_html__( 'Estonia', 'forminator' ),
			'ET' => esc_html__( 'Ethiopia', 'forminator' ),
			'FK' => esc_html__( 'Falkland Islands', 'forminator' ),
			'FO' => esc_html__( 'Faroe Islands', 'forminator' ),
			'FJ' => esc_html__( 'Fiji', 'forminator' ),
			'FI' => esc_html__( 'Finland', 'forminator' ),
			'FR' => esc_html__( 'France', 'forminator' ),
			'FX' => esc_html__( 'France, Metropolitan', 'forminator' ),
			'GF' => esc_html__( 'French Guiana', 'forminator' ),
			'PF' => esc_html__( 'French Polynesia', 'forminator' ),
			'TF' => esc_html__( 'French South Territories', 'forminator' ),
			'GA' => esc_html__( 'Gabon', 'forminator' ),
			'GM' => esc_html__( 'Gambia', 'forminator' ),
			'GE' => esc_html__( 'Georgia', 'forminator' ),
			'DE' => esc_html__( 'Germany', 'forminator' ),
			'GG' => esc_html__( 'Guernsey', 'forminator' ),
			'GH' => esc_html__( 'Ghana', 'forminator' ),
			'GI' => esc_html__( 'Gibraltar', 'forminator' ),
			'GR' => esc_html__( 'Greece', 'forminator' ),
			'GL' => esc_html__( 'Greenland', 'forminator' ),
			'GD' => esc_html__( 'Grenada', 'forminator' ),
			'GP' => esc_html__( 'Guadeloupe', 'forminator' ),
			'GU' => esc_html__( 'Guam', 'forminator' ),
			'GT' => esc_html__( 'Guatemala', 'forminator' ),
			'GN' => esc_html__( 'Guinea', 'forminator' ),
			'GW' => esc_html__( 'Guinea-Bissau', 'forminator' ),
			'GY' => esc_html__( 'Guyana', 'forminator' ),
			'HT' => esc_html__( 'Haiti', 'forminator' ),
			'HM' => esc_html__( 'Heard Island And Mcdonald Island', 'forminator' ),
			'HN' => esc_html__( 'Honduras', 'forminator' ),
			'HK' => esc_html__( 'Hong Kong', 'forminator' ),
			'HU' => esc_html__( 'Hungary', 'forminator' ),
			'IS' => esc_html__( 'Iceland', 'forminator' ),
			'IN' => esc_html__( 'India', 'forminator' ),
			'ID' => esc_html__( 'Indonesia', 'forminator' ),
			'IR' => esc_html__( 'Iran', 'forminator' ),
			'IQ' => esc_html__( 'Iraq', 'forminator' ),
			'IE' => esc_html__( 'Ireland', 'forminator' ),
			'IL' => esc_html__( 'Israel', 'forminator' ),
			'IT' => esc_html__( 'Italy', 'forminator' ),
			'JM' => esc_html__( 'Jamaica', 'forminator' ),
			'JP' => esc_html__( 'Japan', 'forminator' ),
			'JE' => esc_html__( 'Jersey', 'forminator' ),
			'JT' => esc_html__( 'Johnston Island', 'forminator' ),
			'JO' => esc_html__( 'Jordan', 'forminator' ),
			'KZ' => esc_html__( 'Kazakhstan', 'forminator' ),
			'KE' => esc_html__( 'Kenya', 'forminator' ),
			'KI' => esc_html__( 'Kiribati', 'forminator' ),
			'KP' => html_entity_decode( esc_html__( "Korea, Democratic People's Republic of", 'forminator' ), ENT_QUOTES ),
			'KR' => esc_html__( 'Korea, Republic of', 'forminator' ),
			'XK' => esc_html__( 'Kosovo', 'forminator' ),
			'KW' => esc_html__( 'Kuwait', 'forminator' ),
			'KG' => esc_html__( 'Kyrgyzstan', 'forminator' ),
			'LA' => html_entity_decode( esc_html__( "Lao People's Democratic Republic", 'forminator' ), ENT_QUOTES ),
			'LV' => esc_html__( 'Latvia', 'forminator' ),
			'LB' => esc_html__( 'Lebanon', 'forminator' ),
			'LS' => esc_html__( 'Lesotho', 'forminator' ),
			'LR' => esc_html__( 'Liberia', 'forminator' ),
			'LY' => esc_html__( 'Libya', 'forminator' ),
			'LI' => esc_html__( 'Liechtenstein', 'forminator' ),
			'LT' => esc_html__( 'Lithuania', 'forminator' ),
			'LU' => esc_html__( 'Luxembourg', 'forminator' ),
			'MO' => esc_html__( 'Macau', 'forminator' ),
			'MK' => esc_html__( 'North Macedonia', 'forminator' ),
			'MG' => esc_html__( 'Madagascar', 'forminator' ),
			'MW' => esc_html__( 'Malawi', 'forminator' ),
			'MY' => esc_html__( 'Malaysia', 'forminator' ),
			'MV' => esc_html__( 'Maldives', 'forminator' ),
			'ML' => esc_html__( 'Mali', 'forminator' ),
			'MT' => esc_html__( 'Malta', 'forminator' ),
			'MH' => esc_html__( 'Marshall Islands', 'forminator' ),
			'MQ' => esc_html__( 'Martinique', 'forminator' ),
			'MR' => esc_html__( 'Mauritania', 'forminator' ),
			'MU' => esc_html__( 'Mauritius', 'forminator' ),
			'YT' => esc_html__( 'Mayotte', 'forminator' ),
			'MX' => esc_html__( 'Mexico', 'forminator' ),
			'FM' => esc_html__( 'Micronesia', 'forminator' ),
			'MD' => esc_html__( 'Moldova', 'forminator' ),
			'MC' => esc_html__( 'Monaco', 'forminator' ),
			'MN' => esc_html__( 'Mongolia', 'forminator' ),
			'MS' => esc_html__( 'Montserrat', 'forminator' ),
			'ME' => esc_html__( 'Montenegro', 'forminator' ),
			'MA' => esc_html__( 'Morocco', 'forminator' ),
			'MZ' => esc_html__( 'Mozambique', 'forminator' ),
			'MM' => esc_html__( 'Myanmar', 'forminator' ),
			'NA' => esc_html__( 'Namibia', 'forminator' ),
			'NR' => esc_html__( 'Nauru', 'forminator' ),
			'NP' => esc_html__( 'Nepal', 'forminator' ),
			'NL' => esc_html__( 'Netherlands', 'forminator' ),
			'AN' => esc_html__( 'Netherlands Antilles', 'forminator' ),
			'NC' => esc_html__( 'New Caledonia', 'forminator' ),
			'NZ' => esc_html__( 'New Zealand', 'forminator' ),
			'NI' => esc_html__( 'Nicaragua', 'forminator' ),
			'NE' => esc_html__( 'Niger', 'forminator' ),
			'NG' => esc_html__( 'Nigeria', 'forminator' ),
			'NU' => esc_html__( 'Niue', 'forminator' ),
			'NF' => esc_html__( 'Norfolk Island', 'forminator' ),
			'MP' => esc_html__( 'Northern Mariana Islands', 'forminator' ),
			'NO' => esc_html__( 'Norway', 'forminator' ),
			'OM' => esc_html__( 'Oman', 'forminator' ),
			'PK' => esc_html__( 'Pakistan', 'forminator' ),
			'PW' => esc_html__( 'Palau', 'forminator' ),
			'PS' => esc_html__( 'Palestine, State of', 'forminator' ),
			'PA' => esc_html__( 'Panama', 'forminator' ),
			'PG' => esc_html__( 'Papua New Guinea', 'forminator' ),
			'PY' => esc_html__( 'Paraguay', 'forminator' ),
			'PE' => esc_html__( 'Peru', 'forminator' ),
			'PH' => esc_html__( 'Philippines', 'forminator' ),
			'PN' => esc_html__( 'Pitcairn Islands', 'forminator' ),
			'PL' => esc_html__( 'Poland', 'forminator' ),
			'PT' => esc_html__( 'Portugal', 'forminator' ),
			'PR' => esc_html__( 'Puerto Rico', 'forminator' ),
			'QA' => esc_html__( 'Qatar', 'forminator' ),
			'RE' => esc_html__( 'Reunion Island', 'forminator' ),
			'RO' => esc_html__( 'Romania', 'forminator' ),
			'RU' => esc_html__( 'Russia', 'forminator' ),
			'RW' => esc_html__( 'Rwanda', 'forminator' ),
			'KN' => esc_html__( 'Saint Kitts and Nevis', 'forminator' ),
			'LC' => esc_html__( 'Saint Lucia', 'forminator' ),
			'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'forminator' ),
			'WS' => esc_html__( 'Samoa', 'forminator' ),
			'SH' => esc_html__( 'Saint Helena', 'forminator' ),
			'PM' => html_entity_decode( esc_html__( 'Saint Pierre & Miquelon', 'forminator' ), ENT_QUOTES ),
			'SM' => esc_html__( 'San Marino', 'forminator' ),
			'ST' => esc_html__( 'Sao Tome and Principe', 'forminator' ),
			'SA' => esc_html__( 'Saudi Arabia', 'forminator' ),
			'SN' => esc_html__( 'Senegal', 'forminator' ),
			'RS' => esc_html__( 'Serbia', 'forminator' ),
			'SC' => esc_html__( 'Seychelles', 'forminator' ),
			'SL' => esc_html__( 'Sierra Leone', 'forminator' ),
			'SG' => esc_html__( 'Singapore', 'forminator' ),
			'MF' => esc_html__( 'Sint Maarten', 'forminator' ),
			'SK' => esc_html__( 'Slovakia', 'forminator' ),
			'SI' => esc_html__( 'Slovenia', 'forminator' ),
			'SB' => esc_html__( 'Solomon Islands', 'forminator' ),
			'SO' => esc_html__( 'Somalia', 'forminator' ),
			'ZA' => esc_html__( 'South Africa', 'forminator' ),
			'GS' => esc_html__( 'South Georgia and South Sandwich', 'forminator' ),
			'ES' => esc_html__( 'Spain', 'forminator' ),
			'LK' => esc_html__( 'Sri Lanka', 'forminator' ),
			'XX' => esc_html__( 'Stateless Persons', 'forminator' ),
			'SD' => esc_html__( 'Sudan', 'forminator' ),
			'SS' => esc_html__( 'Sudan, South', 'forminator' ),
			'SR' => esc_html__( 'Suriname', 'forminator' ),
			'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'forminator' ),
			'SZ' => esc_html__( 'Swaziland', 'forminator' ),
			'SE' => esc_html__( 'Sweden', 'forminator' ),
			'CH' => esc_html__( 'Switzerland', 'forminator' ),
			'SY' => esc_html__( 'Syria', 'forminator' ),
			'TW' => esc_html__( 'Taiwan, Republic of China', 'forminator' ),
			'TJ' => esc_html__( 'Tajikistan', 'forminator' ),
			'TZ' => esc_html__( 'Tanzania', 'forminator' ),
			'TH' => esc_html__( 'Thailand', 'forminator' ),
			'TG' => esc_html__( 'Togo', 'forminator' ),
			'TK' => esc_html__( 'Tokelau', 'forminator' ),
			'TO' => esc_html__( 'Tonga', 'forminator' ),
			'TT' => esc_html__( 'Trinidad and Tobago', 'forminator' ),
			'TN' => esc_html__( 'Tunisia', 'forminator' ),
			'TR' => esc_html__( 'Turkey', 'forminator' ),
			'TM' => esc_html__( 'Turkmenistan', 'forminator' ),
			'TC' => esc_html__( 'Turks And Caicos Islands', 'forminator' ),
			'TV' => esc_html__( 'Tuvalu', 'forminator' ),
			'UG' => esc_html__( 'Uganda', 'forminator' ),
			'UA' => esc_html__( 'Ukraine', 'forminator' ),
			'AE' => esc_html__( 'United Arab Emirates', 'forminator' ),
			'GB' => esc_html__( 'United Kingdom', 'forminator' ),
			'UM' => esc_html__( 'US Minor Outlying Islands', 'forminator' ),
			'US' => esc_html__( 'United States of America (USA)', 'forminator' ),
			'UY' => esc_html__( 'Uruguay', 'forminator' ),
			'UZ' => esc_html__( 'Uzbekistan', 'forminator' ),
			'VU' => esc_html__( 'Vanuatu', 'forminator' ),
			'VA' => esc_html__( 'Vatican City', 'forminator' ),
			'VE' => esc_html__( 'Venezuela', 'forminator' ),
			'VN' => esc_html__( 'Vietnam', 'forminator' ),
			'VG' => esc_html__( 'Virgin Islands, British', 'forminator' ),
			'VI' => esc_html__( 'Virgin Islands, U.S.', 'forminator' ),
			'WF' => esc_html__( 'Wallis And Futuna Islands', 'forminator' ),
			'EH' => esc_html__( 'Western Sahara', 'forminator' ),
			'YE' => esc_html__( 'Yemen Arab Rep.', 'forminator' ),
			'YD' => esc_html__( 'Yemen Democratic', 'forminator' ),
			'ZM' => esc_html__( 'Zambia', 'forminator' ),
			'ZW' => esc_html__( 'Zimbabwe', 'forminator' ),
		)
	);
}

/**
 * Return sorted available fields
 *
 * @since 1.6
 *
 * @param     $sort_attr
 * @param int       $sort_flag
 *
 * @return array
 */
function forminator_get_fields_sorted( $sort_attr, $sort_flag = SORT_ASC ) {
	$fields       = array();
	$fields_array = forminator_get_fields();

	if ( ! empty( $fields_array ) ) {
		foreach ( $fields_array as $key => $field ) {
			$field_key = '';
			if ( isset( $field->$sort_attr ) ) {
				$field_key = $field->$sort_attr;
			}

			if ( ! empty( $field_key ) ) {
				if ( isset( $fields[ $field_key ] ) ) {
					if ( is_int( $field_key ) ) {
						$field_key = max( array_keys( $fields ) );
						$field_key ++;// increase where there is dupe.
					}
				}
				$fields[ $field_key ] = $field;
			} else {
				$fields[] = $field;
			}
		}
	}

	if ( SORT_ASC === $sort_flag ) {
		ksort( $fields );
	} else {
		krsort( $fields );
	}

	$fields = array_values( $fields );

	return apply_filters( 'forminator_fields_sorted', $fields, $fields_array, $sort_attr, $sort_flag );
}

/**
 * Retrieves the list of common file extensions and their types.
 *
 * extending @see get_allowed_mime_types without filter
 *
 * @since 1.6
 */
function forminator_get_ext_types() {
	/**
	 * - image
	 * - audio
	 * - video
	 * - text
	 * - Doc
	 * - Archive
	 * - Interactive
	 */

	$forminator_types = array(
		'image'       => array(
			// Image formats.
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tiff|tif'     => 'image/tiff',
			'ico'          => 'image/x-icon',
			'psd'          => 'application/octet-stream',
			'xcf'          => 'application/octet-stream',
			'heic'         => 'image/heic',
		),
		'audio'       => array(
			// Audio formats.
			'mp3|m4a|m4b' => 'audio/mpeg',
			'ra|ram'      => 'audio/x-realaudio',
			'wav'         => 'audio/wav',
			'ogg|oga'     => 'audio/ogg',
			'mid|midi'    => 'audio/midi',
			'wma'         => 'audio/x-ms-wma',
			'wax'         => 'audio/x-ms-wax',
			'mka'         => 'audio/x-matroska',
			'aac'         => 'audio/aac',
			'flac'        => 'audio/flac',
		),
		'video'       => array(
			'asf|asx'      => 'video/x-ms-asf',
			'wmv'          => 'video/x-ms-wmv',
			'wmx'          => 'video/x-ms-wmx',
			'wm'           => 'video/x-ms-wm',
			'avi'          => 'video/avi',
			'divx'         => 'video/divx',
			'flv'          => 'video/x-flv',
			'mov|qt'       => 'video/quicktime',
			'mpeg|mpg|mpe' => 'video/mpeg',
			'mp4|m4v'      => 'video/mp4',
			'ogv'          => 'video/ogg',
			'webm'         => 'video/webm',
			'mkv'          => 'video/x-matroska',
			'3gp|3gpp'     => 'video/3gpp', // Can also be audio.
			'3g2|3gp2'     => 'video/3gpp2', // Can also be audio.
		),
		'document'    => array(
			// MS Office formats.
			'doc'                          => 'application/msword',
			'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
			'wri'                          => 'application/vnd.ms-write',

			'mdb'                          => 'application/vnd.ms-access',
			'mpp'                          => 'application/vnd.ms-project',
			'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',

			'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',

			// OpenOffice formats.
			'odt'                          => 'application/vnd.oasis.opendocument.text',
			'odp'                          => 'application/vnd.oasis.opendocument.presentation',
			'odg'                          => 'application/vnd.oasis.opendocument.graphics',
			'odc'                          => 'application/vnd.oasis.opendocument.chart',
			'odb'                          => 'application/vnd.oasis.opendocument.database',
			'odf'                          => 'application/vnd.oasis.opendocument.formula',

			'pages'                        => 'application/vnd.apple.pages',

			'wp|wpd'                       => 'application/wordperfect',

			'pdf'                          => 'application/pdf',
			'oxps'                         => 'application/oxps',
			'xps'                          => 'application/vnd.ms-xpsdocument',

		),
		'archive'     => array(
			'tar'     => 'application/x-tar',
			'zip'     => 'application/zip',
			'gz|gzip' => 'application/x-gzip',
			'rar'     => 'application/rar',
			'7z'      => 'application/x-7z-compressed',
		),
		'text'        => array(
			// Text formats.
			'txt|asc|c|cc|h|srt' => 'text/plain',
			'csv'                => 'text/csv',
			'tsv'                => 'text/tab-separated-values',
			'ics'                => 'text/calendar',
			'rtx'                => 'text/richtext',
			'css'                => 'text/css',
			'htm|html'           => 'text/html',

			'rtf'                => 'application/rtf',
			'js'                 => 'application/javascript',
			'vtt'                => 'text/vtt',
			'dfxp'               => 'application/ttaf+xml',
		),
		'spreadsheet' => array(
			'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
			'xlsx'            => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm'            => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb'            => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx'            => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm'            => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam'            => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'ods'             => 'application/vnd.oasis.opendocument.spreadsheet',
			'numbers'         => 'application/vnd.apple.numbers',
		),
		'interactive' => array(
			'swf'   => 'application/x-shockwave-flash',
			'class' => 'application/java',
			'exe'   => 'application/x-msdownload',
			'key'   => 'application/vnd.apple.keynote',
			'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm'  => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm'  => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm'  => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam'  => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm'  => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
		),
	);

	foreach ( $forminator_types as $type => $forminator_type ) {
		$forminator_types[ $type ] = array_keys( $forminator_type );
	}

	/**
	 * Filter extensions types of files
	 *
	 * @since 1.6
	 *
	 * @param array $forminator_types
	 */
	$forminator_types = apply_filters( 'forminator_get_ext_types', $forminator_types );

	return $forminator_types;
}

/**
 * Format poll data variables to html formatted
 *
 * @since 1.6.1
 *
 * @param string                      $content
 * @param Forminator_Poll_Model       $poll
 * @param array                       $data - submitted `_POST` data.
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return mixed
 */
function forminator_replace_poll_form_data( $content, Forminator_Poll_Model $poll, $data, Forminator_Form_Entry_Model $entry ) {
	if ( stripos( $content, '{poll_name}' ) !== false ) {
		$poll_name = forminator_get_name_from_model( $poll );
		$content   = str_ireplace( '{poll_name}', $poll_name, $content );
	}

	if ( stripos( $content, '{poll_answer}' ) !== false ) {
		$answer_data   = isset( $data[ $poll->id ] ) ? $data[ $poll->id ] : '';
		$extra_field   = isset( $data[ $poll->id . '-extra' ] ) ? $data[ $poll->id . '-extra' ] : '';
		$fields_labels = $poll->pluck_fields_array( 'title', 'element_id', '1' );

		$answer_label = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : '';
		if ( ! empty( $extra_field ) ) {
			$answer_label .= ' ' . $extra_field;
		}
		$content = str_ireplace( '{poll_answer}', $answer_label, $content );
	}

	if ( stripos( $content, '{poll_result}' ) !== false ) {
		$poll_results = array();
		$fields_array = $poll->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $poll->id, $fields_array );
		$fields       = $poll->get_fields();
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$label = addslashes( $field->title );

				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;
				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}
				$poll_results[] = array(
					'label' => $label,
					'value' => $entries,
				);
			}
		}

		$poll_results_html = '<ul>';
		foreach ( $poll_results as $poll_result ) {
			$poll_results_html .= '<li>';
			$poll_results_html .= '<strong>' . $poll_result['label'] . '</strong> : ' . $poll_result['value'];
			$poll_results_html .= '</li>';
		}
		$poll_results_html .= '</ul>';
		$content            = str_ireplace( '{poll_result}', $poll_results_html, $content );
	}

	return apply_filters( 'forminator_replace_poll_form_data', $content, $poll, $data, $entry );
}

/**
 * Return vars for poll
 *
 * @since 1.6.1
 * @return mixed
 */
function forminator_get_poll_vars() {
	$vars_list = array(
		'poll_name'   => esc_html__( 'Poll Name', 'forminator' ),
		'poll_answer' => esc_html__( 'Poll Answer', 'forminator' ),
		'poll_result' => esc_html__( 'Poll Result', 'forminator' ),
	);

	/**
	 * Filter forminator Poll var list
	 *
	 * @see   forminator_replace_poll_form_data()
	 *
	 * @since 1.6.1
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_poll_vars_list', $vars_list );
}

/**
 * Format quiz data variables to html formatted
 *
 * @since 1.6.2
 *
 * @param string                      $content
 * @param Forminator_Quiz_Model       $quiz
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return mixed
 */
function forminator_replace_quiz_form_data( $content, Forminator_Quiz_Model $quiz, Forminator_Form_Entry_Model $entry ) {
	$result_behav = isset( $quiz->settings['results_behav'] ) ? $quiz->settings['results_behav'] : '';

	if ( stripos( $content, '{quiz_name}' ) !== false ) {
		$quiz_name = forminator_get_name_from_model( $quiz );
		$content   = str_ireplace( '{quiz_name}', $quiz_name, $content );
	}

	if ( stripos( $content, '{quiz_type}' ) !== false ) {
		$quiz_type = '';
		if ( 'knowledge' === $quiz->quiz_type ) {
			$quiz_type = 'Knowledge';
		} elseif ( 'nowrong' === $quiz->quiz_type ) {
			$quiz_type = 'Personality';
		}
		$content = str_ireplace( '{quiz_type}', $quiz_type, $content );
	}
	$answers = isset( Forminator_CForm_Front_Action::$prepared_data['answers'] )
			? Forminator_CForm_Front_Action::$prepared_data['answers']
			: array();

	// For single answer quiz.
	if ( stripos( $content, '{quiz_answer}' ) !== false && ( empty( $result_behav ) || 'after' === $result_behav ) ) {
		$answer_content = PHP_EOL . '<ul>' . PHP_EOL;
		if ( is_array( $answers ) ) {
			foreach ( $answers as $question_id => $answer_id ) {
				$question = $quiz->getQuestion( $question_id );
				$answer   = $quiz->getAnswer( $question_id, $answer_id );

				$question_text = isset( $question['title'] ) ? $question['title'] : '';
				$answer_text   = isset( $answer['title'] ) ? $answer['title'] : '';

				$answer_content .= '<li>' . PHP_EOL;

				$answer_content .= '<ul>' . PHP_EOL;
				$answer_content .= '<li><b>' . esc_html__( 'Question : ', 'forminator' ) . '</b>' . esc_html( $question_text ) . '</li>' . PHP_EOL;
				$answer_content .= '<li><b>' . esc_html__( 'Answer : ', 'forminator' ) . '</b>' . esc_html( $answer_text ) . '</li>' . PHP_EOL;
				$answer_content .= '</ul>' . PHP_EOL;

				$answer_content .= '</li>' . PHP_EOL;
			}
		}
		$answer_content .= '</ul>';

		$content = str_ireplace( '{quiz_answer}', $answer_content, $content );

	}

	// For multi-answer quiz.
	if ( stripos( $content, '{quiz_answer}' ) !== false && 'end' === $result_behav ) {
		$answer_content = PHP_EOL . '<ul>' . PHP_EOL;
		$question_ids   = array();
		if ( is_array( $answers ) ) {
			foreach ( $answers as $question_id => $answer_id ) {
				// In multi-answer, $question_id looks like this "question-2051-7608-3".
				$question_id = preg_replace( '/(-\d+$)/', '', $question_id );
				if ( ! in_array( $question_id, $question_ids ) ) {
					$answer                         = $quiz->getAnswer( $question_id, $answer_id );
					$answer_text                    = isset( $answer['title'] ) ? $answer['title'] : '';
					$question_ids[ $question_id ][] = $answer_text;
				}
			}
			foreach ( $question_ids as $question_id => $answer_titles ) {
				$question      = $quiz->getQuestion( $question_id );
				$question_text = isset( $question['title'] ) ? $question['title'] : '';
				$answer_head   = count( $answer_titles ) > 1 ? esc_html__( 'Answers : ', 'forminator' ) : esc_html__( 'Answer : ', 'forminator' );

				$answer_content         .= '<li>' . PHP_EOL;
					$answer_content     .= '<ul>' . PHP_EOL;
						$answer_content .= '<li><b>' . esc_html__( 'Question : ', 'forminator' ) . '</b>' . esc_html( $question_text ) . '</li>' . PHP_EOL;
						$answer_content .= '<li><b>' . $answer_head . '</b>' . esc_html( implode( ', ', $answer_titles ) ) . '</li>' . PHP_EOL;
					$answer_content     .= '</ul>' . PHP_EOL;
				$answer_content         .= '</li>' . PHP_EOL;
			}
		}
		$answer_content .= '</ul>';

		$content = str_ireplace( '{quiz_answer}', $answer_content, $content );

	}

	if ( stripos( $content, '{quiz_result}' ) !== false ) {
		$result_content = '';
		// we saved on $entry->meta_data['entry']['value'] => make sure its fulfilled before going further.
		if ( ! empty( $entry->meta_data ) && isset( $entry->meta_data['entry'] ) && isset( $entry->meta_data['entry']['value'] ) ) {

			if ( 'knowledge' === $quiz->quiz_type ) {
				$answers              = $entry->meta_data['entry']['value'];
				$correct_answer_count = 0;
				$total_answer         = 0;
				foreach ( $answers as $answer ) {
					$is_correct = isset( $answer['isCorrect'] ) ? $answer['isCorrect'] : false;
					$is_correct = filter_var( $is_correct, FILTER_VALIDATE_BOOLEAN );
					if ( $is_correct ) {
						$correct_answer_count ++;
					}

					$total_answer ++;
				}

				$result_content  = PHP_EOL . '<ul>' . PHP_EOL;
				$result_content .= '<li>' .
								sprintf(/* translators: ... */
									esc_html__( '%1$sCorrect Answers%2$s : %3$d', 'forminator' ),
									'<b>',
									'</b>',
									$correct_answer_count
								) .
								'</li>' . PHP_EOL;

				$result_content .= '<li>' .
								sprintf(/* translators: ... */
									esc_html__( '%1$sTotal Question Answered%2$s : %3$d', 'forminator' ),
									'<b>',
									'</b>',
									$total_answer
								) .
								'</li>' . PHP_EOL;
				$result_content .= '</ul>';
			} elseif ( 'nowrong' === $quiz->quiz_type ) {
				$meta = $entry->meta_data['entry']['value'];

				// i know its complicated as eff, but this is how it saved since day 1.
				// and migrating this might pita and affect performance.
				if ( isset( $meta[0] ) && isset( $meta[0]['value'] ) && isset( $meta[0]['value']['result'] ) ) {
					$result         = $meta[0]['value']['result'];
					$result_content = isset( $result['title'] ) ? esc_html( (string) $result['title'] ) : '';
				}
			}
		}
		$content = str_ireplace( '{quiz_result}', $result_content, $content );
		$content = do_shortcode( $content );
	}

	return apply_filters( 'forminator_replace_quiz_form_data', $content, $quiz, Forminator_CForm_Front_Action::$prepared_data, $entry );
}

/**
 * Return uniq key value
 *
 * @since 1.13
 *
 * @return string
 */
function forminator_unique_key() {
	return wp_rand( 1000, 9999 ) . '-' . wp_rand( 1000, 9999 );
}

/**
 * Return vars for quiz
 *
 * @since 1.6.2
 * @return array
 */
function forminator_get_quiz_vars() {
	$vars_list = array(
		'quiz_name'   => esc_html__( 'Quiz Name', 'forminator' ),
		'quiz_answer' => esc_html__( 'Quiz Answer', 'forminator' ),
		'quiz_result' => esc_html__( 'Quiz Result', 'forminator' ),
	);

	/**
	 * Filter forminator Quiz var list
	 *
	 * @see   forminator_replace_quiz_form_data()
	 *
	 * @since 1.6.2
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_quiz_vars_list', $vars_list );
}

/**
 * Replace Stripe data
 *
 * @param $content
 * @param $custom_form
 * @param $entry
 *
 * @return mixed
 */
function forminator_replace_form_payment_data( $content, Forminator_Form_Model $custom_form = null, Forminator_Form_Entry_Model $entry = null ) {
	if ( empty( $custom_form ) ) {
		return $content;
	}
	$form_fields = $custom_form->get_fields();
	if ( ! empty( $form_fields ) ) {
		foreach ( $form_fields as $field ) {
			$field_type = $field->__get( 'type' );
			if ( 'stripe' === $field_type || 'paypal' === $field_type ) {
				$payment_slug = $field->slug;
			}
		}
	}
	if ( ! empty( $payment_slug ) && ! empty( $entry ) ) {
		$payment_meta = isset( $entry->meta_data[ $payment_slug ] ) ? $entry->meta_data[ $payment_slug ]['value'] : array();
		if ( ! empty( $payment_meta ) ) {
			if ( strpos( $content, '{payment_mode}' ) !== false ) {
				$content = str_replace( '{payment_mode}', $payment_meta['mode'], $content );
			}
			if ( strpos( $content, '{payment_status}' ) !== false ) {
				$content = str_replace( '{payment_status}', $payment_meta['status'], $content );
			}
			if ( strpos( $content, '{payment_amount}' ) !== false ) {
				$content = str_replace( '{payment_amount}', $payment_meta['amount'], $content );
			}
			if ( strpos( $content, '{payment_currency}' ) !== false ) {
				$content = str_replace( '{payment_currency}', $payment_meta['currency'], $content );
			}
			if ( strpos( $content, '{transaction_id}' ) !== false ) {
				$content = str_replace( '{transaction_id}', $payment_meta['transaction_id'], $content );
			}
		}
	}

	return apply_filters( 'forminator_replace_form_payment_data', $content, $custom_form, $entry );
}

/**
 * Get Default date format
 *
 * @param $format
 *
 * @return string
 */
function datepicker_default_format( $format ) {
	switch ( $format ) {
		case 'mm/dd/yy':
			$format = 'm/d/Y';
			break;
		case 'mm.dd.yy':
			$format = 'm.d.Y';
			break;
		case 'mm-dd-yy':
			$format = 'm-d-Y';
			break;
		case 'yy-mm-dd':
			$format = 'Y-m-d';
			break;
		case 'yy.mm.dd':
			$format = 'Y.m.d';
			break;
		case 'yy/mm/dd':
			$format = 'Y/m/d';
			break;
		case 'dd/mm/yy':
			$format = 'd/m/Y';
			break;
		case 'dd.mm.yy':
			$format = 'd.m.Y';
			break;
		case 'dd-mm-yy':
			$format = 'd-m-Y';
			break;
		default:
			$format = get_option( 'date_format' );
			break;
	}

	return $format;
}

/**
 * Get entry field value helper
 *
 * @since 1.14
 *
 * @param Forminator_Form_Entry_Model $entry
 * @param                             $mapper
 * @param string                      $sub_meta_key
 * @param bool                        $allow_html
 * @param int                         $truncate
 *
 * @return string
 */
function forminator_get_entry_field_value( $entry, $mapper, $sub_meta_key = '', $allow_html = false, $truncate = PHP_INT_MAX ) {
	/** @var Forminator_Form_Entry_Model $entry */
	if ( isset( $mapper['property'] ) ) {
		if ( property_exists( $entry, $mapper['property'] ) ) {
			$property = $mapper['property'];
			// casting property to string.
			$value = (string) $entry->$property;
		} else {
			$value = '';
		}
	} else {
		$meta_value = $entry->get_meta( $mapper['meta_key'], '' );
		// meta_key based.
		if ( ! isset( $mapper['sub_metas'] ) ) {
			$value = Forminator_Form_Entry_Model::meta_value_to_string( $mapper['type'], $meta_value, $allow_html, $truncate );
		} else {
			if ( empty( $sub_meta_key ) ) {
				$value = '';
			} else {
				if ( isset( $meta_value[ $sub_meta_key ] ) && ! empty( $meta_value[ $sub_meta_key ] ) ) {
					$value      = $meta_value[ $sub_meta_key ];
					$field_type = $mapper['type'] . '.' . $sub_meta_key;
					$value      = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $value, $allow_html, $truncate );
				} else {
					$value = '';
				}
			}
		}
	}

	return $value;
}

/**
 * Forminator upload path
 *
 * @return string|null
 */
function forminator_upload_root() {
	$dir = wp_upload_dir();

	if ( $dir['error'] ) {
		return null;
	}

	return trailingslashit( $dir['basedir'] ) . 'forminator_temp';
}

/**
 * Forminator upload url
 *
 * @return string|null
 */
function formninator_upload_url_root() {
	$dir = wp_upload_dir();

	if ( $dir['error'] ) {
		return null;
	}

	return $dir['baseurl'] . 'forminator_upload';
}

/**
 * Replace lead form data
 *
 * @param $content
 * @param $quiz_settings
 * @param Forminator_Form_Entry_Model|null $entry
 *
 * @return string
 */
function forminator_replace_lead_form_data( $content, $quiz_settings, Forminator_Form_Entry_Model $entry = null ) {

	if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
		$lead_id           = isset( $quiz_settings['leadsId'] ) ? $quiz_settings['leadsId'] : 0;
		$lead_model        = Forminator_Base_Form_Model::get_model( $lead_id );
		$form_entry_fields = forminator_lead_entry_data( $entry );
		$submitted_data    = forminator_lead_submitted_data( $form_entry_fields );
		$entry->meta_data  = $form_entry_fields;
		$content           = forminator_replace_form_data( $content, $lead_model, $entry );
	}

	return $content;
}

/**
 * lead form data
 *
 * @param Forminator_Form_Entry_Model $entry
 *
 * @return array
 */
function forminator_lead_entry_data( $entry ) {
	$entry_data = array();
	if ( isset( $entry->meta_data['lead_entry'] ) && ! empty( $entry->meta_data['lead_entry']['value'] ) ) {
		$entry_data = $entry->meta_data['lead_entry']['value'];
	}

	return $entry_data;
}

/**
 * lead form submitted data
 *
 * @param $lead_entry
 *
 * @return array
 */
function forminator_lead_submitted_data( $lead_entry ) {
	$meta_data = array();
	if ( ! empty( $lead_entry ) ) {
		foreach ( $lead_entry as $lead_value ) {
			if ( ! empty( $lead_value['value'] ) && is_array( $lead_value['value'] ) ) {
				foreach ( $lead_value['value'] as $multi => $multi_value ) {
					if ( ! empty( $multi_value ) && is_array( $multi_value ) ) {
						foreach ( $multi_value as $sub => $sub_value ) {
							$meta_sub_name               = $lead_value['name'] . '-' . $sub;
							$meta_data[ $meta_sub_name ] = $sub_value;
						}
					} else {
						$meta_name               = $lead_value['name'] . '-' . $multi;
						$meta_data[ $meta_name ] = $multi_value;
					}
				}
			} else {
				$meta_data[ $lead_value['name'] ] = $lead_value['value'];
			}
		}
	}

	return $meta_data;
}

/**
 * Check how many instances of a field type exists in a given post ID
 *
 * @param $field_type
 *
 * @return int
 */
function forminator_count_field_type_in_page( $field_type ) {
	global $post;

	if ( empty( $post->ID ) ) {
		return 0;
	}

	$page        = get_post( $post->ID );
	$content     = $page->post_content;
	$field_count = 0;
	$form        = Forminator_Form_Model::model();

	// Check for forminator shortcodes.
	preg_match_all(
		'/' . get_shortcode_regex( array( 'forminator_form' ) ) . '/',
		$content,
		$matches,
		PREG_SET_ORDER
	);

	// Increase count for every $field_type found.
	foreach ( $matches as $shortcode ) {
		preg_match_all( '!\d+!', $shortcode[0], $form_ids );
		$form        = $form->load( $form_ids[0][0] );
		$form_fields = $form->get_fields();

		foreach ( $form_fields as $field ) {
			if ( false !== strpos( $field->slug, $field_type ) ) {
				$field_count++;
			}
		}
	}

	return $field_count;
}

/**
 * Trim field values
 *
 * @since 1.15.15
 *
 * @param $value
 *
 * @return array
 */
function forminator_trim_array ( $value ) {

	foreach( $value as $key => $val ) {
		if ( is_array( $val ) ) {
			$value[$key] = forminator_trim_array( $val );
		} else {
			$value[$key] = wp_unslash( trim( $val ) );
		}
	}

	return $value;
}

/**
 * Truncate text
 *
 * @since 1.17.0
 *
 * @param string	$text
 * @param int		$truncate	PHP_INT_MAX
 */
function forminator_truncate_text( $text, $truncate = PHP_INT_MAX ) {
	if ( strlen( $text ) > $truncate ) {
		$text = substr( $text, 0, $truncate ) . '...';
	}

	return $text;
}