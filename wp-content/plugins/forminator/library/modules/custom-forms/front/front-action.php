<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front ajax for custom forms
 *
 * @since 1.0
 */
class Forminator_CForm_Front_Action extends Forminator_Front_Action {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Submit errors
	 *
	 * @var array
	 */
	public static $submit_errors = array();

	/**
	 * Hidden fields
	 *
	 * @var array
	 */
	public static $hidden_fields = array();

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public static $entry_type = 'custom-forms';

	/**
	 * Registration object
	 *
	 * @var string
	 */
	public static $registration = null;

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	public function __construct() {
		parent::__construct();

		// Save entries
		if ( ! empty( self::$entry_type ) ) {
			add_action( 'wp_ajax_forminator_pp_create_order', array( $this, 'create_paypal_order' ) );
			add_action( 'wp_ajax_nopriv_forminator_pp_create_order', array( $this, 'create_paypal_order' ) );
		}

		add_action( 'wp_ajax_forminator_email_draft_link', array( $this, 'submit_email_draft_link' ) );
		add_action( 'wp_ajax_nopriv_forminator_email_draft_link', array( $this, 'submit_email_draft_link' ) );
	}

	/**
	 * Return the plugin instance
	 *
	 * @since 1.0
	 * @return Forminator_Front_Action
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create PayPal order
	 *
	 * @since 1.14.3
	 */
	public function create_paypal_order() {
		$body = trim( file_get_contents( 'php://input' ) );
		$data = json_decode( $body, true );

		// Check if form data is set
		if ( isset( $data['form_data'] ) && isset( $data['form_data']['purchase_units'] ) ) {

			// Check if payment amount is bigger than zero
			if ( floatval( $data['form_data']['purchase_units'][0]['amount']['value'] ) <= 0 ) {
				wp_send_json_error( esc_html__( 'The payment total must be greater than 0.', 'forminator' ) );
			}

			$amount = $data['form_data']['purchase_units'][0]['amount']['value'];
			$data['form_data']['purchase_units'][0]['amount']['value'] = number_format( (float) $amount, 2, '.', '' );

			$paypal = new Forminator_PayPal_Express();

			$request = array_merge( array( 'intent' => 'CAPTURE' ), $data['form_data'] );
			$request = apply_filters( 'forminator_paypal_create_order_request', $request, $data );

			$order = $paypal->create_order( $request, $data['mode'] );

			if ( is_wp_error( $order ) ) {
				wp_send_json_error( esc_html__( 'Cannot create a new order on PayPal. If the error persists, please contact us for further assistance.', 'forminator' ) );
			}

			$response = array(
				'order_id' => $order->id,
			);

			wp_send_json_success( $response );
		}
	}

	/**
	 * Update payment amount
	 *
	 * @since 1.7.3
	 */
	public function update_payment_amount() {
		$this->init_properties();

		self::check_fields_visibility();

		if ( empty( self::$info['stripe_field'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( "Error: Stripe field doesn't exist in your form!", 'forminator' ),
					'errors'  => array(),
				)
			);
		}
		$forminator_stripe_field = Forminator_Core::get_field_object( 'stripe' );

		if ( $forminator_stripe_field instanceof Forminator_Stripe ) {
			$forminator_stripe_field->update_paymentIntent(
				self::$prepared_data,
				self::$info['stripe_field']
			);
		}
	}

	/**
	 * Get default currency
	 *
	 * @return string
	 */
	private function get_default_currency() {
		try {
			$stripe = new Forminator_Gateway_Stripe();

			return $stripe->get_default_currency();

		} catch ( Forminator_Gateway_Exception $e ) {
			return 'USD';
		}
	}

	/**
	 * Check reCaptcha
	 *
	 * @return string|null
	 */
	private static function check_captcha() {
		// Ignore captcha re-check if we have Stripe field.
		if ( self::$is_draft || ! empty( self::$info['stripe_field'] ) ) {
			return;
		}
		$form_id           = self::$module_id;
		$field_captcha_obj = Forminator_Core::get_field_object( 'captcha' );
		if ( self::$info['captcha_settings'] && $field_captcha_obj ) {
			$field_id              = Forminator_Field::get_property( 'element_id', self::$info['captcha_settings'] );
			$captcha_user_response = '';

			if ( isset( self::$prepared_data['g-recaptcha-response'] ) ) {
				$captcha_user_response = self::$prepared_data['g-recaptcha-response'];
			} elseif ( isset( self::$prepared_data['h-captcha-response'] ) ) {
				$captcha_user_response = self::$prepared_data['h-captcha-response'];
			}

			/**
			 * Filter captcha user response, default is from `g-recaptcha-response`
			 *
			 * @since 1.5.3
			 *
			 * @param string $captcha_user_response
			 * @param int $form_id
			 * @param array $submitted_data
			 *
			 * @return string captcha user response
			 */
			$captcha_user_response = apply_filters( 'forminator_captcha_user_response', $captcha_user_response, $form_id, self::$prepared_data );

			$field_captcha_obj->validate_entry( self::$info['captcha_settings'], $captcha_user_response );
			$valid_response = $field_captcha_obj->is_valid_entry();
			if ( is_array( $valid_response ) && ! empty( $valid_response[ $field_id ] ) ) {
				// if captcha invalid.
				throw new Exception( $valid_response[ $field_id ] );
			}
		}
	}

	/**
	 * Handle login if it's login form
	 *
	 * @param object $entry Entry.
	 * @return boolean
	 */
	private static function maybe_login( $entry ) {
		if ( ! isset( self::$module_settings['form-type'] ) || 'login' !== self::$module_settings['form-type'] ) {
			return;
		}
		// Check who can login.
		if ( is_user_logged_in() ) {
			return;
		}

		$forminator_user_login = new Forminator_CForm_Front_User_Login();
		$login_user            = $forminator_user_login->process_login( self::$module_object, $entry, self::$info['field_data_array'] );
		if ( is_wp_error( $login_user['user'] ) ) {
			$message = $login_user['user']->get_error_message();

			throw new Exception( $message );
		}

		if ( ! empty( $login_user['authentication'] ) && 'invalid' === $login_user['authentication'] ) {
			self::$response_attrs['authentication'] = 'invalid';
			throw new Exception( __( 'Whoops, the passcode you entered was incorrect or expired.', 'forminator' ) );
		}

		if ( isset( $login_user['user']->ID ) ) {
			self::$response_attrs['user_id'] = $login_user['user']->ID;
		}
		if ( isset( $login_user['authentication'] ) ) {
			self::$response_attrs['authentication'] = $login_user['authentication'];
		}
		if ( isset( $login_user['auth_token'] ) ) {
			self::$response_attrs['auth_token'] = $login_user['auth_token'];
		}
		if ( isset( $login_user['auth_method'] ) ) {
			self::$response_attrs['auth_method'] = $login_user['auth_method'];
		}
		if ( isset( $login_user['auth_nav'] ) ) {
			self::$response_attrs['auth_nav'] = $login_user['auth_nav'];
		}
		if ( isset( $login_user['lost_url'] ) ) {
			self::$response_attrs['lost_url'] = $login_user['lost_url'];
		}

		self::remove_password();
		return true;
	}

	/**
	 * Handle registration form validation separately
	 *
	 * @since 1.17.2
	 *
	 * @return \WP_Error|boolean
	 */
	private static function validate_registration() {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		if ( isset( self::$module_settings['form-type'] ) && 'registration' === self::$module_settings['form-type'] ) {
			// Check who can register new users.
			if ( ! is_user_logged_in() ) {
				$can_creat_user = true;
			} elseif ( isset( self::$module_settings['hide-registration-form'] )
					&& '' === self::$module_settings['hide-registration-form']
			) {
				$can_creat_user = true;
			} else {
				$can_creat_user = false;
			}

			if ( ! $can_creat_user ) {
				return;
			}

			self::$registration = new Forminator_CForm_Front_User_Registration();
			$registration_error = self::$registration->process_validation( self::$module_object, self::$info['field_data_array'] );
			if ( true !== $registration_error ) {
				throw new Exception( $registration_error );
			}

			$custom_error = apply_filters( 'forminator_custom_registration_form_errors', $registration_error, self::$module_id, self::$info['field_data_array'] );
			if ( true !== $custom_error ) {
				throw new Exception( $custom_error );
			}

			return true;
		}
	}

	/**
	 * Handle registration if it's registration form.
	 *
	 * @param object $entry Entry.
	 *
	 * @return \WP_Error|boolean
	 */
	private static function maybe_registration( $entry ) {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		if ( isset( self::$module_settings['form-type'] ) && 'registration' === self::$module_settings['form-type'] ) {
			// Check who can register new users.
			if ( ! is_user_logged_in() ) {
				$can_creat_user = true;
			} elseif ( isset( self::$module_settings['hide-registration-form'] )
					&& '' === self::$module_settings['hide-registration-form']
			) {
				$can_creat_user = true;
			} else {
				$can_creat_user = false;
			}

			if ( ! $can_creat_user ) {
				return;
			}

			$new_user_data = self::$registration->process_registration( self::$module_object, $entry );

			if ( ! is_array( $new_user_data ) ) {
				throw new Exception( $new_user_data );
			}

			// Do not send emails later.
			self::$module_object->notifications = array();

			return true;
		}
	}

