<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Captcha
 *
 * @since 1.0
 */
class Forminator_Captcha extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $slug = 'captcha';

	/**
	 * @var string
	 */
	public $type = 'captcha';

	/**
	 * @var int
	 */
	public $position = 16;

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
	public $hide_advanced = 'true';

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-recaptcha';

	/**
	 * Forminator_Captcha constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Captcha', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {

		return array(
			'captcha_provider'		  => 'recaptcha',
			'captcha_type'            => 'v2_checkbox',
			'hcaptcha_type'           => 'hc_checkbox',
			'score_threshold'         => '0.5',
			'captcha_badge'           => 'bottomright',
			'hc_invisible_notice'	  => sprintf(
											__(
												'This site is protected by hCaptcha and its %1$sPrivacy Policy%3$s and %2$sTerms of Service%3$s apply.',
												'forminator'
											),
											'<a href="https://hcaptcha.com/privacy">',
											'<a href="https://hcaptcha.com/terms">',
											'</a>'
										),
			'recaptcha_error_message' => esc_html__( 'reCAPTCHA verification failed. Please try again.', 'forminator' ),
			'hcaptcha_error_message'  => esc_html__( 'hCaptcha verification failed. Please try again.', 'forminator' ),
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
		//Unsupported Autofill
		$autofill_settings = array();

		return $autofill_settings;
	}

	public function is_invisible_recaptcha( $field ) {
		// backward
		$is_invisible = self::get_property( 'invisible_captcha', $field );
		$is_invisible = filter_var( $is_invisible, FILTER_VALIDATE_BOOLEAN );
		if ( ! $is_invisible ) {
			$type = self::get_property( 'captcha_type', $field, '' );
			if ( 'invisible' === $type || 'v3_recaptcha' === $type || 'v2_invisible' === $type ) {
				$is_invisible = true;
			}
		}

		return $is_invisible;
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
	public function markup( $field, $settings = array() ) {

		$captcha_badge	 = '';
		$hcaptcha_notice = '';
		$provider		 = self::get_property( 'captcha_provider', $field, 'recaptcha' );

		if ( 'recaptcha' === $provider ) {
			$captcha_type  = self::get_property( 'captcha_type', $field, 'v3_recaptcha' );
			$captcha_theme = self::get_property( 'captcha_theme', $field, 'light' );
			$captcha_size  = self::get_property( 'captcha_size', $field, 'normal' );
			$captcha_class = 'forminator-g-recaptcha';

			if ( $this->is_invisible_recaptcha( $field ) ) {
				$captcha_badge  = 'data-badge="' . self::get_property( 'captcha_badge', $field, 'inline' ) . '"';
				$captcha_size   = 'invisible';
				$captcha_class .= ' recaptcha-invisible';
			}

			switch ( $captcha_type ) {
				case 'v2_checkbox':
					$key = get_option( 'forminator_captcha_key', '' );
					break;
				case 'v2_invisible':
					$key = get_option( 'forminator_v2_invisible_captcha_key', '' );
					break;
				case 'v3_recaptcha':
					$key = get_option( 'forminator_v3_captcha_key', '' );
					break;
			}

		} else {
			$key 		   = get_option( 'forminator_hcaptcha_key', '' );
			$captcha_type  = self::get_property( 'hcaptcha_type', $field, 'hc_checkbox' );
			$captcha_theme = self::get_property( 'hcaptcha_theme', $field, 'light' );
			$captcha_size  = self::get_property( 'hcaptcha_size', $field, 'normal' );
			$captcha_class = 'forminator-hcaptcha';

			if ( 'hc_invisible' === $captcha_type ) {
				$captcha_size 	  = 'invisible';
				$hcaptcha_notice  = self::get_property( 'hc_invisible_notice', $field, '' );
				$hcaptcha_notice  = sprintf( '<div class="forminator-checkbox__label">%s</div>', wp_kses_post( $hcaptcha_notice ) );
			}
		}
		// dont use .g-recaptcha class as it will rendered automatically when other plugin load recaptcha with default render
		return sprintf( '<div class="%s" data-theme="%s" %s data-sitekey="%s" data-size="%s"></div> %s', $captcha_class, $captcha_theme, $captcha_badge, $key, $captcha_size, $hcaptcha_notice );
	}


	/**
	 * Mark Captcha unavailable when captcha key not available
	 *
	 * @since 1.0.3
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function is_available( $field ) {
		$provider	  = self::get_property( 'captcha_provider', $field, 'recaptcha' );
		$captcha_type = self::get_property( 'captcha_type', $field, '' );

		if ( 'recaptcha' === $provider ) {
			switch ( $captcha_type ) {
				case 'v2_invisible':
					$key = get_option( 'forminator_v2_invisible_captcha_key', '' );
					break;
				case 'v3_recaptcha':
					$key = get_option( 'forminator_v3_captcha_key', '' );
					break;
				default:
					$key = get_option( 'forminator_captcha_key', '' );

			}
		} else {
			$key = get_option( 'forminator_hcaptcha_key', '' );
		}

		if ( ! $key ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate captcha
	 *
	 * @since 1.5.3
	 *
	 * @param array        $field
	 * @param array|string $data
	 *
	 * @return bool
	 */
	public function validate( $field, $data ) {
		$element_id    = self::get_property( 'element_id', $field );
		$provider  	   = self::get_property( 'captcha_provider', $field, 'recaptcha' );
		$captcha_type  = self::get_property( 'captcha_type', $field, '' );
		$score = '';

		if ( 'recaptcha' === $provider ) {

			if ( 'v2_checkbox' === $captcha_type ) {
				$secret = get_option( 'forminator_captcha_secret', '' );
			} elseif ( 'v2_invisible' === $captcha_type ) {
				$secret = get_option( 'forminator_v2_invisible_captcha_secret', '' );
			} elseif ( 'v3_recaptcha' === $captcha_type ) {
				$secret = get_option( 'forminator_v3_captcha_secret', '' );
				$score  = self::get_property( 'score_threshold', $field, '' );
			}

			$error_message	= self::get_property( 'recaptcha_error_message', $field, '' );

		} else {

			// hcaptcha
			$secret			= get_option( 'forminator_hcaptcha_secret', '' );
			$error_message	= self::get_property( 'hcaptcha_error_message', $field, '' );
		}

		$captcha = new Forminator_Captcha_Verification( $secret, $provider );
		$verify	 = $captcha->verify( $data, null, $score );

		if ( is_wp_error( $verify ) ) {
			$invalid_captcha_message = ( ! empty( $error_message ) ? $error_message : __( 'Captcha verification failed. Please try again.', 'forminator' ) );

			/**
			 * Filter message displayed for invalid captcha
			 *
			 * @since 1.5.3
			 *
			 * @param string   $invalid_captcha_message
			 * @param string   $element_id
			 * @param array    $field
			 * @param WP_Error $verify
			 */
			$invalid_captcha_message = apply_filters( 'forminator_invalid_captcha_message', $invalid_captcha_message, $element_id, $field, $verify );

			$this->validation_message[ $element_id ] = $invalid_captcha_message;
		}
	}
}
