<?php
$slug  = $args['slug'];
$nonce = wp_create_nonce( 'forminator_save_import_' . $slug );
?>

<div class="sui-box-body wpmudev-popup-form">

	<div
		role="alert"
		id="wpmudev-ajax-error-placeholder"
		class="sui-notice sui-notice-error"
		aria-live="assertive"
	>
		<!-- Nothing should be placed here -->
	</div>

	<div class="sui-form-field">

		<textarea class="sui-form-control" rows="10" name="importable"></textarea>

		<span class="sui-description"><?php echo esc_html( sprintf( __( 'Paste exported %s above.', 'forminator' ), $slug ) ); ?></span>

	</div>

</div>

<div class="sui-box-footer">

	<button class="sui-button forminator-popup-cancel" data-a11y-dialog-hide="forminator-popup">
		<span class="sui-loading-text"><?php esc_html_e( 'Cancel', 'forminator' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>

	<div class="sui-actions-right">

		<button class="sui-button sui-button-primary wpmudev-action-ajax-done" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<span class="sui-loading-text"><?php esc_html_e( 'Import', 'forminator' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

	</div>

</div>
