<?php
// Defaults.
$vars = array(
	'account_id' => 0,
	'auth_url'   => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
			/* translators: ... */
			echo esc_html( sprintf( __( 'Connect %1$s', 'forminator' ), 'AWeber' ) );
		?>
	</h3>

</div>

<form>

	<div
		role="alert"
		class="sui-notice sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-loader sui-loading" aria-hidden="true"></span>

				<p>
					<?php
						/* translators: ... */
						echo esc_html( sprintf( __( 'We are waiting %1$s authorization...', 'forminator' ), 'AWeber' ) );
					?>
				</p>

			</div>

		</div>

	</div>

	<?php if ( empty( $vars['account_id'] ) ) : ?>
	<div class="sui-block-content-center">
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
			target="_blank"
			class="sui-button sui-button-ghost disable-loader">
			<?php esc_html_e( 'Retry', 'forminator' ); ?>
		</a>
	</div>
	<?php endif; ?>

</form>