	/**
	 * Prepare fields info
	 */
	private static function prepare_fields_info() {
		self::check_fields_visibility();
		self::$is_leads = isset( self::$module_settings['form-type'] ) && 'leads' === self::$module_settings['form-type'];

		$fields = self::get_fields();
		foreach ( $fields as $field_index => $field ) {
			self::set_field_data_array( $field_index, $field );
		}

		// Validate User Registration first before any payments
		$registration = self::validate_registration();
		if ( is_wp_error( $registration ) ) {
			return self::return_error( $registration->get_error_message() );
		}

		self::check_errors();
		self::filter_field_data_array();
	}

	/**
	 * Set field data array
	 *
	 * @param int    $field_index Int key.
	 * @param object $field Forminator_Form_Field_Model.
	 * @return type
	 */
	private static function set_field_data_array( $field_index, $field ) {
		$field_array = $field->to_formatted_array();
		$field_type  = $field_array['type'];
		$field_id    = Forminator_Field::get_property( 'element_id', $field_array );

		if (
			self::$is_draft &&
			in_array( $field_type, array( 'hidden', 'stripe', 'paypal', 'signature' ), true )
		) {
			return;
		}

		// is conditionally hidden or certain field types - go to next field.
		if ( ! $field_id || in_array( $field_id, self::$hidden_fields, true )
				|| in_array( $field_type, array( 'stripe', 'paypal', 'calculation' ), true ) ) {
			return;
		}

		$form_field_obj = Forminator_Core::get_field_object( $field_type );
		if ( isset( self::$prepared_data[ $field_id ] ) ) {
			$field_data = self::$prepared_data[ $field_id ];
		} else {
			$field_data = array();
		}

		/**
		 * Filter handle specific field types
		 *
		 * @since 1.13
		 *
		 * @param array  $field_data Field data
		 * @param object $form_field_obj Form field object
		 * @param array  $field_array field settings
		 *
		 * @return array $field_data Set `return` element of the array as true for returning
		 */
		$field_data = apply_filters( 'forminator_handle_specific_field_types', $field_data, $form_field_obj, $field_array );

		if ( ! empty( $field_data['return'] ) ) {
			unset( $field_data['return'] );

			self::$info['field_data_array'][] = $field_data;
			return;
		}

		/**
		 * Sanitize data
		 *
		 * @since 1.0.2
		 *
		 * @param array $field
		 * @param array|string $data - the data to be sanitized.
		 */
		$field_data = $form_field_obj->sanitize( $field_array, $field_data );

		if ( ! self::$is_draft ) {
			$field_data = $form_field_obj->validate_entry( $field_array, $field_data );
		}
		$form_field_obj->is_valid_entry();

		if ( ! empty( $field_data ) || '0' === $field_data ) {
			self::$info['field_data_array'][] = array(
				'name'           => $field_id,
				'value'          => $field_data,
				'field_type'     => $field_type,
				'key'            => $field_index,
				'field_array'    => $field_array,
				'form_field_obj' => $form_field_obj,
			);
		}
	}

	/**
	 * Stop submission process if it has errors
	 *
	 * @throws Exception
	 */
	private static function check_errors() {
		/**
		 * Filter submission errors
		 *
		 * @since 1.0.2
		 *
		 * @param array $submit_errors - the submission errors.
		 * @param int $form_id - the form id.
		 *
		 * @return array $submit_errors
		 */
		self::$submit_errors = apply_filters( 'forminator_custom_form_submit_errors', self::$submit_errors, self::$module_id, self::$info['field_data_array'] );
		if ( ! empty( self::$submit_errors ) ) {
			throw new Exception( self::get_invalid_form_message() );
		}
	}

	/**
	 * Filter field_data_array property
	 *
	 * @throws Exception
	 */
	private static function filter_field_data_array() {
		if ( empty( self::$info['field_data_array'] ) ) {
			if ( self::$is_draft ) {
				throw new Exception( __( 'The form is empty and cannot be saved as a draft. Please fill out at least one form field and try again.', 'forminator' ) );
			}

			throw new Exception( __( 'At least one field must be filled out to submit the form.', 'forminator' ) );
		}

		if ( isset( self::$prepared_data['product-shipping'] ) && intval( self::$prepared_data['product-shipping'] > 0 ) ) {
			self::$info['field_data_array'][] = array(
				'name'  => 'product_shipping',
				'value' => self::$prepared_data['product-shipping'],
			);
		}
		self::$info['field_data_array'][] = array(
			'name'  => '_forminator_user_ip',
			'value' => Forminator_Geo::get_user_ip(),
		);

		// Add draft_page if present (based on form's pagination index)
		if ( isset( self::$prepared_data['draft_page'] ) ) {
			self::$info['field_data_array'][] = array(
				'name'  => 'draft_page',
				'value' => self::$prepared_data['draft_page'],
			);
		}

		/**
		 * Filter saved data before persisted into the database
		 *
		 * @since 1.0.2
		 *
		 * @param array $field_data_array - the entry data.
		 * @param int $form_id - the form id.
		 *
		 * @return array $field_data_array
		 */
		self::$info['field_data_array'] = apply_filters( 'forminator_custom_form_submit_field_data', self::$info['field_data_array'], self::$module_id );
	}

	/**
	 * Handle stripe single payment
	 *
	 * @since 1.15
	 *
	 * @param array  $field Field data.
	 * @param object $entry Entry.
	 *
	 * @return array|WP_ERROR
	 */
	private static function handle_stripe_subscription( $field_object, $field, $entry, $payment_plan ) {
		if ( class_exists( 'Forminator_Stripe_Subscription' ) ) {
			try {
				$stripe_addon = Forminator_Stripe_Subscription::get_instance();
				$amount_type  = isset( $payment_plan['subscription_amount_type'] ) ? $payment_plan['subscription_amount_type'] : 'fixed';
				$amount       = isset( $payment_plan['subscription_amount'] ) ? $payment_plan['subscription_amount'] : 0.0;

				if ( 'fixed' === $amount_type && empty( $amount ) ) {
					throw new Exception( __( 'Payment amount should be larger than 0.', 'forminator' ) );
				}

				$entry_data = $stripe_addon->handle_subscription( $field_object, self::$module_object, self::$prepared_data, $field, $entry, $payment_plan );

				$stripe_entry_data = array(
					'name'  => $field['element_id'],
					'value' => $entry_data,
				);

				/**
				 * Filter stripe entry data that might be stored/used later
				 *
				 * @since 1.7
				 *
				 * @param array $calculation_entry_data
				 * @param Forminator_Form_Model $module_object
				 * @param array $field field_properties.
				 * @param array $field_data_array
				 *
				 * @return array
				 */
				$stripe_entry_data = apply_filters( 'forminator_custom_form_stripe_entry_data', $stripe_entry_data, self::$module_object, $field, self::$info['field_data_array'] );

				forminator_maybe_log( __METHOD__, $stripe_entry_data['value'] );
				if ( ! empty( $stripe_entry_data['value']['error'] ) ) {
					throw new Exception( $stripe_entry_data['value']['error'] );
				}

				return $stripe_entry_data;

			} catch ( Exception $e ) {
				// Delete entry if paymentIntent confirmation is not successful
				$entry->delete();

				return new WP_Error( 'forminator_stripe_error', $e->getMessage() );
			}
		}
	}

