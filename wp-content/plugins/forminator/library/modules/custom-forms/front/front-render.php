<?php

/**
 * Front render class for custom forms
 *
 * @since 1.0
 */
class Forminator_CForm_Front extends Forminator_Render_Form {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * @var null|Forminator_PayPal_Express
	 */
	private static $paypal = null;

	/**
	 * @var array
	 */
	private static $paypal_forms = array();

	/**
	 * @var string
	 */
	private $inline_rules = '';

	/**
	 * @var string
	 */
	private $inline_messages = '';

	/**
	 * Model data
	 *
	 * @var Forminator_Form_Model
	 */
	public $lead_model = null;

	/**
	 * Return class instance
	 *
	 * @since 1.0
	 * @return Forminator_CForm_Front
	 */
	public static function get_instance() {
		return new self();
	}

	/**
	 * Display form method
	 *
	 * @since 1.0
	 *
	 * @param      $id
	 * @param bool  $is_preview
	 * @param bool  $data
	 * @param bool  $hide If true, display: none will be added on the form markup and later removed with JS.
	 * @param array $quiz_model
	 * @param array $is_ajax
	 */
	public function display( $id, $is_preview = false, $data = false, $hide = true, $quiz_model = null ) {
		if ( $data && ! empty( $data ) ) {
			$this->model = Forminator_Form_Model::model()->load_preview( $id, $data );
			// its preview!
			$this->model->id = $id;
		} else {
			$this->model = Forminator_Base_Form_Model::get_model( $id );

			if ( ! $this->model instanceof Forminator_Form_Model ) {
				return;
			}
		}

		if ( isset( $this->model->settings['form-type'] ) && 'leads' === $this->model->settings['form-type'] && is_null( $quiz_model ) ) {
			return;
		}

		$is_ajax_load = $this->is_ajax_load( $is_preview );

		if ( $quiz_model ) {
			$this->lead_model = $quiz_model;
			$is_ajax_load     = isset( $this->lead_model->settings['use_ajax_load'] ) ? $this->lead_model->settings['use_ajax_load'] : false;
		}

		$this->maybe_define_cache_constants();

		// TODO: make preview and ajax load working similar.

		// preview force using ajax.

		// hide login/registration form if a user is already logged in.
		$hide_form = $hidden_form_message = false;
		if ( isset( $this->model->settings['form-type'] ) && in_array( $this->model->settings['form-type'], array( 'login', 'registration' ) ) && is_user_logged_in() ) {
			// Option 'Is a form hide?'.
			$hide_option = 'hide-' . $this->model->settings['form-type'] . '-form';
			$hide_form   = ( isset( $this->model->settings[ $hide_option ] ) && '1' === $this->model->settings[ $hide_option ] ) ? true : false;
			// Display message if a form is hidden.
			$hide_form_message_option = 'hidden-' . $this->model->settings['form-type'] . '-form-message';
			$hidden_form_message      = isset( $this->model->settings[ $hide_form_message_option ] ) && ! empty( $this->model->settings[ $hide_form_message_option ] )
				? $this->model->settings[ $hide_form_message_option ]
				: false;
		}

		if ( ! $this->is_displayable( $is_preview ) ) {
			return;
		}

		$this->generate_render_id( $id );

		if ( $hide_form ) {
			if ( $hidden_form_message ) {
				echo wp_kses_post( $this->render_hidden_form_message( $hidden_form_message ) );
			}
			return;
		}

		if ( $is_ajax_load ) {
			if ( ! $this->lead_model ) {
				$this->get_form_placeholder( esc_attr( $id ), true );
			}
			$this->enqueue_form_scripts( $is_preview, $is_ajax_load );

			return;
		}

		echo $this->get_html( $hide, $is_preview, self::$render_ids[ $id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( is_admin() || $is_preview ) {
			$this->print_styles();
		}

		if ( $is_preview ) {
			$this->forminator_render_front_scripts();
		}

		$this->enqueue_form_scripts( $is_preview );
	}

	/**
	 * Header message to handle error message
	 *
	 * @since 1.0
	 */
	public function render_form_header( $maybe_error = '' ) {
		// if rendered on Preview, the array is empty and sometimes PHP notices show up.
		if ( ! isset( self::$render_ids[ $this->model->id ] ) ) {
			self::$render_ids[ $this->model->id ] = 0;
		}

		ob_start();
		do_action( 'forminator_form_post_message', $this->model->id, self::$render_ids[ $this->model->id ] ); // prints html, so we need to capture this.
		$error = ob_get_clean();

		if ( ! empty( $error ) ) {
			return $error;
		}

		if ( empty( $maybe_error ) ) {
			$wrapper = '<div class="forminator-response-message forminator-error" aria-hidden="true"></div>';
		} else {
			$wrapper = '<div class="forminator-response-message forminator-error" aria-hidden="false">' . esc_html( $maybe_error ) . '</div>';
		}

		return $wrapper;
	}

	/**
	 * Footer handle
	 *
	 * @since 1.12
	 */
	public function render_form_authentication() {

		$wrapper = '';
		// These are unique IDs.
		$module_id = 'forminator-module-' . $this->model->id . '-authentication';
		$title_id  = $module_id . '-title';
		$label_id  = $module_id . '-label';
		$input_id  = $module_id . '-input';
		$notice_id = $module_id . '-notice';
		$token_id = $module_id . '-token';

		$form_type = isset( $this->model->settings['form-type'] ) ? $this->model->settings['form-type'] : '';

		if ( 'login' !== $form_type ) {
			return '';
		}

		if ( is_multisite() ) {
			$login_header_url   = network_home_url();
			$login_header_title = get_network()->site_name;
		} else {
			$login_header_url   = __( 'https://wordpress.org/' );
			$login_header_title = __( 'Powered by WordPress' );
		}

		$defender_data  = forminator_defender_compatibility();
		$settings       = $defender_data['two_fa_settings'];
		$custom_graphic = ! $defender_data['is_free'] && $settings->custom_graphic
			? $settings->custom_graphic_url
			: $defender_data['img_dir_url'] . '2factor-disabled.svg';

		$app_text = isset( $settings->app_text )
			? $settings->app_text
			: esc_html__( 'Open the Google Authenticator app and enter the 6 digit passcode.', 'forminator' );

		$providers = $this->get_2FA_poviders();

		$wrapper .= '<div class="forminator-authentication">';

		$wrapper .= '<div role="dialog" id="' . $module_id . '" class="forminator-authentication-content" aria-modal="true" aria-labelledby="' . $title_id . '">';

		$wrapper .= '<h1 id="' . $title_id . '"><a href="' . esc_url( $login_header_url ) . '" title="' . esc_attr( $login_header_title ) . '" style="background-image: url(' . $custom_graphic . ');">' . esc_html__( 'Authenticate to login', 'forminator' ) . '</a></h1>';

		$wrapper .= '<div role="alert" id="' . $notice_id . '" class="forminator-authentication-notice" data-error-message="' . esc_html__( 'The passcode was incorrect.', 'forminator' ) . '"></div>';

		foreach ( $providers as $slug => $provider ) {
			$wrapper .= '<div class="forminator-authentication-box" id="forminator-2fa-' . $slug . '">';

			ob_start();

			$provider->authentication_form();

			$wrapper .= ob_get_clean();

			$wrapper .= '</div>';
		}
		$wrapper .='<input type="hidden" class="forminator-auth-method" name="auth_method" value="' . esc_attr( $slug ) . '" id="' . $input_id . '" disabled />';
		$wrapper .='<input type="hidden" class="forminator-auth-token" name="auth_token" id="' . $token_id . '" />';
        $wrapper .= '<div class="forminator-wrap-nav">';
		$wrapper .= esc_html__( 'Having problems? Try another way to log in', 'forminator' );
		$wrapper .= '<ul class="forminator-authentication-nav">';
		foreach ( $providers as $slug => $provider ) {
			$wrapper .= '<li class="forminator-2fa-link" id="forminator-2fa-link-' . esc_attr( $slug ) . '" data-slug="' . esc_attr( $slug ) . '">';
			$wrapper .= $provider->get_login_label();
			$wrapper .= '</li>';
		}
		$wrapper .= '</ul>';
		$wrapper .= '<img class="def-ajaxloader" src="' . $defender_data['img_dir_url'] . 'spinner.svg"/>';
		$wrapper .= '<strong class="notification"></strong>';
        $wrapper .= '</div>';
		global $interim_login;
		if ( ! $interim_login ) {
			$link_back_to = sprintf( _x( '&larr; Back to %s', 'forminator' ), get_bloginfo( 'title', 'display' ) );
			$wrapper .= '<p class="forminator-authentication-backtolog"><a class="auth-back" href="#">' . $link_back_to . '</a></p>';
		}

		$wrapper .= '</div>';

		$wrapper .= '</div>';

		return $wrapper;

	}

	/**
	 * Enqueue form scripts
	 *
	 * @since 1.0
	 *
	 * @param      $is_preview
	 * @param bool       $is_ajax_load
	 */
	public function enqueue_form_scripts( $is_preview, $is_ajax_load = false ) {
		$is_ajax_load = $is_preview || $is_ajax_load;

		// Load assets conditionally.
		$assets = new Forminator_Assets_Enqueue_Form( $this->model, $is_ajax_load );
		$assets->enqueue_styles( $this );
		$assets->enqueue_scripts( $this );

		// Load reCaptcha scripts.
		if ( $this->has_captcha() ) {
			$first_captcha    = $this->find_first_captcha();
			$site_language    = get_locale();
			$captcha_language = get_option( 'forminator_captcha_language', '' );
			$global_language  = ! empty( $captcha_language ) ? $captcha_language : $site_language;
			$language         = Forminator_Field::get_property( 'language', $first_captcha, $global_language );
			$language         = ! empty( $language ) ? $language : $global_language;

			// Check whether provider is reCaptcha or hCaptcha
			if ( $this->is_recaptcha() ) {
				$src = 'https://www.google.com/recaptcha/api.js?hl=' . $language . '&onload=forminator_render_captcha&render=explicit';
				$script_tag = 'forminator-google-recaptcha';
				$script_load = 'grecaptcha';
			} else {
				$src = 'https://js.hcaptcha.com/1/api.js?hl=' . $language . '&onload=forminator_render_hcaptcha&render=explicit&recaptchacompat=off';
				$script_tag = 'forminator-hcaptcha';
				$script_load = 'hcaptcha';
			}

			if ( ! $is_ajax_load ) {
				wp_enqueue_script(
					$script_tag,
					$src,
					array( 'jquery' ),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts[ $script_tag ] = array(
					'src'  => $src,
					'on'   => 'window',
					'load' => $script_load,
				);
			}
		}

		// Load Stripe scripts.
		if ( $this->has_stripe() ) {
			$src = 'https://js.stripe.com/v3/';

			if ( ! $is_ajax_load ) {
				wp_enqueue_script(
					'forminator-stripe',
					$src,
					array( 'jquery' ),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts['forminator-stripe'] = array(
					'src'  => $src,
					'on'   => 'window',
					'load' => 'StripeCheckout',
				);
			}
		}

		// load int-tels.
		if ( $this->has_phone() ) {
			$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
			$style_version = '4.0.3';

			$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
			$script_version = FORMINATOR_VERSION;

			if ( $is_ajax_load ) {
				// load later via ajax to avoid cache.
				$this->styles['intlTelInput-forminator-css'] = array( 'src' => add_query_arg( 'ver', $style_version, $style_src ) );
				$this->scripts['forminator-intlTelInput']    = array(
					'src'  => add_query_arg( 'ver', $style_version, $script_src ),
					'on'   => '$',
					'load' => 'intlTelInput',
				);
			}
		}

		// Load Paypal scripts.
		if ( $this->has_paypal() ) {
			$paypal_src = $this->paypal_script_argument( 'https://www.paypal.com/sdk/js' );

			// If there is more than 1 paypal field in a page, even if it's ajax loaded, enqueue script as usual to prevent paypal button errors.
			if ( ! $is_ajax_load || forminator_count_field_type_in_page( 'paypal' ) > 1 ) {
				wp_enqueue_script(
					'forminator-paypal-' . $this->model->id,
					$paypal_src,
					array( 'jquery' ),
					FORMINATOR_VERSION,
					true
				);
			} else {
				// load later via ajax to avoid cache.
				$this->scripts[ 'forminator-paypal-' . $this->model->id ] = array(
					'src'  => $paypal_src,
					'on'   => 'window',
					'id'   => $this->model->id,
					'load' => 'PayPalCheckout',
				);
			}

			add_action( 'wp_footer', array( $this, 'print_paypal_scripts' ), 9999 );
		}

		if ( $this->has_formatting() ) {
			$base_url                                      = forminator_plugin_url() . 'assets/js/library/';
			$this->scripts['forminator-inputmask']         = array(
				'src'  => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'inputmask.min.js' ),
				'on'   => 'window',
				'load' => 'inputmask',
			);
			$this->scripts['forminator-jquery-inputmask']  = array(
				'src'  => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'jquery.inputmask.min.js' ),
				'on'   => 'window',
				'load' => 'jquery-inputmask',
			);
			$this->scripts['forminator-inputmask-binding'] = array(
				'src'  => add_query_arg( 'ver', FORMINATOR_VERSION, $base_url . 'inputmask.binding.js' ),
				'on'   => 'window',
				'load' => 'inputmask-binding',
			);
		}

		// todo: solve this.
		// load buttons css.
		wp_enqueue_style( 'buttons' );

		if ( $this->has_postdata() || $this->has_editor() ) {
			if ( $is_ajax_load && function_exists( 'wp_enqueue_editor' ) ) {
				wp_enqueue_editor();
			}
		}

		// Load selected google font.
		$fonts        = $this->get_google_fonts();
		$loaded_fonts = array();
		foreach ( $fonts as $setting_name => $font_name ) {
			if ( ! empty( $font_name ) ) {
				if ( in_array( sanitize_title( $font_name ), $loaded_fonts, true ) ) {
					continue;
				}

				$google_font_url = add_query_arg(
					array( 'family' => $font_name ),
					'https://fonts.googleapis.com/css'
				);

				if ( ! $is_ajax_load ) {
					wp_enqueue_style( 'forminator-font-' . sanitize_title( $font_name ), 'https://fonts.googleapis.com/css?family=' . $font_name, array(), '1.0' );
				} else {
					// load later via ajax to avoid cache.
					$this->styles[ 'forminator-font-' . sanitize_title( $font_name ) . '-css' ] = array( 'src' => $google_font_url );
				}
				$loaded_fonts[] = sanitize_title( $font_name );
			}
		}

		/**
		 * Filter enqueue form styles
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->styles = apply_filters( 'forminator_enqueue_form_styles', $this->styles, $is_preview, $is_ajax_load );

		/**
		 * Filter enqueue form scripts
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->scripts = apply_filters( 'forminator_enqueue_form_scripts', $this->scripts, $is_preview, $is_ajax_load );

		/**
		 * Filter enqueue form inline script
		 *
		 * @since 1.13
		 *
		 * @param bool $is_preview
		 * @param bool $is_ajax_load
		 */
		$this->script = apply_filters( 'forminator_enqueue_form_script', $this->script, $is_preview, $is_ajax_load );

		// Load Front Render Scripts.
		// render front script of form front end initialization.
		if ( ! $is_ajax_load ) {
			add_action( 'wp_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
		}
		add_action( 'admin_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
	}

	/**
	 * PayPal Script url parameters
	 *
	 * @param $script
	 *
	 * @return string
	 */
	public function paypal_script_argument( $script ) {
		$paypal_setting = $this->get_paypal_properties();
		if ( ! empty( $paypal_setting ) ) {
			$arg           = array();
			$card_array    = array( 'visa', 'mastercard', 'amex', 'discover', 'jcb', 'elo', 'hiper' );
			$funding_array = array(
				'card',
				'credit',
				'bancontact',
				'blik',
				'eps',
				'giropay',
				'ideal',
				'mercadopago',
				'mybank',
				'p24',
				'sepa',
				'sofort',
				'venmo',
			);
			if ( 'live' === $paypal_setting['mode'] ) {
				$arg['client-id'] = $paypal_setting['live_id'];
			} else {
				$arg['client-id'] = esc_html( $paypal_setting['sandbox_id'] );
			}
			if ( ! empty( $paypal_setting['currency'] ) ) {
				$arg['currency'] = $paypal_setting['currency'];
			}
			if ( ! empty( $paypal_setting['locale'] ) ) {
				$arg['locale'] = $paypal_setting['locale'];
			}
			foreach ( $funding_array as $fund ) {
				if ( ! empty( $paypal_setting[ $fund ] ) ) {
					$funding[] = $fund;
				}
			}
			if ( ! empty( $funding ) ) {
				$arg['disable-funding'] = implode( ',', $funding );
			}
			foreach ( $card_array as $card ) {
				if ( ! empty( $paypal_setting[ $card ] ) ) {
					$cards[] = $card;
				}
			}
			if ( ! empty( $cards ) && ! isset( $paypal_setting['credit'] ) ) {
				$arg['disable-card'] = implode( ',', $cards );
			}
			if ( 'enable' === $paypal_setting['debug_mode'] ) {
				$arg['debug'] = 'true';
			}
			$script = add_query_arg( $arg, $script );
		}

		return $script;
	}

	/**
	 * Return form wrappers & fields
	 *
	 * @since 1.0
	 * @return array|mixed
	 */
	public function get_wrappers() {
		if ( is_object( $this->model ) ) {
			return $this->model->get_fields_grouped();
		} else {
			return $this->message_not_found();
		}
	}

	/**
	 * Return form wrappers & fields
	 *
	 * @since 1.0
	 * @return array|mixed
	 */
	public function get_fields() {
		$fields   = array();
		$wrappers = $this->get_wrappers();

		// Fallback.
		if ( empty( $wrappers ) ) {
			return $fields;
		}

		foreach ( $wrappers as $key => $wrapper ) {

			if ( ! isset( $wrapper['fields'] ) ) {
				return array();
			}

			foreach ( $wrapper['fields'] as $k => $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Get submit field
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function get_submit_field() {
		$settings = $this->get_form_settings();
		if ( ! isset( $settings['submitData'] ) ) {
			$settings['submitData'] = array();
		}
		$defaults = array(
			'element_id' => 'submit',
			'type'       => 'submit',
			'conditions' => array(),
		);

		$submit_data = array_merge( $defaults, $settings['submitData'] );

		return $submit_data;
	}

	/**
	 * Get Pagination field
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function get_pagination_field() {
		$settings = $this->get_form_settings();

		if ( ! isset( $settings['paginationData'] ) ) {
			$settings['paginationData'] = array();
		}
		$defaults = array(
			'element_id' => 'pagination',
			'type'       => 'pagination',
			'conditions' => array(),
		);

		$submit_data = array_merge( $defaults, $settings['paginationData'] );

		return $submit_data;
	}

	/**
	 * Return before wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param $wrapper
	 *
	 * @return mixed
	 */
	public function render_wrapper_before( $wrapper ) {
		$class = 'forminator-row';

		if ( $this->is_only_hidden( $wrapper ) ) {
			$class .= ' forminator-hidden';

			if ( isset( $wrapper['fields'] ) && isset( $wrapper['fields'][0]['custom-class'] ) ) {
				$class .= ' ' . $wrapper['fields'][0]['custom-class'];
			}
		}

		$html = sprintf( '<div class="%1$s">', esc_attr( $class ) );

		return apply_filters( 'forminator_before_wrapper_markup', $html, $wrapper );
	}

	/**
	 * Return after wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param $wrapper
	 *
	 * @return mixed
	 */
	public function render_wrapper_after( $wrapper ) {
		$html = '</div>';

		return apply_filters( 'forminator_after_wrapper_markup', $html, $wrapper );
	}

	/**
	 * Extra form classes for ajax
	 *
	 * @since 1.0
	 */
	public function form_extra_classes() {
		$ajax_form = $this->is_ajax_submit();

		if ( $this->is_preview ) {
			$ajax_form = true;
		}

		$extra_class = $ajax_form ? 'forminator_ajax' : '';

		if ( isset( $this->lead_model->id ) ) {
			$extra_class .= ' forminator-leads-form';
		}

		return $extra_class;
	}

	/**
	 * Return true if we have only hidden field in the row
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function is_only_hidden( $wrapper ) {
		// We don't have any fields, abort.
		if ( ! isset( $wrapper['fields'] ) ) {
			return false;
		}

		// We have more than one field in the row, abort.
		if ( count( $wrapper['fields'] ) > 1 ) {
			return false;
		}

		// Check if the field type is hidden.
		if ( 'hidden' === $wrapper['fields'][0]['type'] || 'paypal' === $wrapper['fields'][0]['type'] ) {
			// Field type is hidden, return true.
			return true;
		}

		return false;
	}

	/**
	 * Return fields markup
	 *
	 * @since 1.0
	 *
	 * @param bool $render
	 *
	 * @return string|void
	 */
	public function render_fields( $render = true ) {
		$html             = '';
		$step             = 1;
		$pagination_field = array();

		$wrappers = apply_filters( 'forminator_cform_render_fields', $this->get_wrappers(), $this->model->id );

		$html .= $this->do_before_render_form_fields_for_addons();

		// Check if we have pagination field.
		if ( $this->has_pagination() ) {
			if ( ! empty( $wrappers ) ) {
				foreach ( $wrappers as $key => $wrapper ) {
					foreach ( $wrapper['fields'] as $fields ) {
						if ( $this->is_pagination( $fields ) ) {
							$pagination_field[] = $fields;
						}
					}
				}
			}
			$html .= $this->pagination_header();
			$html .= $this->pagination_start( $pagination_field );
			$html .= $this->pagination_content_start();
		}

		if ( ! empty( $wrappers ) ) {
			foreach ( $wrappers as $key => $wrapper ) {

				// a wrapper with no fields, continue to next wrapper.
				if ( ! isset( $wrapper['fields'] ) ) {
					continue;
				}

				$has_pagination = false;

				// Skip row markup if pagination field.
				if ( ! $this->is_pagination_row( $wrapper ) ) {
					// Render before wrapper markup.
					$html .= $this->render_wrapper_before( $wrapper );
				}

				foreach ( $wrapper['fields'] as $k => $field ) {

					if ( $this->is_pagination( $field ) ) {
						$has_pagination = true;
					}

					// Skip row markup if pagination field.
					if ( ! $this->is_pagination_row( $wrapper ) ) {
						$html .= $this->get_field( $field );
					}
				}

				// Skip row markup if pagination field.
				if ( ! $this->is_pagination_row( $wrapper ) ) {
					// Render after wrapper markup.
					$html .= $this->render_wrapper_after( $wrapper );
				}

				if ( $has_pagination ) {
					$html .= $this->pagination_content_end();
					if ( isset( $field ) ) {
						$html .= $this->pagination_step( $step, $field, $pagination_field );
					}
					$html .= $this->pagination_content_start();
					$step ++;
				}
			}
		}

		// Check if we have pagination field.
		if ( $this->has_pagination() ) {
			$html .= $this->pagination_content_end();
			$html .= $this->pagination_submit_button();
			$html .= $this->pagination_end();
		}

		$html .= $this->do_after_render_form_fields_for_addons();

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			/** @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_fields_markup', $html, $wrappers );
		}
	}

	/**
	 * Return if the row is pagination
	 *
	 * @since 1.0
	 *
	 * @param $wrapper
	 *
	 * @return bool
	 */
	public function is_pagination_row( $wrapper ) {
		$is_single = $this->is_single_field( $wrapper );

		if ( $is_single && isset( $wrapper['fields'][0]['type'] ) && 'page-break' === $wrapper['fields'][0]['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if only single field in the wrapper
	 *
	 * @since 1.0
	 *
	 * @param $wrapper
	 *
	 * @return bool
	 */
	public function is_single_field( $wrapper ) {
		if ( isset( $wrapper['fields'] ) && ( count( $wrapper['fields'] ) === 1 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return pagination header
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_header() {
		$type           = $this->get_pagination_type();
		$has_pagination = $this->has_pagination_header();

		if ( ! $has_pagination ) {
			return '';
		}

		if ( 'bar' === $type ) {
			$html = '<div class="forminator-pagination-progress" aria-hidden="true"></div>';
		} else {
			$html = '<div role="tablist" class="forminator-pagination-steps" aria-label="Pagination"></div>';
		}

		return apply_filters( 'forminator_pagination_header_markup', $html );
	}

	/**
	 * Return pagination start markup
	 *
	 * @param $element
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_start( $element = array() ) {

		$form_settings = $this->get_form_settings();
		$label         = __( 'Finish', 'forminator' );
		$element_id    = ! empty( $element ) ? $element[0]['element_id'] : '';

		if ( isset( $form_settings['paginationData']['last-steps'] ) ) {
			$label = $form_settings['paginationData']['last-steps'];
		}

		$html = sprintf(
			'<div tabindex="0" role="tabpanel" id="forminator-custom-form-%3$s--page-0" class="forminator-pagination forminator-pagination-start" aria-labelledby="forminator-custom-form-%3$s--page-0-label" data-step="0" data-label="%1$s" data-name="%2$s">',
			$label,
			$element_id,
			$form_settings['form_id']
		);

		return apply_filters( 'forminator_pagination_start_markup', $html, $label, $element_id );

	}


	/**
	 * Get Pagination Properties as array
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	public function get_pagination_properties() {

		$form_fields         = $this->get_fields();
		$pagination_settings = $this->get_pagination_field();
		$properties          = array(
			'has-pagination'           => $this->has_pagination(),
			'pagination-header-design' => 'show',
			'pagination-header'        => 'nav',
			'last-steps'               => __( 'Finish', 'forminator' ),
			'last-previous'            => __( 'Previous', 'forminator' ),
			'pagination-labels'        => 'default',
			'has-paypal'               => $this->has_paypal(),
		);

		foreach ( $properties as $property => $value ) {
			if ( isset( $pagination_settings[ $property ] ) ) {
				$new_value = $pagination_settings[ $property ];
				if ( is_bool( $value ) ) {
					// return boolean.
					$new_value = filter_var( $new_value, FILTER_VALIDATE_BOOLEAN );
				} elseif ( is_string( $new_value ) ) {
					// if empty string fallback to default.
					if ( empty( $new_value ) ) {
						$new_value = $value;
					}
				}
				$properties[ $property ] = $new_value;
			}
			foreach ( $form_fields as $form_field ) {
				if ( $this->is_pagination( $form_field ) ) {
					$element                             = $form_field['element_id'];
					$properties[ $element ]['prev-text'] = isset( $pagination_settings[ $element . '-previous' ] ) ? $pagination_settings[ $element . '-previous' ] : 'Previous';
					$properties[ $element ]['next-text'] = isset( $pagination_settings[ $element . '-next' ] ) ? $pagination_settings[ $element . '-next' ] : 'Next';
				}
				if ( $this->is_paypal( $form_field ) ) {
					$properties['paypal-id'] = $form_field['element_id'];
				}
			}
		}

		$form_id = $this->model->id;

		/**
		 * Filter pagination properties
		 *
		 * @since 1.1
		 *
		 * @param array $properties
		 * @param int $form_id Current Form ID.
		 */
		$properties = apply_filters( 'forminator_pagination_properties', $properties, $form_id );

		return $properties;

	}

	/**
	 * Get paypal Properties as array
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	public function get_paypal_properties() {
		global $wp;
		$form_fields = $this->get_fields();
		$paypal      = new Forminator_PayPal_Express();
		foreach ( $form_fields as $form_field ) {
			if ( $this->is_paypal( $form_field ) ) {
				foreach ( $form_field as $key => $field ) {
					$properties[ $key ] = $field;
				}
			}
		}
		$properties['live_id']      = $paypal->get_live_id();
		$properties['sandbox_id']   = $paypal->get_sandbox_id();
		$properties['redirect_url'] = home_url( $wp->request );

		$form_id               = $this->model->id;
		$properties['form_id'] = $form_id;

		/**
		 * Filter PayPal properties
		 *
		 * @since 1.1
		 *
		 * @param array $properties
		 * @param int $form_id Current Form ID.
		 */
		$properties = apply_filters( 'forminator_paypal_properties', $properties, $form_id );

		return $properties;

	}

	/**
	 * Return pagination content start markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_content_start() {
		$html = '<div class="forminator-pagination--content">';

		return apply_filters( 'forminator_pagination_content_start_markup', $html );
	}

	/**
	 * Return pagination content end markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_content_end() {
		$html = '</div>';

		return apply_filters( 'forminator_pagination_content_end_markup', $html );
	}

	/**
	 * Return submit field custom class
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function get_submit_custom_clas() {
		$settings = $this->get_form_settings();

		// Submit data is missing.
		if ( ! isset( $settings['submitData'] ) ) {
			return false;
		}

		if ( isset( $settings['submitData']['custom-class'] ) && ! empty( $settings['submitData']['custom-class'] ) ) {
			return $settings['submitData']['custom-class'];
		}

		return false;
	}

	/**
	 * Return pagination submit button markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_submit_button() {
		$button        = $this->get_submit_button_text();
		$custom_class  = $this->get_submit_custom_clas();
		$form_settings = $this->get_form_settings();

		$class = 'forminator-button forminator-pagination-submit';

		if ( $custom_class && ! empty( $custom_class ) ) {
			$class .= ' ' . $custom_class;
		}

		if ( $this->get_form_design() !== 'material' ) {

			$html = sprintf( '<button class="' . $class . '" style="display: none;" disabled>%s</button>', $button );
		} else {
			$html
				=
				sprintf(
					'<button class="' . $class
					. '" style="display: none;" disabled><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">%s</span></button>',
					$button
				);
		}

		$html .= $this->get_save_draft_button( $form_settings );

		return apply_filters( 'forminator_pagination_submit_markup', $html );
	}

	/**
	 * Return pagination end markup
	 *
	 * @since 1.0
	 * @return string
	 */
	public function pagination_end() {
		$html = '</div>';

		return apply_filters( 'forminator_pagination_end_markup', $html );
	}

	/**
	 * Return pagination start markup
	 *
	 * @since 1.0
	 *
	 * @param $step
	 * @param $field
	 * @param $pagination
	 *
	 * @return string
	 */
	public function pagination_step( $step, $field, $pagination ) {
		$form_settings       = $this->get_form_settings();
		$label               = sprintf( '%s %s', __( 'Page ', 'forminator' ), $step );
		$pagination_settings = $this->get_pagination_field();
		if ( isset( $pagination_settings[ $field['element_id'] . '-steps' ] ) ) {
			$label = $pagination_settings[ $field['element_id'] . '-steps' ];
		}
		$element_id = '';
		if ( ! empty( $pagination ) ) {
			for ( $i = $step; $i <= count( $pagination ); $i ++ ) {
				if ( isset( $pagination[ $i ]['element_id'] ) && ( $field['element_id'] !== $pagination[ $i ]['element_id'] ) ) {
					$element_id = $pagination[ $i ]['element_id'];
					break;
				}
			}
		}

		$html = sprintf(
			'</div><div tabindex="0" role="tabpanel" id="forminator-custom-form-%4$s--page-%1$s" class="forminator-pagination" aria-labelledby="forminator-custom-form-%4$s--page-%1$s-label" aria-hidden="true" data-step="%1$s" data-label="%2$s" data-name="%3$s" hidden>',
			$step,
			$label,
			$element_id,
			$form_settings['form_id']
		);

		return apply_filters( 'forminator_pagination_step_markup', $html, $step, $label, $element_id );
	}

	/**
	 * Return field markup
	 *
	 * @since 1.0
	 * @since 1.17.0 Add draft_value parameter
	 *
	 * @param $field
	 * @param $draft_value
	 *
	 * @return mixed
	 */
	public function get_field( $field ) {
		$html = '';

		do_action( 'forminator_before_field_render', $field );

		// Get field object.
		/** @var Forminator_Field $field_object */
		$field_object = Forminator_Core::get_field_object( $this->get_field_type( $field ) );

		// If bool, abort.
		if ( is_bool( $field_object ) || is_null( $field_object ) ) {
			return $html;
		}

		if ( $field_object->is_available( $field ) ) {
			if ( ! self::is_hidden( $field ) ) {
				// Render before field markup.
				$html .= $this->render_field_before( $field );
			}

			// Render field.
			$html .= $this->render_field( $field );

			if ( ! self::is_hidden( $field ) ) {
				// Render after field markup.
				$html .= $this->render_field_after( $field );
			}
		}

		do_action( 'forminator_after_field_render', $field );

		return $html;
	}

	/**
	 * Return field markup
	 *
	 * @since 1.0
	 * @since 1.17.0 Add draft_value parameter
	 *
	 * @param $field
	 * @param $draft_value
	 *
	 * @return mixed
	 */
	public function render_field( $field ) {
		$html            = '';
		$type            = $this->get_field_type( $field );
		$field_label     = $this->get_field_label( $field );
		$placeholder     = $this->get_placeholder( $field );
		$has_placeholder = $placeholder ? true : false;
		$draft_value 	 = isset( $this->draft_data[ $field['element_id'] ] ) ? $this->draft_data[ $field['element_id'] ] : null;

		// deprecated, label should be handled by field class it seld.
		// if ( ! self::is_hidden( $field ) && ! $this->has_label( $field ) ) {.
		//
		// if ( ! $this->is_multi_name( $field ) ) {.
		// $html .= $this->get_field_label_markup( $field_label, $is_required, $has_placeholder, $field );.
		// }
		//
		// If field labels are empty.
		// if ( ! $field_label ) {.
		// if ( $is_required ) {.
		// $html .= $this->get_field_label_markup( '', true, true, $field );.
		// }
		// }
		// }

		// Get field object.
		/** @var Forminator_Field $field_object */
		$field_object = Forminator_Core::get_field_object( $type );

		// Print field markup.
		$html .= $field_object->markup( $field, $this->model->settings, $draft_value );

		$this->inline_rules    .= $field_object->get_validation_rules();
		$this->inline_messages .= $field_object->get_validation_messages();

		// Print field description.
		// $html .= $this->get_description( $field );.

		return apply_filters( 'forminator_field_markup', $html, $field, $this );
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
		if ( ! isset( $field['element_id'] ) ) {
			return '';
		}

		return $field['element_id'];
	}

	/**
	 * Return field columns
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return string
	 */
	public function get_cols( $field ) {
		if ( ! isset( $field['cols'] ) ) {
			return '12';
		}

		return $field['cols'];
	}

	/**
	 * Return field type
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_field_type( $field ) {
		if ( ! isset( $field['type'] ) ) {
			return false;
		}

		return $field['type'];
	}

	/**
	 * Return placeholder
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_placeholder( $field ) {
		if ( ! isset( $field['placeholder'] ) ) {
			return '';
		}

		return $this->sanitize_output( $field['placeholder'] );
	}

	/**
	 * Return field label
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_field_label( $field ) {
		if ( ! isset( $field['field_label'] ) ) {
			return '';
		}

		return $this->sanitize_output( $field['field_label'] );
	}

	/**
	 * Return field label markup
	 *
	 * @since 1.0
	 *
	 * @param $label
	 * @param $required
	 * @param $placeholder
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_field_label_markup( $label, $required, $placeholder, $field ) {
		_deprecated_function( __METHOD__, '1.6' );
		// Skip markup if label missing.
		if ( empty( $label ) ) {
			return '';
		}

		$container_class = 'forminator-field--label';

		if ( $required ) {
			$asterisk = ' ' . forminator_get_required_icon();
		} else {
			$asterisk = '';
		}

		$html  = sprintf( '<div class="%s">', $container_class );
		$html .= sprintf( '<label class="forminator-label" id="%s">%s%s</label>', 'forminator-label-' . $field['element_id'], $label, $asterisk );
		$html .= sprintf( '</div>' );

		return apply_filters( 'forminator_field_get_field_label', $html, $label );
	}

	/**
	 * Return description markup
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_description( $field ) {
		_deprecated_function( __METHOD__, '1.6' );
		$type = $this->get_field_type( $field );
		/** @var Forminator_Field $field_object */
		$field_object              = Forminator_Core::get_field_object( $type );
		$has_phone_character_limit = ( ( isset( $field['phone_validation'] ) && $field['phone_validation'] )
									   && ( isset( $field['validation'] )
											&& 'character_limit' === $field['validation'] ) );

		if ( ( isset( $field['description'] ) && ! empty( $field['description'] ) ) || isset( $field['text_limit'] ) || $has_phone_character_limit ) {

			$html = sprintf( '<div class="forminator-description">' );

			if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
				$description = $this->sanitize_output( $field['description'] );
				if ( 'false' === $description ) {
					$description = '';
				}

				$html .= $description;
			}

			if ( ( isset( $field['text_limit'] ) || isset( $field['phone_limit'] ) ) && isset( $field['limit'] ) && $field_object->has_counter || $has_phone_character_limit ) {
				if ( ( isset( $field['text_limit'] ) && $field['text_limit'] ) || ( isset( $field['phone_limit'] ) && $field['phone_limit'] ) || $has_phone_character_limit ) {
					$limit = isset( $field['limit'] ) ? $field['limit'] : '';
					if ( empty( $limit ) && $has_phone_character_limit ) {
						$limit = 10;
					}
					$limit_type = isset( $field['limit_type'] ) ? $field['limit_type'] : '';
					$html      .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', $limit, $limit_type, $limit );
				}
			}

			$html .= sprintf( '</div>' );
		} else {
			$html = '';
		}

		return apply_filters( 'forminator_field_get_description', $html, $field );
	}

	/**
	 * Return field before markup
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function render_field_before( $field ) {
		$class = $this->get_classes( $field );
		$cols  = $this->get_cols( $field );
		$id    = $this->get_id( $field );

		$html = sprintf( '<div id="%s" class="forminator-col forminator-col-%s %s">', $id, $cols, $class );

		return apply_filters( 'forminator_before_field_markup', $html, $class );
	}

	/**
	 * Return field after markup
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function render_field_after( $field ) {
		$html = sprintf( '</div>' );

		return apply_filters( 'forminator_after_field_markup', $html, $field );
	}

	/**
	 * Return Form Settins
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_form_settings() {
		return $this->model->get_form_settings();
	}

	/**
	 * Return if hidden field
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public static function is_hidden( $field ) {
		// Array of hidden fields.
		$hidden = apply_filters( 'forminator_cform_hidden_fields', array( 'hidden' ) );

		if ( in_array( $field['type'], $hidden, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if name field
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function is_multi_name( $field ) {
		// Array of hidden fields.
		$hidden = apply_filters( 'forminator_cform_hidden_fields', array( 'name' ) );

		if ( ( in_array( $field['type'], $hidden, true ) ) && ( isset( $field['multiple_name'] ) && $field['multiple_name'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if field has label
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function has_label( $field ) {
		// Array of hidden fields.
		$without_label = apply_filters( 'forminator_cform_fields_without_label', array( '' ) );

		if ( in_array( $field['type'], $without_label, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return Form Design
	 *
	 * @since 1.0
	 * @return mixed|string
	 */
	public function get_form_design() {
		$form_settings = $this->get_form_settings();

		if ( ! isset( $form_settings['form-style'] ) ) {
			return 'default';
		}

		return $form_settings['form-style'];
	}

	/**
	 * Return fields style
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_fields_style() {
		$form_settings = $this->get_form_settings();

		if ( isset( $form_settings['fields-style'] ) ) {
			return $form_settings['fields-style'];
		}

		return 'open';
	}

	/**
	 * Check if honeypot protection is enabled
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_honeypot_enabled() {
		$form_settings = $this->get_form_settings();

		if ( ! isset( $form_settings['honeypot'] ) ) {
			return false;
		}

		return filter_var( $form_settings['honeypot'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if form has a captcha field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_captcha() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'captcha' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a recaptcha field
	 *
	 * @since 1.15.5
	 * @return bool
	 */
	public function is_recaptcha() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( "captcha" === $field["type"] && 'recaptcha' === $field["captcha_provider"] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a date field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_date() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'date' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a date field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_upload() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'upload' === $field['type'] || 'postdata' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a pagination field
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_pagination() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'page-break' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return if field is pagination
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_pagination( $field ) {
		if ( isset( $field['type'] ) && 'page-break' === $field['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return if field is paypal
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_paypal( $field ) {
		if ( isset( $field['type'] ) && 'paypal' === $field['type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return field classes
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return string
	 */
	public function get_classes( $field ) {

		$class = '';

		if ( isset( $field['custom-class'] ) && ! empty( $field['custom-class'] ) ) {
			$class .= ' ' . esc_html( $field['custom-class'] );
		}

		return $class;
	}

	/**
	 * Return fields conditions for JS
	 *
	 * @since 1.0
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_relations( $id ) {
		$relations = array();
		$fields    = $this->get_fields();

		// Add submit as field.
		$fields[] = $this->get_submit_field();

		// Fallback.
		if ( empty( $fields ) ) {
			return $relations;
		}

		foreach ( $fields as $field ) {
			if ( $this->is_conditional( $field ) ) {
				$field_conditions = isset( $field['conditions'] ) ? $field['conditions'] : array();

				foreach ( $field_conditions as $condition ) {
					if ( $id === $condition['element_id'] ) {
						$relations[] = $this->get_field_id( $field );
					}
				}
			}
		}

		return $relations;
	}

	/**
	 * Compare element_id with precision elements
	 *
	 * @since 1.13
	 *
	 * @param string $element_id
	 *
	 * @return bool
	 */
	public function compare_element_id_with_precision_elements( $element_id ) {
		return false !== strpos( $element_id, 'calculation-' )
		       || false !== strpos( $element_id, 'currency-' );
	}

	/**
	 * Change condition value with specified precision
	 *
	 * @since 1.13
	 *
	 * @param string $condition_value
	 * @param array  $field
	 *
	 * @return string
	 */
	public function change_condition_value_with_precision( $condition_value, $field ) {
		if ( '' === $condition_value ) {
			return $condition_value;
		}

		$precision = Forminator_Field::get_property( 'precision', $field, 2 );

		return sprintf( "%.{$precision}f", $condition_value );
	}

	/**
	 * Return fields conditions for JS
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_conditions() {
		$conditions = array();
		$relations  = array();
		$fields     = $this->get_fields();

		// Add submit as field.
		$fields[] = $this->get_submit_field();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$id               = $this->get_field_id( $field );
				$relations[ $id ] = $this->get_relations( $id );

				// Check if conditions are enabled.
				if ( $this->is_conditional( $field ) ) {
					$field_data       = array();
					$condition_action = isset( $field['condition_action'] ) ? $field['condition_action'] : 'show';
					$condition_rule   = isset( $field['condition_rule'] ) ? $field['condition_rule'] : 'all';
					$field_conditions = isset( $field['conditions'] ) ? $field['conditions'] : array();

					foreach ( $field_conditions as $condition ) {
						if ( $this->compare_element_id_with_precision_elements( $condition['element_id'] ) ) {
							foreach ( $fields as $key => $field_array ) {
								if ( $field_array['element_id'] === $condition['element_id'] ) {
									$condition['value'] = $this->change_condition_value_with_precision( $condition['value'], $field_array );
									break;
								}
							}
						}
						$new_condition = array(
							'field'    => $condition['element_id'],
							'operator' => $condition['rule'],
							'value'    => $condition['value'],
						);

						$field_data[] = $new_condition;
					}

					$conditions[ $id ] = array(
						'action'     => $condition_action,
						'rule'       => $condition_rule,
						'conditions' => $field_data,
					);
				}
			}
		}

		return array(
			'fields'    => $conditions,
			'relations' => $relations,
		);
	}

	/**
	 * Check field is conditional
	 *
	 * @since 1.0
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_conditional( $field ) {
		if ( isset( $field['conditions'] ) && ! empty( $field['conditions'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the form encryption type if there is an upload
	 *
	 * @since 1.0
	 * @return string
	 */
	public function form_enctype() {
		if ( $this->has_upload() ) {
			return 'enctype="multipart/form-data"';
		} else {
			return '';
		}
	}

	/**
	 * @since 1.0
	 * @return bool
	 */
	public function has_paypal() {
		$is_enabled = forminator_has_paypal_settings();
		$selling    = 0;
		$fields     = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'paypal' === $field['type'] ) {
					$selling ++;
				}
			}
		}

		return ( $is_enabled && $selling > 0 ) ? true : false;
	}

	/**
	 * Return button markup
	 *
	 * @since 1.6
	 * @return mixed
	 */
	public function get_button_markup() {

		$html  = '';
		$class = 'forminator-button forminator-button-submit';
		$form_settings = $this->get_form_settings();

		if ( empty( $this->lead_model->id ) || empty( $this->lead_model->settings['pagination'] )
				|| ! empty( $this->lead_model->settings )
				&& 'end' === $this->get_form_placement( $this->lead_model->settings ) ) {
			$button = $this->get_submit_button_text();
		} else {
			$class .= ' forminator-quiz-start';
			$button = $this->get_start_button_text( $this->lead_model->settings );
		}

		$custom_class = $this->get_submit_custom_clas();

		if ( $custom_class && ! empty( $custom_class ) ) {
			$class .= ' ' . $custom_class;
		}

		$html .= '<div class="forminator-row forminator-row-last">';

		$html .= '<div class="forminator-col">';

		$html .= '<div class="forminator-field">';

		$html .= sprintf( '<button class="%s">', $class );

		if ( 'material' === $this->get_form_design() ) {

			$html .= sprintf( '<span>%s</span>', $button );

			$html .= '<span aria-hidden="true"></span>';

		} else {

			$html .= $button;

		}

		$html .= '</button>';

		$html .= $this->get_save_draft_button( $form_settings );

		$html .= '</div>';

		$html .= '</div>';

		$html .= '</div>';

		return apply_filters( 'forminator_render_button_markup', $html, $button );
	}

	/**
	 * PayPal button markup
	 *
	 * @since 1.0
	 *
	 * @param $form_id
	 *
	 * @return mixed
	 */
	public function get_paypal_button_markup( $form_id ) {

		$html        = '';
		$custom_form = Forminator_Base_Form_Model::get_model( $form_id );
		if ( is_object( $custom_form ) ) {
			$fields = $custom_form->get_fields();
			foreach ( $fields as $field ) {

				$field_array = $field->to_formatted_array();
				$field_type  = $field_array['type'];

				if ( 'paypal' === $field_type ) {

					$id = Forminator_Field::get_property( 'element_id', $field_array );

					$html  = '<div class="forminator-row forminator-paypal-row">';
					$html .= '<div class="forminator-col forminator-col-12">';
					$html .= '<div class="forminator-field">';
					$html .= '<div id="paypal-button-container-' . $form_id . '" class="' . $id . '-payment forminator-button-paypal">';
					$html .= '</div>';
					$html .= $this->get_save_draft_button( $this->get_form_settings() );
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

				}
			}
		}

		return apply_filters( 'forminator_render_button_markup', $html );
	}

	/**
	 * Return form submit button markup
	 *
	 * @since 1.0
	 *
	 * @param        $form_id
	 * @param bool    $render
	 *
	 * @return mixed|void
	 */
	public function get_submit( $form_id, $render = true, $render_id = 0 ) {
		$html       = '';
		$nonce      = $this->nonce_field( 'forminator_submit_form', 'forminator_nonce' );
		$post_id    = $this->get_post_id();
		$has_paypal = $this->has_paypal();
		$form_type  = isset( $this->model->settings['form-type'] ) ? $this->model->settings['form-type'] : '';

		if ( $has_paypal ) {
			if ( ! ( self::$paypal instanceof Forminator_Paypal_Express ) ) {
				self::$paypal = new Forminator_Paypal_Express();
			}
			self::$paypal_forms[] = $form_id;
		}

		// If we have pagination skip button markup.
		if ( ! $this->has_pagination() ) {
			if ( $has_paypal ) {
				$html .= '<input type="hidden" name="payment_gateway_total" value="" />';
				$html .= $this->get_paypal_button_markup( $form_id );
			}
			$html .= $this->get_button_markup();
		}

		$html .= $nonce;
		$html .= sprintf( '<input type="hidden" name="form_id" value="%s">', $form_id );
		$html .= sprintf( '<input type="hidden" name="page_id" value="%s">', $post_id );
		$html .= sprintf( '<input type="hidden" name="form_type" value="%s">', $form_type );
		$html .= sprintf( '<input type="hidden" name="current_url" value="%s">', forminator_get_current_url() );
		$html .= sprintf( '<input type="hidden" name="render_id" value="%s">', $render_id );

		if ( $this->has_multiupload() ) {
			$html .= sprintf( '<input type="hidden" name="forminator-multifile-hidden" class="forminator-multifile-hidden">' );
		}

		if ( $this->is_login_form() ) {
			$redirect_url = ! empty( $this->model->settings['redirect-url'] ) ? $this->model->settings['redirect-url'] : admin_url();
			$redirect_url = forminator_replace_variables( $redirect_url, $form_id );
			$html        .= sprintf( '<input type="hidden" name="redirect_to" value="%s">', $redirect_url );
		}

		if ( isset( $this->lead_model->id ) ) {
			$html .= sprintf( '<input type="hidden" name="lead_quiz" value="%s">', $this->lead_model->id );
		}

		if ( $this->is_preview ) {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_preview_form_custom-forms' );
		} else {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_form_custom-forms' );
		}

		if ( isset( $this->model->settings['use_save_and_continue'] ) && filter_var( $this->model->settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN ) ) {
			$html .= '<input type="hidden" name="save_draft" value="false">';

			if ( ! empty( $this->draft_id ) ) {
				$html .= sprintf( '<input type="hidden" name="previous_draft_id" value="%s">', esc_attr( $this->draft_id ) );
			}
		}

		$html .= $this->do_after_render_form_for_addons();

		if ( $render ) {
			$html = apply_filters( 'forminator_render_form_submit_markup', $html, $form_id, $post_id, $nonce );
			echo wp_kses_post( $html );
		} else {
			/** @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_form_submit_markup', $html, $form_id, $post_id, $nonce );
		}
	}

	/**
	 * Submit button text
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_submit_button_text() {
		if ( $this->has_custom_submit_text() ) {
			return $this->get_custom_submit_text();
		} else {
			parent::get_submit_button_text();
		}
	}

	/**
	 * Return custom submit button text
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_custom_submit_text() {
		$settings = $this->get_form_settings();

		return $this->sanitize_output( $settings['submitData']['custom-submit-text'] );
	}

	/**
	 * Return if custom submit button text
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_custom_submit_text() {
		$settings = $this->get_form_settings();

		if ( isset( $settings['submitData']['custom-submit-text'] ) && ! empty( $settings['submitData']['custom-submit-text'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render honeypot field
	 *
	 * @since 1.0
	 *
	 * @param string $html - the button html.
	 * @param int    $form_id - the current form id.
	 * @param int    $post_id - the current post id.
	 * @param string $nonce - the nonce field.
	 *
	 * @return string $html
	 */
	public function render_honeypot_field(
		$html,
		$form_id,
		/** @noinspection PhpUnusedParameterInspection */
		$post_id,
		/** @noinspection PhpUnusedParameterInspection */
		$nonce
	) {
		if ( (int) $form_id === (int) $this->model->id && $this->is_honeypot_enabled() ) {
			$fields       = $this->model->get_real_fields();
			$total_fields = count( $fields ) + 1;
			// Most bots wont bother with hidden fields, so set to text and hide it.
			$html .= sprintf( '<label for="%1$s" class="forminator-hidden" aria-hidden="true">%2$s <input id="%1$s" type="text" name="%1$s" value="" autocomplete="off"></label>', "input_$total_fields", __( 'Please do not fill in this field.', 'forminator' ) );
		}

		return $html;
	}

	/**
	 * Get CSS prefix
	 *
	 * @param string $prefix Default prefix.
	 * @param array  $properties CSS properties.
	 * @return string
	 */
	protected static function get_css_prefix( $prefix, $properties, $slug ) {
		if ( 'none' !== $properties['form-style'] ) {
			$prefix .= '.forminator-design--' . $properties['form-style'] . ' ';
		}
		return $prefix;
	}

	/**
	 * Get PayPal field properties
	 *
	 * @since 1.7.1
	 *
	 * @return array
	 */
	public function get_pp_field_properties() {
		$fields = $this->get_fields();
		$props  = array();

		foreach ( $fields as $field ) {

			if ( 'paypal' === $field['type'] ) {

				if ( isset( $field['width'] ) ) {
					$props['paypal-width'] = $field['width'];
				}

				if ( isset( $field['height'] ) ) {
					$props['paypal-height'] = $field['height'];
				}

				if ( isset( $field['layout'] ) ) {
					$props['paypal-layout'] = $field['layout'];
				}

				if ( isset( $field['tagline'] ) ) {
					$props['paypal-tagline'] = $field['tagline'];
				}
			}
		}

		return $props;
	}

	/**
	 * Return if form pagination has header
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_pagination_header() {
		$settings  = $this->get_pagination_field();
		$is_active = 'show';

		if ( isset( $settings['pagination-header-design'] ) ) {
			$is_active = $settings['pagination-header-design'];
		}

		if ( 'show' === $is_active && ( 'nav' === $this->get_pagination_type() || 'bar' === $this->get_pagination_type() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get pagination type
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_pagination_type() {
		$settings = $this->get_pagination_field();
		if ( ! isset( $settings['pagination-header'] ) ) {
			return 'nav';
		}
		return $settings['pagination-header'];
	}

	/**
	 * Prints Javascript required for each form with PayPal
	 *
	 * @since 1.0
	 */
	public function print_paypal_scripts() {
		foreach ( self::$paypal_forms as $paypal_form_id ) {
			self::$paypal->render_buttons_script( $paypal_form_id );
		}
	}

	/**
	 * Defines translatable strings to pass to datepicker
	 * Add other strings if required
	 *
	 * @since 1.0.5
	 */
	public function get_strings_for_calendar() {
		$calendar['days']   = array(
			esc_html__( 'Su', 'forminator' ),
			esc_html__( 'Mo', 'forminator' ),
			esc_html__( 'Tu', 'forminator' ),
			esc_html__( 'We', 'forminator' ),
			esc_html__( 'Th', 'forminator' ),
			esc_html__( 'Fr', 'forminator' ),
			esc_html__( 'Sa', 'forminator' ),
		);
		$calendar['months'] = array(
			esc_html__( 'Jan', 'forminator' ),
			esc_html__( 'Feb', 'forminator' ),
			esc_html__( 'Mar', 'forminator' ),
			esc_html__( 'Apr', 'forminator' ),
			esc_html__( 'May', 'forminator' ),
			esc_html__( 'Jun', 'forminator' ),
			esc_html__( 'Jul', 'forminator' ),
			esc_html__( 'Aug', 'forminator' ),
			esc_html__( 'Sep', 'forminator' ),
			esc_html__( 'Oct', 'forminator' ),
			esc_html__( 'Nov', 'forminator' ),
			esc_html__( 'Dec', 'forminator' ),
		);

		return json_encode( $calendar );
	}

	/**
	 * Return if form use google font
	 *
	 * @since 1.0
	 * @since 1.2 Deprecate function
	 * @return bool
	 */
	public function has_google_font() {

		/**
		 * Deprecate this function, since `use-fonts-settings` and `font-family` no longer valid on 1.2
		 * Font / typography settings changed to different sections
		 * such as `cform-label-font-family`, `cform-title-font-family` etc
		 *
		 * @since 1.2
		 */
		_deprecated_function( 'has_google_font', '1.2', 'get_google_fonts' );

		$settings = $this->get_form_settings();

		// Check if custom font enabled.
		if ( ! isset( $settings['use-fonts-settings'] ) || empty( $settings['use-fonts-settings'] ) ) {
			return false;
		}

		// Check if custom font.
		if ( ! isset( $settings['font-family'] ) || empty( $settings['font-family'] ) || 'custom' === $settings['font-family'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Return google font
	 *
	 * @since 1.0
	 * @since 1.2 Deprecated Function
	 * @return string
	 */
	public function get_google_font() {

		/**
		 * Deprecate this function, since `use-fonts-settings` and `font-family` no longer valid on 1.2
		 * Font / typography settings changed to different sections
		 * such as `cform-label-font-family`, `cform-title-font-family` etc
		 *
		 * @since 1.2
		 */
		_deprecated_function( 'get_google_font', '1.2', 'get_google_fonts' );

		$settings = $this->get_form_settings();

		return $settings['font-family'];
	}

	/**
	 * Return if form use inline validation
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_inline_validation() {
		$settings = $this->get_form_settings();

		if ( isset( $settings['validation-inline'] ) && $settings['validation-inline'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Render Front Script
	 *
	 * @since 1.0
	 * @since 1.1 add pagination properties on `window`
	 */
	public function forminator_render_front_scripts() {
		?>
        <script type="text/javascript">
            jQuery(function () {
                window.Forminator_Cform_Paginations = window.Forminator_Cform_Paginations || [];
				<?php
				if ( ! empty( $this->forms_properties ) ) {
				foreach ( $this->forms_properties as $form_properties ) {
				$options           = $this->get_front_init_options( $form_properties );
				$pagination_config = $options['pagination_config'];
				unset( $options['pagination_config'] );
				?>
                window.Forminator_Cform_Paginations[<?php echo esc_attr( $form_properties['id'] ); ?>] =
				<?php echo wp_json_encode( $pagination_config ); ?>;

                var runForminatorFront = function () {
                    jQuery('#forminator-module-<?php echo esc_attr( $form_properties['id'] ); ?>[data-forminator-render="<?php echo esc_attr( $form_properties['render_id'] ); ?>"]')
                        .forminatorFront(<?php echo wp_json_encode( $options ); ?>);
                }

				runForminatorFront();

				if (window.elementorFrontend) {
					if (typeof elementorFrontend.hooks !== "undefined") {
						elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
							runForminatorFront();
						});
					}
				}

						<?php
					}
				}
				?>
				if (typeof ForminatorValidationErrors !== 'undefined') {
					var forminatorFrontSubmit = jQuery(ForminatorValidationErrors.selector).data('forminatorFrontSubmit');
					if (typeof forminatorFrontSubmit !== 'undefined') {
						forminatorFrontSubmit.show_messages(ForminatorValidationErrors.errors);
					}
				}
				if (typeof ForminatorFormHider !== 'undefined') {
					var forminatorFront = jQuery(ForminatorFormHider.selector).data('forminatorFront');
					if (typeof forminatorFront !== 'undefined') {
						jQuery(forminatorFront.forminator_selector).find('.forminator-row').hide();
						jQuery(forminatorFront.forminator_selector).find('.forminator-pagination-steps').hide();
						jQuery(forminatorFront.forminator_selector).find('.forminator-pagination-footer').hide();
					}
				}
				if (typeof ForminatorFormNewTabRedirect !== 'undefined') {
					var forminatorFront = ForminatorFormNewTabRedirect.url;
					if (typeof forminatorFront !== 'undefined') {
						window.open(ForminatorFormNewTabRedirect.url, '_blank');
					}
				}
			});
		</script>
		<?php

	}

	/**
	 * Get Output of addons after_render_form
	 *
	 * @see   Forminator_Addon_Zapier_Form_Hooks::on_after_render_form()
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_after_render_form_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_form_hooks( $this->model->id );
				if ( $form_hooks instanceof Forminator_Addon_Form_Hooks_Abstract ) {
					$form_hooks->on_after_render_form();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_after_render_form', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get Output of addons before render form fields
	 *
	 * @see   Forminator_Addon_Zapier_Form_Hooks::on_before_render_form_fields()
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_before_render_form_fields_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_form_hooks( $this->model->id );
				if ( $form_hooks instanceof Forminator_Addon_Form_Hooks_Abstract ) {
					$form_hooks->on_before_render_form_fields();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_before_render_form_fields', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;

	}

	/**
	 * Get Output of addons after render form fields
	 *
	 * @see   Forminator_Addon_Zapier_Form_Hooks::on_after_render_form_fields()
	 *
	 * @since 1.1
	 * @return string
	 */
	public function do_after_render_form_fields_for_addons() {
		// find is_form_connected.
		$model            = $this->model;
		$connected_addons = forminator_get_addons_instance_connected_with_module( $model->id, $model::$module_slug );

		ob_start();
		foreach ( $connected_addons as $connected_addon ) {
			try {
				$form_hooks = $connected_addon->get_addon_form_hooks( $this->model->id );
				if ( $form_hooks instanceof Forminator_Addon_Form_Hooks_Abstract ) {
					$form_hooks->on_after_render_form_fields();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_after_render_form_fields', $e->getMessage() );
			}
		}
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Get Google Fonts setup on a form
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_google_fonts() {
		$fonts    = array();
		$settings = $this->get_form_settings();

		$font_settings_enabled = isset( $settings['form-font-family'] ) ? $settings['form-font-family'] : false;
		$font_settings_enabled = ( 'custom' === $font_settings_enabled ) ? true : false;

		// on clean design, disable google fonts.
		if ( 'none' !== $this->get_form_design() && $font_settings_enabled ) {
			$configs = array(
				'label',
				'title',
				'subtitle',
				'input',
				'radio',
				'select',
				'dropdown',
				'calendar',
				'multiselect',
				'timeline',
				'button',
				'upload-single-button',
				'upload-single-text',
				'upload-multiple-panel',
				'upload-multiple-file-name',
				'upload-multiple-file-size',
			);

			foreach ( $configs as $font_setting_key ) {
				$font_family_settings_name = 'cform-' . $font_setting_key . '-font-family';

				// Dont add cform- prefix if setting is related to upload button.
				if (
					'upload-single-button' === $font_setting_key ||
					'upload-single-text' === $font_setting_key ||
					'upload-multiple-panel' === $font_setting_key ||
					'upload-multiple-file-name' === $font_setting_key ||
					'upload-multiple-file-size' === $font_setting_key
				) {
					$font_family_settings_name = $font_setting_key . '-font-family';
				}

				$font_family_name = '';
				// check if font family selected.
				if ( isset( $settings[ $font_family_settings_name ] ) && ! empty( $settings[ $font_family_settings_name ] ) ) {
					$font_family_name = $settings[ $font_family_settings_name ];
				}

				// skip not selected / `custom` is selected.
				if ( empty( $font_family_name ) || 'custom' === $font_family_name ) {
					$fonts[ $font_family_settings_name ] = false;
					continue;
				}

				$fonts[ $font_family_settings_name ] = $font_family_name;

			}
		}

		$form_id = $this->model->id;

		/**
		 * Filter google fonts to be loaded for a form
		 *
		 * @since 1.2
		 *
		 * @param array $fonts
		 * @param int $form_id
		 * @param array $settings form settings.
		 */
		$fonts = apply_filters( 'forminator_custom_form_google_fonts', $fonts, $form_id, $settings );

		return $fonts;

	}

	/**
	 * Check if field with type exist on a form, and check if its setting match
	 *
	 * @since 1.2
	 *
	 * @param             $field_type
	 * @param string|null $setting_name
	 * @param string|null $setting_value
	 *
	 * @return bool
	 */
	public function has_field_type_with_setting_value( $field_type, $setting_name = null, $setting_value = null ) {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( $field_type === $field['type'] ) {
					if ( is_null( $setting_name ) ) {
						return true;
					} elseif ( isset( $field[ $setting_name ] ) ) {
						$field_settings_value = $field[ $setting_name ];
						if ( is_bool( $field_settings_value ) ) {
							// cast to bool.
							$field_settings_value = filter_var( $field[ $setting_name ], FILTER_VALIDATE_BOOLEAN );
						}
						if ( $field_settings_value === $field[ $setting_name ] ) {
							return true;
						}
					} else {
						// for backward compatibility if select type doesn't set.
						if ( 'select' === $field_type && 'value_type' === $setting_name && 'single' === $setting_value ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Find last captcha
	 *
	 * @since 1.6
	 * @return array|bool
	 */
	public function find_first_captcha() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'captcha' === $field['type'] ) {
					return $field;
				}
			}
		}

		return false;
	}

	/**
	 * Get forminatorFront js init options to be passed
	 *
	 * @since 1.6.1
	 *
	 * @param $form_properties
	 *
	 * @return array
	 */
	public function get_front_init_options( $form_properties ) {

		if ( empty( $form_properties ) ) {
			return array();
		}

		$options = array(
			'form_type'           => $this->get_form_type(),
			'inline_validation'   => filter_var( $form_properties['inline_validation'], FILTER_VALIDATE_BOOLEAN ),
			'print_value'         => ! empty( $form_properties['settings']['print_value'] )
					? filter_var( $form_properties['settings']['print_value'], FILTER_VALIDATE_BOOLEAN ) : false,
			'rules'               => $form_properties['validation_rules'],
			// this is string, todo: refactor this to array (ALL FIELDS will be affected) to avoid client JSON.parse.
			'messages'            => $form_properties['validation_messages'],
			// this is string, todo: refactor this to array (ALL FIELDS will be affected)  to avoid client JSON.parse.
			'conditions'          => $form_properties['conditions'],
			'calendar'            => $this->get_strings_for_calendar(),
			// this is string, todo: refactor this to array to (ALL FIELDS will be affected)  avoid client JSON.parse.
			'pagination_config'   => $form_properties['pagination'],
			'paypal_config'       => $form_properties['paypal_payment'],
			'forminator_fields'   => Forminator_Core::get_field_types(),
			'general_messages'    => array(
				'calculation_error'            => Forminator_Calculation::default_error_message(),
				'payment_require_ssl_error'    => apply_filters(
					'forminator_payment_require_ssl_error_message',
					__( 'SSL required to submit this form, please check your URL.', 'forminator' )
				),
				'payment_require_amount_error' => __( 'PayPal amount must be greater than 0.', 'forminator' ),
				'form_has_error'               => __( 'Please correct the errors before submission.', 'forminator' ),
			),
			'payment_require_ssl' => $this->model->is_payment_require_ssl(),
			'has_loader'          => $this->form_has_loader( $form_properties ),
			'loader_label'        => $this->get_loader_label( $form_properties ),
			'calcs_memoize_time'  => $this->get_memoize_time(),
			'is_reset_enabled'    => $this->is_reset_enabled(),
			'has_stripe'          => $this->has_stripe(),
			'has_paypal'          => $this->has_paypal(),
			'submit_button_class' => $form_properties['submit_button_class'],
		);

		if ( ! empty( $this->lead_model ) && $this->has_lead( $this->lead_model->settings ) ) {
			$options['hasLeads']       = $this->has_lead( $this->lead_model->settings );
			$options['form_placement'] = $this->get_form_placement( $this->lead_model->settings );
			$options['leads_id']       = $this->get_leads_id( $this->lead_model->settings );
			$options['quiz_id']        = $this->lead_model->id;
		}

		return $options;
	}

	/**
	 * Return calculations time in ms
	 *
	 * @since 1.11
	 *
	 * @return mixed
	 */
	public function get_memoize_time() {
		$default = 300; // Memoize time in ms.

		$time = apply_filters( 'forminator_calculation_memoize_time', $default );

		return $time;
	}

	/**
	 * Return if form reset after submit is enabled
	 *
	 * @since 1.12
	 *
	 * @return mixed
	 */
	public function is_reset_enabled() {
		$default = true; // Memoize time in ms.

		$value = apply_filters( 'forminator_is_form_reset_enabled', $default );

		return $value;
	}

	/**
	 * Return if form has submission loader enabled
	 *
	 * @param $properties
	 *
	 * @since 1.7.1
	 *
	 * @return bool
	 */
	public function form_has_loader( $properties ) {
		if ( isset( $properties['settings']['submission-indicator'] ) && 'show' === $properties['settings']['submission-indicator'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Return loader label
	 *
	 * @param $properties
	 *
	 * @since 1.7.1
	 *
	 * @return mixed
	 */
	public function get_loader_label( $properties ) {
		if ( isset( $properties['settings']['indicator-label'] ) ) {
			return $properties['settings']['indicator-label'];
		}

		return __( 'Submitting...', 'forminator' );
	}

	/**
	 * Set options to Model object.
	 *
	 * @param object $form_model Model.
	 * @param array  $data Data.
	 * @return object
	 */
	protected function set_form_model_data( $form_model, $data ) {
		$fields = array();
		$title  = '';

		// Build the fields.
		if ( isset( $data ) ) {
			$fields = forminator_sanitize_field( $data['wrappers'] );
			unset( $data['wrappers'] );

			$title = ! empty( $data['settings']['formName'] ) ? sanitize_text_field( $data['settings']['formName'] ) : $title;
		}

		foreach ( $fields as $row ) {
			foreach ( $row['fields'] as $f ) {
				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['element_id'];
				$field->import( $f );
				$form_model->add_field( $field );
			}
		}

		// Sanitize custom css.
		if ( isset( $data['settings']['custom_css'] ) ) {
			$form_model->settings['custom_css'] = sanitize_textarea_field( $data['settings']['custom_css'] );
		}

		// Sanitize thank you message.
		if ( isset( $data['settings']['thankyou-message'] ) ) {
			$form_model->settings['thankyou-message'] = $data['settings']['thankyou-message'];
		}

		// Sanitize user email message.
		if ( isset( $data['settings']['user-email-editor'] ) ) {
			$form_model->settings['user-email-editor'] = $data['settings']['user-email-editor'];
		}

		$form_model->settings['formName'] = $title;

		return $form_model;
	}

	/**
	 * Html markup of form
	 *
	 * @since 1.6.1
	 *
	 * @param bool $hide
	 * @param bool $is_preview
	 * @param int $render_id
	 *
	 * @return false|string
	 */
	public function get_html( $hide = true, $is_preview = false, $render_id = 0 ) {
		$form_settings = $this->model->settings;
		$form_type     = isset( $form_settings['form-type'] ) ? $form_settings['form-type'] : '';
		// Hide registration or login form for logged-in users if enabled.
		$hide_option = 'hide-' . $form_type . '-form';
		if ( ! $is_preview
		     && in_array( $form_type, array( 'login', 'registration' ), true ) && is_user_logged_in()
		     && isset( $form_settings[ $hide_option ] ) && '1' === $form_settings[ $hide_option ] ) {

			$hidden_message_option = 'hidden-' . $form_type . '-form-message';
			$html                  = isset( $form_settings[ $hidden_message_option ] )
				? $form_settings[ $hidden_message_option ]
				: __( 'User is logged in.', 'forminator' );

			return $html;
		}
		ob_start();
		if ( $this->model->form_is_visible( $is_preview ) ) {
			add_filter( 'forminator_render_form_submit_markup', array( $this, 'render_honeypot_field' ), 10, 4 );
			// Render form.
			$this->render( $this->model->id, $hide, $is_preview, $render_id );

			// setup properties for later usage.
			$this->set_forms_properties( $render_id );
		} else {
			$form_settings = $this->get_form_settings();
			?>
            <div class="forminator-custom-form">
				<?php
				if ( isset( $form_settings['expire_message'] ) && '' !== $form_settings['expire_message'] ) {
					$message = $form_settings['expire_message'];
					?>
                    <label class="forminator-label--info"><span><?php echo esc_html( $message ); ?></span></label>
				<?php } ?>
            </div>
			<?php
		}

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Set module properties
	 */
	protected function set_forms_properties( $render_id = null ) {
		$submit_field = $this->get_submit_field();

		if ( is_null( $render_id ) ) {
			if ( empty( self::$render_ids ) || ! isset( self::$render_ids[ $this->model->id ] ) ) {
				$this->generate_render_id( $this->model->id );
			}
			$render_id = self::$render_ids[ $this->model->id ];
		}
        $submit_custom_data = '';
        if ( isset( $submit_field['custom-class'] ) ) {
	        $submit_custom_data = $submit_field['custom-class'];
        }
		$this->forms_properties[] = array(
			'id'                  => $this->model->id,
			'render_id'           => $render_id,
			'inline_validation'   => $this->has_inline_validation() ? 'true' : 'false',
			'conditions'          => $this->get_conditions(),
			'validation_rules'    => $this->inline_rules,
			'validation_messages' => $this->inline_messages,
			'settings'            => $this->get_form_settings(),
			'pagination'          => $this->get_pagination_properties(),
			'paypal_payment'      => $this->get_paypal_properties(),
			'fonts_settings'      => $this->get_google_fonts(),
			'submit_button_class' => $submit_custom_data,
		);
	}

	/**
	 * Check if form has a phone field
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function has_phone() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'phone' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a postdata field
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function has_postdata() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'postdata' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a stripe field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_stripe() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'stripe' === $field['type'] ) {
					$stripe = new Forminator_Gateway_Stripe();
					return $stripe->is_ready();
				}
			}
		}

		return false;
	}

	/**
	 * Check if form has a editor field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_editor() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$editor_type = Forminator_Field::get_property( 'editor-type', $field, false, 'bool' );
				if ( 'textarea' === $field['type'] && true === $editor_type ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if form given field type
	 *
	 * @since 1.14
	 * @return bool
	 */
	public function has_field_type( $type ) {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( $type === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check login form
	 *
	 * @return bool
	 */
	public function is_login_form() {
		$settings = $this->model->settings;

		if ( isset( $settings['form-type'] ) && 'login' === $settings['form-type'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Render a message if form is hidden
	 *
	 * @since 1.11
	 *
	 * @param string $hidden_form_message
	 *
	 * @return string
	 */
	public function render_hidden_form_message( $hidden_form_message ) {
		return apply_filters( 'forminator_render_hidden_form_message', $hidden_form_message );
	}

	/**
	 * Check if Custom form has upload field
	 *
	 * @since 1.7
	 * @return bool
	 */
	public function has_multiupload() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['type'] ) && 'upload' === $field['type'] &&
				     isset( $field['file-type'] ) && 'multiple' === $field['file-type']
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get lead skip text
	 *
	 * @param array $form_settings
	 *
	 * @return bool
	 */
	public function get_skip_text( $form_settings ) {
		$skip_text = isset( $form_settings['skip-text'] ) ? $form_settings['skip-text'] : __( 'Skip and continue', 'forminator' );

		return $skip_text;
	}

	/**
	 * Render skip form content
	 *
	 * @return string
	 */
	public function render_skip_form_content() {
		$html          = '';
		$lead_settings = isset( $this->lead_model->settings ) ? $this->lead_model->settings : array();
		if ( ! empty( $lead_settings ) && $this->has_lead( $lead_settings ) && $this->has_skip_form( $lead_settings ) ) {
			$html .= '<div class="forminator-quiz--skip forminator-lead-form-skip">';
			$html .= sprintf( '<button>%s</button>', $this->get_skip_text( $lead_settings ) );
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Check if form has a formatting field
	 *
	 * @since 1.15.1
	 * @return bool
	 */
	public function has_formatting() {
		$fields = $this->get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'number' === $field['type'] || 'currency' === $field['type'] || 'calculation' === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get 2FA provider
	 *
	 * @return array[]
	 */
	public function get_2FA_poviders() {
		$defender_data    = defender_backward_compatibility();
		$two_fa_component = new $defender_data['two_fa_component']();
		$providers = $two_fa_component->get_providers();

		return $providers;
	}

	/**
	 * Get Save draft button
	 *
	 * @return string
	 */
	public function get_save_draft_button( $form_settings ) {
		if (
			! isset( $form_settings['use_save_and_continue'] ) ||
			! filter_var( $form_settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN )
		) {
			return;
		}

		$draft_permissions = isset( $form_settings['sc_permission'] ) ? $form_settings['sc_permission'] : 'public';
		if ( 'registered' === $draft_permissions && ! is_user_logged_in() ) {
			return;
		}

		$button = sprintf(
						'<a href="#" class="forminator-save-draft-link disabled" title="%s" formnovalidate>%s</a>',
						esc_attr__(
							'Fill in form fields before saving it as a draft',
							'forminator'
						),
						esc_html__(
							isset( $form_settings['sc_link_text'] ) && ! empty( $form_settings['sc_link_text'] ) ? $form_settings['sc_link_text'] : 'Save as Draft',
							'forminator'
						)
					);

		return $button;
	}
}