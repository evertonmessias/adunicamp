<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_L10n
 *
 * @since 1.0
 */
class Forminator_Admin_L10n {

	public $forminator = null;

	public function __construct() {
	}

	public function get_l10n_strings() {
		$l10n = $this->admin_l10n();

		$admin_locale = require_once forminator_plugin_dir() . 'admin/locale.php';

		$locale = array(
			'' => array(
				'localeSlug' => 'default',
			),
		);

		$l10n['locale'] = array_merge( $locale, (array) $admin_locale );

		return apply_filters( 'forminator_l10n', $l10n );
	}

	/**
	 * Default Admin properties
	 *
	 * @return array
	 */
	public function admin_l10n() {
		$properties = array(
			'popup'         => array(
				'form_name_label'         => __( 'Name your form', 'forminator' ),
				'form_name_placeholder'   => __( 'E.g., Contact Form', 'forminator' ),
				'name'                    => __( 'Name', 'forminator' ),
				'fields'                  => __( 'Fields', 'forminator' ),
				'date'                    => __( 'Date', 'forminator' ),
				'clear_all'               => __( 'Clear All', 'forminator' ),
				'your_exports'            => __( 'Your exports', 'forminator' ),
				'edit_login_form'         => __( 'Edit Login or Register form', 'forminator' ),
				'edit_scheduled_export'   => __( 'Edit Scheduled Export', 'forminator' ),
				'frequency'               => __( 'Frequency', 'forminator' ),
				'daily'                   => __( 'Daily', 'forminator' ),
				'weekly'                  => __( 'Weekly', 'forminator' ),
				'monthly'                 => __( 'Monthly', 'forminator' ),
				'week_day'                => __( 'Day of the week', 'forminator' ),
				'monday'                  => __( 'Monday', 'forminator' ),
				'tuesday'                 => __( 'Tuesday', 'forminator' ),
				'wednesday'               => __( 'Wednesday', 'forminator' ),
				'thursday'                => __( 'Thursday', 'forminator' ),
				'friday'                  => __( 'Friday', 'forminator' ),
				'saturday'                => __( 'Saturday', 'forminator' ),
				'sunday'                  => __( 'Sunday', 'forminator' ),
				'day_time'                => __( 'Time of the day', 'forminator' ),
				'email_to'                => __( 'Email export data to', 'forminator' ),
				'email_placeholder'       => __( 'E.g., john@doe.com', 'forminator' ),
				'schedule_help'           => __( "Leave blank if you don't want to receive exports via email.", 'forminator' ),
				'congratulations'         => __( 'Congratulations!', 'forminator' ),
				'is_ready'                => __( 'is ready!', 'forminator' ),
				'new_form_desc'           => __( 'Add it to any post / page by clicking Forminator button, or set it up as a Widget.', 'forminator' ),
				'paypal_settings'         => __( 'Edit PayPal credentials', 'forminator' ),
				'preview_cforms'          => __( 'Preview Custom Form', 'forminator' ),
				'preview_polls'           => __( 'Preview Poll', 'forminator' ),
				'preview_quizzes'         => __( 'Preview Quiz', 'forminator' ),
				'captcha_settings'        => __( 'Edit reCAPTCHA credentials', 'forminator' ),
				'currency_settings'       => __( 'Edit default currency', 'forminator' ),
				'pagination_entries'      => __( 'Submissions | Pagination Settings', 'forminator' ),
				'pagination_listings'     => __( 'Listings | Pagination Settings', 'forminator' ),
				'email_settings'          => __( 'Email Settings', 'forminator' ),
				'uninstall_settings'      => __( 'Uninstall Settings', 'forminator' ),
				'privacy_settings'        => __( 'Privacy Settings', 'forminator' ),
				'validate_form_name'      => __( 'Form name cannot be empty! Please pick a name for your form.', 'forminator' ),
				'close'                   => __( 'Close', 'forminator' ),
				'close_label'             => __( 'Close this dialog window', 'forminator' ),
				'go_back'                 => __( 'Go back', 'forminator' ),
				'records'                 => __( 'Records', 'forminator' ),
				'delete'                  => __( 'Delete', 'forminator' ),
				'confirm'                 => __( 'Confirm', 'forminator' ),
				'are_you_sure'            => __( 'Are you sure?', 'forminator' ),
				'cannot_be_reverted'      => __( 'Have in mind this action cannot be reverted.', 'forminator' ),
				'are_you_sure_form'       => __( 'Are you sure you wish to permanently delete this form?', 'forminator' ),
				'are_you_sure_poll'       => __( 'Are you sure you wish to permanently delete this poll?', 'forminator' ),
				'are_you_sure_quiz'       => __( 'Are you sure you wish to permanently delete this quiz?', 'forminator' ),
				'delete_form'             => __( 'Delete Form', 'forminator' ),
				'delete_poll'             => __( 'Delete Poll', 'forminator' ),
				'delete_quiz'             => __( 'Delete Quiz', 'forminator' ),
				'confirm_action'          => __( 'Please confirm that you want to do this action.', 'forminator' ),
				'confirm_title'           => __( 'Confirm Action', 'forminator' ),
				'confirm_field_delete'    => __( 'Please confirm that you want to delete this field', 'forminator' ),
				'cancel'                  => __( 'Cancel', 'forminator' ),
				'save_alert'              => __( 'The changes you made may be lost if you navigate away from this page.', 'forminator' ),
				'save_changes'            => __( 'Save Changes', 'forminator' ),
				'save'                    => __( 'Save', 'forminator' ),
				'export_form'             => __( 'Export Form', 'forminator' ),
				'export_poll'             => __( 'Export Poll', 'forminator' ),
				'export_quiz'             => __( 'Export Quiz', 'forminator' ),
				'import_form'             => __( 'Import Form', 'forminator' ),
				'import_form_cf7'         => __( 'Import', 'forminator' ),
				'import_form_ninja'       => __( 'Import Ninja Forms', 'forminator' ),
				'import_form_gravity'     => __( 'Import Gravity Forms', 'forminator' ),
				'import_poll'             => __( 'Import Poll', 'forminator' ),
				'import_quiz'             => __( 'Import Quiz', 'forminator' ),
				'enable_scheduled_export' => __( 'Enable scheduled exports', 'forminator' ),
				'scheduled_export_if_new' => __( 'Send email only if there are new submissions', 'forminator' ),
				'download_csv'            => __( 'Download CSV', 'forminator' ),
				'scheduled_exports'       => __( 'Scheduled Exports', 'forminator' ),
				'manual_exports'          => __( 'Manual Exports', 'forminator' ),
				'manual_description'      => __( 'Download the submissions list in .csv format.', 'forminator' ),
				'scheduled_description'   => __( 'Enable scheduled exports to get the submissions list in your email.', 'forminator' ),
				'disable'                 => __( 'Disable', 'forminator' ),
				'enable'                  => __( 'Enable', 'forminator' ),
				'enter_name'              => __( 'Enter a name', 'forminator' ),
				'new_form_desc2'          => __( 'Name your new form, then let\'s start building!', 'forminator' ),
				'new_poll_desc2'          => __( 'Name your new poll, then let\'s start building!', 'forminator' ),
				'new_quiz_desc2'          => __( 'Choose whether you want to collect participants details (e.g. name, email, etc.) on your quiz.' ),
				'learn_more'              => __( 'Learn more', 'forminator' ),
				'input_label'             => __( 'Input Label', 'forminator' ),
				'form_name_validation'    => __( 'Form name cannot be empty.', 'forminator' ),
				'poll_name_validation'    => __( 'Poll name cannot be empty.', 'forminator' ),
				'quiz_name_validation'    => __( 'Quiz name cannot be empty.', 'forminator' ),
				'new_form_placeholder'    => __( 'E.g., Blank Form', 'forminator' ),
				'new_poll_placeholder'    => __( 'E.g., Blank Poll', 'forminator' ),
				'new_quiz_placeholder'    => __( 'E.g., My Awesome Quiz', 'forminator' ),
				'create'                  => __( 'Create', 'forminator' ),
				'reset'                   => __( 'RESET', 'forminator' ),
				'disconnect'              => __( 'Disconnect', 'forminator' ),
				'apply_submission_filter' => __( 'Apply Submission Filters', 'forminator' ),
				'registration_notice'     => __( "This template allows you to create your own registration form and insert it on a custom page. This doesn't modify the default registration form.", 'forminator' ),
				'login_notice'            => __( "This template allows you to create your own login form and insert it on a custom page. This doesn't modify the default login form.", 'forminator' ),
				'approve_user'            => __( 'Approve', 'forminator' ),
				'registration_name'       => __( 'User Registration', 'forminator' ),
				'login_name'              => __( 'User Login', 'forminator' ),
				'deactivate'              => __( 'Deactivate', 'forminator' ),
				'deactivateContent'       => __( 'Are you sure you want to deactivate this Add-on?', 'forminator' ),
				'deactivateAnyway'        => __( 'Deactivate Anyway', 'forminator' ),
				'forms'                   => __( 'Forms', 'forminator' ),
			),
			'quiz'          => array(
				'choose_quiz_title'       => __( 'Create Quiz', 'forminator' ),
				'choose_quiz_description' => __( "Let's start by giving your quiz a name and choosing the appropriate quiz type based on your goal.", 'forminator' ),
				'quiz_name'               => __( 'Quiz Name', 'forminator' ),
				'quiz_type'               => __( 'Quiz Type', 'forminator' ),
				'collect_leads'           => __( 'Collect Leads', 'forminator' ),
				'no_pagination'           => __( 'No Pagination', 'forminator' ),
				'paginate_quiz'           => __( 'Paginated Quiz', 'forminator' ),
				'presentation'            => __( 'Presentation', 'forminator' ),
				'quiz_pagination'         => __( 'Quiz Presentation', 'forminator' ),
				'quiz_pagination_descr'   => __( 'How do you want the quiz questions to be presented to your users? You can break your quiz questions into pages, and display a number of questions at a time or show all questions at once.', 'forminator' ),
				'quiz_pagination_descr2'  => __( 'You can adjust this configuration at any time in the Behavior settings for your quiz.', 'forminator' ),
				'knowledge_label'         => __( 'Knowledge Quiz', 'forminator' ),
				'knowledge_description'   => __( 'Test the knowledge of your visitors on a subject and final score is calculated based on number of right answers. E.g., Test your music knowledge.', 'forminator' ),
				'nowrong_label'           => __( 'Personality Quiz', 'forminator' ),
				'nowrong_description'     => __( "Show different outcomes depending on the visitor's answers. There are no wrong answers. E.g., Which superhero are you?", 'forminator' ),
				'continue_button'         => __( 'Continue', 'forminator' ),
				'quiz_leads_toggle'       => __( 'Collect leads on your quiz', 'forminator' ),
				'create_quiz'             => __( 'Create Quiz', 'forminator' ),
				'quiz_leads_desc'         => __( 'We will automatically create a default lead generation form for you. The lead generation form uses the Forms module, and some of the settings are shared between this quiz and the leads form.' ),
			),
			'form'          => array(
				'form_template_title'       => __( 'Choose a template', 'forminator' ),
				'form_template_description' => __( 'Customize one of our pre-made form templates, or start from scratch.' ),
				'continue_button'           => __( 'Continue', 'forminator' ),
				'result'                    => __( 'result', 'forminator' ),
				'results'                   => __( 'results', 'forminator' ),
			),
			'sidebar'       => array(
				'label'         => __( 'Label', 'forminator' ),
				'value'         => __( 'Value', 'forminator' ),
				'add_option'    => __( 'Add Option', 'forminator' ),
				'delete'        => __( 'Delete', 'forminator' ),
				'pick_field'    => __( 'Pick a field', 'forminator' ),
				'field_will_be' => __( 'This field will be', 'forminator' ),
				'if'            => __( 'if', 'forminator' ),
				'shown'         => __( 'Shown', 'forminator' ),
				'hidden'        => __( 'Hidden', 'forminator' ),
			),
			'colors'        => array(
				'poll_shadow'       => __( 'Poll shadow', 'forminator' ),
				'title'             => __( 'Title text', 'forminator' ),
				'question'          => __( 'Question text', 'forminator' ),
				'answer'            => __( 'Answer text', 'forminator' ),
				'input_background'  => __( 'Input field bg', 'forminator' ),
				'input_border'      => __( 'Input field border', 'forminator' ),
				'input_placeholder' => __( 'Input field placeholder', 'forminator' ),
				'input_text'        => __( 'Input field text', 'forminator' ),
				'btn_background'    => __( 'Button background', 'forminator' ),
				'btn_text'          => __( 'Button text', 'forminator' ),
				'link_res'          => __( 'Results link', 'forminator' ),
			),
			'options'       => array(
				'browse'                => __( 'Browse', 'forminator' ),
				'clear'                 => __( 'Clear', 'forminator' ),
				'no_results'            => __( "You don't have any results yet.", 'forminator' ),
				'select_result'         => __( 'Select result', 'forminator' ),
				'no_answers'            => __( "You don't have any answer yet.", 'forminator' ),
				'placeholder_image'     => __( 'Click browse to add image...', 'forminator' ),
				'placeholder_image_alt' => __( 'Click on browse to add an image', 'forminator' ),
				'placeholder_answer'    => __( 'Add an answer here', 'forminator' ),
				'multiqs_empty'         => __( "You don't have any questions yet.", 'forminator' ),
				'add_question'          => __( 'Add Question', 'forminator' ),
				'add_new_question'      => __( 'Add New Question', 'forminator' ),
				'question_title'        => __( 'Question title', 'forminator' ),
				'question_title_error'  => __( 'Question title cannot be empty! Please, add some content to your question.', 'forminator' ),
				'answers'               => __( 'Answers', 'forminator' ),
				'add_answer'            => __( 'Add Answer', 'forminator' ),
				'add_new_answer'        => __( 'Add New Answer', 'forminator' ),
				'add_result'            => __( 'Add Result', 'forminator' ),
				'delete_result'         => __( 'Delete Result', 'forminator' ),
				'title'                 => __( 'Title', 'forminator' ),
				'image'                 => __( 'Image (optional)', 'forminator' ),
				'description'           => __( 'Description', 'forminator' ),
				'trash_answer'          => __( 'Delete this answer', 'forminator' ),
				'correct'               => __( 'Correct answer', 'forminator' ),
				'no_options'            => __( "You don't have any options yet.", 'forminator' ),
				'delete'                => __( 'Delete', 'forminator' ),
				'restricted_dates'      => __( 'Restricted dates:', 'forminator' ),
				'add'                   => __( 'Add', 'forminator' ),
				'custom_date'           => __( 'Pick custom date(s) to restrict:', 'forminator' ),
				'form_data'             => __( 'Form Data', 'forminator' ),
				'required_form_fields'  => __( 'Required Fields', 'forminator' ),
				'optional_form_fields'  => __( 'Optional Fields', 'forminator' ),
				'all_fields'            => __( 'All Submitted Fields', 'forminator' ),
				'form_name'             => __( 'Form Name', 'forminator' ),
				'misc_data'             => __( 'Misc Data', 'forminator' ),
				'form_based_data'       => __( 'Add form data', 'forminator' ),
				'been_saved'            => __( 'has been saved.', 'forminator' ),
				'been_published'        => __( 'has been published.', 'forminator' ),
				'error_saving'          => __( 'Error! Form cannot be saved.' ),
				'default_value'         => __( 'Default Value', 'forminator' ),
				'admin_email'           => get_option( 'admin_email' ),
				'delete_question'       => __( 'Delete this question', 'forminator' ),
				'remove_image'          => __( 'Remove image', 'forminator' ),
				'answer_settings'       => __( 'Show extra settings', 'forminator' ),
				'add_new_result'        => __( 'Add New Result', 'forminator' ),
				'multiorder_validation' => __( 'You need to add at least one result for this quiz so you can re-order the results priority.', 'forminator' ),
				'user_ip_address'       => __( 'User IP Address', 'forminator' ),
				'date'                  => __( 'Date', 'forminator' ),
				'embed_id'              => __( 'Embed Post/Page ID', 'forminator' ),
				'embed_title'           => __( 'Embed Post/Page Title', 'forminator' ),
				'embed_url'             => __( 'Embed URL', 'forminator' ),
				'user_agent'            => __( 'HTTP User Agent', 'forminator' ),
				'refer_url'             => __( 'HTTP Refer URL', 'forminator' ),
				'display_name'          => __( 'User Display Name', 'forminator' ),
				'user_email'            => __( 'User Email', 'forminator' ),
				'user_login'            => __( 'User Login', 'forminator' ),
				'shortcode_copied'      => __( 'Shortcode has been copied successfully.', 'forminator' ),
				'uri_copied'            => __( 'URI has been copied successfully.', 'forminator' ),
			),
			'commons'       => array(
				'color'                          => __( 'Color', 'forminator' ),
				'colors'                         => __( 'Colors', 'forminator' ),
				'border_color'                   => __( 'Border color', 'forminator' ),
				'border_color_hover'             => __( 'Border color (hover)', 'forminator' ),
				'border_color_active'            => __( 'Border color (active)', 'forminator' ),
				'border_color_correct'           => __( 'Border color (correct)', 'forminator' ),
				'border_color_incorrect'         => __( 'Border color (incorrect)', 'forminator' ),
				'border_width'                   => __( 'Border width', 'forminator' ),
				'border_style'                   => __( 'Border style', 'forminator' ),
				'background'                     => __( 'Background', 'forminator' ),
				'background_hover'               => __( 'Background (hover)', 'forminator' ),
				'background_active'              => __( 'Background (active)', 'forminator' ),
				'background_correct'             => __( 'Background (correct)', 'forminator' ),
				'background_incorrect'           => __( 'Background (incorrect)', 'forminator' ),
				'font_color'                     => __( 'Font color', 'forminator' ),
				'font_color_hover'               => __( 'Font color (hover)', 'forminator' ),
				'font_color_active'              => __( 'Font color (active)', 'forminator' ),
				'font_color_correct'             => __( 'Font color (correct)', 'forminator' ),
				'font_color_incorrect'           => __( 'Font color (incorrect)', 'forminator' ),
				'font_background'                => __( 'Font background', 'forminator' ),
				'font_background'                => __( 'Font background (hover)', 'forminator' ),
				'font_background_active'         => __( 'Font background (active)', 'forminator' ),
				'font_family'                    => __( 'Font family', 'forminator' ),
				'font_family_custom'             => __( 'Custom font family', 'forminator' ),
				'font_family_placeholder'        => __( "E.g., 'Arial', sans-serif", 'forminator' ),
				'font_family_custom_description' => __( 'Here you can type the font family you want to use, as you would in CSS.', 'forminator' ),
				'icon_size'                      => __( 'Icon size', 'forminator' ),
				'enable'                         => __( 'Enable', 'forminator' ),
				'dropdown'                       => __( 'Dropdown', 'forminator' ),
				'appearance'                     => __( 'Appearance', 'forminator' ),
				'expand'                         => __( 'Expand', 'forminator' ),
				'placeholder'                    => __( 'Placeholder', 'forminator' ),
				'preview'                        => __( 'Preview', 'forminator' ),
				'icon_color'                     => __( 'Icon color', 'forminator' ),
				'icon_color_hover'               => __( 'Icon color (hover)', 'forminator' ),
				'icon_color_active'              => __( 'Icon color (active)', 'forminator' ),
				'icon_color_correct'             => __( 'Icon color (correct)', 'forminator' ),
				'icon_color_incorrect'           => __( 'Icon color (incorrect)', 'forminator' ),
				'box_shadow'                     => __( 'Box shadow', 'forminator' ),
				'enable_settings'                => __( 'Enable settings', 'forminator' ),
				'font_size'                      => __( 'Font size', 'forminator' ),
				'font_weight'                    => __( 'Font weight', 'forminator' ),
				'text_align'                     => __( 'Text align', 'forminator' ),
				'regular'                        => __( 'Regular', 'forminator' ),
				'medium'                         => __( 'Medium', 'forminator' ),
				'large'                          => __( 'Large', 'forminator' ),
				'light'                          => __( 'Light', 'forminator' ),
				'normal'                         => __( 'Normal', 'forminator' ),
				'bold'                           => __( 'Bold', 'forminator' ),
				'typography'                     => __( 'Typography', 'forminator' ),
				'padding_top'                    => __( 'Top padding', 'forminator' ),
				'padding_right'                  => __( 'Right padding', 'forminator' ),
				'padding_bottom'                 => __( 'Bottom padding', 'forminator' ),
				'padding_left'                   => __( 'Left padding', 'forminator' ),
				'border_radius'                  => __( 'Border radius', 'forminator' ),
				'date_placeholder'               => __( '20 April 2018', 'forminator' ),
				'left'                           => __( 'Left', 'forminator' ),
				'center'                         => __( 'Center', 'forminator' ),
				'right'                          => __( 'Right', 'forminator' ),
				'none'                           => __( 'None', 'forminator' ),
				'solid'                          => __( 'Solid', 'forminator' ),
				'dashed'                         => __( 'Dashed', 'forminator' ),
				'dotted'                         => __( 'Dotted', 'forminator' ),
				'delete_option'                  => __( 'Delete option', 'forminator' ),
				'label'                          => __( 'Label', 'forminator' ),
				'value'                          => __( 'Value', 'forminator' ),
				'reorder_option'                 => __( 'Re-order this option', 'forminator' ),
				'forminator_ui'                  => __( 'Forminator UI', 'forminator' ),
				'forminator_bold'                => __( 'Forminator Bold', 'forminator' ),
				'forminator_flat'                => __( 'Forminator Flat', 'forminator' ),
				'material_design'                => __( 'Material Design', 'forminator' ),
				'no_file_chosen'                 => __( 'No file chosen', 'forminator' ),
				'update_successfully'            => __( 'saved succesfully!', 'forminator' ),
				'update_unsuccessfull'           => __( 'Error! Settings were not saved.', 'forminator' ),
				'approve_user_successfull'       => __( 'User approved succesfully.', 'forminator' ),
				'error_message'                  => __( 'Something went wrong!', 'forminator' ),
				'approve_user_unsuccessfull'     => __( 'Error! User was not approved.', 'forminator' ),
			),
			'social'        => array(
				'facebook'    => __( 'Facebook', 'forminator' ),
				'twitter'     => __( 'Twitter', 'forminator' ),
				'google_plus' => __( 'Google+', 'forminator' ),
				'linkedin'    => __( 'LinkedIn', 'forminator' ),
			),
			'calendar'      => array(
				'day_names_min' => array(
					esc_html__( 'Su', 'forminator' ),
					esc_html__( 'Mo', 'forminator' ),
					esc_html__( 'Tu', 'forminator' ),
					esc_html__( 'We', 'forminator' ),
					esc_html__( 'Th', 'forminator' ),
					esc_html__( 'Fr', 'forminator' ),
					esc_html__( 'Sa', 'forminator' ),
				),
				'month_names'   => array(
					esc_html__( 'January', 'forminator' ),
					esc_html__( 'February', 'forminator' ),
					esc_html__( 'March', 'forminator' ),
					esc_html__( 'April', 'forminator' ),
					esc_html__( 'May', 'forminator' ),
					esc_html__( 'June', 'forminator' ),
					esc_html__( 'July', 'forminator' ),
					esc_html__( 'August', 'forminator' ),
					esc_html__( 'September', 'forminator' ),
					esc_html__( 'October', 'forminator' ),
					esc_html__( 'November', 'forminator' ),
					esc_html__( 'December', 'forminator' ),
				),
				'day_names_min' => self::get_short_days_names(),
				'month_names'   => self::get_months_names(),
			),
			'exporter'      => array(
				'export_nonce' => wp_create_nonce( 'forminator_export' ),
				'form_id'      => forminator_get_form_id_helper(),
				'form_type'    => forminator_get_form_type_helper(),
				'enabled'      => filter_var( forminator_get_exporter_info( 'enabled', forminator_get_form_id_helper() . forminator_get_form_type_helper() ), FILTER_VALIDATE_BOOLEAN ),
				'interval'     => forminator_get_exporter_info( 'interval', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'month_day'    => forminator_get_exporter_info( 'month_day', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'day'          => forminator_get_exporter_info( 'day', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'hour'         => forminator_get_exporter_info( 'hour', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'email'        => forminator_get_exporter_info( 'email', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'if_new'       => forminator_get_exporter_info( 'if_new', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'noResults'    => esc_html__( 'No Result Found', 'forminator' ),
				'searching'    => esc_html__( 'Searching', 'forminator' ),
			),
			'exporter_logs' => forminator_get_export_logs( forminator_get_form_id_helper() ),
		);

		$properties = self::add_notice( $properties );

		return $properties;
	}

	/**
	 * Maybe add notices to properties
	 *
	 * @param array $properties Properties.
	 * @return array
	 */
	private static function add_notice( $properties ) {
		$key = filter_input( INPUT_GET, 'forminator_notice' );
		if ( $key ) {
			$notices = self::get_notices_list();
			if ( ! empty( $notices[ $key ] ) ) {
				$properties['notices'][] = $notices[ $key ];
			}
		}

		$text_notice = filter_input( INPUT_GET, 'forminator_text_notice' );
		if ( $text_notice ) {
			$properties['notices']['custom_notice'] = $text_notice;
		}

		return $properties;
	}

	/**
	 * All possible notices that can be shown after refreshing page
	 *
	 * @return array
	 */
	private static function get_notices_list() {
		$list = array(
			'settings_reset'  => __( 'Data and settings have been reset successfully!', 'forminator' ),
			'form_deleted'    => __( 'Form successfully deleted.', 'forminator' ),
			'poll_deleted'    => __( 'Poll successfully deleted.', 'forminator' ),
			'quiz_deleted'    => __( 'Quiz successfully deleted.', 'forminator' ),
			'form_duplicated' => __( 'Form successfully duplicated.', 'forminator' ),
			'poll_duplicated' => __( 'Poll successfully duplicated.', 'forminator' ),
			'quiz_duplicated' => __( 'Quiz successfully duplicated.', 'forminator' ),
			'form_reset'      => __( 'Form tracking data successfully reset.', 'forminator' ),
			'poll_reset'      => __( 'Poll tracking data successfully reset.', 'forminator' ),
			'quiz_reset'      => __( 'Quiz tracking data successfully reset.', 'forminator' ),
			'preset_deleted'  => __( 'The selected preset has been successfully deleted.', 'forminator' ),
		);

		return $list;
	}

	/**
	 * Get short days names html escaped and translated
	 *
	 * @since 1.5.4
	 * @return array
	 */
	public static function get_short_days_names() {
		return array(
			esc_html__( 'Su', 'forminator' ),
			esc_html__( 'Mo', 'forminator' ),
			esc_html__( 'Tu', 'forminator' ),
			esc_html__( 'We', 'forminator' ),
			esc_html__( 'Th', 'forminator' ),
			esc_html__( 'Fr', 'forminator' ),
			esc_html__( 'Sa', 'forminator' ),
		);
	}

	/**
	 * Get months names html escaped and translated
	 *
	 * @since 1.5.4
	 * @return array
	 */
	public static function get_months_names() {
		return array(
			esc_html__( 'January', 'forminator' ),
			esc_html__( 'February', 'forminator' ),
			esc_html__( 'March', 'forminator' ),
			esc_html__( 'April', 'forminator' ),
			esc_html__( 'May', 'forminator' ),
			esc_html__( 'June', 'forminator' ),
			esc_html__( 'July', 'forminator' ),
			esc_html__( 'August', 'forminator' ),
			esc_html__( 'September', 'forminator' ),
			esc_html__( 'October', 'forminator' ),
			esc_html__( 'November', 'forminator' ),
			esc_html__( 'December', 'forminator' ),
		);

	}

}
