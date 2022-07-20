<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Module_Edit_Page
 *
 * @since 1.14.10
 */
abstract class Forminator_Admin_Module_Edit_Page extends Forminator_Admin_Page {

	/**
	 * Page number
	 *
	 * @var int
	 */
	protected static $page_number = 1;

	/**
	 * Initialize
	 *
	 * @since 1.0
	 */
	public function init() {
		$pagenum           = absint( Forminator_Core::sanitize_text_field( 'paged' ) );
		self::$page_number = max( 1, $pagenum );
		$this->processRequest();
	}

	/**
	 * Trigger before render
	 */
	public function before_render() {
		wp_enqueue_script( 'forminator-chart', forminator_plugin_url() . 'assets/js/library/Chart.bundle.min.js', array( 'jquery' ), '2.9.4', false );
	}

	/**
	 * Count modules
	 *
	 * @param string $status Modules status.
	 * @since 1.0
	 * @return int
	 */
	public function countModules( $status = '' ) {
		$class_name = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
		return $class_name::model()->count_all( $status );
	}

	/**
	 * Get modules
	 *
	 * @since 1.0
	 * @return array
	 */
	public function getModules() {
		$modules = array();
		$limit   = null;
		$const   = 'FORMINATOR_' . strtoupper( static::$module_slug ) . '_LIST_LIMIT';
		if ( defined( $const ) && constant( $const ) ) {
			$limit = constant( $const );
		}

		$data      = self::get_models( $limit );
		$form_view = Forminator_Form_Views_Model::get_instance();

		// Fallback.
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		foreach ( $data['models'] as $model ) {
			$form_name = $model->name;
			if ( isset( $model->settings['formName'] ) && ! empty( $model->settings['formName'] ) ) {
				$form_name = $model->settings['formName'];
			}

			$modules[] = static::module_array(
				$model->id,
				$form_name,
				$form_view->count_views( $model->id ),
				date( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
				$model->status,
				$model
			);
		}

		return $modules;
	}

	/**
	 * Get modules for search
	 *
	 * @since 1.14.12
	 * @return array
	 */
	public static function get_searched_modules( $search_keyword = null ) {
		$modules = array();
		$data    = self::get_models( -1 );

		// Fallback.
		if ( ! isset( $data['models'] ) || empty( $data['models'] ) ) {
			return $modules;
		}

		// Search.
		if ( ! is_null( $search_keyword ) ) {

			$search_keyword = explode( ' ', $search_keyword );
			$form_view      = Forminator_Form_Views_Model::get_instance();
			$module_slug    = self::get_slug_ajax( true );

			$class_name = 'Forminator_' . $module_slug . '_Page';

			foreach ( $data['models'] as $model ) {

				foreach ( $search_keyword as $keyword ) {

					// If found.
					if ( false !== stripos( $model->settings['formName'], $keyword ) ) {

						$modules[] = $class_name::module_array(
							$model->id,
							$model->settings['formName'],
							$form_view->count_views( $model->id ),
							date( get_option( 'date_format' ), strtotime( $model->raw->post_date ) ),
							$model->status,
							$model
						);
						// prevent duplicates.
						break;
					}
				}
			}
		}

		return $modules;
	}

	/**
	 * Get slug for ajax search
	 *
	 * @since 1.14.12
	 * @return array
	 */
	public static function get_slug_ajax( $class = false ) {
		$page = filter_input( INPUT_POST, 'page' );
		if ( ! $page ) {
			return '';
		}

		switch ( $page ) {
			case 'forminator-poll':
				$module_slug = $class ? 'Poll' : 'poll';
				break;
			case 'forminator-quiz':
				$module_slug = $class ? 'Quiz' : 'quiz';
				break;
			default:
				$module_slug = $class ? 'CForm' : 'form';
				break;
		}

		return $module_slug;
	}

	/**
	 * Return module array
	 *
	 * @since 1.14.12
	 *
	 * @param $id
	 * @param $title
	 * @param $views
	 * @param $date
	 * @param $status
	 *
	 * @return array
	 */
	// protected static function module_array( $id, $title, $views, $date, $status, $model ) {}.

	/**
	 * Show the modules
	 *
	 * @since 1.14.12
	 * @return array
	 */
	public static function show_modules( $modules, $module_slug, $preview_dialog, $preview_title, $export_dialog, $post_type, $soon, $sql_month_start_date, $wizard_page, $search_keyword = null ) {

		if ( empty( $modules ) ) {
			$is_search = true;
			require_once forminator_plugin_dir() . 'admin/views/common/list/empty_content.php';
		}

		$page = $module_slug;
		if ( 'form' === $page ) {
			$page = 'cform';
		}

		foreach ( $modules as $module ) {
			$module_entries_from_last_month = 0 !== $module['entries'] ? count( Forminator_Form_Entry_Model::get_newer_entry_ids_of_form_id( $module['id'], $sql_month_start_date ) ) : 0;
			$opened_class                   = '';
			$opened_chart                   = '';
			$has_leads                      = isset( $module['has_leads'] ) ? $module['has_leads'] : false;
			$leads_id                       = isset( $module['leads_id'] ) ? $module['leads_id'] : 0;

			if ( ! is_null( $wizard_page ) && ! isset( $module['type'] ) ) {
				$edit_url = admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $module['id'] );
			} else {
				// For quizzes.
				$edit_url = admin_url( 'admin.php?page=forminator-' . ( 'nowrong' === $module['type'] ? $module['type'] : 'knowledge' ) . '-wizard&id=' . $module['id'] );
			}

			$view_stats = filter_input( INPUT_GET, 'view-stats', FILTER_VALIDATE_INT );
			if ( $view_stats && intval( $module['id'] ) === $view_stats ) {
				$opened_class = ' sui-accordion-item--open forminator-scroll-to';
				$opened_chart = ' sui-chartjs-loaded';
			}
			?>

			<div class="sui-accordion-item<?php echo esc_attr( $opened_class ); ?>">

				<div class="sui-accordion-item-header">

					<div class="sui-accordion-item-title sui-trim-title">

						<label for="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" class="sui-checkbox sui-accordion-item-action">
							<input type="checkbox" id="wpf-module-<?php echo esc_attr( $module['id'] ); ?>" value="<?php echo esc_html( $module['id'] ); ?>">
							<span aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Select this module', 'forminator' ); ?></span>
						</label>

						<span class="sui-trim-text"><?php echo esc_html( htmlspecialchars( forminator_get_form_name( $module['id'] ) ) ); ?></span>

						<?php
						if ( 'publish' === $module['status'] ) {
							echo '<span class="sui-tag sui-tag-blue">' . esc_html__( 'Published', 'forminator' ) . '</span>';
						}
						?>

						<?php
						if ( 'draft' === $module['status'] ) {
							echo '<span class="sui-tag">' . esc_html__( 'Draft', 'forminator' ) . '</span>';
						}
						?>

					</div>

					<div class="sui-accordion-item-date"><strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong> <?php echo esc_html( $module['last_entry_time'] ); ?></div>

					<div class="sui-accordion-col-auto">

						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="sui-button sui-button-ghost sui-accordion-item-action sui-desktop-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit', 'forminator' ); ?>
						</a>

						<a href="<?php echo esc_url( $edit_url ); ?>"
							class="sui-button-icon sui-accordion-item-action sui-mobile-visible">
							<i class="sui-icon-pencil" aria-hidden="true"></i>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Edit', 'forminator' ); ?></span>
						</a>

						<div class="sui-dropdown sui-accordion-item-action<?php echo $soon ? ' fui-dropdown-soon' : ''; ?>">

							<button class="sui-button-icon sui-dropdown-anchor">
								<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Open list settings', 'forminator' ); ?></span>
							</button>

							<ul class="module-actions">

								<li><a href="#"
									class="wpmudev-open-modal"
									data-modal="<?php echo esc_attr( $preview_dialog ); ?>"
									data-modal-title="<?php printf( '%s - %s', esc_html( $preview_title ), esc_attr( htmlspecialchars( htmlspecialchars( forminator_get_form_name( $module['id'] ) ) ) ) ); ?>"
									data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
									data-has-leads="<?php echo esc_attr( $has_leads ); ?>"
									data-leads-id="<?php echo esc_attr( $leads_id ); ?>"
									data-nonce-preview="<?php echo esc_attr( wp_create_nonce( 'forminator_load_module' ) ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_' . $preview_dialog ) ); ?>">
									<i class="sui-icon-eye" aria-hidden="true"></i> <?php esc_html_e( 'Preview', 'forminator' ); ?>
								</a></li>

								<li>
									<button class="copy-clipboard" data-shortcode='[forminator_<?php echo esc_attr( $module_slug ); ?> id="<?php echo esc_attr( $module['id'] ); ?>"]'><i class="sui-icon-code" aria-hidden="true"></i> <?php esc_html_e( 'Copy Shortcode', 'forminator' ); ?></button>
								</li>

								<li>
									<form method="post">
										<input type="hidden" name="forminator_action" value="update-status">
										<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
										<input type="hidden" name="msearch" value="" />

										<?php if ( 'publish' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="draft"/>
										<?php elseif ( 'draft' === $module['status'] ) : ?>
											<input type="hidden" name="status" value="publish"/>
										<?php endif; ?>

										<?php
											$update_status_nonce = esc_attr( 'forminator-nonce-update-status-' . $module['id'] );
											wp_nonce_field( $update_status_nonce, $update_status_nonce, false );
										?>
										<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( admin_url( 'admin.php?page=forminator-' . $page ) ); ?>">
										<button type="submit">

											<?php if ( 'publish' === $module['status'] ) : ?>
												<i class="sui-icon-unpublish" aria-hidden="true"></i> <?php esc_html_e( 'Unpublish', 'forminator' ); ?>
											<?php elseif ( 'draft' === $module['status'] ) : ?>
												<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Publish', 'forminator' ); ?>
											<?php endif; ?>

										</button>
									</form>
								</li>

								<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&form_id=' . $module['id'] ) ); ?>">
									<i class="sui-icon-community-people" aria-hidden="true"></i> <?php esc_html_e( 'View Submissions', 'forminator' ); ?>
								</a></li>

								<li <?php echo ( $has_leads ) ? 'aria-hidden="true"' : ''; ?>><form method="post">
									<input type="hidden" name="forminator_action" value="clone">
									<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
									<input type="hidden" name="msearch" value="" />
									<?php
										$clone_nonce = esc_attr( 'forminator-nonce-clone-' . $module['id'] );
										wp_nonce_field( $clone_nonce, 'forminatorNonce' );
									?>
									<?php if ( $has_leads ) : ?>
										<button type="submit" disabled="disabled" class="fui-button-with-tag sui-tooltip sui-tooltip-left sui-constrained" data-tooltip="<?php esc_html_e( 'Duplicate isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-page-multiple" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Duplicate', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</button>
									<?php else : ?>
										<button type="submit">
											<i class="sui-icon-page-multiple" aria-hidden="true"></i> <?php esc_html_e( 'Duplicate', 'forminator' ); ?>
										</button>
									<?php endif; ?>
								</form></li>

								<li>
									<button
										class="wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php esc_attr_e( 'Reset Tracking Data', 'forminator' ); ?>"
										data-modal-content="<?php echo esc_attr( sprintf( __( 'This action will reset the views and conversions data for this %s. Are you sure you want to proceed?', 'forminator' ), $module_slug ) ); ?>"
										data-button-text="<?php esc_attr_e( 'Reset', 'forminator' ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-action="reset-views"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator-nonce-reset-views-' . $module['id'] ) ); ?>"
									>
										<i class="sui-icon-update" aria-hidden="true"></i> <?php esc_html_e( 'Reset Tracking data', 'forminator' ); ?>
									</button>
								</li>

								<?php if ( 'form' === $module_slug ) { ?>
								<li>
									<button
										class="wpmudev-open-modal"
										data-modal="apply_preset"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
									>
										<i class="sui-icon-brush" aria-hidden="true"></i> <?php esc_html_e( 'Apply Preset', 'forminator' ); ?>
									</button>
								</li>
								<?php } ?>

								<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>
									<?php if ( $has_leads ) : ?>
										<li aria-hidden="true"><a href="#" class="fui-button-with-tag sui-tooltip sui-tooltip-left"
											data-tooltip="<?php esc_html_e( 'Export isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
											<span class="sui-icon-cloud-migration" aria-hidden="true"></span>
											<span class="fui-button-label"><?php esc_html_e( 'Export', 'forminator' ); ?></span>
											<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
										</a></li>
									<?php else : ?>
										<li><a href="#"
											class="wpmudev-open-modal"
											data-modal="<?php echo esc_attr( $export_dialog ); ?>"
											data-modal-title=""
											data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_export_' . $module_slug ) ); ?>">
											<i class="sui-icon-cloud-migration" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?>
										</a></li>
									<?php endif; ?>

								<?php endif; ?>

								<li>
									<button
										class="sui-option-red wpmudev-open-modal"
										data-modal="delete-module"
										data-modal-title="<?php echo esc_attr( sprintf( __( 'Delete %s', 'forminator' ), forminator_get_prefix( $module_slug, '', true ) ) ); ?>"
										data-modal-content="<?php echo esc_attr( sprintf( __( 'Are you sure you wish to permanently delete this %s?', 'forminator' ), $module_slug ) ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_' . $module_slug . '_request' ) ); ?>"
									>
										<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', 'forminator' ); ?>
									</button>
								</li>

							</ul>

						</div>

						<button class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_html_e( 'Open item', 'forminator' ); ?>"><i class="sui-icon-chevron-down" aria-hidden="true"></i></button>

					</div>

				</div>

				<div class="sui-accordion-item-body">

					<ul class="sui-accordion-item-data">

						<li data-col="large">
							<strong><?php esc_html_e( 'Last Submission', 'forminator' ); ?></strong>
							<span><?php echo esc_html( $module['last_entry_time'] ); ?></span>
						</li>

						<li data-col="small">
							<strong><?php esc_html_e( 'Views', 'forminator' ); ?></strong>
							<span><?php echo esc_html( $module['views'] ); ?></span>
						</li>

						<li>
							<?php if ( $has_leads ) : ?>
								<strong class="forminator-leads-leads" style="display:none;"><?php esc_html_e( 'Leads Collected', 'forminator' ); ?></strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-quiz-view&form_id=' . $module['id'] ) ); ?>" class="forminator-leads-leads" style="display:none;"><?php echo esc_html( $module['leads'] ); ?></a>
							<?php endif; ?>
							<strong class="forminator-leads-submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></strong>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . $post_type . '&form_id=' . $module['id'] ) ); ?>" class="forminator-leads-submissions"><?php echo esc_html( $module['entries'] ); ?></a>
						</li>

						<li>
							<strong><?php esc_html_e( 'Conversion Rate', 'forminator' ); ?></strong>
							<span class="forminator-submission-rate"><?php echo esc_html( self::getRate( $module ) ); ?>%</span>
							<?php if ( $has_leads ) : ?>
								<span class="forminator-leads-rate" style="display:none;"><?php echo esc_html( Forminator_Quiz_Page::getLeadsRate( $module ) ); ?>%</span>
							<?php endif; ?>
						</li>

						<?php if ( $has_leads ) : ?>
							<li class="fui-conversion-select" data-col="selector">
								<label class="fui-selector-label"><?php esc_html_e( 'View data for', 'forminator' ); ?></label>
								<select class="sui-select sui-select-sm fui-selector-button fui-select-listing-data">
									<option value="submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></option>
									<option value="leads"><?php esc_html_e( 'Leads Form', 'forminator' ); ?></option>
								</select>
							</li>
						<?php endif; ?>

					</ul>

					<div class="sui-chartjs <?php echo esc_attr( $opened_chart ); ?> forminator-stats-chart" data-chart-id="<?php echo esc_attr( $module['id'] ); ?>">

						<?php
						unset( $message );
						if ( 0 === $module['entries'] ) {
							$message = sprintf( esc_html__( "Your %s doesn't have any submission yet. Try again in a moment.", 'forminator' ), $module_slug );
						} elseif ( 'draft' === $module['status'] ) {
							$message = sprintf( esc_html__( "This %s is in draft state, so we've paused collecting data until you publish it live.", 'forminator' ), $module_slug );
						} elseif ( 0 === $module_entries_from_last_month ) {
							$message = sprintf( esc_html__( "Your %s didn't collect submissions the past 30 days.", 'forminator' ), $module_slug );
						}
						?>
						<?php if ( ! empty( $message ) ) { ?>

							<div class="sui-chartjs-message sui-chartjs-message--empty">
								<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
							</div>

						<?php } ?>

						<div class="sui-chartjs-canvas">

							<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
								<canvas id="forminator-module-<?php echo esc_attr( $module['id'] ); ?>-stats"></canvas>
							<?php } ?>

						</div>

					</div>

					<?php if ( $has_leads ) { ?>

						<div class="sui-chartjs <?php echo esc_attr( $opened_chart ); ?> forminator-leads-chart" style="display: none;" data-chart-id="<?php echo esc_attr( $leads_id ); ?>">

							<?php if ( ! empty( $message ) ) { ?>

								<div class="sui-chartjs-message sui-chartjs-message--empty">
									<p><i class="sui-icon-info" aria-hidden="true"></i> <?php echo esc_html( $message ); ?></p>
								</div>

							<?php } ?>

							<div class="sui-chartjs-canvas">

								<?php if ( ( 0 !== $module['entries'] ) || ( 0 !== $module_entries_from_last_month ) ) { ?>
									<canvas id="forminator-module-<?php echo esc_attr( $leads_id ); ?>-stats"></canvas>
								<?php } ?>

							</div>

						</div>

					<?php } ?>

				</div>

			</div>

			<?php
		}
	}

