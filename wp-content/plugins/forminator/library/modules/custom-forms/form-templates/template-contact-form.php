<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Template_Contact_Form
 *
 * @since 1.0
 */
class Forminator_Template_Contact_Form extends Forminator_Template {

	/**
	 * Template defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'id'          => 'contact_form',
			'name'        => __( 'Contact Us', 'forminator' ),
			'description' => __( 'A simple contact form for your users to contact you', 'forminator' ),
			'icon'        => 'clipboard-notes',
			'priortiy'    => 2,
		);
	}

	/**
	 * Template fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function fields() {
		return array(
			array(
				'wrapper_id' => 'wrapper-1511347711918-1669',
				'fields'     => array(
					array(
						'element_id'        => 'name-1',
						'type'              => 'name',
						'cols'              => '12',
						'required'          => 'true',
						'field_label'       => __( 'First Name', 'forminator' ),
						'placeholder'       => __( 'E.g. John', 'forminator' ),
						'prefix_label'      => __( 'Prefix', 'forminator' ),
						'fname_label'       => __( 'First Name', 'forminator' ),
						'fname_placeholder' => __( 'E.g. John', 'forminator' ),
						'mname_label'       => __( 'Middle Name', 'forminator' ),
						'mname_placeholder' => __( 'E.g. Smith', 'forminator' ),
						'lname_label'       => __( 'Last Name', 'forminator' ),
						'lname_placeholder' => __( 'E.g. Doe', 'forminator' ),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347712118-1739',
				'fields'     => array(
					array(
						'element_id'      => 'email-1',
						'type'            => 'email',
						'cols'            => '12',
						'required'        => 'true',
						'field_label'     => __( 'Email Address', 'forminator' ),
						'placeholder'     => __( 'E.g. john@doe.com', 'forminator' ),
						'validation'      => true,
						'validation_text' => '',
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1311247712118-1194',
				'fields'     => array(
					array(
						'element_id'            => 'phone-1',
						'type'                  => 'phone',
						'cols'                  => '12',
						'required'              => false,
						'field_label'           => __( 'Phone Number', 'forminator' ),
						'placeholder'           => __( 'E.g. +1 3004005000', 'forminator' ),
						'validation'            => 'none',
						'phone_validation_type' => 'standard',
						'validation_text'       => '',
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1988247712118-9871',
				'fields'     => array(
					array(
						'element_id'  => 'textarea-1',
						'type'        => 'textarea',
						'cols'        => '12',
						'required'    => false,
						'field_label' => __( 'Message', 'forminator' ),
						'placeholder' => __( 'Enter your message...', 'forminator' ),
						'input_type'  => 'paragraph',
						'limit'       => '180',
						'limit_type'  => 'characters',
					),
				),
			),
		);
	}

	/**
	 * Template settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function settings() {
		return array(
			'form-type'                     => 'default',
			'submission-behaviour'          => 'behaviour-thankyou',
			'thankyou-message'              => __( 'Thank you for contacting us, we will be in touch shortly.', 'forminator' ),
			'submitData'                    => array(
				'custom-submit-text'          => __( 'Send Message', 'forminator' ),
				'custom-invalid-form-message' => __( 'Error: Your form is not valid, please fix the errors!', 'forminator' ),
			),
			'enable-ajax'                   => 'true',
			'validation-inline'             => true,
			'fields-style'                  => 'open',
			'form-expire'                   => 'no_expire',
			// Main container.
			'form-padding-top'              => '0',
			'form-padding-right'            => '0',
			'form-padding-bottom'           => '0',
			'form-padding-left'             => '0',
			'form-border-width'             => '0',
			'form-border-style'             => 'none',
			'form-border-radius'            => '0',
			// Typography - Label.
			'cform-label-font-family'       => 'Roboto',
			'cform-label-custom-family'     => '',
			'cform-label-font-size'         => '12',
			'cform-label-font-weight'       => 'bold',
			// Typography - Section Title.
			'cform-title-font-family'       => 'Roboto',
			'cform-title-custom-family'     => '',
			'cform-title-font-size'         => '45',
			'cform-title-font-weight'       => 'normal',
			'cform-title-text-align'        => 'left',
			// Typography - Section Subtitle.
			'cform-subtitle-font-family'    => 'Roboto',
			'cform-subtitle-custom-font'    => '',
			'cform-subtitle-font-size'      => '18',
			'cform-subtitle-font-weight'    => 'normal',
			'cform-subtitle-text-align'     => 'left',
			// Typography - Input & Textarea.
			'cform-input-font-family'       => 'Roboto',
			'cform-input-custom-font'       => '',
			'cform-input-font-size'         => '16',
			'cform-input-font-weight'       => 'normal',
			// Typography - Radio & Checkbox.
			'cform-radio-font-family'       => 'Roboto',
			'cform-radio-custom-font'       => '',
			'cform-radio-font-size'         => '14',
			'cform-radio-font-weight'       => 'normal',
			// Typography - Select.
			'cform-select-font-family'      => 'Roboto',
			'cform-select-custom-family'    => '',
			'cform-select-font-size'        => '16',
			'cform-select-font-weight'      => 'normal',
			// Typography - Multi Select.
			'cform-multiselect-font-family' => 'Roboto',
			'cform-multiselect-custom-font' => '',
			'cform-multiselect-font-size'   => '16',
			'cform-multiselect-font-weight' => 'normal',
			// Typography - Dropdown.
			'cform-dropdown-font-family'    => 'Roboto',
			'cform-dropdown-custom-font'    => '',
			'cform-dropdown-font-size'      => '16',
			'cform-dropdown-font-weight'    => 'normal',
			// Typography - Calendar.
			'cform-calendar-font-family'    => 'Roboto',
			'cform-calendar-custom-font'    => '',
			'cform-calendar-font-size'      => '13',
			'cform-calendar-font-weight'    => 'normal',
			// Typography - Buttons.
			'cform-button-font-family'      => 'Roboto',
			'cform-button-custom-font'      => '',
			'cform-button-font-size'        => '14',
			'cform-button-font-weight'      => '500',
			// Typography - Timeline.
			'cform-timeline-font-family'    => 'Roboto',
			'cform-timeline-custom-font'    => '',
			'cform-timeline-font-size'      => '12',
			'cform-timeline-font-weight'    => 'normal',
			// Typography - Pagination.
			'cform-pagination-font-family'  => '',
			'cform-pagination-custom-font'  => '',
			'cform-pagination-font-size'    => '16',
			'cform-pagination-font-weight'  => 'normal',
			'payment_require_ssl'           => false,
			'submission-file'               => 'delete',
		);
	}
}
