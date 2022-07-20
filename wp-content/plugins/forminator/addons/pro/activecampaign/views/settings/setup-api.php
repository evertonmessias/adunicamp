<?php
// Defaults.
$vars = array(
	'identifier'    => '',
	'error_message' => '',
	'api_url'       => '',
	'api_url_error' => '',
	'api_key'       => '',
	'api_key_error' => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
	<?php
		/* translators: %s: Addon name */
		echo esc_html( sprintf( __( 'Configure %1$s API', 'forminator' ), 'ActiveCampaign' ) );
	?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Setup ActiveCampaign API Access.', 'forminator' ); ?></p>

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
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['api_url_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'API URL', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="api_url"
				placeholder="<?php /* translators: ... */ echo esc_attr( sprintf( __( 'Enter %1$s API URL', 'forminator' ), 'ActiveCampaign' ) ); ?>"
				value="<?php echo esc_attr( $vars['api_url'] ); ?>"
				class="sui-form-control" />

			<i class="sui-icon-link" aria-hidden="true"></i>

		</div>

		<?php if ( ! empty( $vars['api_url_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['api_url_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['api_key_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'API Key', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="api_key"
				placeholder="<?php /* translators: ... */ echo esc_attr( sprintf( __( 'Enter %1$s API Key', 'forminator' ), 'ActiveCampaign' ) ); ?>"
				value="<?php echo esc_attr( $vars['api_key'] ); ?>"
				class="sui-form-control" />

			<i class="sui-icon-key" aria-hidden="true"></i>

		</div>

		<?php if ( ! empty( $vars['api_key_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['api_key_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['identifier'] ); ?>"
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>


	</div>

</form>