	/**
	 * Calculate rate
	 *
	 * @since 1.0
	 *
	 * @param $module
	 *
	 * @return float|int
	 */
	public static function getRate( $module ) {
		if ( $module['views'] > 0 ) {
			$rate = round( ( $module['entries'] * 100 ) / $module['views'], 1 );
		} else {
			$rate = 0;
		}

		return $rate;
	}

	/**
	 * Pagination
	 *
	 * @since 1.0
	 */
	public function pagination( $is_search, $count ) {
		echo '<span class="sui-pagination-results">'
			/* translators: ... */
			. esc_html( sprintf( _n( '%s result', '%s results', $count, 'forminator' ), $count ) )
			. '</span>';

		if ( $is_search ) {
			return;
		}
		forminator_list_pagination( $count );
	}

	/**
	 * Get models
	 *
	 * @since 1.0
	 * @since 1.6 add $limit
	 * @param int $limit
	 *
	 * @return array
	 */
	public static function get_models( $limit = null ) {

		if ( ! isset( static::$module_slug ) ) {
			$module_slug = self::get_slug_for_search();
		} else {
			$module_slug = static::$module_slug;
		}

		$class_name = 'Forminator_' . forminator_get_prefix( $module_slug, '', true ) . '_Model';
		$data       = $class_name::model()->get_all_paged( self::$page_number, $limit );

		return $data;
	}

