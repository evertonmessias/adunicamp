<?php
// defaults.
$vars = array(
	'error_message'   => '',
	'target_id'       => '',
	'target_id_error' => '',
	'targets'         => array(),
	'help_message'    => '',
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php echo esc_html( __( 'Select Target', 'forminator' ) ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php echo esc_html( $vars['help_message'] ); ?></p>

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

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['target_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">
		<label class="sui-label" for="slack-target-id"><?php esc_html_e( 'Type', 'forminator' ); ?></label>

		<select name="target_id" id="slack-target-id" class="sui-select" data-placeholder="<?php esc_html_e( 'Please select target', 'forminator' ); ?>" data-search="true">
			<option></option>
			<?php foreach ( $vars['targets'] as $target_id => $target_name ) : ?>
				<option value="<?php echo esc_attr( $target_id ); ?>" <?php selected( $vars['target_id'], $target_id ); ?>><?php echo esc_html( $target_name ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php if ( ! empty( $vars['target_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['target_id_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
