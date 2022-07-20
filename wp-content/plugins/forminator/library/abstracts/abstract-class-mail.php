<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Mail
 *
 * Handle mail sending
 *
 * @since 1.0
 */
abstract class Forminator_Mail {
	protected $message_vars;

	/**
	 * Default content type
	 *
	 * @since 1.5
	 * @var string
	 */
	protected $content_type = 'text/html; charset=UTF-8';

	/**
	 * Mail recipient
	 * The email to receive the mail
	 *
	 * @var string
	 */
	protected $recipient = '';

	/**
	 * Mail recipients
	 * The emails to receive the mail
	 *
	 * @var array
	 */
	protected $recipients = array();

	/**
	 * Mail message
	 *
	 * @var string
	 */
	protected $message = '';

	/**
	 * Mail subject
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * Mail from email
	 *
	 * @var string
	 */
	protected $sender_email = '';

	/**
	 * Mail from name
	 *
	 * @var string
	 */
	protected $sender_name = '';

	/**
	 * Mail headers
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Mail headers
	 *
	 * @var array
	 */
	protected $attachment = array();

	/**
	 * Main constructor
	 *
	 * @since 1.0
	 *
	 * @param string $recipient - mail recipient email.
	 * @param string $message   - mail message.
	 * @param string $subject   - mail subject.
	 */
	public function __construct( $recipient = '', $message = '', $subject = '' ) {
		if ( ! empty( $recipient ) && filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
			$this->recipient = $recipient;
		}
		if ( ! empty( $message ) ) {
			$this->message = $message;
		}
		if ( ! empty( $subject ) ) {
			$this->subject = $subject;
		}
		$this->sender_email = get_global_sender_email_address();
		$this->sender_name  = get_global_sender_name();
		$this->set_headers();
	}

	/**
	 * Initialize the mail
	 */
	public function init() {
		$user_email  = false;
		$user_name   = '';
		$user_login  = '';
		$embed_id    = filter_input( INPUT_POST, 'page_id', FILTER_VALIDATE_INT );
		$embed_title = get_the_title( $embed_id );
		$embed_url   = forminator_get_current_url();
		$site_url    = site_url();

		// Check if user is logged in.
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_email   = $current_user->user_email;
			if ( ! empty( $current_user->user_firstname ) ) {
				$user_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			} elseif ( ! empty( $current_user->display_name ) ) {
				$user_name = $current_user->display_name;
			} else {
				$user_name = $current_user->display_name;
			}
			$user_login = $current_user->user_login;
		}

		// Set up mail variables.
		$message_vars = forminator_set_message_vars( $embed_id, $embed_title, $embed_url, $user_name, $user_email, $user_login, $site_url );

