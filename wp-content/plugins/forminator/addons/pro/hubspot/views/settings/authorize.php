<?php
// defaults.
$vars = array(
	'auth_url' => '',
	'token'    => '',
	'user'     => '',
);

/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
			/* translators: ... */
			echo esc_html( sprintf( __( 'Connect %1$s', 'forminator' ), 'HubSpot' ) );
		?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php if ( ! empty( $vars['token'] ) ) :
			echo esc_html_e( 'You are already connected to the HubSpot. You can disconnect your HubSpot Integration (if you need to) using the button below.', 'forminator' );
		else :
			echo esc_html_e( "Authenticate your HubSpot account using the button below. Note that you'll be taken to the HubSpot website to grant access to Forminator and then redirected back.", 'forminator' );
		endif; ?>
	</p>

</div>

<?php if ( ! empty( $vars['token'] ) ) : ?>

	<div
		role="alert"
		class="sui-notice sui-notice-green sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-check-tick" aria-hidden="true"></span>

				<p>
					<?php
						/* translators: ... */
						echo sprintf( esc_html__( 'You are connected to %2$s%1$s%3$s.', 'forminator' ), esc_html( $vars['user'] ), '<strong>', '</strong>' );
					?>
				</p>

			</div>

		</div>

	</div>

<?php endif; ?>

<?php if ( empty( $vars['token'] ) ) : ?>

	<div class="sui-form-field" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Identifier', 'forminator' ); ?></label>

		<input name="identifier"
			placeholder="<?php esc_attr_e( 'E.g., Business Account', 'forminator' ); ?>"
			value=""
			class="sui-form-control" />

		<span class="sui-description"><?php esc_html_e( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ); ?></span>

	</div>

	<div class="forminator-integration-popup__footer-temp">
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button sui-button-primary forminator-addon-connect forminator-integration-popup__close"><?php esc_html_e( 'Authenticate', 'forminator' ); ?></a>
	</div>

	<script>
		(function ($) {
			$('input[name="identifier"]').on( 'change', function (e) {
				var parent = $(this).closest('.sui-box-body'),
					val = $(this).val(),
					link = $( '.forminator-addon-connect', parent.next() ),
					href = link.prop('href');
				if ( href ) {
					var index = href.indexOf('identifier');

					if ( index ) {
						href = href.slice(0, index);
					}
					href += encodeURIComponent( 'identifier=' + encodeURIComponent( val ) );
					link.prop('href', href);
				}
			});
		})(jQuery);
	</script>

<?php endif; ?>
