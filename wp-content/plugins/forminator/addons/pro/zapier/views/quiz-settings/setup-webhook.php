<?php
// defaults.
$vars = array(
	'error_message'     => '',
	'name'              => '',
	'name_error'        => '',
	'multi_id'          => '',
	'new_zap_url'       => '',
	'webhook_url'       => '',
	'webhook_url_error' => '',
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Setup Webhook', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Put your ZAP Webhook URL below. ', 'forminator' ); ?>
	</p>

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

<form enctype="multipart/form-data">

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['name_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'Zapier Integration Name', 'forminator' ); ?></label>
		<div class="sui-control-with-icon">
			<input type="text"
				name="name"
				placeholder="<?php esc_attr_e( 'Friendly Name', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['name'] ); ?>"
				class="sui-form-control"
			/>
			<i class="sui-icon-web-globe-world" aria-hidden="true"></i>
		</div>
		<?php if ( ! empty( $vars['name_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['name_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['webhook_url_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">
		<label class="sui-label"><?php esc_html_e( 'Webhook URL', 'forminator' ); ?></label>
		<div class="sui-control-with-icon">
			<input
					type="text"
					name="webhook_url"
					placeholder="<?php esc_attr_e( 'Webhook URL', 'forminator' ); ?>"
					value="<?php echo esc_attr( $vars['webhook_url'] ); ?>"
					class="sui-form-control"/>
			<i class="sui-icon-link" aria-hidden="true"></i>
		</div>
		<?php if ( ! empty( $vars['webhook_url_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['webhook_url_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>

<div
	role="alert"
	class="sui-notice sui-notice-yellow sui-active"
	style="display: block; text-align: left;"
	aria-live="assertive"
>

	<div class="sui-notice-content">

		<div class="sui-notice-message">

			<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

			<p>
				<?php
				echo sprintf(/* translators: ... */
					esc_html__( 'Please go %1$shere%2$s if you do not have any ZAP created. Remember to choose %3$sWebhooks by Zapier%4$s as Trigger App.', 'forminator' ),
					'<a href="' . esc_url( $vars['new_zap_url'] ) . '" target="_blank">',
					'</a>',
					'<strong>',
					'</strong>'
				);
				?>
			</p>

		</div>

	</div>

</div>
