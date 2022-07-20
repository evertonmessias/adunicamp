<?php
// Defaults.
$vars = array(
	'identifier'      => '',
	'error_message'   => '',
	'api_key'         => '',
	'api_key_error'   => '',
	'client_id'       => '',
	'client_id_error' => '',
	'client_name'     => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
			/* translators: ... */
			echo esc_html( sprintf( __( 'Configure %1$s API', 'forminator' ), 'Campaign Monitor' ) );
		?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Setup Campaign Monitor API Access.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['client_name'] ) ) : ?>
		<div
			role="alert"
			class="sui-notice sui-notice-green sui-active"
			style="display: block; text-align: left;"
			aria-live="assertive"
		>

			<div class="sui-notice-content">

				<div class="sui-notice-message">

					<span class="sui-notice-icon sui-icon-check-tick" aria-hidden="true"></span>

					<p><?php esc_html_e( 'Campaign Monitor Integrations currently connected to API Client: ', 'forminator' ); ?> <strong><?php echo esc_html( $vars['client_name'] ); ?></strong></p>

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

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['api_key_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'API Key', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="api_key"
				placeholder="<?php /* translators: ... */ echo esc_attr( sprintf( __( 'Enter %1$s API Key', 'forminator' ), 'Campaign Monitor' ) ); ?>"
				value="<?php echo esc_attr( $vars['api_key'] ); ?>"
				class="sui-form-control" />

			<span class="sui-icon-key" aria-hidden="true"></span>

		</div>

		<?php if ( ! empty( $vars['api_key_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['api_key_error'] ); ?></span>
		<?php endif; ?>

		<div class="sui-description">
			<?php esc_html_e( 'To obtain Campaign Monitor API Credentials, follow these steps :', 'forminator' ); ?>
			<ol class="instructions" id="apikey-instructions">
				<li>
					<?php
					echo sprintf(/* translators: ... */
						esc_html__( 'Login to your Campaign Monitor account %1$shere%2$s.', 'forminator' ),
						'<a href="https://login.createsend.com/l" target="_blank">',
						'</a>'
					); //phpcs:ignore Standard.Category.SniffName.ErrorCode
					?>
				</li>
				<li>
					<?php
					echo sprintf(/* translators: ... */
						esc_html__( 'Go to Account Settings, then navigate to %1$sAPI Keys%2$s section.', 'forminator' ),
						'<strong>',
						'</strong>'
					); //phpcs:ignore Standard.Category.SniffName.ErrorCode
					?>
				</li>
				<li>
					<?php
					echo sprintf(/* translators: ... */
						esc_html__( 'Click on %1$sShow API Key%2$s, select and copy on the shown up value.', 'forminator' ),
						'<strong>',
						'</strong>'
					); //phpcs:ignore Standard.Category.SniffName.ErrorCode
					?>
				</li>
			</ol>
		</div>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['client_id_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'Client ID', 'forminator' ); ?></label>

		<div class="sui-control-with-icon">

			<input name="client_id"
				placeholder="<?php /* translators: ... */ echo esc_attr( sprintf( __( 'Enter %1$s Client ID', 'forminator' ), 'Campaign Monitor' ) ); ?>"
				value="<?php echo esc_attr( $vars['client_id'] ); ?>"
				class="sui-form-control" />

			<span class="sui-icon-profile-male" aria-hidden="true"></span>

		</div>

		<?php if ( ! empty( $vars['client_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['client_id_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description">
			<?php
				echo sprintf(/* translators: ... */
					esc_html__( 'Client ID is optional, unless you are on %1$sAgency-Mode%2$s, then you can find your desired Client ID on the %3$sAccount Settings%4$s > %5$sAPI Keys%6$s', 'forminator' ),
					'<strong>',
					'</strong>',
					'<strong>',
					'</strong>',
					'<strong>',
					'</strong>'
				); //phpcs:ignore Standard.Category.SniffName.ErrorCode
				?>
		</span>
	</div>

	<div class="sui-form-field">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['identifier'] ); ?>"
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>


	</div>

</form>
