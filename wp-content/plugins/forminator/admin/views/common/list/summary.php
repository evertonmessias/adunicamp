<?php
$preview_dialog = 'preview_' . forminator_get_prefix( static::$module_slug, 'c', false, true );
$export_dialog  = 'export_' . static::$module_slug;
$post_type      = 'forminator_' . forminator_get_prefix( static::$module_slug, '', false, true );
$soon           = 'quiz' === static::$module_slug;

if ( $count > 0 || $is_search ) {
	$count_active = $this->countModules( 'publish' );
	// Count total entries from last 30 days.
	$total_entries_from_last_month = count( Forminator_Form_Entry_Model::get_newer_entry_ids( $entry_type, $sql_month_start_date ) );

	$most_entry = Forminator_Form_Entry_Model::get_most_entry( $entry_type );

	?>

	<div class="sui-box sui-summary <?php echo esc_attr( $this->get_box_summary_classes() ); ?>">

		<div class="sui-summary-image-space" aria-hidden="true"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details">

				<span class="sui-summary-large"><?php echo esc_html( $count_active ); ?></span>

				<span class="sui-summary-sub">
				<?php
				echo esc_html(
					_n(
						sprintf( 'Active %s', forminator_get_prefix( static::$module_slug, '', true ) ),
						sprintf( 'Active %s', forminator_get_prefix( static::$module_slug, '', true, true ) ),
						esc_html( $count_active ),
						'forminator'
					)
				);
				?>
											  </span>

				<form id="forminator-search-modules" class="forminator-search-modules" data-searched="false">

					<div class="sui-row">

						<div class="sui-col-lg-10 sui-col-md-12">

							<div class="sui-form-field">

								<div class="sui-control-with-icon">
									<button class="forminator-search-submit"><i class="sui-icon-magnifying-glass-search"></i></button>
									<input type="text" name="search" value="<?php echo esc_attr( $search_keyword ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Search %s...', 'forminator' ), static::$module_slug ) ); ?>" id="forminator-module-search" class="sui-form-control">
								</div>
								<button role="button" class="search-reset sui-button-icon" title="<?php esc_attr_e( 'Reset search', 'forminator' ); ?>">
									<span class="sui-icon-cross-close" aria-hidden="true"></span>
									<span class="sui-screen-reader-text"><?php esc_html_e( 'Reset search', 'forminator' ); ?></span>
								</button>

							</div>

						</div>

					</div>

					<input type="hidden" name="module_slug" value="<?php echo esc_attr( static::$module_slug ); ?>" />
					<input type="hidden" name="preview_title" value="<?php echo esc_attr( $preview_title ); ?>" />
					<input type="hidden" name="sql_month_start_date" value="<?php echo esc_attr( $sql_month_start_date ); ?>" />
					<input type="hidden" name="wizard_page" value="<?php echo esc_attr( $wizard_page ); ?>" />

					<input type="hidden" name="preview_dialog" value="<?php echo esc_attr( $preview_dialog ); ?>" />
					<input type="hidden" name="export_dialog" value="<?php echo esc_attr( $export_dialog ); ?>" />
					<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />
					<input type="hidden" name="soon" value="<?php echo esc_attr( $soon ); ?>" />
					<input type="hidden" name="page" value="<?php echo esc_attr( (string) filter_input( INPUT_GET, 'page' ) ); ?>" />
					<?php
						wp_nonce_field( $search_module_nonce, $search_module_nonce, false );
					?>

				</form>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Last Submission', 'forminator' ); ?></span>
					<span class="sui-list-detail"><?php echo esc_html( forminator_get_latest_entry_time( static::$module_slug ) ); ?></span>
				</li>

				<li>
					<span class="sui-list-label"><?php esc_html_e( 'Submissions in the last 30 days', 'forminator' ); ?></span>
					<span class="sui-list-detail"><?php echo esc_html( $total_entries_from_last_month ); ?></span>
				</li>
				<?php if ( ! empty( $most_entry ) && get_post_status( $most_entry->form_id ) && 0 !== (int) $most_entry->entry_count ) { ?>
					<li>
						<span class="sui-list-label"><?php esc_html_e( 'Most submissions', 'forminator' ); ?></span>
						<span class="sui-list-detail">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $most_entry->form_id ) ); ?>">
								<?php echo esc_html( forminator_get_form_name( $most_entry->form_id ) ); ?>
							</a>
						</span>
					</li>
				<?php } ?>
			</ul>

		</div>

	</div>

	<?php
	// Call the css here to prevent search icon from flashing above the search form while the page is loading...
	$this->template( 'common/list/temp_css' );
}
