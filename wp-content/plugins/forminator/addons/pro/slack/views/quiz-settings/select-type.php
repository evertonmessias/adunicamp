<?php
// defaults.
$vars = array(
	'error_message' => '',
	'type'          => '',
	'type_error'    => '',
	'types'         => array(),
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php echo esc_html( __( 'Select Type', 'forminator' ) ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Select what type of channel Slack will send the message to: a public channel, a private group or a DM channel.', 'forminator' ); ?></p>

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

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['type_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">
		<label class="sui-label"><?php esc_html_e( 'Type', 'forminator' ); ?></label>

		<select name="type" class="sui-select" data-placeholder="<?php esc_html_e( 'Please select type', 'forminator' ); ?>">
			<option></option>
			<?php foreach ( $vars['types'] as $type_id => $type_name ) : ?>
				<option value="<?php echo esc_attr( $type_id ); ?>" <?php selected( $vars['type'], $type_id ); ?>><?php echo esc_html( $type_name ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php if ( ! empty( $vars['type_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['type_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
