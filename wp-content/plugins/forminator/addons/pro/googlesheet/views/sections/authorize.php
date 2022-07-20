<?php
// Defaults.
$vars = array(
	'error_message' => '',
	'is_close'      => false,
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div id="forminator-integrations" class="wpmudev-settings--box">

	<div class="sui-box">
		<div class="sui-box-header">
			<h2 class="sui-box-title"><?php esc_html_e( 'Authorizing Google SpreadSheets', 'forminator' ); ?></h2>
		</div>
		<div class="sui-box-body">
			<?php if ( ! empty( $vars['error_message'] ) ) : ?>
				<div
					role="alert"
					class="sui-notice sui-notice-red sui-active"
					style="display: block; text-align: left;"
					aria-live="assertive"
				>

					<div class="sui-notice-content">

						<div class="sui-notice-message">

							<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

							<p><?php echo esc_html( $vars['error_message'] ); ?></p>

						</div>

					</div>

				</div>
			<?php elseif ( $vars['is_close'] ) : ?>
				<div
					role="alert"
					class="sui-notice sui-notice-green sui-active"
					style="display: block; text-align: left;"
					aria-live="assertive"
				>

					<div class="sui-notice-content">

						<div class="sui-notice-message">

							<span class="sui-notice-icon sui-icon-check-tick" aria-hidden="true"></span>

							<p>
								<?php
								esc_html_e(
									'Successfully authorized Google SpreadSheets, you can go back to integration settings.',
									'forminator'
								);
								?>
							</p>

						</div>

					</div>

				</div>
			<?php else : ?>
				<div
					role="alert"
					class="sui-notice sui-active"
					style="display: block; text-align: left;"
					aria-live="assertive"
				>

					<div class="sui-notice-content">

						<div class="sui-notice-message">

							<span class="sui-notice-icon sui-icon-loader sui-loading" aria-hidden="true"></span>

							<p><?php esc_html_e( 'Please wait...', 'forminator' ); ?></p>

						</div>

					</div>

				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
	(function ($) {
		$( function (e) {
			<?php if ( $vars['is_close'] ) : ?>
			setTimeout(function () {
				window.close();
			}, 3000);
			<?php endif; ?>
		});
	})(jQuery);
</script>
