<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_Admin
 *
 * @property string            page_edit_nowrong
 * @property Forminator_Module module
 * @property string            page_edit_knowledge
 *
 * @since 1.0
 */
class Forminator_Quiz_Admin extends Forminator_Admin_Module {

	/**
	 * Initialize
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->module              = Forminator_Quizzes::get_instance();
		$this->page                = 'forminator-quiz';
		$this->page_edit_nowrong   = 'forminator-nowrong-wizard';
		$this->page_edit_knowledge = 'forminator-knowledge-wizard';
		$this->page_entries        = 'forminator-quiz-view';
	}

	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/admin-page-new-nowrong.php';
		include_once dirname( __FILE__ ) . '/admin-page-new-knowledge.php';
		include_once dirname( __FILE__ ) . '/admin-page-view.php';
		include_once dirname( __FILE__ ) . '/admin-page-entries.php';
		include_once dirname( __FILE__ ) . '/admin-renderer-entries.php';
	}

	/**
	 * Add module pages to Admin
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {
		new Forminator_Quiz_Page( $this->page, 'quiz/list', __( 'Quizzes', 'forminator' ), __( 'Quizzes', 'forminator' ), 'forminator' );
		new Forminator_Quiz_New_NoWrong( $this->page_edit_nowrong, 'quiz/nowrong', __( 'New Quiz', 'forminator' ), __( 'New Quiz', 'forminator' ), 'forminator' );
		new Forminator_Quiz_New_Knowledge( $this->page_edit_knowledge, 'quiz/knowledge', __( 'New Quiz', 'forminator' ), __( 'New Quiz', 'forminator' ), 'forminator' );
		new Forminator_Quiz_View_Page( $this->page_entries, 'quiz/entries', __( 'Submissions:', 'forminator' ), __( 'View Quizzes', 'forminator' ), 'forminator' );
	}

	/**
	 * Remove necessary pages from menu
	 *
	 * @since 1.0
	 */
	public function hide_menu_pages() {
		remove_submenu_page( 'forminator', $this->page_edit_nowrong );
		remove_submenu_page( 'forminator', $this->page_edit_knowledge );
		remove_submenu_page( 'forminator', $this->page_entries );
		remove_submenu_page( 'forminator', $this->page_entries );
	}

	/**
	 * Is the type of the quiz "knowledge"
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_knowledge_wizard() {
		global $plugin_page;

		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( empty( $plugin_page ) && $page ) {
			$plugin_page = $page;
		}

		return $this->page_edit_knowledge === $plugin_page;
	}

	/**
	 * Is the type of the quiz "no wrong answer"
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_nowrong_wizard() {
		global $plugin_page;

		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( empty( $plugin_page ) && $page ) {
			$plugin_page = $page;
		}

		return $this->page_edit_nowrong === $plugin_page;
	}

	/**
	 * Highlight parent page in sidebar
	 *
	 * @deprecated 1.1 No longer used because this function override prohibited WordPress global of $plugin_page
	 * @since      1.0
	 *
	 * @param $file
	 *
	 * @return mixed
	 */
	public function highlight_admin_parent( $file ) {
		_deprecated_function( __METHOD__, '1.1', null );

		return $file;
	}

	/**
	 * Highlight submenu on admin page
	 *
	 * @since 1.1
	 *
	 * @param $submenu_file
	 * @param $parent_file
	 *
	 * @return string
	 */
	public function admin_submenu_file( $submenu_file, $parent_file ) {
		global $plugin_page;

		if ( 'forminator' !== $parent_file ) {
			return $submenu_file;
		}

		if ( $this->page_edit_nowrong === $plugin_page || $this->page_edit_knowledge === $plugin_page || $this->page_entries === $plugin_page ) {
			$submenu_file = $this->page;
		}

		return $submenu_file;
	}

