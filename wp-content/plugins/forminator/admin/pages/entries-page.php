<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Entries_Page
 *
 * @since 1.0.5
 */
class Forminator_Entries_Page extends Forminator_Admin_Page {

	/**
	 * Available Modules
	 *
	 * @since 1.0.5
	 * @var array
	 */
	private $modules = array();

	/**
	 * Merged default parameter with superglobal REQUEST
	 *
	 * @since 1.0.5
	 * @var array
	 */
	private $screen_params = array();

	/**
	 * HTML representative of entries page
	 *
	 * @since 1.0.5
	 * @var string
	 */
	private $entries_page = '';

	/**
	 * Current Form Model of requested entries
	 *
	 * @since 1.0.5
	 * @var null|Forminator_Base_Form_Model
	 */
	private $form_model = null;

	/**
	 * Populating Modules that available on Plugin
	 *
	 * @since 1.0.5
	 */
	public function populate_modules() {
		$modules[] = array(
			'name'  => __( 'Forms', 'forminator' ),
			'model' => Forminator_Form_Model::model(),
		);
		$modules[] = array(
			'name'  => __( 'Polls', 'forminator' ),
			'model' => Forminator_Poll_Model::model(),
		);
		$modules[] = array(
			'name'  => __( 'Quizzes', 'forminator' ),
			'model' => Forminator_Quiz_Model::model(),
		);

		$this->modules = apply_filters( 'forminator_entries_page_modules', $modules );
	}

	/**
	 * Populating Current Page Parameters
	 *
	 * @since 1.0.5
	 */
	public function populate_screen_params() {
		$this->screen_params = array(
			'form_type' => Forminator_Core::sanitize_text_field( 'form_type', 'forminator_forms' ),
			'form_id'   => Forminator_Core::sanitize_text_field( 'form_id', 0 ),
		);
	}

	/**
	 * Executed Action before render the page
	 *
	 * @since 1.0.5
	 */
	public function before_render() {
		$this->populate_screen_params();
		$this->populate_modules();
		$this->prepare_entries_page();
		$this->enqueue_entries_scripts();
	}

	/**
	 * Get Form types based on available modules
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_form_types() {
		$form_types = array();
		foreach ( $this->modules as $module ) {
			/** @var Forminator_Base_Form_Model $model */
			$model = $module['model'];
			$name  = $module['name'];

