<?php
// defaults.
$vars = array(
	'message'       => '',
	'message_error' => '',
	'error_message' => '',
	'multi_id'      => '',
	'tags'          => array(),
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php echo esc_html( __( 'Setup Message', 'forminator' ) ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Configure message to be sent.', 'forminator' ); ?></p>

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
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['message_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Message', 'forminator' ); ?></label>

		<div class="sui-insert-variables">

			<textarea
				id="slack_message"
				class="sui-form-control"
				name="message"
				placeholder="<?php echo esc_attr( __( 'Message', 'forminator' ) ); ?>"
			><?php echo esc_html( $vars['message'] ); ?></textarea>

			<select class="sui-variables" data-textarea-id="slack_message">
				<?php foreach ( $vars['tags'] as $key => $label ) : ?>
					<option value="{<?php echo esc_attr( $key ); ?>}"
							data-content="{<?php echo esc_attr( $key ); ?>}"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

		</div>

		<?php if ( ! empty( $vars['message_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['message_error'] ); ?></span>
		<?php endif; ?>
		<span class="sui-description">
			<?php
			/* translators: ... */
			printf(
				esc_html_e( 'You can format your message using Slack Flavored Markdown, find more information %shere%s.', 'forminator' ),
				'<a href="https://get.slack.help/hc/en-us/articles/202288908-how-can-i-add-formatting-to-my-messages" target="_blank">',
				'</a>'
			);
			?>
		</span>
		<span class="sui-description">
			<?php
			/* translators: ... */
			printf(
				esc_html_e( 'By default sent message will include Poll Answer and Poll Results as attachment using Forminator Format to ease you up, more information about attachment can be found %shere%s.', 'forminator' ),
				'<a href="https://api.slack.com/docs/message-attachments" target="_blank">',
				'</a>'
			);
			?>
		</span>

	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>
