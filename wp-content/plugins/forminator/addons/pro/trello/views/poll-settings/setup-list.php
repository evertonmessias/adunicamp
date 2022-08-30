<?php
// defaults.
$vars = array(
	'error_message'    => '',
	'list_id'          => '',
	'list_id_error'    => '',
	'multi_id'         => '',
	'lists'            => array(),
	'board_name'       => '',
	'step_description' => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}

$vars['step_description'] = sprintf(
	/* translators: ... */
	esc_html__( 'Which list from %1$s do you want auto-generated cards to be added to?', 'forminator' ),
	'<b>' . $vars['board_name'] . '</b>'
);
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;"><?php echo esc_html( __( 'Assign List', 'forminator' ) ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php echo wp_kses_post( $vars['step_description'] ); ?></p>

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
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['list_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">
		<label class="sui-label"><?php esc_html_e( 'List', 'forminator' ); ?></label>
		<?php // DEV NOTE: Select without JS. ?>
		<select name="list_id" style="max-width: none;">
			<option><?php esc_html_e( 'Please select a list', 'forminator' ); ?></option>
			<?php foreach ( $vars['lists'] as $list_id => $list_name ) : ?>
				<option value="<?php echo esc_attr( $list_id ); ?>" <?php selected( $vars['list_id'], $list_id ); ?>><?php echo esc_html( $list_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $vars['list_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['list_id_error'] ); ?></span>
		<?php endif; ?>
	</div>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
