<?php
// defaults.
$vars = array(
	'error_message' => '',
	'multi_id'      => '',
	'fields_map'    => array(),
	'fields'        => array(),
	'form_fields'   => array(),
	'email_fields'  => array(),
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php echo esc_html( __( 'Assign Fields', 'forminator' ) ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Match up your form fields with your campaign fields to make sure we\'re sending data to the right place.', 'forminator' ); ?>
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

<form>
	<table class="sui-table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'ActiveCampaign Field', 'forminator' ); ?></th>
			<th><?php esc_html_e( 'Forminator Field', 'forminator' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $vars['fields'] as $key => $field_title ) : ?>
			<tr>
				<td>
					<?php echo esc_html( $field_title ); ?>
					<?php if ( 'email' === $key ) : ?>
						<span class="integrations-required-field">*</span>
					<?php endif; ?>
				</td>
				<td>
					<?php
					$forminator_fields = $vars['form_fields'];
					if ( 'email' === $key ) {
						$forminator_fields = $vars['email_fields'];
					}
					$current_error    = '';
					$current_selected = '';
					if ( isset( $vars[ $key . '_error' ] ) && ! empty( $vars[ $key . '_error' ] ) ) {
						$current_error = $vars[ $key . '_error' ];
					}
					if ( isset( $vars['fields_map'][ $key ] ) && ! empty( $vars['fields_map'][ $key ] ) ) {
						$current_selected = $vars['fields_map'][ $key ];
					}
					?>
					<div class="sui-form-field <?php echo esc_attr( ! empty( $current_error ) ? 'sui-form-field-error' : '' ); ?>">
						<select name="fields_map[<?php echo esc_attr( $key ); ?>]" class="sui-select sui-select-sm" data-placeholder="<?php esc_html_e( 'Please select a field', 'forminator' ); ?>">
							<option></option>
							<?php foreach ( $forminator_fields as $forminator_field ) : ?>
								<option value="<?php echo esc_attr( $forminator_field['element_id'] ); ?>"
									<?php selected( $current_selected, $forminator_field['element_id'] ); ?>>
									<?php echo esc_html( $forminator_field['field_label'] . ' | ' . $forminator_field['element_id'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php if ( ! empty( $current_error ) ) : ?>
							<span class="sui-error-message"><?php echo esc_html( $current_error ); ?></span>
						<?php endif; ?>
					</div>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