	/**
	 * Pass module defaults to JS
	 *
	 * @since 1.0
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		$model         = null;
		$wrappers      = array();
		$lead_settings = array();

		if ( $this->is_knowledge_wizard() || $this->is_nowrong_wizard() ) {
			$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			if ( $id && is_null( $model ) ) {
				/** @var  Forminator_Quiz_Model $model */
				$model = Forminator_Base_Form_Model::get_model( $id );
			}

			if ( $this->is_knowledge_wizard() ) {
				$data['formNonce']   = wp_create_nonce( 'forminator_save_quiz' );
				$data['application'] = 'knowledge';

				// Load stored record.
				if ( is_object( $model ) ) {

					$settings = apply_filters( 'forminator_quiz_settings', $model->settings, $model, $data, $this );

					$has_lead   = isset( $settings['hasLeads'] ) ? $settings['hasLeads'] : false;
					$lead_id    = isset( $settings['leadsId'] ) ? $settings['leadsId'] : 0;
					$form_model = Forminator_Base_Form_Model::get_model( $lead_id );
					if ( is_object( $form_model ) && $has_lead ) {
						$wrappers      = $form_model->get_fields_grouped();
						$lead_settings = $form_model->settings;
					}

					$notifications = $this->get_quiz_notifications( $model );
					$notifications = apply_filters( 'forminator_quiz_notifications', $notifications, $model, $data, $this );

					$data['currentForm'] = array(
						'results'       => array(),
						'questions'     => $model->questions,
						'settings'      => array_merge(
							$settings,
							array(
								'formName'      => forminator_get_name_from_model( $model ),
								'form_id'       => $model->id,
								'form_status'   => $model->status,
								'quiz_title'    => $model->name,
								'version'       => FORMINATOR_VERSION,
								'wrappers'      => $wrappers,
								'lead_settings' => $lead_settings,
							)
						),
						'notifications' => $notifications,
					);
				} else {
					$data['currentForm'] = array();
				}
			}

			if ( $this->is_nowrong_wizard() ) {
				$data['formNonce']   = wp_create_nonce( 'forminator_save_quiz' );
				$data['application'] = 'nowrong';

				// Load stored record.
				if ( is_object( $model ) ) {
					unset( $model->settings['priority_order'] );
					$settings = apply_filters( 'forminator_quiz_settings', $model->settings, $model, $data, $this );

					$has_lead   = isset( $settings['hasLeads'] ) ? $settings['hasLeads'] : false;
					$lead_id    = isset( $settings['leadsId'] ) ? $settings['leadsId'] : 0;
					$form_model = Forminator_Base_Form_Model::get_model( $lead_id );
					if ( is_object( $form_model ) && $has_lead ) {
						$wrappers      = $form_model->get_fields_grouped();
						$lead_settings = $form_model->settings;
					}

					$notifications = $this->get_quiz_notifications( $model );
					$notifications = apply_filters( 'forminator_quiz_notifications', $notifications, $model, $data, $this );

					$data['currentForm'] = array(
						'results'       => $model->getResults(),
						'questions'     => $model->questions,
						'settings'      => array_merge(
							$settings,
							array(
								'formName'      => forminator_get_name_from_model( $model ),
								'form_id'       => $model->id,
								'form_status'   => $model->status,
								'quiz_title'    => $model->name,
								'version'       => FORMINATOR_VERSION,
								'wrappers'      => $wrappers,
								'lead_settings' => $lead_settings,
							)
						),
						'notifications' => $notifications,
					);
				} else {
					$data['currentForm'] = array();
				}
			}
		}

		$data['modules']['quizzes'] = array(
			'nowrong_url'   => menu_page_url( $this->page_edit_nowrong, false ),
			'knowledge_url' => menu_page_url( $this->page_edit_knowledge, false ),
			'form_list_url' => menu_page_url( $this->page, false ),
			'preview_nonce' => wp_create_nonce( 'forminator_popup_preview_quiz' ),
		);

		return apply_filters( 'forminator_quiz_admin_data', $data, $model, $this );
	}

	/**
	 * Common quiz default data
	 *
	 * @return array
	 */
	private static function common_default_data() {
		return array(
			// Pagination.
			'page-indicator-color'                    => '#888888',
			'start-button-background-static'          => '#17A8E3',
			'start-button-background-hover'           => '#008FCA',
			'start-button-background-active'          => '#008FCA',
			'start-button-color-static'               => '#FFFFFF',
			'start-button-color-hover'                => '#FFFFFF',
			'start-button-color-active'               => '#FFFFFF',
			'start-button-font-family'                => 'Roboto',
			'start-button-font-size'                  => '14',
			'start-button-font-weight'                => '500',
			'navigation-button-background-static'     => '#1ABCA1',
			'navigation-button-background-hover'      => '#159C85',
			'navigation-button-background-active'     => '#159C85',
			'navigation-button-color-static'          => '#FFFFFF',
			'navigation-button-color-hover'           => '#FFFFFF',
			'navigation-button-color-active'          => '#FFFFFF',
			'navigation-button-font-family'           => 'Roboto',
			'navigation-button-font-size'             => '14',
			'navigation-button-font-weight'           => '500',
			'back-questions-button-background-static' => '#1ABCA1',
			'back-questions-button-background-hover'  => '#159C85',
			'back-questions-button-background-active' => '#159C85',
			'back-questions-button-color-static'      => '#FFFFFF',
			'back-questions-button-color-hover'       => '#FFFFFF',
			'back-questions-button-color-active'      => '#FFFFFF',
			'page-indicator-font-family'              => 'Roboto',
			'page-indicator-font-size'                => '13',
			'page-indicator-font-weight'              => '400',
		);
	}

	/**
	 * Knowledge quiz default data
	 *
	 * @since 1.14
	 *
	 * @return array
	 */
	public static function knowledge_default_data( $name, $has_leads ) {
		return array_merge(
			self::common_default_data(),
			array(
				'hasLeads'                                 => $has_leads,
				'formName'                                 => $name,
				'version'                                  => FORMINATOR_VERSION,
				'admin-email-recipients'                   => array(
					get_option( 'admin_email' ),
				),
				'admin-email-title'                        => __( 'New Quiz Submission for {quiz_name}', 'forminator' ),
				'admin-email-editor'                       => __(
					'You have a new quiz submission: <br/><br/>{quiz_answer}<br/><br/>Quiz results: <br/>{quiz_result} <br/>---<br/> This message was sent from {site_url}.',
					'forminator'
				),
				'results_behav'                            => 'after',
				'visual_style'                             => 'list',
				'forminator-quiz-theme'                    => 'default',
				'msg_correct'                              => 'Correct! It was %UserAnswer%.',
				'msg_incorrect'                            => 'Wrong! It was %CorrectAnswer%, sorry...',
				'msg_count'                                => 'You got %YourNum%/%Total% correct!',
				// KNOWLEDGE title.
				'knowledge-title-color'                    => '#333333',
				'knowledge-title-font-family'              => 'Roboto',
				'knowledge-title-font-size'                => '42',
				'knowledge-title-font-weight'              => '500',
				// KNOWLEDGE description.
				'knowledge-description-color'              => '#8C8C8C',
				'knowledge-description-font-family'        => 'Roboto',
				'knowledge-description-font-size'          => '20',
				'knowledge-description-font-weight'        => '400',
				// KNOWLEDGE question.
				'knowledge-question-color'                 => '#333333',
				'knowledge-question-font-family'           => 'Roboto',
				'knowledge-question-font-size'             => '24',
				'knowledge-question-font-weight'           => '700',
				'knowledge-question-description-color'     => '#8C8C8C',
				'question-description-font-family'         => 'Roboto',
				'question-description-font-size'           => '20',
				'question-description-font-weight'         => '400',
				// KNOWLEDGE answer.
				'knowledge-answer-background-static'       => '#FAFAFA',
				'knowledge-answer-background-hover'        => '#F3FBFE',
				'knowledge-answer-background-active'       => '#F3FBFE',
				'knowledge-aright-background'              => '#F4FCF2',
				'knowledge-awrong-background'              => '#FDF2F2',
				'knowledge-answer-border-static'           => '#EBEDEB',
				'knowledge-answer-border-hover'            => '#17A8E3',
				'knowledge-answer-border-active'           => '#17A8E3',
				'knowledge-aright-border'                  => '#0BC30B',
				'knowledge-awrong-border'                  => '#DA0000',
				'knowledge-answer-color-static'            => '#888888',
				'knowledge-answer-color-active'            => '#333333',
				'knowledge-aright-color'                   => '#0BC30B',
				'knowledge-awrong-color'                   => '#DA0000',
				'knowledge-answer-font-size'               => '14',
				'knowledge-answer-font-family'             => 'Roboto',
				'knowledge-answer-font-weight'             => '500',
				'knowledge-answer-check-border-static'     => '#BFBFBF',
				'knowledge-answer-check-border-active'     => '#17A8E3',
				'knowledge-answer-check-border-correct'    => '#0BC30B',
				'knowledge-answer-check-border-incorrect'  => '#DA0000',
				'knowledge-answer-check-background-static' => '#FFFFFF',
				'knowledge-answer-check-background-active' => '#17A8E3',
				'knowledge-answer-check-background-correct' => '#0BC30B',
				'knowledge-answer-check-background-incorrect' => '#DA0000',
				'knowledge-phrasing-color'                 => '#4D4D4D',
				'knowledge-phrasing-font-size'             => '16',
				'knowledge-phrasing-font-family'           => 'Roboto',
				'knowledge-phrasing-font-weight'           => '700',
				// KNOWLEDGE button.
				'knowledge-submit-background-static'       => '#17A8E3',
				'knowledge-submit-background-hover'        => '#008FCA',
				'knowledge-submit-background-active'       => '#008FCA',
				'knowledge-submit-color-static'            => '#FFFFFF',
				'knowledge-submit-color-hover'             => '#FFFFFF',
				'knowledge-submit-color-active'            => '#FFFFFF',
				'knowledge-submit-font-family'             => 'Roboto',
				'knowledge-submit-font-size'               => '14',
				'knowledge-submit-font-weight'             => '500',
				// KNOWLEDGE summary.
				'knowledge-summary-color'                  => '#333333',
				'knowledge-summary-font-family'            => 'Roboto',
				'knowledge-summary-font-size'              => '40',
				'knowledge-summary-font-weight'            => '400',
				'knowledge-result-retake-font-family'      => 'Roboto',
				'knowledge-result-retake-font-size'        => '13',
				'knowledge-result-retake-font-weight'      => '500',
				'knowledge-result-retake-background-static' => '#222222',
				'knowledge-result-retake-background-hover' => '#222222',
				'knowledge-result-retake-background-active' => '#222222',
				// KNOWLEDGE social.
				'enable-share'                             => 'on',
				'knowledge-sshare-color'                   => '#4D4D4D',
				'knowledge-sshare-font-family'             => 'Roboto',
				'knowledge-sshare-font-size'               => '20',
				'knowledge-social-facebook'                => '#0084BF',
				'knowledge-social-twitter'                 => '#1DA1F2',
				'knowledge-social-google'                  => '#DB4437',
				'forminator-knowledge-social-linkedin'     => '#0084BF',
				'knowledge-social-size'                    => '36',
			)
		);
	}

	/**
	 * No wrong quiz default data
	 *
	 * @since 1.14
	 *
	 * @return array
	 */
	public static function nowrong_default_data( $name, $has_leads ) {
		return array_merge(
			self::common_default_data(),
			array(
				'hasLeads'                                => $has_leads,
				'formName'                                => $name,
				'version'                                 => FORMINATOR_VERSION,
				'admin-email-recipients'                  => array(
					get_option( 'admin_email' ),
				),
				'results_behav'                           => 'after',
				'visual_style'                            => 'list',
				'forminator-quiz-theme'                   => 'default',
				'msg_correct'                             => 'Correct! It was %UserAnswer%.',
				'msg_incorrect'                           => 'Wrong! It was %CorrectAnswer%, sorry...',
				'msg_count'                               => 'You got %YourNum%/%Total% correct!',
				// NOWRONG title.
				'nowrong-title-settings'                  => false,
				'nowrong-title-color'                     => '#333333',
				'nowrong-title-font-family'               => 'Roboto',
				'nowrong-title-font-size'                 => '42',
				'nowrong-title-font-weight'               => '500',
				// NOWRONG description.
				'nowrong-description-settings'            => false,
				'nowrong-description-color'               => '#8C8C8C',
				'nowrong-description-font-family'         => 'Roboto',
				'nowrong-description-font-size'           => '20',
				'nowrong-description-font-weight'         => '400',
				// NOWRONG image.
				'nowrong-image-settings'                  => false,
				'nowrong-image-border-color'              => '#000000',
				'nowrong-image-border-width'              => '0',
				'nowrong-image-border-style'              => 'solid',
				// NOWRONG question.
				'nowrong-question-settings'               => false,
				'nowrong-question-font-size'              => '24',
				'nowrong-question-font-family'            => 'Roboto',
				'nowrong-question-font-weight'            => '700',
				'nowrong-question-description-color'      => '#8C8C8C',
				'question-description-font-family'        => 'Roboto',
				'question-description-font-size'          => '20',
				'question-description-font-weight'        => '400',
				// NOWRONG answer.
				'nowrong-answer-settings'                 => false,
				'nowrong-answer-border-static'            => '#EBEDEB',
				'nowrong-answer-border-hover'             => '#17A8E3',
				'nowrong-answer-border-active'            => '#17A8E3',
				'nowrong-answer-background-static'        => '#FAFAFA',
				'nowrong-answer-background-hover'         => '#F3FBFE',
				'nowrong-answer-background-active'        => '#F3FBFE',
				'nowrong-answer-chkbo-static'             => '#BFBFBF',
				'nowrong-answer-chkbo-active'             => '#17A8E3',
				'nowrong-answer-color-static'             => '#888888',
				'nowrong-answer-color-active'             => '#333333',
				'nowrong-answer-font-size'                => '14',
				'nowrong-answer-font-family'              => 'Roboto',
				'nowrong-answer-font-weight'              => '500',
				// NOWRONG submit.
				'nowrong-submit-background-static'        => '#17A8E3',
				'nowrong-submit-background-hover'         => '#008FCA',
				'nowrong-submit-background-active'        => '#008FCA',
				'nowrong-submit-color-static'             => '#FFFFFF',
				'nowrong-submit-color-hover'              => '#FFFFFF',
				'nowrong-submit-color-active'             => '#FFFFFF',
				'nowrong-submit-font-family'              => 'Roboto',
				'nowrong-submit-font-size'                => '14',
				'nowrong-submit-font-weight'              => '500',
				// NOWRONG result.
				'nowrong-result-background-main'          => '#FAFAFA',
				'nowrong-result-background-header'        => '#FAFAFA',
				'nowrong-result-border-color'             => '#17A8E3',
				'nowrong-result-quiz-color'               => '#888888',
				'nowrong-result-quiz-font-family'         => 'Roboto',
				'nowrong-result-quiz-font-size'           => '15',
				'nowrong-result-quiz-font-weight'         => '500',
				'nowrong-result-retake-font-family'       => 'Roboto',
				'nowrong-result-retake-font-size'         => '13',
				'nowrong-result-retake-font-weight'       => '500',
				'nowrong-result-retake-background-static' => '#222222',
				'nowrong-result-retake-background-hover'  => '#222222',
				'nowrong-result-retake-background-active' => '#222222',
				'nowrong-result-background-body'          => '#EBEDEB',
				'nowrong-result-title-color'              => '#333333',
				'nowrong-result-title-font-family'        => 'Roboto',
				'nowrong-result-title-font-size'          => '15',
				'nowrong-result-title-font-weight'        => '500',
				'nowrong-result-description-color'        => '#4D4D4D',
				'nowrong-result-description-font-family'  => 'Roboto',
				'nowrong-result-description-font-size'    => '13',
				'nowrong-result-description-font-weight'  => '400',
				// NOWRONG social.
				'enable-share'                            => 'on',
			)
		);
	}

	/**
	 * Localize modules strings
	 *
	 * @since 1.0
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function add_l10n_strings( $data ) {
		$data['quizzes'] = array(
			'quizzes'                      => __( 'Quizzes', 'forminator' ),
			'popup_label'                  => __( 'Choose Quiz Type', 'forminator' ),
			'results'                      => __( 'Results', 'forminator' ),
			'questions'                    => __( 'Questions', 'forminator' ),
			'details'                      => __( 'Details', 'forminator' ),
			'settings'                     => __( 'Settings', 'forminator' ),
			'appearance'                   => __( 'Appearance', 'forminator' ),
			'preview'                      => __( 'Preview', 'forminator' ),
			'preview_quiz'                 => __( 'Preview Quiz', 'forminator' ),
			'list'                         => __( 'List', 'forminator' ),
			'grid'                         => __( 'Grid', 'forminator' ),
			'visual_style'                 => __( 'Visual style', 'forminator' ),
			'quiz_title'                   => __( 'Quiz Title', 'forminator' ),
			'quiz_title_desc'              => __( "Further customize the appearance for quiz title. It appears as result's header.", 'forminator' ),
			'title'                        => __( 'Title', 'forminator' ),
			'title_desc'                   => __( 'Further customize appearance for quiz title.', 'forminator' ),
			'image_desc'                   => __( 'Further customize appearance for quiz featured image.', 'forminator' ),
			'enable_styles'                => __( 'Enable custom styles', 'forminator' ),
			'desc_desc'                    => __( 'Further customize appearance for quiz description / intro.', 'forminator' ),
			'description'                  => __( 'Description / Intro', 'forminator' ),
			'feat_image'                   => __( 'Featured image', 'forminator' ),
			'font_color'                   => __( 'Font color', 'forminator' ),
			'browse'                       => __( 'Browse', 'forminator' ),
			'clear'                        => __( 'Clear', 'forminator' ),
			'results_behav'                => __( 'Results behavior', 'forminator' ),
			'rb_description'               => __( 'Pick if you want to reveal the correct answer as user finishes question, or only after the whole quiz is completed.', 'forminator' ),
			'reveal'                       => __( 'When to reveal correct answer', 'forminator' ),
			'after'                        => __( 'After user picks answer', 'forminator' ),
			'before'                       => __( 'At the end of whole quiz', 'forminator' ),
			'phrasing'                     => __( 'Answer phrasing', 'forminator' ),
			'phrasing_desc'                => __(
				'Pick how you want the correct & incorrect answers to read. Use <strong>%UserAnswer%</strong> to pull in the value user selected & <strong>%CorrectAnswer%</strong> to pull in the correct value.',
				'forminator'
			),
			'phrasing_desc_alt'            => __( 'Further customize appearance for answer message.', 'forminator' ),
			'msg_correct'                  => __( 'Correct answer message', 'forminator' ),
			'msg_incorrect'                => __( 'Incorrect answer message', 'forminator' ),
			'msg_count'                    => __( 'Final count message', 'forminator' ),
			'msg_count_desc'               => __(
				'Edit the copy of the final result count message that will appear after the quiz is complete. Use <strong>%YourNum%</strong> to display number of correct answers and <strong>%Total%</strong> for total number of questions.',
				'forminator'
			),
			'msg_count_info'               => __( 'You can now add some html content here to personalize even more text displayed as Final Count Message. Try it now!', 'forminator' ),
			'share'                        => __( 'Share on social media', 'forminator' ),
			'order'                        => __( 'Results priority order', 'forminator' ),
			'order_label'                  => __( 'Pick priority for results', 'forminator' ),
			'order_alt'                    => __( 'Quizzes can have even number of scores for 2 or more results, in those scenarios, this order will help determine the result.', 'forminator' ),
			'questions_title'              => __( 'Questions', 'forminator' ),
			'question_desc'                => __( 'Further customize appearance for quiz questions.', 'forminator' ),
			'result_title'                 => __( 'Result title', 'forminator' ),
			'result_description'           => __( 'Result description', 'forminator' ),
			'result_description_desc'      => __( 'Further customize the appearance for result description typography.', 'forminator' ),
			'result_title_desc'            => __( 'Further customize the appearance for result title typography.', 'forminator' ),
			'retake_button'                => __( 'Retake button', 'forminator' ),
			'retake_button_desc'           => __( 'Further customize the appearance for retake quiz button.', 'forminator' ),
			'validate_form_name'           => __( 'Form name cannot be empty! Please pick a name for your quiz.', 'forminator' ),
			'validate_form_question'       => __( 'Quiz question cannot be empty! Please add questions for your quiz.', 'forminator' ),
			'validate_form_answers'        => __( 'Quiz answers cannot be empty! Please add some questions.', 'forminator' ),
			'validate_form_answers_result' => __( 'Result answer cannot be empty! Please select a result.', 'forminator' ),
			'validate_form_correct_answer' => __( 'This question needs a correct answer. Please, select one before saving or proceeding to next step.', 'forminator' ),
			'validate_form_no_answer'      => __( 'Please add an answer for this question.', 'forminator' ),
			'answer'                       => __( 'Answers', 'forminator' ),
			'no_answer'                    => __( "You don't have any answer for this question yet.", 'forminator' ),
			'answer_desc'                  => __( 'Further customize appearance for quiz answers.', 'forminator' ),
			'back'                         => __( 'Back', 'forminator' ),
			'cancel'                       => __( 'Cancel', 'forminator' ),
			'continue'                     => __( 'Continue', 'forminator' ),
			'correct_answer'               => __( 'Correct answer', 'forminator' ),
			'correct_answer_desc'          => __( 'Customize appearance for correct answers.', 'forminator' ),
			'finish'                       => __( 'Finish', 'forminator' ),
			'smartcrawl'                   => __(
				"<strong>Want more control?</strong> <strong><a href='https://wpmudev.com/project/smartcrawl-wordpress-seo/' target='_blank'>SmartCrawl</a></strong> OpenGraph and Twitter Card support lets you choose how your content looks when it’s shared on social media.",
				'forminator'
			),
			'submit'                       => __( 'Submit', 'forminator' ),
			'submit_desc'                  => __( 'Further customize appearance for quiz submit button.', 'forminator' ),
			'main_styles'                  => __( 'Main styles', 'forminator' ),
			'border'                       => __( 'Border', 'forminator' ),
			'border_desc'                  => __( "Further customize border for result's main container.", 'forminator' ),
			'padding'                      => __( 'Padding', 'forminator' ),
			'background'                   => __( 'Background', 'forminator' ),
			'background_desc'              => __(
				'The Results box has three different backgrounds: main container, header background (where quiz title and reload button are placed), and content background (where result title and description are placed). Here you can customize the three of them.',
				'forminator'
			),
			'bg_main'                      => __( 'Main BG', 'forminator' ),
			'bg_header'                    => __( 'Header BG', 'forminator' ),
			'bg_content'                   => __( 'Content BG', 'forminator' ),
			'color'                        => __( 'Color', 'forminator' ),
			'result_appearance'            => __( "Result's Box", 'forminator' ),
			'margin'                       => __( 'Margin', 'forminator' ),
			'summary'                      => __( 'Summary', 'forminator' ),
			'summary_desc'                 => __( 'Further customize appearance for quiz final count message', 'forminator' ),
			'sshare'                       => __( 'Sharing text', 'forminator' ),
			'sshare_desc'                  => __( 'Further customize appearance for share on social media text', 'forminator' ),
			'social'                       => __( 'Social icons', 'forminator' ),
			'social_desc'                  => __( 'Further customize appearance for social media icons', 'forminator' ),
			'wrong_answer'                 => __( 'Wrong answer', 'forminator' ),
			'wrong_answer_desc'            => __( 'Customize appearance for wrong answers.', 'forminator' ),
			'msg_description'              => __(
				'Use <strong>%UserAnswer%</strong> to pull in the value user selected and <strong>%CorrectAnswer%</strong> to pull in the correct value.',
				'forminator'
			),
			'facebook'                     => __( 'Facebook', 'forminator' ),
			'twitter'                      => __( 'Twitter', 'forminator' ),
			'google'                       => __( 'Google', 'forminator' ),
			'linkedin'                     => __( 'LinkedIn', 'forminator' ),
			'title_styles'                 => __( 'Title Appearance', 'forminator' ),
			'enable'                       => __( 'Enable', 'forminator' ),
			'checkbox_styles'              => __( 'Checkbox styles', 'forminator' ),
			'main'                         => __( 'Main', 'forminator' ),
			'header'                       => __( 'Header', 'forminator' ),
			'content'                      => __( 'Content', 'forminator' ),
			'quiz_design'                  => __( 'Quiz design', 'forminator' ),
			'quiz_design_description'      => __( "Choose a pre-made style for your quiz and further customize it's appearance.", 'forminator' ),
			'customize_quiz_colors'        => __( 'Customize quiz colors', 'forminator' ),
			'visual_style_description'     => __( 'There are two ways for displaying your quiz answers: grid or list.', 'forminator' ),
		);

		$data['quiz_details'] = array(
			'name'                => __( 'Quiz Name', 'forminator' ),
			'name_details'        => __( "This won't be displayed on your quiz, but will help you to identify it.", 'forminator' ),
			'name_validate'       => __( 'Quiz name cannot be empty! Please, pick a name for your quiz.', 'forminator' ),
			'title'               => __( 'Quiz Title', 'forminator' ),
			'title_details'       => __( 'This is the main title of your quiz and will be displayed on front.', 'forminator' ),
			'image'               => __( 'Featured image', 'forminator' ),
			'image_details'       => __( 'Add some nice main image to your quiz.', 'forminator' ),
			'description'         => __( 'Description', 'forminator' ),
			'description_details' => __( 'Give more information related to your quiz. This content will be displayed on front.' ),
		);

		$data['quiz_appearance'] = array(
			'answer'               => __( 'Answer', 'forminator' ),
			'checkbox'             => __( 'Checkbox', 'forminator' ),
			'container_border'     => __( 'Container border', 'forminator' ),
			'container_background' => __( 'Container background', 'forminator' ),
			'customize_main'       => __( 'Customize main colors', 'forminator' ),
			'customize_question'   => __( 'Customize question colors', 'forminator' ),
			'customize_answer'     => __( 'Customize answer colors', 'forminator' ),
			'customize_result'     => __( "Customize result's box colors", 'forminator' ),
			'customize_submit'     => __( 'Customize submit button colors', 'forminator' ),
			'main_container'       => __( 'Main container', 'forminator' ),
			'main_border'          => __( 'Main border', 'forminator' ),
			'main_styles'          => __( 'Main styles', 'forminator' ),
			'header_styles'        => __( 'Header styles', 'forminator' ),
			'content_styles'       => __( 'Content styles', 'forminator' ),
			'quiz_title'           => __( 'Quiz Title', 'forminator' ),
			'retake_button'        => __( 'Retake button', 'forminator' ),
			'result_title'         => __( 'Result title', 'forminator' ),
			'quiz_description'     => __( 'Quiz description', 'forminator' ),
			'result_description'   => __( 'Result description', 'forminator' ),
			'quiz_image'           => __( 'Quiz image', 'forminator' ),
			'question'             => __( 'Question', 'forminator' ),
			'answer_message'       => __( 'Answer message', 'forminator' ),
			'submit_button'        => __( 'Submit Button', 'forminator' ),
			'quiz_result'          => __( 'Quiz result', 'forminator' ),
			'social_share'         => __( 'Social share', 'forminator' ),
			'customize_colors'     => __( 'Customize colors', 'forminator' ),
			'customize_typography' => __( 'Customize typography', 'forminator' ),
			'checkbox_border'      => __( 'Checkbox border', 'forminator' ),
			'checkbox_background'  => __( 'Checkbox background', 'forminator' ),
			'checkbox_icon'        => __( 'Checkbox icon', 'forminator' ),
			'quiz_title_notice'    => __( "The quiz title appears on result's header.", 'forminator' ),
		);

		return $data;
	}

	/**
	 * Create quiz module
	 *
	 * @since 1.14
	 *
	 * @return no return
	 */
	public function create_module() {
		if ( ! $this->is_knowledge_wizard() && ! $this->is_nowrong_wizard() || self::is_edit() ) {
			return;
		}

		// Get module name.
		$name = Forminator_Core::sanitize_text_field( 'name' );

		// Get if quiz has leads.
		$has_leads = Forminator_Core::sanitize_text_field( 'leads', false );

		if ( $this->is_knowledge_wizard() ) {
			$quiz_type = 'knowledge';
		} else {
			$quiz_type = 'nowrong';
		}

		$status = Forminator_Quiz_Model::STATUS_DRAFT;

		$template            = new stdClass();
		$template->quiz_type = $quiz_type;
		$template->has_leads = $has_leads;

		$pagination = filter_input( INPUT_GET, 'pagination' );
		if ( ! empty( $pagination ) ) {
			$template->has_pagination = true;
		}

		$id = self::create( $name, $status, $template );

		$wizard_url = admin_url( 'admin.php?page=forminator-' . $quiz_type . '-wizard&id=' . $id );

		wp_safe_redirect( $wizard_url );
	}

	/**
	 * Create quiz
	 *
	 * @param string $name Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return int post ID
	 */
	public static function create( $name, $status, $template ) {
		$has_leads = ! empty( $template->has_leads );
		$quiz_type = ! empty( $template->quiz_type ) ? $template->quiz_type : '';
		// If we have leads, create leads form automatically.
		if ( $has_leads ) {
			$leads_id = self::create_leads_form( $name );
		}

		if ( 'knowledge' === $quiz_type ) {
			$settings = self::knowledge_default_data( $name, $has_leads );
		} else {
			$settings = self::nowrong_default_data( $name, $has_leads );
		}

		if ( $has_leads && ! empty( $leads_id ) ) {
			$settings['leadsId'] = $leads_id;
		}

		$model            = new Forminator_Quiz_Model();
		$model->quiz_type = $quiz_type;
		$model->results   = ! empty( $template->results )
			? $template->results : array();
		$model->questions = ! empty( $template->questions )
			? $template->questions : array();
		$model->name      = $name;
		$model->status    = $status;

		if ( ! empty( $template->settings ) ) {
			$settings = array_merge( $settings, $template->settings );
		}
		if ( ! empty( $template->has_pagination ) ) {
			$settings['pagination'] = 'true';
		}
		$model->settings = self::validate_settings( $settings );

		if ( $has_leads ) {
			if ( 'knowledge' === $quiz_type ) {
				$email_body = __( 'Hey {name-1},<br/><br/>Thanks for participating in {quiz_name} quiz.<br/><br/><b>{quiz_name}</b><br/>{quiz_answer}<br/><br/>Want to retake the quiz? Follow this link {embed_url}<br/><br/>---<br/><br/>This message was sent from {site_url}.', 'forminator' );
			} else {
				$email_body = __( 'Hey {name-1},<br/><br/>Thanks for participating in our {quiz_name} quiz.<br/><br/>Your scored {quiz_result} on this quiz and following are your answers:<br/>{quiz_answer}<br/><br/>Want to retake the quiz? Follow this link {embed_url}<br/><br/>---<br/><br/>This message was sent from {site_url}.', 'forminator' );
			}
			$model->notifications = array(
				array(
					'slug'             => 'notification-1234-4567',
					'label'            => 'Admin Notification',
					'email-recipients' => 'default',
					'recipients'       => get_option( 'admin_email' ),
					'email-subject'    => __( 'New Quiz Submission #{submission_id} for {quiz_name}', 'forminator' ),
					'email-editor'     => __( 'You have a new {quiz_type} quiz submission: <br/><br/>Lead details:<br/>{all_fields}<br/><br/>---<br/><br/>Quiz details: <br/>{quiz_result} <br/>{quiz_answer}<br/><br/>This message was sent from {site_url}.', 'forminator' ),
				),
				array(
					'slug'             => 'notification-4567-8765',
					'label'            => 'Participant\'s Notification',
					'email-recipients' => 'default',
					'recipients'       => '{email-1}',
					'email-subject'    => __( 'Your quiz result', 'forminator' ),
					'email-editor'     => $email_body,
				),
			);
		}

		// Save data.
		$id = $model->save();

		return $id;
	}

	/**
	 * Update quiz
	 *
	 * @param string $id Module ID.
	 * @param string $title Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return int post ID
	 */
	public static function update( $id, $title, $status, $template ) {
		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Quiz_Model();
			$action     = 'create';

			if ( empty( $status ) ) {
				$status = Forminator_Poll_Model::STATUS_PUBLISH;
			}
		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );
			$action     = 'update';

			if ( ! is_object( $form_model ) ) {
				return new WP_Error( __( "Quiz model doesn't exist", 'forminator' ) );
			}

			if ( empty( $status ) ) {
				$status = $form_model->status;
			}

			// we need to empty fields cause we will send new data.
			$form_model->clear_fields();
		}

		// Detect action.
		$form_model->quiz_type = 'knowledge';
		if ( ! empty( $template->type ) && 'forminator_save_quiz_nowrong' === $template->type ) {
			$form_model->quiz_type = 'nowrong';
		}

		$results = array();
		// Check if results exist.
		if ( isset( $template->results ) && is_array( $template->results ) ) {
			$results = forminator_sanitize_array_field( $template->results );
			foreach ( $template->results as $key => $result ) {
				$description = '';
				if ( isset( $result['description'] ) ) {
					$description = wp_kses_post( $result['description'] );
				}
				$results[ $key ]['description'] = $description;
			}

			$form_model->results = $results;
		}

		$questions = array();
		// Check if answers exist.
		if ( isset( $template->questions ) ) {
			$questions = $template->questions;

			// Check if questions exist.
			foreach ( $questions as &$question ) {
				$question['type'] = $form_model->quiz_type;
				if ( ! isset( $question['slug'] ) || empty( $question['slug'] ) ) {
					$question['slug'] = uniqid();
				}
			}
		}

		$form_model->name = sanitize_title( $title );

		// Handle quiz questions.
		$form_model->questions = $questions;

		$settings = isset( $template->settings ) ? $template->settings : array();

		$notifications = array();
		if ( isset( $template->notifications ) ) {
			$notifications = forminator_sanitize_array_field( $template->notifications );

			$count = 0;
			foreach ( $notifications as $notification ) {
				if ( isset( $notification['email-editor'] ) ) {
					$notifications[ $count ]['email-editor'] = wp_kses_post( $template->notifications[ $count ]['email-editor'] );
				}

				$count++;
			}
		}

		$form_model->settings      = self::validate_settings( $settings );
		$form_model->notifications = $notifications;
		$form_model->status        = $status;

		// Save data.
		$id = $form_model->save();

		$type = $form_model->quiz_type;

		/**
		 * Action called after quiz saved to database
		 *
		 * @since 1.11
		 *
		 * @param int    $id - quiz id.
		 * @param string $type - quiz type.
		 * @param string $status - quiz status.
		 * @param array  $questions - quiz questions.
		 * @param array  $results - quiz results.
		 */
		do_action( 'forminator_quiz_action_' . $action, $id, $type, $status, $questions, $results );

		Forminator_Render_Form::regenerate_css_file( $id );

		return $id;
	}

	/**
	 * Create leads form
	 *
	 * @since 1.14
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public static function create_leads_form( $name ) {
		$model = new Forminator_Form_Model();

		$name = $name . __( ' - Leads form', 'forminator' );

		$model->name          = $name;
		$model->notifications = array();

		$template = new Forminator_Template_Leads();

		// Setup template fields.
		foreach ( $template->fields() as $row ) {
			foreach ( $row['fields'] as $f ) {
				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['element_id'];
				unset( $f['element_id'] );
				$field->import( $f );
				$model->add_field( $field );
			}
		}

		$settings = $template->settings();

		// form name & version.
		$settings['formName'] = $name;
		$settings['version']  = FORMINATOR_VERSION;

		// settings.
		$model->settings = $settings;

		// status.
		$model->status = 'leads';

		// Save data.
		$id = $model->save();

		return $id;
	}

	/**
	 * Return quiz notifications
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Quiz_Model|null $quiz
	 *
	 * @return mixed
	 */
	public function get_quiz_notifications( $quiz ) {
		if ( ! isset( $quiz ) || ! isset( $quiz->notifications ) ) {
			return array(
				array(
					'slug'             => 'notification-1234-4567',
					'label'            => 'Admin Email',
					'email-recipients' => 'default',
					'recipients'       => get_option( 'admin_email' ),
					'email-subject'    => __( 'New Quiz Submission for {quiz_name}', 'forminator' ),
					'email-editor'     => __( 'You have a new quiz submission: <br/><br/>{quiz_answer}<br/><br/>Quiz results: <br/>{quiz_result} <br/>---<br/> This message was sent from {site_url}.', 'forminator' ),
				),
			);
		}

		return $quiz->notifications;
	}
}