	/**
	 * Handle stripe single payment
	 *
	 * @since 1.15
	 *
	 * @param array  $field_object
	 * @param array  $field Field data.
	 * @param object $entry Entry.
	 * @param string $mode Stripe payment mode
	 *
	 * @return array|WP_ERROR
	 */
	private static function handle_stripe_single( $field_object, $field, $entry, $mode ) {
		$entry_data = $field_object->process_to_entry_data( $field );

		$stripe_entry_data = array(
			'name'  => $field['element_id'],
			'value' => $entry_data,
		);

		/**
		 * Filter stripe entry data that might be stored/used later
		 *
		 * @since 1.7
		 *
		 * @param array $calculation_entry_data
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field_properties.
		 * @param array $field_data_array
		 *
		 * @return array
		 */
		$stripe_entry_data = apply_filters( 'forminator_custom_form_stripe_entry_data', $stripe_entry_data, self::$module_object, $field, self::$info['field_data_array'] );

		forminator_maybe_log( __METHOD__, $stripe_entry_data['value'] );
		if ( ! empty( $stripe_entry_data['value']['error'] ) ) {
			throw new Exception( $stripe_entry_data['value']['error'] );
		}

		/**
		 * Fires after charge stripe
		 *
		 * @since 1.7
		 *
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field properties.
		 * @param array $stripe_entry_data
		 * @param array $submitted_data
		 * @param array $field_data_array
		 */
		do_action( 'forminator_custom_form_after_stripe_charge', self::$module_object, $field, $stripe_entry_data, self::$prepared_data, self::$info['field_data_array'] );

		// Try to get Payment Intent from submitted date
		try {
			$intent = $field_object->get_paymentIntent( $field );

			if ( is_wp_error( $intent ) ) {
				return $intent;
			}

			$result = $intent->confirm();
		} catch ( Exception $e ) {
			// Delete entry if paymentIntent confirmation is not successful
			$entry->delete();

			return new WP_Error( 'forminator_stripe_error', $e->getMessage() );
		}

		// If we have 3D security on the card return for verification
		if ( 'requires_action' === $result->status ) {
			// Delete entry if 3d security is needed, we will store it on next attempt
			$entry->delete();

			self::$response_attrs['stripe3d'] = true;
			self::$response_attrs['secret']   = $result->client_secret;

			return new WP_Error( 'forminator_stripe_error', __( 'This payment require 3D Secure authentication! Please follow the instructions.', 'forminator' ) );
		}

		// Try to capture payment
		try {
			$capture = $intent->capture();
		} catch ( Exception $e ) {
			// Delete entry if capture is not successful
			$entry->delete();

			return new WP_Error( 'forminator_stripe_error', $e->getMessage() );
		}

		if ( ! isset( $capture->charges->data[0]->captured ) || true !== $capture->charges->data[0]->captured ) {
			// Delete entry if capture is not successful.
			$entry->delete();

			return new WP_Error( 'forminator_stripe_error', __( 'Payment failed, please try again!', 'forminator' ) );
		}

		$result                     = array(
			'status'           => 'COMPLETED',
			'transaction_id'   => $intent->id,
			'transaction_link' => $field_object::get_transanction_link( $mode, $intent->id ),
		);
		$stripe_entry_data['value'] = array_merge( $stripe_entry_data['value'], $result );

		return $stripe_entry_data;
	}

	/**
	 * Handle stripe payments
	 *
	 * @param object $entry Entry.
	 * @return array
	 */
	private static function handle_stripe( $entry ) {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		$stripe = new Forminator_Gateway_Stripe();

		if ( ! $stripe->is_ready() || ! self::$info['stripe_field'] ) {
			return;
		}

		self::stripe_field_to_entry_data_array( $entry );
	}

	/**
	 * Handle paypal
	 *
	 * @param object $entry Entry.
	 * @return array
	 */
	private static function handle_paypal( $entry ) {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		if ( ! self::$info['paypal_field'] ) {
			return;
		}

		if ( self::$module_object->is_payment_require_ssl() && ! is_ssl() ) {
			throw new Exception(
				apply_filters(
					'forminator_payment_require_ssl_error_message',
					__( 'SSL required to submit this form, please check your URL.', 'forminator' )
				)
			);
		}

		self::paypal_field_to_entry_data_array( $entry );
	}

