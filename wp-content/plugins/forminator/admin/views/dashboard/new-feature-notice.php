<?php
$current_user = wp_get_current_user();
$banner_1x    = forminator_plugin_url() . 'assets/images/Feature_highlight_17.png';
$banner_2x    = forminator_plugin_url() . 'assets/images/Feature_highlight_17@2x.png';
?>

<div class="sui-modal sui-modal-md">

	<div
		role="dialog"
		id="forminator-new-feature"
		class="sui-modal-content"
		aria-live="polite"
		aria-modal="true"
		aria-labelledby="forminator-new-feature__title"
	>

		<div class="sui-box forminator-feature-modal" data-prop="forminator_dismiss_feature_1170" data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

			<div class="sui-box-header sui-flatten sui-content-center">

				<figure class="sui-box-banner" aria-hidden="true">
					<img
						src="<?php echo esc_url( $banner_1x ); ?>"
						srcset="<?php echo esc_url( $banner_1x ); ?> 1x, <?php echo esc_url( $banner_2x ); ?> 2x"
						alt=""
					/>
				</figure>

				<button class="sui-button-icon sui-button-white sui-button-float--right forminator-dismiss-new-feature" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">Close this dialog.</span>
				</button>

				<h3 class="sui-box-title sui-lg" style="overflow: initial; white-space: initial; text-overflow: initial;"><?php esc_html_e( 'Introducing! Save form as Draft', 'forminator' ); ?></h3>

				<p class="sui-description"><?php printf( esc_html__( 'Hey! %s, Do you have a multi-page or lengthy form? If you do, you\'ve probably already received complaints from visitors about how long it takes them to fill it out. Now, with the latest version of Forminator, your visitors can save a form as a draft and come back later to finish and submit it.', 'forminator' ), esc_html( ucfirst( $current_user->display_name ) ) ); ?></p>

			</div>

			<!--<div class="sui-box-body sui-spacing-top--0">

				<ul>

					<li style="margin-bottom: 15px;">
						<p class="sui-description" style="margin-bottom: 5px;"><strong><?php esc_html_e( 'Conditional After Submission Behavior', 'forminator' ); ?></strong></p>
						<p class="sui-description"><?php esc_html_e( 'After submission behaviors have a great new feature: you can now choose what happens after users successfully submit a form based on the data they provide. For example, you can redirect users to specific pages or display different submission messages.', 'forminator' ); ?></p>
					</li>

					<li>
						<p class="sui-description" style="margin-bottom: 5px;"><strong><?php esc_html_e( 'Support for hCaptcha', 'forminator' ); ?></strong></p>
						<p class="sui-description"><?php esc_html_e( 'In the latest release, you can choose between reCAPTCHA and hCaptcha to stop pesky robots from submitting form data.', 'forminator' ); ?></p>
					</li>

				</ul>

			</div>-->

			<div class="sui-box-footer sui-flatten sui-content-center">

				<button class="sui-button forminator-dismiss-new-feature" data-modal-close><?php esc_html_e( 'Ok, Got it!', 'forminator' ); ?></button>

			</div>

		</div>

	</div>

</div>

<script type="text/javascript">
	jQuery( '#forminator-new-feature .forminator-dismiss-new-feature' ).on( 'click', function( e ) {
		e.preventDefault();

		var $notice = jQuery( e.currentTarget ).closest( '.forminator-feature-modal' );
		var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

		jQuery.post(
			ajaxUrl,
			{
				action: 'forminator_dismiss_notification',
				prop: $notice.data('prop'),
				_ajax_nonce: $notice.data('nonce')
			}
		).always( function() {
			$notice.hide();
		});
	});
</script>