		/**
		 * Message variables filter
		 *
		 * @since 1.0.2
		 *
		 * @param array $message_vars - the message variables.
		 * @param int   $embed_id     - the current module id.
		 *
		 * @return array $message_vars
		 */
		$this->message_vars = apply_filters( 'forminator_custom_' . static::$module_slug . '_message_vars', $message_vars, $embed_id );
	}

	/**
	 * Check if all conditions are met to send admin email
	 *
	 * @since 1.0
	 *
	 * @param array $setting - the module settings.
	 *
	 * @return bool
	 */
	public function is_send_admin_mail( $setting ) {
		if ( isset( $setting['use-admin-email'] ) && ! empty( $setting['use-admin-email'] ) ) {
			if ( filter_var( $setting['use-admin-email'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( isset( $setting['admin-email-title'] ) && isset( $setting['admin-email-editor'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get Recipients of admin emails
	 *
	 * @since 1.0.3
	 * @since 1.6.2 add $custom_form model, and entry
	 *
	 * @param array                       $notification
	 * @param Forminator_Base_Form_Model  $module
	 * @param Forminator_Form_Entry_Model $entry
	 * @param                             $lead_model
	 *
	 * @return array
	 */
	public function get_admin_email_recipients( $notification, $module = null, $entry = null, $lead_model = array() ) {

		$email = array();
		if ( isset( $notification['email-recipients'] ) && 'routing' === $notification['email-recipients'] ) {
			if ( ! empty( $notification['routing'] ) ) {
				foreach ( $notification['routing'] as $routing ) {
					if ( $this->is_routing( $routing, $module ) ) {
						$recipients = array_map( 'trim', explode( ',', $routing['email'] ) );
					}
				}
			}
		} elseif ( isset( $notification['recipients'] ) && ! empty( $notification['recipients'] ) ) {
			$recipients = array_map( 'trim', explode( ',', $notification['recipients'] ) );
		}
		if ( ! empty( $recipients ) ) {
			foreach ( $recipients as $key => $recipient ) {
				$recipient = $this->get_recipient( $recipient, $module, $entry, $lead_model );
				if ( is_email( $recipient ) ) {
					$email[] = $recipient;
				}
			}
		}

		return apply_filters( 'forminator_' . static::$module_slug . '_get_admin_email_recipients', $email, $notification, Forminator_CForm_Front_Action::$prepared_data, $module, $entry );
	}

	/**
	 * Set recipeint
	 *
	 * @since 1.0
	 *
	 * @param string $recipient - recipient email.
	 */
	public function set_recipient( $recipient ) {
		if ( filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
			$this->recipient = $recipient;
		}
	}


	/**
	 * Set Recipients as array
	 *
	 * @since 1.0.3
	 *
	 * @param array $recipients
	 */
	public function set_recipients( $recipients ) {
		$this->recipients = array();
		if ( ! empty( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				if ( filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
					$this->recipients[] = $recipient;
				}
			}
		}
	}

	/**
	 * Set message
	 *
	 * @since 1.0
	 *
	 * @param string $message - the mail message.
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}

	/**
	 * Set message with vars
	 *
	 * @since 1.0
	 *
	 * @param array  $message_vars - the mail message array variables.
	 * @param string $message      - the mail message.
	 */
	public function set_message_with_vars( $message_vars, $message ) {
		$this->message = str_replace(
			array_keys( $message_vars ),
			array_values( $message_vars ),
			stripslashes( $message )
		);
	}

	/**
	 * Set subject
	 *
	 * @since 1.0
	 *
	 * @param string $subject - the mail subject.
	 */
	public function set_subject( $subject ) {
		$this->subject = $subject;
	}

	/**
	 * Set attachment
	 *
	 * @since 1.0
	 *
	 * @param array $attachment - the mail attachment.
	 */
	public function set_attachment( $attachment ) {
		$this->attachment = $attachment;
	}

	/**
	 * Set headers
	 *
	 * @since 1.0
	 *
	 * @param array $headers - the mail headers.
	 */
	public function set_headers( $headers = array() ) {
		if ( ! empty( $headers ) ) {
			$this->headers = $headers;
		} else {
			$this->headers   = array(
				'From: ' . $this->sender_name . ' <' . $this->sender_email . '>',
			);
			$this->headers[] = 'Content-Type: text/html; charset=UTF-8';
		}
	}

	/**
	 * Clean mail variables
	 *
	 * @since 1.0
	 */
	private function clean() {
		$subject       = stripslashes( $this->subject );
		$subject       = wp_strip_all_tags( $subject );
		$this->subject = $subject;

		$message       = stripslashes( $this->message );
		$message       = wpautop( $message );
		$message       = make_clickable( $message );
		$this->message = $message;
	}

	/**
	 * Get Forminator mailer headers
	 *
	 * @since 1.5
	 * @return array
	 */
	public function get_headers() {
		$headers = $this->headers;

		/**
		 * Filter headers that will be sent by Forminator Mailer
		 *
		 * @since 1.5
		 *
		 * @param array $headers
		 */
		$headers = apply_filters( 'forminator_mailer_headers', $headers );

		return $headers;
	}

	/**
	 * Send mail
	 *
	 * @since 1.0
	 * @since 1.5 use `get_headers`
	 * @return bool
	 */
	public function send() {
		$sent    = false;
		$headers = $this->get_headers();
		if ( ! empty( $this->recipient ) && ! empty( $this->subject ) && ! empty( $this->message ) ) {
			$this->clean();
			$sent = wp_mail( $this->recipient, $this->subject, $this->message, $headers );
		}

		return $sent;
	}

	/**
	 * Send mail for multiple recipients
	 *
	 * @since 1.0.3
	 * @since 1.5 use `get_headers`
	 *
	 * @return bool
	 */
	public function send_multiple() {
		$sent    = false;
		$headers = $this->get_headers();
		if ( ! empty( $this->recipients ) && ! empty( $this->subject ) && ! empty( $this->message ) ) {
			$this->clean();
			if ( $this->attachment ) {
				$sent = wp_mail( $this->recipients, $this->subject, $this->message, $headers, $this->attachment );
			} else {
				$sent = wp_mail( $this->recipients, $this->subject, $this->message, $headers );
			}
		}

		return $sent;
	}

	/**
	 * Check if Form Field value fullfilled the condition
	 *
	 * @since 1.0
	 *
	 * @param $form_field_value
	 * @param $condition
	 *
	 * @return bool
	 */
	public static function is_condition_fulfilled( $form_field_value, $condition ) {
		return Forminator_Field::is_condition_fulfilled( $form_field_value, $condition );
	}

	/**
	 * Check whether answer is correct for a question on Knowledge Quiz
	 *
	 * @since 1.6.2
	 *
	 * @param string $slug         question slug.
	 * @param  int    $answer_index answer index.
	 * @param  int    $quiz_model
	 *
	 * @return bool
	 */
	public static function is_correct_answer( $slug, $answer_index, $quiz_model ) {
		if ( ! empty( $quiz_model->questions ) ) {
			foreach ( $quiz_model->questions as $question ) {
				if ( isset( $question['slug'] ) && $question['slug'] === $slug ) {
					$answers = $question['answers'];
					foreach ( $answers as $k => $answer ) {
						if ( isset( $answer['toggle'] ) && filter_var( $answer['toggle'], FILTER_VALIDATE_BOOLEAN ) === true ) {
							if ( (int) $answer_index === (int) $k ) {
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the result slug for Personality Quiz
	 *
	 * @since   1.15.3
	 *
	 * @param   array $form_data  submitted data.
	 *
	 * @return  string
	 */
	public static function get_result_slug( $form_data ) {
		if ( ! empty( $form_data ) ) {
			if ( isset( $form_data['entry'] ) ) {
				if ( isset( $form_data['entry'][0]['value']['result'] ) ) {
					return $form_data['entry'][0]['value']['result']['slug'];
				}
			}
		}

		return '';
	}
}
