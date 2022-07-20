<?php
// defaults.
$vars = array(
	'auth_url' => '',
	'token'    => '',
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
			/* translators: ... */
			echo esc_html( sprintf( __( 'Connect %1$s', 'forminator' ), 'HubSpot' ) );
		?>
	</h3>

</div>

<?php if ( ! empty( $vars['token'] ) ) : ?>

	<div
		role="alert"
		class="sui-notice sui-notice-green sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

				<p>
					<?php
					/* translators: ... */
					echo esc_html( sprintf( __( 'Your %1$s account is already authorized.', 'forminator' ), 'HubSpot' ) );
					?>
				</p>

			</div>

		</div>

	</div>

<?php else : ?>

	<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;">
		<?php esc_html_e( "Authenticate your HubSpot account using the button below. Note that you'll be taken to the HubSpot website to grant access to Forminator and then redirected back.", 'forminator' ); ?>
	</p>

	<div class="forminator-integration-popup__footer-temp">
		<button type="button" class="sui-button forminator-integration-popup__close">
			<span class="sui-loading-text"><?php esc_html_e( 'Authenticating', 'forminator' ); ?></span>
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
		</button>
	</div>

<?php endif; ?>
