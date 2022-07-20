<?php
$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
$slug    = $args['slug'];
$_page   = 'forminator-' . forminator_get_prefix( $slug, 'c' );
$nonce   = wp_create_nonce( 'forminator_' . $slug . '_request' );

$exportable = array();
$model      = Forminator_Base_Form_Model::get_model( $form_id );
if ( $model instanceof Forminator_Base_Form_Model ) {
	$exportable = $model->to_exportable_data();
}
$text_area_id = uniqid( 'export-text-' );
?>

<div class="sui-box-body wpmudev-popup-form">

	<div class="sui-form-field">
		<textarea class="sui-form-control" readonly="readonly" rows="10" id="<?php echo esc_attr( $text_area_id ); ?>"></textarea>
		<span class="sui-description"><?php esc_html_e( 'Copy ALL text above, and paste to import dialog.', 'forminator' ); ?></span>
	</div>

	<div
		role="alert"
		class="sui-notice sui-notice-blue sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

				<p>
					<?php
					echo(
					sprintf(
						esc_html__( 'You can import this %1$s in Forminator %2$s%3$s%4$s or above. The %5$s may break on a version lower than your install.', 'forminator' ),
						esc_html( $slug ),
						'<strong>',
						esc_html( FORMINATOR_VERSION ),
						'</strong>',
						esc_html( $slug )
					)
					);
					?>
				</p>

			</div>

		</div>

	</div>

</div>

<div class="sui-box-footer">

	<button class="sui-button forminator-popup-cancel" data-a11y-dialog-hide="forminator-popup"><?php esc_html_e( 'Close', 'forminator' ); ?></button>

	<div class="sui-actions-right">

		<form action="<?php echo esc_attr( admin_url( 'admin.php?page=' . $_page ) ); ?>" method="post">
			<input type="hidden" name="forminator_action" value="export">
			<input type="hidden" name="forminatorNonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( $form_id ); ?>">
			<button class="sui-button sui-button-primary"><i class="sui-icon-download" aria-hidden="true"></i> <?php esc_html_e( 'Download', 'forminator' ); ?></button>
		</form>

	</div>

</div>

<?php // using jquery to avoid html escape on popup ajax load. ?>
<script type="text/javascript">
	jQuery('#<?php echo esc_attr( $text_area_id ); ?>').val(JSON.stringify(<?php echo wp_json_encode( $exportable ); ?>));
</script>
