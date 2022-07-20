<?php
if ( ! FORMINATOR_PRO ) {
	$submission               = $args['submissions'];
	$form_id                  = $this->form_id;
	$notice_success           = get_option( 'forminator_rating_success', false );
	$notice_dismissed         = get_option( 'forminator_rating_dismissed', false );
	$submission_later         = get_post_meta( $form_id, 'forminator_submission_rating_later' );
	$submission_later_dismiss = get_post_meta( $form_id, 'forminator_submission_rating_later_dismiss' );
	$min_submissions          = isset( $args['min_submissions'] ) ? $args['min_submissions'] : 10;
	if ( ! $notice_dismissed && ! $notice_success ) {
		if ( ( $min_submissions < $submission && 100 >= $submission && ! $submission_later )
			 || ( 100 < $submission && ! $submission_later_dismiss ) ) { ?>
			<div
				role="alert"
				class="sui-notice sui-notice-purple sui-active forminator-rating-notice fui-notice-rate<?php echo forminator_is_show_branding() ? '' : ' fui-unbranded'; ?>"
				style="display: block; text-align: left;"
				aria-live="assertive"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>"
			>

				<div class="sui-notice-content">

					<div class="sui-notice-message">

						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

						<p>
							<?php
							if ( empty( $args['notice'] ) ) {
								$milestone = ( 100 >= $submission ) ? $min_submissions : 100;
								printf( esc_html__( "Hey, we noticed you just crossed %1\$s submissions%2\$s on this %3\$s - that's awesome! We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.", 'forminator' ), '<strong> ' . (int) $milestone, '</strong>', esc_html( static::$module_slug ) );
							} else {
								echo wp_kses_post( $args['notice'] );
							}
							?>
						</p>

						<p>
							<a type="button" href="#" target="_blank"
							class="sui-button sui-button-purple"
							data-prop="forminator_rating_success"><?php esc_html_e( 'Rate Forminator', 'forminator' ); ?></a>

							<button type="button"
									class="sui-button sui-button-ghost"
									data-prop="<?php echo 100 > $submission ? 'forminator_submission_rating_later' : 'forminator_submission_rating_later_dismiss'; ?>"><?php esc_html_e( 'Maybe later', 'forminator' ); ?></button>

							<a href="#" style="color: #888;"
							data-prop="forminator_rating_dismissed"
							data-prop="forminator_rating_dismissed"><?php esc_html_e( 'No Thanks', 'forminator' ); ?></a>
						</p>

					</div>

				</div>

			</div>

			<script type="text/javascript">
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				jQuery('.forminator-rating-notice a').on('click', function (e) {
					e.preventDefault();

					var $notice = jQuery(e.currentTarget).closest('.forminator-rating-notice'),
						prop = jQuery(this).data('prop');

					if ('forminator_rating_success' === prop) {
						window.open('https://wordpress.org/support/plugin/forminator/reviews/#new-post', '_blank');
					}

					jQuery.post(
						ajaxUrl,
						{
							action: 'forminator_dismiss_notification',
							prop: prop,
							_ajax_nonce: $notice.data('nonce')
						}
					).always(function () {
						$notice.hide();
					});
				});

				jQuery('.forminator-rating-notice button').on('click', function (e) {
					e.preventDefault();

					var $notice = jQuery(e.currentTarget).closest('.forminator-rating-notice'),
						prop = jQuery(this).data('prop');

					jQuery.post(
						ajaxUrl,
						{
							action: 'forminator_later_notification',
							prop: prop,
							form_id: <?php echo (int) $form_id; ?>,
							_ajax_nonce: $notice.data('nonce')
						}
					).always(function () {
						$notice.hide();
					});
				});
			</script>
			<?php
		}
	}
}