	/**
	 * Handle form
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 * @since 1.5.1 utilize `_post_data` which already defined on submit
	 *
	 * @param bool $preview Is preview.
	 *
	 * @return array|bool
	 */
	public function handle_form( $preview = false ) {
		if ( ! self::$module_object ) {
			return false;
		}

		try {
			self::can_submit();
			self::prepare_fields_info();
			self::check_captcha();

			$entry = self::get_entry();
			self::maybe_login( $entry );

			$passed = self::is_honeypot();
			if ( ! $passed ) {
				// show success but dont save form.
				return self::return_success();
			}

			if ( self::is_spam() ) {
				$entry->is_spam = 1;
				self::$is_spam  = true;
			}

			// If preview, skip integrations.
			if ( ! $preview ) {
				self::attach_addons_on_form_submit();

				$entry->draft_id = $this->set_entry_draft_id();

				if ( self::$is_draft || ! self::prevent_store() ) {
					$entry->save( null, null, self::$previous_draft_id );
				}
			}

			self::handle_stripe( $entry );
			self::handle_paypal( $entry );
			self::maybe_create_post();

			// save field_data_array with password field for registration forms.
			$data_for_registration = self::$info['field_data_array'];

			self::save_entry_fields( $entry );

			self::attach_addons_after_entry_saved( $entry );
			self::maybe_registration( $entry, $data_for_registration );

			self::send_email( $entry );

			$response = self::get_response( $entry );
		} catch ( Exception $e ) {
			return self::return_error( $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Send email
	 *
	 * @param object $entry Entry.
	 */
	private static function send_email( $entry ) {
		if ( ! self::$is_leads && ! self::$is_draft && ! self::$is_spam ) {
			$forminator_mail_sender = new Forminator_CForm_Front_Mail();
			$forminator_mail_sender->process_mail( self::$module_object, $entry );
		}
	}

	/**
	 * Save entry fields
	 *
	 * @param object $entry Entry object.
	 */
	private static function save_entry_fields( $entry ) {
		self::remove_password();

		/**
		 * Action called before setting fields to database
		 *
		 * @since 1.0.2
		 *
		 * @param Forminator_Form_Entry_Model $entry - the entry model.
		 * @param int $form_id - the form id.
		 * @param array $field_data_array - the entry data.
		 */
		do_action( 'forminator_custom_form_submit_before_set_fields', $entry, self::$module_id, self::$info['field_data_array'] );

		// ADDON add_entry_fields.
		// @since 1.2 Add field_data_array to param.
		$added_data_array = self::$info['field_data_array'];
		if ( ! self::$is_draft ) {
			$added_data_array = self::attach_addons_add_entry_fields( $added_data_array, $entry );
			$added_data_array = self::replace_values_to_labels( $added_data_array, $entry );
		} else {
			// remove IP for drafts
			$ip_key = array_search( '_forminator_user_ip', array_column( $added_data_array, 'name' ) );
			if ( false !== $ip_key ) {
				unset( $added_data_array[ $ip_key ] );
			}
		}

		if ( self::$is_leads ) {
			self::$response_attrs['entry_id'] = $entry->entry_id;

			$added_data_array[] = array(
				'name'  => 'skip_form',
				'value' => '0',
			);
		}

		$entry->set_fields( $added_data_array );
	}

	/**
	 * Prepare submitted data to sending to addons
	 *
	 * @param array $current_entry_fields Entry fields.
	 * @return array
	 */
	protected static function get_prepared_submitted_data_for_addons( $current_entry_fields ) {
		$data = self::get_submitted_data();

		foreach ( wp_list_pluck( $current_entry_fields, 'name' ) as $element_id ) {
			$data[ $element_id ] = Forminator_Addon_Form_Hooks_Abstract::prepare_field_value_for_addon( $element_id, $current_entry_fields, $data );
		}

		return $data;
	}

	/**
	 * Maybe create post
	 */
	private static function maybe_create_post() {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		// Get saved postdata fields data and replace upload tags with uploaded data.
		$postdata_fields = self::get_specific_field_data( 'postdata' );
		if ( empty( $postdata_fields ) ) {
			return;
		}
		$postdata_return = self::create_post_from_postdata( $postdata_fields );

		if ( isset( $postdata_return['type'] ) && 'error' === $postdata_return['type'] ) {
			throw new Exception( $postdata_return['value'] );
		}

		foreach ( $postdata_return as $postdata ) {
			if ( 'success' === $postdata['type'] ) {
				foreach ( self::$info['field_data_array'] as $field_key => $field_datum ) {
					if ( $field_datum['name'] === $postdata['field_id'] ) {
						self::$info['field_data_array'][ $field_key ] = array(
							'name'  => $postdata['field_id'],
							'value' => $postdata['field_data'],
						);
					}
				}
			} else {
				throw new Exception( $postdata['value'] );
			}
		}
	}

	/**
	 * Get submission response
	 *
	 * @param object $entry Form entry object.
	 * @return type
	 */
	private static function get_response( $entry ) {
		if ( self::$is_draft ) {
			return self::get_draft_response( $entry );
		}

		self::set_behaviour_settings( $entry );
		$response = self::return_success();

		if ( empty( self::$module_settings['enable-ajax'] ) ) {
			$is_ajax_enabled = false;
		} else {
			$is_ajax_enabled = filter_var( self::$module_settings['enable-ajax'], FILTER_VALIDATE_BOOLEAN );
		}
		if ( $is_ajax_enabled ) {
			// Hide select options that already are reached limit.
			$response['select_field'] = self::get_limited_select_values();
		}

		$response = self::handle_product_fields( $response, $entry );

		return $response;
	}

	/**
	 * Get draft response
	 *
	 * @param object $entry Form entry object.
	 * @return type
	 */
	private static function get_draft_response( $entry ) {
		$setting = self::$module_settings;
		// Will be used to auto-fill the email field in send draft link form
		$first_email = self::get_first_email( self::$prepared_data );
		if ( ! is_null( $first_email ) ) {
			self::$response_attrs['first_email'] = $first_email;
		}

		self::$response_attrs['draft_id']           = $entry->draft_id;
		self::$response_attrs['page_id']            = self::$prepared_data['page_id'];
		self::$response_attrs['enable_email_link']  = isset( $setting['sc_email_link'] ) ? filter_var( $setting['sc_email_link'], FILTER_VALIDATE_BOOLEAN ) : true;
		self::$response_attrs['email_label']        = isset( $setting['sc_email_input_label'] ) ? $setting['sc_email_input_label'] : __( 'Send draft link to', 'forminator' );
		self::$response_attrs['email_placeholder']  = isset( $setting['sc_email_placeholder'] ) ? $setting['sc_email_placeholder'] : __( 'E.g., johndoe@gmail.com', 'forminator' );
		self::$response_attrs['email_button_label'] = isset( $setting['sc_email_button_label'] ) ? $setting['sc_email_button_label'] : __( 'Send draft link', 'forminator' );
		self::$response_attrs['retention_period']   = isset( $setting['sc_draft_retention'] ) ? $setting['sc_draft_retention'] : 30;

		return self::return_success( $setting['sc_message'] );
	}

	/**
	 * Handle product fields
	 *
	 * @param array  $response Response.
	 * @param object $entry Form entry object.
	 */
	private static function handle_product_fields( $response, $entry ) {
		$product_fields = self::get_specific_field_data( 'product' );
		if ( ! empty( $product_fields ) ) {
			// Process purchase.
			$page_id  = self::$prepared_data['page_id']; // use page id to get permalink for redirect.
			$shipping = isset( self::$prepared_data['product-shipping'] ) ? self::$prepared_data['product-shipping'] : 0;

			/**
			 * Process purchase
			 *
			 * @since 1.0.0
			 *
			 * @param array $response - the response array.
			 * @param array $product_fields - the product fields.
			 * @param int $entry_id - the entry id ( reference for callback).
			 * @param int $page_id - the page id. Used to generate a return url.
			 * @param int $shipping - the shipping cost.
			 */
			$response = apply_filters( 'forminator_cform_process_purchase', $response, $product_fields, self::$info['field_data_array'], $entry->entry_id, $page_id, $shipping );
		}

		return $response;
	}

	/**
	 * Get product field data
	 *
	 * @param string $type Field type.
	 * @return array
	 */
	private static function get_specific_field_data( $type ) {
		$product_fields = array();
		foreach ( self::$info['field_data_array'] as $data ) {
			if ( ! isset( $data['field_type'] ) || $type !== $data['field_type'] ) {
				continue;
			}
			$product_fields[] = $data;
		}

		return $product_fields;
	}

	/**
	 * Get fields
	 */
	private static function get_fields() {
		$fields = self::$module_object->get_real_fields();

		if ( ! $fields ) {
			throw new Exception( __( 'At least one field must be filled out to submit the form.', 'forminator' ) );
		}

		return $fields;
	}

	/**
	 * Check if submission is possible.
	 */
	private static function can_submit() {
		$form_submit = self::$module_object->form_can_submit();
		if ( ! $form_submit['can_submit'] ) {
			throw new Exception( $form_submit['error'] );
		}
	}

	/**
	 * Honeypot check
	 *
	 * @return boolean
	 */
	private static function is_honeypot() {
		if ( isset( self::$module_settings['honeypot'] ) && filter_var( self::$module_settings['honeypot'], FILTER_VALIDATE_BOOLEAN ) ) {
			$total_fields = count( self::$module_object->get_real_fields() ) + 1;
			if ( ! empty( self::$prepared_data[ "input_$total_fields" ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Set behavior settings
	 *
	 * @param object $entry Entry.
	 */
	private static function set_behaviour_settings( $entry ) {
		$all_behaviours   = array( 'behaviour-thankyou', 'behaviour-hide', 'behaviour-redirect' );
		$behavior_options = self::get_relevant_behavior_options();
		if ( ! isset( $behavior_options['submission-behaviour'] ) || ! in_array( $behavior_options['submission-behaviour'], $all_behaviours, true ) ) {
			return;
		}

		$custom_form                   = self::$module_object;
		self::$response_attrs['behav'] = self::get_submission_behaviour( $behavior_options );
		if ( 'behaviour-redirect' === $behavior_options['submission-behaviour'] && ! empty( $behavior_options['redirect-url'] ) ) {
			self::$response_attrs['redirect'] = true;
			// replace form data vars with value.
			$redirect_url = forminator_replace_form_data( $behavior_options['redirect-url'], $custom_form, $entry, false, true );
			$tab_value    = isset( $behavior_options['newtab'] ) ? $behavior_options['newtab'] : 'sametab';
			$newtab       = forminator_replace_form_data( $tab_value, $custom_form, $entry );
			// replace misc data vars with value.
			$redirect_url                   = forminator_replace_variables( $redirect_url, self::$module_id );
			$newtab                         = forminator_replace_variables( $newtab, self::$module_id );
			self::$response_attrs['url']    = esc_url_raw( $redirect_url );
			self::$response_attrs['newtab'] = esc_html( $newtab );
		}

		if ( ( ! isset( $tab_value ) || 'newtab_thankyou' === $tab_value ) && ! empty( $behavior_options['thankyou-message'] ) ) {
			/**
			 * Filter thankyou message
			 *
			 * @since 1.11
			 *
			 * @param string $behavior_options ['thankyou-message'].
			 * @param array $submitted_data
			 * @param Forminator_Form_Model $custom_form
			 *
			 * @return string
			 */
			$behavior_options['thankyou-message'] = apply_filters( 'forminator_custom_form_thankyou_message', $behavior_options['thankyou-message'], $custom_form );
			// replace form data vars with value.
			$thankyou_message = forminator_replace_form_data( $behavior_options['thankyou-message'], $custom_form, $entry, true );
			// replace misc data vars with value.
			$thankyou_message                = forminator_replace_variables( $thankyou_message, self::$module_id );
			self::$response_attrs['message'] = $thankyou_message;
			if ( ! empty( $behavior_options['autoclose'] ) ) {
				self::$response_attrs['fadeout']      = $behavior_options['autoclose'];
				self::$response_attrs['fadeout_time'] = ! empty( $behavior_options['autoclose-time'] )
						? $behavior_options['autoclose-time'] * 1000 : 0;
			}
		}
	}

	/**
	 * Get submission behavior
	 *
	 * @param array $behavior_options Behavior settings.
	 * @return string
	 */
	private static function get_submission_behaviour( $behavior_options ) {
		$submission_behaviour = 'behaviour-thankyou';

		if ( isset( $behavior_options['submission-behaviour'] ) ) {
			$submission_behaviour = $behavior_options['submission-behaviour'];
		}

		// If Stripe field exist & submit is AJAX we fall back to hide to force page reload when form submitted.
		if ( ! empty( self::$info['stripe_field'] ) || ! empty( self::$info['paypal_field'] ) && self::$module_object->is_ajax_submit() ) {
			$submission_behaviour = 'behaviour-hide';
		}

		$submission_behaviour = apply_filters( 'forminator_custom_form_get_submission_behaviour', $submission_behaviour, self::$module_id, $behavior_options );

		return $submission_behaviour;
	}

	/**
	 * Get the relevant behavior which will be applied according conditions
	 *
	 * @return array|false Return the relevant behavior or false if no behavior found.
	 */
	private static function get_relevant_behavior_options() {
		$behavior_array = self::$module_object->get_behavior_array();

		foreach ( $behavior_array as $behavior ) {
			if ( empty( $behavior['conditions'] ) ) {
				// If this behavior doesn't have any conditions - return it.
				return $behavior;
			}
			$condition_rule      = isset( $behavior['condition_rule'] ) ? $behavior['condition_rule'] : 'all';
			$condition_fulfilled = 0;

			foreach ( $behavior['conditions'] as $condition ) {
				$is_matched = Forminator_Field::is_condition_matched( $condition );
				if ( $is_matched ) {
					if ( 'any' === $condition_rule ) {
						// If this behavior is matched the conditions - return it. No need to check others.
						return $behavior;
					}
					$condition_fulfilled ++;
				}
			}
			if ( 'all' === $condition_rule && count( $behavior['conditions'] ) === $condition_fulfilled ) {
				// Return this behavior if all conditions are matched.
				return $behavior;
			}
		}

		// If all behaviors aren't matched - return false.
		return false;
	}

	/**
	 * Get select values according limit.
	 *
	 * @return array
	 */
	private static function get_limited_select_values() {
		$result = array();
		if ( self::$is_draft || empty( self::$info['select_field_value'] ) ) {
			return $result;
		}
		foreach ( self::$info['select_field_value'] as $select_name => $select_field ) {
			$select_value = array();
			foreach ( $select_field as $select ) {
				if ( empty( $select['limit'] ) ) {
					continue;
				}
				$entries = Forminator_Form_Entry_Model::select_count_entries_by_meta_field( self::$module_id, $select_name, $select['value'], $select['label'], $select['type'] );
				if ( $select['limit'] <= $entries ) {
					$select_value[] = $select;
				}
			}
			if ( ! empty( $select_value ) ) {
				$result[ $select_name ] = $select_value;
			}
		}
		return $result;
	}

	/**
	 * Remove a password field.
	 *
	 * @return array
	 */
	private static function remove_password() {
		foreach ( self::$info['field_data_array'] as $key => $field_arr ) {
			if ( false !== stripos( $field_arr['name'], 'password-' ) ) {
				unset( self::$info['field_data_array'][ $key ] );
				break;
			}
		}
	}

	/**
	 * Replace values to labels for radios, selectboxes and checkboxes
	 *
	 * @param type $data
	 * @param type $entry
	 * @return type
	 */
	private static function replace_values_to_labels( $data, $entry ) {
		foreach ( $data as $key => $value ) {
			if ( empty( $value['name'] ) ) {
				continue;
			}
			$slug = $value['name'];
			if ( strpos( $slug, 'radio' ) !== false
					|| strpos( $slug, 'select' ) !== false
					|| strpos( $slug, 'checkbox' ) !== false
					) {
				$data[ $key ]['value'] = forminator_replace_form_data( '{' . $slug . '}', self::$module_object, $entry, true );
			}
		}

		return $data;
	}

	/**
	 * Multiple File upload
	 */
	public function multiple_file_upload() {
		$this->init_properties();

		if ( ! isset( self::$prepared_data['nonce'] ) || ! wp_verify_nonce( self::$prepared_data['nonce'], 'forminator_submit_form' ) ) {
			wp_send_json_error( new WP_Error( 'invalid_code' ) );
		}
		$response = array();

		$fields = self::$module_object->get_fields();
		foreach ( $fields as $field ) {
			$field_array = $field->to_formatted_array();
			$element_id  = isset( $field_array['element_id'] ) ? $field_array['element_id'] : '';
			$field_type  = isset( $field_array['type'] ) ? $field_array['type'] : '';
			if ( isset( self::$prepared_data['element_id'] ) && 'upload' === $field_type && self::$prepared_data['element_id'] === $element_id ) {
				$upload_field_obj = Forminator_Core::get_field_object( $field_type );
				$response         = $upload_field_obj->handle_file_upload( $field_array, self::$prepared_data, 'upload' );

				if ( ! $response['success'] || isset( $response['errors'] ) ) {
					wp_send_json_error( $response );
				} else {
					wp_send_json_success( $response );
				}
			}
		}
	}

	/**
	 * Response message
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 * @since 1.5.1 utilize `_post_data` which already defined on submit
	 *
	 * @param $form_id
	 * @param $render_id
	 */
	public function form_response_message( $form_id, $render_id ) {
		$post_render_id = isset( self::$prepared_data['render_id'] ) ? sanitize_text_field( self::$prepared_data['render_id'] ) : 0;
		$response       = self::$response;

		// only show to related form
		if ( ! empty( $response ) && is_array( $response ) && (int) $form_id === (int) self::$module_id && (int) $render_id === (int) $post_render_id ) {
			$label_class = $response['success'] ? 'forminator-success' : 'forminator-error';
			?>
			<div class="forminator-response-message forminator-show <?php echo esc_attr( $label_class ); ?>"
				 tabindex="-1">
				<label class="forminator-label--<?php echo esc_attr( $label_class ); ?>"><?php echo wp_kses_post( $response['message'] ); ?></label>
				<?php
				if ( isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
					?>
					<ul class="forminator-screen-reader-only">
						<?php
						foreach ( $response['errors'] as $key => $error ) {
							foreach ( $error as $id => $value ) {
								?>
								<li><?php echo esc_html( $value ); ?></li>
								<?php
							}
						}
						?>
					</ul>
					<?php
				}
				?>
			</div>
			<?php

			if ( isset( $response['success'] ) && $response['success'] && isset( $response['behav'] ) && ( 'behaviour-hide' === $response['behav'] || ( isset( $response['newtab'] ) && 'newtab_hide' === $response['newtab'] ) ) ) {
				$selector = '#forminator-module-' . $form_id . '[data-forminator-render="' . $render_id . '"]';
				?>
				<script type="text/javascript">var ForminatorFormHider =
					<?php
					echo wp_json_encode(
						array(
							'selector' => $selector,
						)
					);
					?>
				</script>
				<?php
			}
			if ( isset( $response['success'] ) && $response['success'] && isset( $response['behav'] ) && 'behaviour-redirect' === $response['behav'] && isset( $response['newtab'] ) && ( 'newtab_hide' === $response['newtab'] || 'newtab_thankyou' === $response['newtab'] ) ) {
				$url = $response['url'];
				?>
				<script type="text/javascript">var ForminatorFormNewTabRedirect =
					<?php
					echo wp_json_encode(
						array(
							'url' => $url,
						)
					);
					?>
				</script>
				<?php
			}
		}
	}

	/**
	 * @since 1.0
	 *
	 * @return mixed
	 */
	private static function get_invalid_form_message() {
		$invalid_form_message = __( 'Error: Your form is not valid, please fix the errors!', 'forminator' );
		if ( ! empty( self::$module_settings['submitData']['custom-invalid-form-message'] ) ) {
			$invalid_form_message = self::$module_settings['submitData']['custom-invalid-form-message'];
		}

		return apply_filters( 'forminator_custom_form_invalid_form_message', $invalid_form_message, self::$module_id );
	}

	/**
	 * Executor On form submit for attached addons
	 *
	 * @see   Forminator_Addon_Form_Hooks_Abstract::on_form_submit()
	 * @since 1.1
	 *
	 * @return bool true on success|string error message from addon otherwise
	 */
	private static function attach_addons_on_form_submit() {
		if ( self::$is_draft || self::$is_spam ) {
			return;
		}

		// find is_form_connected.
		$connected_addons = forminator_get_addons_instance_connected_with_module( self::$module_id, 'form' );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_form_hooks( self::$module_id );
				if ( ! $form_hooks instanceof Forminator_Addon_Form_Hooks_Abstract ) {
					continue;
				}
				$addon_return = $form_hooks->on_form_submit( self::$prepared_data );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to attach_addons_on_form_submit', $e->getMessage() );
			}
			if ( true !== $addon_return ) {
				throw new Exception( $addon_return );
			}
		}

		return true;
	}

	/**
	 * Process stripe charge
	 *
	 * @since 1.7
	 * @param object $entry Entry.
	 *
	 * @return array
	 */
	private static function stripe_field_to_entry_data_array( $entry ) {
		$field_object = Forminator_Core::get_field_object( 'stripe' );

		if ( ! $field_object ) {
			return;
		}

		$field = self::$info['stripe_field'];

		/**
		 * Fires before process stripe
		 *
		 * @since 1.7
		 *
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field properties.
		 * @param array $submitted_data
		 * @param array $field_data_array
		 */
		do_action( 'forminator_custom_form_before_stripe_charge', self::$module_object, $field, self::$prepared_data, self::$info['field_data_array'] );

		$payment_plan = $field_object->get_payment_plan( $field );
		if ( 'single' === $payment_plan['payment_method'] ) {
			$mode            = isset( $field['mode'] ) ? $field['mode'] : 'sandbox';
			$plan_data_array = self::handle_stripe_single( $field_object, $field, $entry, $mode );
		} else {
			$plan_data_array = self::handle_stripe_subscription( $field_object, $field, $entry, $payment_plan );
		}

		if ( is_wp_error( $plan_data_array ) ) {
			throw new Exception( $plan_data_array->get_error_message() );
		}

		self::$info['field_data_array'][] = $plan_data_array;
	}

	/**
	 * Process PayPal charge
	 *
	 * @since 1.7
	 * @param object $entry Entry.
	 *
	 * @return array
	 */
	private static function paypal_field_to_entry_data_array( $entry ) {
		$field_object = Forminator_Core::get_field_object( 'paypal' );

		if ( ! $field_object ) {
			return;
		}

		$field = self::$info['paypal_field'];

		/**
		 * Fires before process paypal
		 *
		 * @since 1.7
		 *
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field properties.
		 * @param array $submitted_data
		 * @param array $field_data_array
		 */
		do_action( 'forminator_custom_form_before_paypal_charge', self::$module_object, $field, self::$prepared_data, self::$info['field_data_array'] );

		$entry_data        = $field_object->process_to_entry_data( $field );
		$paypal_entry_data = array(
			'name'  => $field['element_id'],
			'value' => $entry_data,
		);

		/**
		 * Filter paypal entry data that might be stored/used later
		 *
		 * @since 1.7
		 *
		 * @param array $calculation_entry_data
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field_properties.
		 * @param array $field_data_array
		 *
		 * @return array
		 */
		$paypal_entry_data = apply_filters( 'forminator_custom_form_paypal_entry_data', $paypal_entry_data, self::$module_object, $field, self::$info['field_data_array'] );

		if ( empty( $paypal_entry_data ) ) {
			return;
		}
		forminator_maybe_log( __METHOD__, $paypal_entry_data['value'] );
		if ( ! empty( $paypal_entry_data['value']['error'] ) ) {
			throw new Exception( $paypal_entry_data['value']['error'] );
		}

		/**
		 * Fires after charge paypal
		 *
		 * @since 1.7
		 *
		 * @param Forminator_Form_Model $module_object
		 * @param array $field field properties.
		 * @param array $paypal_entry_data
		 * @param array $submitted_data
		 * @param array $field_data_array
		 */
		do_action( 'forminator_custom_form_after_paypal_charge', self::$module_object, $field, $paypal_entry_data, self::$prepared_data, self::$info['field_data_array'] );

		$paypal  = new Forminator_PayPal_Express();
		$mode    = isset( $field['mode'] ) ? $field['mode'] : 'sandbox';
		$capture = $paypal->capture_order( $entry_data['transaction_id'], $mode );

		if ( ! isset( $capture->status ) || 'COMPLETED' !== $capture->status ) {
			// Delete entry if capture is not successful.
			$entry->delete();

			throw new Exception( __( 'Payment failed, please try again!', 'forminator' ) );
		}
		$paypal_entry_data['value']['status'] = 'COMPLETED';

		if ( isset( $capture->purchase_units[0]->payments->captures[0]->id ) ) {
			$transaction_id = $capture->purchase_units[0]->payments->captures[0]->id;

			$paypal_entry_data['value']['transaction_id']   = $transaction_id;
			$paypal_entry_data['value']['transaction_link'] = $field_object::get_transanction_link( $mode, $transaction_id );
		}

		self::$info['field_data_array'][] = $paypal_entry_data;
	}

	/**
	 * Prepare hidden and visible fields
	 */
	public static function check_fields_visibility() {
		$fields         = self::$module_object->get_fields();
		$visible_fields = array(); // Visible fields with calculable values.
		$unspecified    = array(); // it's not clear these fields are hidden or visible.
		do {
			$previous_unspecified = $unspecified;
			$unspecified          = array();
			foreach ( $fields as $field ) {
				$field_settings = $field->to_formatted_array();
				$field_id       = Forminator_Field::get_property( 'element_id', $field_settings );

				// Skip if this field is already checked.
				if ( in_array( $field_id, array_keys( $visible_fields ), true )
						|| in_array( $field_id, self::$hidden_fields, true ) ) {
					continue;
				}

				$conditions   = Forminator_Field::get_property( 'conditions', $field_settings, array() );
				$field_type   = Forminator_Field::get_property( 'type', $field_settings );
				$field_object = Forminator_Core::get_field_object( $field_type );
				$is_hidden    = null;

				if ( $conditions ) {
					$dependent_fields = wp_list_pluck( $conditions, 'element_id' );
					$depends          = self::dependencies_not_ready( $dependent_fields, $visible_fields );
					if ( $depends ) {
						$unspecified[ $field_id ] = $conditions;
						continue;
					} else {
						$is_hidden = Forminator_Field::is_hidden( $field_settings );
						if ( $is_hidden ) {
							self::update_hidden_fields_array( $field_id );
							continue;
						}
					}
				}

				// - old build_pseudo_submitted_data //
				$submitted_field_data = isset( self::$prepared_data[ $field_id ] ) ? self::$prepared_data[ $field_id ] : null;
				$calculable_value     = $field_object::get_calculable_value( $submitted_field_data, $field_settings );
				if ( 'calculation' !== $field_type ) {
					// - old has_stripe_field //
					// - old has_paypal_field //
					if ( in_array( $field_type, array( 'stripe', 'paypal' ), true ) ) {
						$dependent_fields = $field_object->get_amount_dependent_fields( $field_settings );
						$depends          = self::dependencies_not_ready( $dependent_fields, $visible_fields );
						if ( $depends ) {
							$unspecified[ $field_id ] = true;
							continue;
						}
						$amount = $field_object->get_payment_amount( $field_settings );

						$visible_fields[ $field_id ] = $amount;
						if ( ! empty( self::$prepared_data[ $field_id ] ) ) {
							self::$prepared_data['payment_transaction_id'] = self::$prepared_data[ $field_id ];
						}
						self::$prepared_data[ $field_id ] = $amount;
						// Save 'stripe_field' and 'paypal_field'.
						self::$info[ $field_type . '_field' ] = $field_settings;
					} else {
						$visible_fields[ $field_id ] = $calculable_value === $field_object::FIELD_NOT_CALCULABLE ? $submitted_field_data : $calculable_value;

						$visible_fields = self::prepare_subfields( $visible_fields, $field_settings, $field_object );
						$method         = 'handle_' . $field_type . '_field';
						if ( method_exists( self::class, $method ) ) {
							self::$method( $field_settings );
						}
					}
					continue;
				}
				// handle calculation field.
				$formula           = $calculable_value;
				$fields_in_formula = forminator_calculator_pull_fields( $formula );
				$depends           = self::dependencies_not_ready( $fields_in_formula[1], $visible_fields );
				if ( $depends ) {
					$unspecified[ $field_id ] = $formula;
					continue;
				}
				$result                      = self::calculate_formula( $formula, $visible_fields, $field_settings );
				$visible_fields[ $field_id ] = $result;

				// Store result of calculation field.
				self::$prepared_data[ $field_id ] = $result;

				$formatting_result = Forminator_Field::forminator_number_formatting( $field_settings, $result );

				$calculation_entry_data = array(
					'name'  => $field_id,
					'value' => array(
						'result'            => $result,
						'formatting_result' => $formatting_result,
					),
				);

				self::$info['field_data_array'][] = $calculation_entry_data;
			}
		} while ( $unspecified && $previous_unspecified !== $unspecified );

		if ( $unspecified ) {
			error_log( '[Forminator] Unspecified fields ' . wp_json_encode( array_keys( $unspecified ) ) );
			self::$hidden_fields = array_merge( self::$hidden_fields, array_keys( $unspecified ) );
		}

		/**
		 * Filter Handled submitted data on Custom Form
		 *
		 * @param array $prepared_data
		 * @param Forminator_Form_Model $module_object
		 *
		 * @return array
		 */
		self::$prepared_data = apply_filters( 'forminator_prepared_data', self::$prepared_data, self::$module_object );
	}

	/**
	 * Add $field_id and its subfields to $hidden_fields array
	 *
	 * @param string $field_id Field slug.
	 */
	private static function update_hidden_fields_array( $field_id ) {
		self::$hidden_fields[] = $field_id;
		unset( self::$prepared_data[ $field_id ] );
		$field_suffix = Forminator_Form_Entry_Model::field_suffix();
		foreach ( $field_suffix as $suffix ) {
			$mod_field_id          = $field_id . '-' . $suffix;
			self::$hidden_fields[] = $mod_field_id;
			unset( self::$prepared_data[ $mod_field_id ] );
		}
	}

	/**
	 * Check if all dependent fields are already defined
	 *
	 * @param array $dependent_fields Dependent fields.
	 * @param array $visible_fields Visible fields.
	 * @return boolean
	 */
	private static function dependencies_not_ready( $dependent_fields, $visible_fields ) {
		$unspecified_depend_fields = array_diff( $dependent_fields, array_keys( $visible_fields ), self::$hidden_fields );
		if ( $unspecified_depend_fields ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare subfields
	 *
	 * @param array  $visible_fields Visible fields.
	 * @param array  $field_settings Field settings.
	 * @param object $field_object Field object.
	 */
	private static function prepare_subfields( $visible_fields, $field_settings, $field_object ) {
		$field_id = Forminator_Field::get_property( 'element_id', $field_settings );
		if ( isset( self::$prepared_data[ $field_id ] ) ) {
			return $visible_fields;
		}
		$field_type   = Forminator_Field::get_property( 'type', $field_settings );
		$field_suffix = Forminator_Form_Entry_Model::field_suffix();

		foreach ( $field_suffix as $suffix ) {
			$mod_field_id = $field_id . '-' . $suffix;
			if ( isset( self::$prepared_data[ $mod_field_id ] ) ) {
				// Add subfield to $visible_fields array.
				$visible_fields[ $mod_field_id ]             = self::$prepared_data[ $mod_field_id ];
				self::$prepared_data[ $field_id ][ $suffix ] = self::$prepared_data[ $mod_field_id ];
			} elseif (
				isset( $_FILES[ $mod_field_id ] ) &&
				'postdata' === $field_type &&
				'post-image' === $suffix &&
				! self::$is_draft
			) {
				$post_image = $field_object->upload_post_image( $field_settings, $mod_field_id );
				if ( is_array( $post_image ) && $post_image['attachment_id'] > 0 ) {
					self::$prepared_data[ $field_id ]['post-image'] = $post_image;
				} else {
					self::$prepared_data[ $field_id ]['post-image'] = '';
				}
			}
		}

		return $visible_fields;
	}

	/**
	 * Calculate formula.
	 *
	 * @param string $formula Formula.
	 * @param array  $visible_fields Not hidden field values.
	 * @param array  $field_settings Field settings.
	 * @return type
	 */
	public static function calculate_formula( $formula, $visible_fields, $field_settings ) {
		// - old get_converted_formula //
		// - old forminator_calculator_maybe_replace_fields_on_formula //
		$fields_in_formula = forminator_calculator_pull_fields( $formula );

		// later usage for str_replace.
		$full_matches      = $fields_in_formula[0];
		$converted_formula = $formula;
		foreach ( $fields_in_formula[1] as $key => $field_id ) {
			if ( ! isset( $full_matches[ $key ] ) ) {
				continue;
			}

			if ( in_array( $field_id, self::$hidden_fields, true ) ) {
				// skip validation, hidden values = 0 or 1.
				// - old replace_hidden_field_values //.
				$value = self::replace_to( $field_id, $converted_formula );
			} else {
				$value = $visible_fields[ $field_id ];
			}

			$converted_formula = str_replace( $full_matches[ $key ], '(' . ( $value ) . ')', $converted_formula );
		}

		// - old get_calculated_value //
		$precision  = Forminator_Calculation::get_calculable_precision( $field_settings );
		$calculator = new Forminator_Calculator( $converted_formula );
		$calculator->set_is_throwable( true );

		try {
			$result = round( floatval( $calculator->calculate() ), $precision );
		} catch ( Forminator_Calculator_Exception $e ) {
			$result = round( 0.0, $precision );
		}
		// - old get_calculated_value //

		return $result;
	}

	/**
	 * Handle captcha field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_captcha_field( $field_settings ) {
		self::$info['captcha_settings'] = $field_settings;
	}

	/**
	 * Handle postdata field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_postdata_field( $field_settings ) {
		$field_id      = Forminator_Field::get_property( 'element_id', $field_settings );
		$post_type     = Forminator_Field::get_property( 'post_type', $field_settings, 'post' );
		$category_list = forminator_post_categories( $post_type );
		if ( ! empty( $category_list ) ) {
			foreach ( $category_list as $category ) {
				$mod_field_id = $field_id . '-' . $category['value'];
				if ( isset( self::$prepared_data[ $mod_field_id ] ) ) {
					self::$prepared_data[ $field_id ][ $category['value'] ] = self::$prepared_data[ $mod_field_id ];
				}
			}
		}
		$custom_vars = Forminator_Field::get_property( 'post_custom_fields', $field_settings );
		$custom_meta = Forminator_Field::get_property( 'options', $field_settings );
		if ( empty( $custom_vars ) || empty( $custom_meta ) || self::$is_draft ) {
			return;
		}
		foreach ( $custom_meta as $meta ) {
			$value = ! empty( $meta['value'] ) ? trim( $meta['value'] ) : '';
			$label = $meta['label'];

			if ( strpos( $value, '{' ) !== false && strpos( $value, '{upload' ) === false ) {
				$value = forminator_replace_form_data( $value, self::$module_object );
				$value = forminator_replace_variables( $value, self::$module_id );
			} elseif ( isset( self::$prepared_data[ $value ] ) ) {
				$value = self::$prepared_data[ $value ];
			}

			// Store data that will be used later by upload fields.
			if ( strpos( $value, '{upload' ) !== false ) {
				self::$info['upload_in_customfield'][] = array(
					'postdata_id' => $field_id,
					'upload_id'   => trim( $value, '{}' ),
					'uploads'     => '',
				);
			}

			self::$prepared_data[ $field_id ]['post-custom'][] = array(
				'key'   => $label,
				'value' => $value,
			);
		}
	}

	/**
	 * Handle date field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_date_field( $field_settings ) {
		$file_type = Forminator_Field::get_property( 'field_type', $field_settings );
		if ( 'picker' !== $file_type ) {
			$field_id    = Forminator_Field::get_property( 'element_id', $field_settings );
			$date_format = Forminator_Field::get_property( 'date_format', $field_settings );

			self::$prepared_data[ $field_id ]['format'] = datepicker_default_format( $date_format );
		}
	}

	/**
	 * Handle Time field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_time_field( $field_settings ) {
		$time_type = Forminator_Field::get_property( 'time_type', $field_settings, 'twelve' );

		if ( 'twelve' === $time_type ) {
			$field_id = Forminator_Field::get_property( 'element_id', $field_settings );
			$time     = self::$prepared_data[ $field_id ];

			if ( empty( $time['hours'] ) && empty( $time['minutes'] ) ) {
				unset( self::$prepared_data[ $field_id ] );
			}
		}
	}

	/**
	 * Handle url field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_url_field( $field_settings ) {
		$form_field_obj = Forminator_Core::get_field_object( 'url' );
		$field_id       = Forminator_Field::get_property( 'element_id', $field_settings );

		self::$prepared_data[ $field_id ] = $form_field_obj->add_scheme_url( self::$prepared_data[ $field_id ] );
	}

	/**
	 * Handle upload field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_upload_field( $field_settings ) {
		if ( self::$is_draft ) {
			return;
		}

		$file_type     = Forminator_Field::get_property( 'file-type', $field_settings, 'single' );
		$upload_method = Forminator_Field::get_property( 'upload-method', $field_settings, 'ajax' );
		$field_id      = Forminator_Field::get_property( 'element_id', $field_settings );

		$form_field_obj   = Forminator_Core::get_field_object( 'upload' );
		$form_upload_data = isset( self::$prepared_data['forminator-multifile-hidden'] )
			? json_decode( stripslashes( self::$prepared_data['forminator-multifile-hidden'] ), true )
			: array();

		/** @var  Forminator_Upload $form_field_obj */
		if ( 'multiple' === $file_type && 'ajax' === $upload_method ) {
			$upload_multiple_data = isset( $form_upload_data[ $field_id ] ) ? $form_upload_data[ $field_id ] : array();
			$upload_data          = $form_field_obj->handle_ajax_multifile_upload( $upload_multiple_data, $field_settings );
		} elseif ( 'multiple' === $file_type && 'submission' === $upload_method ) {
			$upload_multiple_data = isset( $_FILES[ $field_id ] ) ? $_FILES[ $field_id ] : array();
			$upload_data          = $form_field_obj->handle_submission_multifile_upload( $field_settings, $upload_multiple_data );
		} else {
			$upload_data = $form_field_obj->handle_file_upload( $field_settings );
		}
		if ( isset( $upload_data['success'] ) && $upload_data['success'] ) {
			self::$prepared_data[ $field_id ]['file'] = $upload_data;

			// If upload is successful, add the upload data to custom field if tag is present.
			if ( ! empty( self::$info['upload_in_customfield'] ) ) {
				$file_url = $upload_data['file_url'];
				if ( 'multiple' === $file_type ) {
					$file_url = implode( ', ', $upload_data['file_url'] );
				}

				foreach ( self::$info['upload_in_customfield'] as $cf_key => $cf ) {
					if ( $field_id === $cf['upload_id'] ) {
						self::$info['upload_in_customfield'][ $cf_key ]['uploads'] = $file_url;
					}
				}
			}
		} elseif ( isset( $upload_data['success'] ) && false === $upload_data['success'] ) {
			$error = isset( $upload_data['message'] ) ? $upload_data['message'] : self::get_invalid_form_message();

			self::$submit_errors[][ $field_id ] = $error;
		} else {
			// no file uploaded for this field_id.
			self::$prepared_data[ $field_id ] = '';
		}
	}

	/**
	 * Handle select field
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_select_field( $field_settings ) {
		$is_limit = Forminator_Field::get_property( 'limit_status', $field_settings );
		if ( self::$is_draft || 'enable' !== $is_limit ) {
			return;
		}
		$field_id     = Forminator_Field::get_property( 'element_id', $field_settings );
		$options      = Forminator_Field::get_property( 'options', $field_settings );
		$value_type   = Forminator_Field::get_property( 'value_type', $field_settings );
		$select_array = (array) self::$prepared_data[ $field_id ];
		foreach ( $options as $o => $option ) {
			if ( in_array( $option['value'], $select_array ) ) {
				self::$info['select_field_value'][ $field_id ][ $o ] = array(
					'limit' => $option['limit'],
					'value' => $option['value'],
					'label' => $option['label'],
					'type'  => $value_type,
				);
			}
		}
	}

	/**
	 * Apply updated values to hidden-type fields after submission
	 *
	 * @param array $field_settings Field settings.
	 */
	private static function handle_hidden_field( $field_settings ) {
		if ( ! empty( $field_settings['element_id'] ) && ! empty( $field_settings['default_value'] ) ) {
			if ( 'user_ip' === $field_settings['default_value'] ) {
				self::$prepared_data[ $field_settings['element_id'] ] = Forminator_Geo::get_user_ip();
			}
			if ( 'submission_time' === $field_settings['default_value'] ) {
				self::$prepared_data[ $field_settings['element_id'] ] = date_i18n( 'g:i:s a, T', forminator_local_timestamp(), true );
			}
		}
	}

	/**
	 * Returns what the current field should be replaced to (0 or 1)
	 *
	 * @param string $field_id Field id.
	 * @param string $formula Formula.
	 * @return int 0|1
	 */
	public static function replace_to( $field_id, $formula ) {
		$replace        = 0;
		$quoted_operand = preg_quote( '{' . $field_id . '}', '/' );
		$pattern        = '/([\\+\\-\\*\\/]?)[^\\+\\-\\*\\/\\(]*' . $quoted_operand
				. '[^\\)\\+\\-\\*\\/]*([\\+\\-\\*\\/]?)/';

		$matches = array();
		if ( preg_match( $pattern, $formula, $matches ) ) {
			// if operand in multiplication or division set value = 1.
			if ( '*' === $matches[1] || '/' === $matches[1] || '*' === $matches[2] || '/' === $matches[2] ) {
				$replace = 1;
			}
		}

		return $replace;
	}

	/**
	 * Create new post from postdata field
	 * Add upload file urls to postdata custom fields if necessary
	 *
	 * @param array $postdata_fields Fields with postdata type.
	 * @return array
	 */
	public static function create_post_from_postdata( $postdata_fields ) {
		$postdata_result = array();
		foreach ( $postdata_fields as $postdata_field ) {
			$field_id       = $postdata_field['name'];
			$field_data     = $postdata_field['value'];
			$field_array    = $postdata_field['field_array'];
			$form_field_obj = $postdata_field['form_field_obj'];

			// check if field_data of post values not empty (happen when postdata is not required).
			$filtered   = array_filter( $field_data );
			$post_value = $field_data;
			if ( ! empty( $filtered ) ) {
				if ( isset( $filtered['post-custom'] ) ) {
					foreach ( $filtered['post-custom'] as $custom_field_index => $custom_field ) {
						if ( preg_match( '/\{upload-(\d+)\}/', $custom_field['value'] ) ) {
							$upload_id = trim( $custom_field['value'], '{}' );

							foreach ( self::$info['upload_in_customfield'] as $cf_data ) {
								if ( $upload_id === $cf_data['upload_id'] && $field_id === $cf_data['postdata_id'] ) {
									$field_data['post-custom'][ $custom_field_index ]['value'] = $cf_data['uploads'];
								}
							}
						}
					}
				}

				$post_id = $form_field_obj->save_post( $field_array, $field_data );
				if ( $post_id ) {
					$field_data = array(
						'postdata' => $post_id,
						'value'    => $field_data,
					);
				} else {
					return array(
						'type'     => 'error',
						'field_id' => $field_id,
						'value'    => __( 'There was an error saving the post data. Please try again', 'forminator' ),
					);
				}
			} else {
				$field_data = array(
					'postdata' => null,
					'value'    => $post_value,
				);
			}

			$postdata_result[] = array(
				'type'       => 'success',
				'field_id'   => $field_id,
				'field_data' => $field_data,
			);
		}

		return $postdata_result;
	}

	/**
	 * Retrieve the backup code if lost phone.
	 *
	 * @return void
	 */
	public function fallback_email() {
		$defender_data    = forminator_defender_compatibility();
		$two_fa_component = new $defender_data['two_fa_component']();
		$post_data        = $this->get_post_data();
		$token            = isset( $post_data['token'] ) ? $post_data['token'] : '';
		$ret              = $two_fa_component->send_otp_to_email( $token );
		if ( false === $ret ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please try again.', 'forminator' ),
				)
			);
		}

		if ( is_wp_error( $ret ) ) {
			wp_send_json_error(
				array(
					'message' => $ret->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Your code has been sent to your email.', 'forminator' ),
			)
		);
	}

	/**
	 * Get the first email in submitted data
	 *
	 * @since 1.17.0
	 */
	private static function get_first_email( $submitted_data ) {
		foreach ( $submitted_data as $key => $val ) {
			if ( false !== strpos( $key, 'email-' ) && ! empty( $val ) ) {
				return $val;
			}
		}

		return;
	}

	/**
	 * Create draft ID
	 *
	 * @since 1.17.0
	 */
	public function create_draft_id( $form_id ) {
		// Must guarantee alphanumeric
		$draft_id  = rand( 0, 9 );
		$draft_id .= substr( str_shuffle( 'abcdefghijklmnopqrstvwxyz' ), 0, 1 );
		$draft_id .= substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 10 );

		return apply_filters( 'forminator_create_draft_id', $draft_id, $form_id );
	}

	/**
	 * Set the Draft ID
	 */
	public function set_entry_draft_id() {
		// 1st draft save
		if ( self::$is_draft && is_null( self::$previous_draft_id ) ) {
			return $this->create_draft_id( self::$module_id );

			// Succeeding draft saves
		} elseif ( self::$is_draft && ! is_null( self::$previous_draft_id ) ) {
			return self::$previous_draft_id;
		}
	}

	/**
	 * Get draft notification from form notifications
	 *
	 * @since 1.17.0
	 */
	public function get_draft_notification( $notifications, $data ) {
		foreach ( $notifications as $key => $notif ) {
			if ( false !== array_search( 'save_draft', $notif ) ) {
				$notifications[ $key ]['recipients']   = str_replace( '{save_and_continue_email}', $data['email-1'], $notif['recipients'] );
				$email_msg                             = $notif['email-editor'];
				$email_msg                             = str_replace( '{form_link}', $data['draft_link'], $email_msg );
				$email_msg                             = str_replace( '{retention_period}', $data['retention_period'], $email_msg );
				$notifications[ $key ]['email-editor'] = $email_msg;

				return $notifications[ $key ];
			}
		}

		return false;
	}

	/**
	 * Draft link email submission
	 *
	 * @since 1.17.0
	 */
	public function submit_email_draft_link() {
		$draft_id = filter_input( INPUT_POST, 'draft_id', FILTER_DEFAULT );
		$nonce    = 'forminator_nonce_email_draft_link_' . $draft_id;
		if ( ! check_ajax_referer( $nonce, $nonce ) ) {
			wp_send_json_error( __( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		$submitted_data = Forminator_Core::sanitize_array( $_POST );
		$form_id        = $submitted_data['form_id'];
		$email          = $submitted_data['email-1'];

		if ( empty( $email ) ) {

			wp_send_json_error(
				array(
					'field'   => 'email-1',
					'message' => apply_filters(
						'forminator_draft_email_required',
						esc_html__( 'Email is required.', 'forminator' ),
						$form_id
					),
				)
			);
		} elseif ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {

			wp_send_json_error(
				array(
					'field'   => 'email-1',
					'message' => apply_filters(
						'forminator_draft_email_invalid',
						esc_html__( 'Please enter a valid email.', 'forminator' ),
						$form_id
					),
				)
			);
		}

		// Send email
		$custom_form = Forminator_Form_Model::model()->load( $form_id );
		if ( ! is_object( $custom_form ) ) {
			wp_send_json_error(
				array(
					'message' => apply_filters(
						'forminator_draft_invalid_form_id',
						esc_html__( 'Invalid form ID.', 'forminator' ),
						$form_id
					),
				)
			);
		}

		$draft_notifications = $this->get_draft_notification( $custom_form->notifications, $submitted_data );
		unset( $custom_form->notifications );
		$custom_form->notifications[] = $draft_notifications;
		$forminator_mail_sender       = new Forminator_CForm_Front_Mail();
		$mail_sent                    = $forminator_mail_sender->process_mail( $custom_form, self::get_entry(), $submitted_data );
		// $mail_sent = true;
		$response['draft_mail_sent'] = $mail_sent;

		if ( $mail_sent ) {
			$response['draft_mail_message'] = sprintf(
				'<p>%s</p><a href="#" class="draft-resend-mail">%s</a>',
				esc_html__( "We've sent the resume form link to your email address. Please check your spam folder if you can't find the link in your inbox.", 'forminator' ),
				esc_html__( 'Change email', 'forminator' )
			);
		} else {
			$response['draft_mail_message'] = sprintf(
				'<p>%s</p><a href="#" class="draft-resend-mail">%s</a>',
				esc_html__( "We couldn't send the form resume link to your email at this time. Click on the link below to resend it or manually copy and save the link in a safe place.", 'forminator' ),
				esc_html__( 'Resend link', 'forminator' )
			);
		}

		wp_send_json_success( $response );
	}

}
