<?php
// defaults.
$vars = array(
	'error_message'  => '',
	'board_id'       => '',
	'board_id_error' => '',
	'multi_id'       => '',
	'boards'         => array(),
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>
<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;"><?php echo esc_html( __( 'Assign Board', 'forminator' ) ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Your account is now authorized, choose which board you want Trello cards to be added to.', 'forminator' ); ?></p>

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
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['board_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">
		<label class="sui-label"><?php esc_html_e( 'Board', 'forminator' ); ?>
			<?php // DEV NOTE: Select without JS. ?>
			<select name="board_id" style="max-width: none;">
				<option><?php esc_html_e( 'Please select a board', 'forminator' ); ?></option>
				<?php foreach ( $vars['boards'] as $board_id => $board_name ) : ?>
					<option value="<?php echo esc_attr( $board_id ); ?>" <?php selected( $vars['board_id'], $board_id ); ?>><?php echo esc_html( $board_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! empty( $vars['board_id_error'] ) ) : ?>
				<span class="sui-error-message"><?php echo esc_html( $vars['board_id_error'] ); ?></span>
			<?php endif; ?>
		</label>
	</div>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
