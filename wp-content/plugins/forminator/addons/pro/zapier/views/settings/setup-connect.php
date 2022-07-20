<?php
// Defaults.
$vars = array(
	'error_message' => '',
	'is_connected'  => false,
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: ... */
		echo esc_html( sprintf( __( 'Activate %1$s', 'forminator' ), 'Zapier' ) );
		?>
	</h3>

	<?php if ( ! empty( $vars['is_connected'] ) || ! empty( $vars['error_message'] ) ) : ?>
		<p id="forminator-integration-popup__description" class="sui-description">
			<?php esc_html_e( 'Activate Zapier to start using it on your forms.', 'forminator' ); ?>
		</p>
	<?php endif; ?>

</div>

<?php if ( empty( $vars['is_connected'] ) && empty( $vars['error_message'] ) ) : ?>
	<p id="forminator-integration-popup__description" class="sui-description" style="margin: 0; text-align: center;">
		<?php esc_html_e( 'Activate Zapier to start using it on your forms.', 'forminator' ); ?>
	</p>
<?php endif; ?>

<?php if ( ! empty( $vars['is_connected'] ) ) : ?>
	<div
		role="alert"
		class="sui-notice sui-notice-green sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-check-tick" aria-hidden="true"></span>

				<p><?php esc_html_e( 'Zapier is already active.', 'forminator' ); ?></p>

			</div>

		</div>

	</div>
<?php endif; ?>

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

<form>
	<input type="hidden" value="1" name="connect">
</form>