			$form_types[ $model->get_post_type() ] = $name;
		}

		return apply_filters( 'forminator_entries_page_modules', $form_types );
	}

	/**
	 * Prepare Entries Page
	 *
	 * @since 1.0.5
	 */
	private function prepare_entries_page() {
		$this->form_model = $this->get_form_model();
		// Form not found.
		if ( ! $this->form_model instanceof Forminator_Base_Form_Model ) {
			// if form_id available remove it from request, and redirect.
			if ( $this->get_current_form_id() ) {
				$url = remove_query_arg( 'form_id' );
				if ( wp_safe_redirect( $url ) ) {
					exit;
				}
			}
		} else {
			switch ( $this->get_current_form_type() ) {
				case Forminator_Form_Model::model()->get_post_type():
					$entries_renderer = new Forminator_CForm_Renderer_Entries( 'custom-form/entries' );
					break;
				case Forminator_Poll_Model::model()->get_post_type():
					$entries_renderer = new Forminator_Poll_Renderer_Entries( 'poll/entries' );
					break;
				case Forminator_Quiz_Model::model()->get_post_type():
					$entries_renderer = new Forminator_Quiz_Renderer_Entries( 'quiz/entries' );
					break;
				default:
					$entries_renderer = null;
					break;
			}

			if ( $entries_renderer instanceof Forminator_Admin_Page ) {
				ob_start();
				// render the entries page.
				$entries_renderer->render_page_content();
				$this->entries_page = ob_get_clean();
			}
		}
	}

	/**
	 * Get Form Model if current requested form_id available and matched form_type
	 *
	 * @since 1.0.5
	 *
	 * @return bool|Forminator_Base_Form_Model|null
	 */
	private function get_form_model() {
		if ( $this->get_current_form_id() ) {
			$form_model = forminator_get_model_from_id( $this->get_current_form_id() );
			if ( ! $form_model instanceof Forminator_Base_Form_Model ) {
				return null;
			}
			if ( $form_model->get_post_type() !== $this->get_current_form_type() ) {
				return null;
			}

			return $form_model;
		}

		return null;
	}

	/**
	 * Return rendered entries page
	 *
	 * @since 1.0.5
	 *
	 * @return string
	 */
	public function render_entries() {
		return $this->entries_page;
	}

	/**
	 * Render Form switcher / select based on current form_type
	 *
	 * @since 1.0.5
	 */
	public static function render_form_switcher( $form_type = 'forminator_forms', $form_id = 0 ) {
		$classes = 'sui-select';
		// Using this method for Create Appearance Preset.
		if ( 0 !== $form_id ) {
			$classes .= ' sui-select-sm sui-select-inline';
		}

		$empty_option = __( 'Choose Form', 'forminator' );
		$method       = 'get_forms';
		$model       = 'Forminator_Form_Model';

		if ( $form_type === Forminator_Poll_Model::model()->get_post_type() ) {
			$empty_option = __( 'Choose Poll', 'forminator' );
			$method       = 'get_polls';
			$model       = 'Forminator_Poll_Model';
		} elseif ( $form_type === Forminator_Quiz_Model::model()->get_post_type() ) {
			$empty_option = __( 'Choose Quiz', 'forminator' );
			$method       = 'get_quizzes';
			$model       = 'Forminator_Quiz_Model';
		}

		echo '<select name="form_id" data-allow-search="1" data-minimum-results-for-search="0" class="' . esc_attr( $classes ) . '" data-search="true" data-search="true" data-placeholder="' . esc_attr( $empty_option ) . '">';
		echo '<option><option>';

		$forms = Forminator_API::$method( null, 1, 999, $model::STATUS_PUBLISH );
		$forms = apply_filters( 'forminator_entries_get_forms', $forms, $form_type );

		foreach ( $forms as $form ) {
			/**@var Forminator_Base_Form_Model $form */
			$title = ! empty( $form->settings['formName'] ) ? $form->settings['formName'] : $form->raw->post_title;
			echo '<option value="' . esc_attr( $form->id ) . '" ' . selected( $form->id, $form_id, false ) . '>' . esc_html( $title ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Get current form type
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_current_form_type() {
		return $this->screen_params['form_type'];
	}

	/**
	 * Get current form id
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_current_form_id() {
		return $this->screen_params['form_id'];
	}

	/**
	 * Custom scripts that only used on submissions page
	 *
	 * @since 1.5.4
	 */
	public function enqueue_entries_scripts() {
		wp_enqueue_script(
			'forminator-entries-datepicker-range',
			forminator_plugin_url() . 'assets/js/library/daterangepicker.min.js',
			array( 'moment' ),
			'3.0.3',
			true
		);

		wp_enqueue_script(
			'forminator-inputmask',
			forminator_plugin_url() . 'assets/js/library/inputmask.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // inputmask.

		wp_enqueue_script(
			'forminator-jquery-inputmask',
			forminator_plugin_url() . 'assets/js/library/jquery.inputmask.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // jquery inputmask.

		wp_enqueue_script(
			'forminator-inputmask-binding',
			forminator_plugin_url() . 'assets/js/library/inputmask.binding.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // inputmask binding.

		// TODO - Deprecation warning fix: Need to add value to moment() in ISO or RFC format.
		// use inline script to allow hooking into this.
		$daterangepicker_ranges
			= sprintf(
				"
			var forminator_entries_datepicker_ranges = {
				'%s': [moment(), moment()],
		        '%s': [moment().subtract(1,'days'), moment().subtract(1,'days')],
		        '%s': [moment().subtract(6,'days'), moment()],
		        '%s': [moment().subtract(29,'days'), moment()],
		        '%s': [moment().startOf('month'), moment().endOf('month')],
		        '%s': [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
			};",
				__( 'Today', 'forminator' ),
				__( 'Yesterday', 'forminator' ),
				__( 'Last 7 Days', 'forminator' ),
				__( 'Last 30 Days', 'forminator' ),
				__( 'This Month', 'forminator' ),
				__( 'Last Month', 'forminator' )
			);

		/**
		 * Filter ranges to be used on submissions date range
		 *
		 * @since 1.5.4
		 *
		 * @param string $daterangepicker_ranges
		 */
		$daterangepicker_ranges = apply_filters( 'forminator_entries_datepicker_ranges', $daterangepicker_ranges );

		wp_add_inline_script( 'forminator-entries-datepicker-range', $daterangepicker_ranges );

		add_filter( 'forminator_l10n', array( $this, 'add_l10n' ) );

	}

	/**
	 * Hook into forminator_l10n
	 *
	 * Allow to modify `daterangepicker` locale
	 *
	 * @param $l10n
	 *
	 * @return mixed
	 */
	public function add_l10n( $l10n ) {
		$daterangepicker_lang = array(
			'daysOfWeek' => Forminator_Admin_L10n::get_short_days_names(),
			'monthNames' => Forminator_Admin_L10n::get_months_names(),
		);

		/**
		 * Filter daterangepicker locale to be used
		 *
		 * @since 1.5.4
		 *
		 * @param array $daterangepicker_lang
		 */
		$daterangepicker_lang    = apply_filters( 'forminator_l10n_daterangepicker', $daterangepicker_lang );
		$l10n['daterangepicker'] = $daterangepicker_lang;

		return $l10n;
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.11
	 *
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}
}