	/**
	 * Get slug when in search
	 *
	 * @since 1.14.12
	 *
	 * @return string
	 */
	public static function get_slug_for_search() {
		$page = filter_input( INPUT_POST, 'page' );

		switch ( $page ) {
			case 'forminator-poll':
				$module_slug = 'poll';
				break;
			case 'forminator-quiz':
				$module_slug = 'quiz';
				break;
			default:
				$module_slug = 'form';
				break;
		}

		return $module_slug;
	}

	/**
	 * Clone Module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function clone_module( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( is_object( $model ) ) {
			// create one.
			// reset id.
			$model->id = null;

			// update title.
			if ( isset( $model->settings['formName'] ) ) {
				$model->settings['formName'] = sprintf( __( 'Copy of %s', 'forminator' ), $model->settings['formName'] );
			}

			// save it to create new record.
			$new_id = $model->save( true );

			/**
			 * Action called after module cloned
			 *
			 * @since 1.11
			 *
			 * @param int    $id - module id.
			 * @param object $model - module model.
			 */
			do_action( 'forminator_' . static::$module_slug . '_action_clone', $new_id, $model );

			$function = 'forminator_clone_' . static::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, $new_id );
			}

			// Purge count forms cache.
			$cache_prefix = 'forminator_' . static::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );
		}
	}

	/**
	 * Delete module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public static function delete_module( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			// For Quizzes with Leads.
			if ( isset( $model->settings['hasLeads'] ) && isset( $model->settings['leadsId'] ) && $model->settings['hasLeads'] ) {
				$leads_id    = $model->settings['leadsId'];
				$leads_model = Forminator_Base_Form_Model::get_model( $leads_id );

				if ( is_object( $leads_model ) ) {
					wp_delete_post( $leads_id );
				}
			}

			Forminator_Form_Entry_Model::delete_by_form( $id );
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );

			$function = 'forminator_update_' . $model::$module_slug . '_submissions_retention';
			if ( function_exists( $function ) ) {
				$function( $id, null, null );
			}
			wp_delete_post( $id );

			// Purge count forms cache.
			$cache_prefix = 'forminator_' . $model::$module_slug . '_total_entries';
			wp_cache_delete( $cache_prefix, $cache_prefix );
			wp_cache_delete( $cache_prefix . '_publish', $cache_prefix . '_publish' );
			wp_cache_delete( $cache_prefix . '_draft', $cache_prefix . '_draft' );

			/**
			 * Action called after module deleted
			 *
			 * @since 1.11
			 *
			 * @param int    $id - module id.
			 */
			do_action( 'forminator_' . $model::$module_slug . '_action_delete', $id );
		}
	}

	/**
	 * Delete module entries
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function delete_module_entries( $id ) {
		// check if this id is valid and the record is exists.
		$model = Forminator_Base_Form_Model::get_model( $id );
		if ( is_object( $model ) ) {
			Forminator_Form_Entry_Model::delete_by_form( $id );
		}
	}

	/**
	 * Export module
	 *
	 * @since 1.6
	 *
	 * @param $id
	 */
	public function export_module( $id ) {

		$exportable = array();
		$model_name = '';
		$model      = Forminator_Base_Form_Model::get_model( $id );
		if ( $model instanceof Forminator_Base_Form_Model ) {
			$model_name = $model->name;
			$exportable = $model->to_exportable_data();
		}
		$encoded = wp_json_encode( $exportable );
		$fp      = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $fp, $encoded ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fseek( $fp, 0 );

		$filename = sanitize_title( __( 'forminator', 'forminator' ) ) . '-' . sanitize_title( $model_name ) . '-' . static::$module_slug . '-export' . '.txt';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Content-Length: ' . strlen( $encoded ) );

		// make php send the generated csv lines to the browser.
		fpassthru( $fp );
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

	/**
	 * Process request
	 *
	 * @since 1.0
	 */
	public function processRequest() {
		$action = Forminator_Core::sanitize_text_field( 'forminator_action' );
		if ( ! $action ) {
			return;
		}
		$page = Forminator_Core::sanitize_text_field( 'page' );
		// Check if the page is not the relevant module type page and not forminator dashboard page.
		if ( 'forminator-' . forminator_get_prefix( static::$module_slug, 'c' ) !== $page && 'forminator' !== $page ) {
			return;
		}
		$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
		// In forminator dashboard, check if form type is not the relevant module type.
		if ( 'forminator' === $page && $form_type && forminator_get_prefix( static::$module_slug, 'custom-' ) !== $form_type ) {
			return;
		}

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		// Set nonce names first for verification.
		switch ( $action ) {
			case 'clone':
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator-nonce-clone-' . $id;
				break;

			case 'reset-views':
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator-nonce-reset-views-' . $id;
				break;

			case 'update-status':
				$nonce_name   = 'forminator-nonce-update-status-' . $id;
				$nonce_action = $nonce_name;
				break;

			default:
				$nonce_name   = 'forminatorNonce';
				$nonce_action = 'forminator_' . static::$module_slug . '_request';
				break;
		}

		// Verify nonce.
		$nonce = filter_input( INPUT_POST, $nonce_name );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return;
		}

		$plural_slug = forminator_get_prefix( static::$module_slug, '', false, true );
		$is_redirect = true;
		$ids         = Forminator_Core::sanitize_text_field( 'ids' );
		$module_ids  = ! empty( $ids ) ? explode( ',', $ids ) : array();
		switch ( $action ) {
			case 'delete':
				if ( ! empty( $id ) ) {
					static::delete_module( $id );
					$notice = static::$module_slug . '_deleted';
				}
				break;

			case 'clone':
				if ( ! empty( $id ) ) {
					$this->clone_module( $id );
					$notice = static::$module_slug . '_duplicated';
				}
				break;

			case 'reset-views':
				if ( ! empty( $id ) ) {
					self::reset_module_views( $id );
					$notice = static::$module_slug . '_reset';
				}
				break;

			case 'delete-votes':
			case 'delete-entries':
				if ( ! empty( $id ) ) {
					$this->delete_module_entries( $id );
				}
				break;

			case 'export':
				if ( ! empty( $id ) ) {
					$this->export_module( $id );
				}
				$is_redirect = false;
				break;

			case 'delete-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					static::delete_module( $id );
				}
				break;

			case 'delete-votes-polls':
			case 'delete-entries-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					$this->delete_module_entries( $id );
				}
				break;

			case 'clone-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					$this->clone_module( $id );
				}
				break;

			case 'reset-views-' . $plural_slug:
				foreach ( $module_ids as $id ) {
					self::reset_module_views( $id );
				}
				break;

			case 'update-status':
				$status = Forminator_Core::sanitize_text_field( 'status' );

				if ( ! empty( $id ) && ! empty( $status ) ) {
					// only publish and draft status avail.
					if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
						$model = Forminator_Base_Form_Model::get_model( $id );
						if ( $model instanceof Forminator_Base_Form_Model ) {
							$model->status = $status;
							$model->save();
						}
					}
				}
				break;
			case 'update-statuses':
				$status = Forminator_Core::sanitize_text_field( 'status' );

				// only publish and draft status avail.
				if ( ! empty( $status ) && in_array( $status, array( 'publish', 'draft' ), true ) ) {
					foreach ( $module_ids as $id ) {
						$model = Forminator_Base_Form_Model::get_model( $id );
						if ( $model instanceof Forminator_Base_Form_Model ) {
							$model->status = $status;
							$model->save();
						}
					}
				}
				break;

			case 'draft-forms':
				foreach ( $module_ids as $form_id ) {
					$this->update_module_status( $form_id, 'draft' );
				}
				break;

			case 'publish-forms':
				foreach ( $module_ids as $form_id ) {
					$this->update_module_status( $form_id, 'publish' );
				}
				break;

			default:
				break;
		}

		if ( $is_redirect ) {
			$to_referer = true;

			if ( 'false' === filter_input( INPUT_POST, 'forminatorRedirect' ) ) {
				$to_referer = false;
			}

			$args   = array(
				'page' => $this->get_admin_page(),
			);
			$search = Forminator_Core::sanitize_text_field( 'msearch' );
			if ( $search ) {
				$args['module-search'] = $search;
				$to_referer            = false;
			}
			if ( ! empty( $notice ) ) {
				$args['forminator_notice'] = $notice;
				$to_referer                = false;
			}

			$fallback_redirect = add_query_arg(
				$args,
				admin_url( 'admin.php' )
			);

			$this->maybe_redirect_to_referer( $fallback_redirect, $to_referer );
		}

		exit;
	}

	/**
	 * Update Module Status
	 *
	 * @since 1.6
	 *
	 * @param $id
	 * @param $status
	 */
	public function update_module_status( $id, $status ) {
		// only publish and draft status avail.
		if ( in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$model = Forminator_Base_Form_Model::get_model( $id );
			if ( $model instanceof Forminator_Base_Form_Model ) {
				$model->status = $status;
				$model->save();
			}
		}
	}

	/**
	 * Reset views data
	 *
	 * @since 1.6
	 *
	 * @param int $id Module ID.
	 */
	public static function reset_module_views( $id ) {
		$form_types = forminator_form_types();
		$module     = get_post( $id );
		if ( ! empty( $module->post_type ) && in_array( $module->post_type, $form_types, true ) ) {
			$form_view = Forminator_Form_Views_Model::get_instance();
			$form_view->delete_by_form( $id );
		}
	}
}
