<?php

if ( ! isset( $pid ) ) {
	return;
}

$pid = intval( $pid );

$res = Forminator_Admin_Addons_page::forminator_addon_by_pid( $pid );

// Skip invalid projects.
if ( empty( $res->pid ) || empty( $res->name ) ) {
	return;
}

// Skip hidden projects.
if ( $res->is_hidden ) {
	return;
}

$has_features = false;
$features	 = array(
	0 => array(),
	1 => array(),
);
// chunk feature into 2.
if ( is_array( $res->features ) && ! empty( $res->features ) ) {
	$has_features = true;
	$chunk_size   = ceil( count( $res->features ) / 2 );
	$features	 = array_chunk( $res->features, $chunk_size );
}
?>

<div class="sui-modal sui-modal-lg">

	<div
		role="dialog"
		id="forminator-modal-addons-details-<?php echo esc_attr( $pid ); ?>"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="forminator-modal-addons-details-<?php echo esc_attr( $pid ); ?>__title"
		aria-describedby="forminator-modal-addons-details-<?php echo esc_attr( $pid ); ?>__description"
	>

		<div class="sui-box">

			<div class="sui-box-header">

				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">Close this modal</span>
				</button>

				<div class="forminator-details-header">

					<?php if ( forminator_is_show_branding() ) : ?>
						<div class="forminator-details-header--image" style="background-image: url(<?php echo esc_url( $res->url->thumbnail ); ?>);" aria-hidden="true"></div>
					<?php endif; ?>

					<div class="forminator-details-header--data">

						<h3 class="forminator-details-header--title">
							<?php echo esc_html( $res->name ); ?>
							<?php
							if ( ! FORMINATOR_PRO ) {
								echo '<span class="sui-tag sui-tag-purple sui-tag-sm">' . esc_html__( 'PRO', 'forminator' ) . '</span>';
							}
							?>
						</h3>

						<div class="forminator-details-header--tags">

							<?php
							if ( FORMINATOR_PRO ) {
								/* translators: Plugin latest version */
								echo '<span class="sui-tag sui-tag-sm addons-version">' . sprintf( esc_html__( 'Version %s', 'forminator' ), esc_html( $res->version_installed ) ) . '</span>';

								if ( $res->is_installed && $res->has_update ) {
									/* translators: Plugin latest version */
									echo '<span class="sui-tag sui-tag-sm sui-tag-yellow addons-update-tag">' . sprintf( esc_html__( 'v%s update available', 'forminator' ), esc_html( $res->version_latest ) ) . '</span>';
								}

								if ( $res->is_installed && $res->is_active ) {
									echo '<span class="sui-tag sui-tag-sm sui-tag-blue">' . esc_html__( 'Active', 'forminator' ) . '</span>';
								}
							}
							?>

						</div>

						<?php
						if ( FORMINATOR_PRO && $res->is_installed && $res->has_update ) {
							Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
									'label' => esc_html__( 'Update', 'forminator' ),
									'icon'  => 'update',
									'color' => 'blue',
									'class' => 'addons-actions',
									'attrs' => array(
										'data-action'  => 'addons-update',
										'data-addon'   => esc_attr( $res->pid ),
										'data-nonce'   => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
										'data-version' => sprintf( esc_html__( 'Version %s', 'forminator' ), esc_html( $res->version_latest ) ),
									),
								)
							);
						}
						?>

						<?php if ( ! FORMINATOR_PRO ) { ?>
							<a
								href="https://wpmudev.com/project/forminator-pro/?coupon=FORMINATOR-SUBSCRIPTIONS&checkout=0&utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_stripe-addon"
								target="_blank"
								class="sui-button sui-button-purple"
							>
								<?php esc_html_e( 'Try Pro For 10% Off', 'forminator' ); ?>
							</a>
						<?php } ?>

					</div>

				</div>

			</div>

			<div class="sui-box-body">

				<div class="sui-tabs sui-tabs-flushed">

					<div role="tablist" class="sui-tabs-menu">

						<button
							type="button"
							role="tab"
							id="tab-details-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-item active"
							aria-controls="tab-content-details-<?php echo esc_attr( $pid ); ?>"
							aria-selected="true"
						>
							<?php esc_html_e( 'Details', 'forminator' ); ?>
						</button>

						<button
							type="button"
							role="tab"
							id="tab-features-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-item"
							aria-controls="tab-content-features-<?php echo esc_attr( $pid ); ?>"
							aria-selected="false"
							tabindex="-1"
						>
							<?php esc_html_e( 'Features', 'forminator' ); ?>
						</button>

						<button
							type="button"
							role="tab"
							id="tab-changelog-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-item"
							aria-controls="tab-content-changelog-<?php echo esc_attr( $pid ); ?>"
							aria-selected="false"
							tabindex="-1"
						>
							<?php esc_html_e( 'Changelog', 'forminator' ); ?>
						</button>

					</div>

					<div class="sui-tabs-content">

						<div
							role="tabpanel"
							tabindex="0"
							id="tab-content-details-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-content active"
							aria-labelledby="tab-details-<?php echo esc_attr( $pid ); ?>"
						>

							<h4><?php esc_html_e( 'Description', 'forminator' ); ?></h4>

							<p><?php echo isset( $res->info ) ? esc_html( $res->info ) : ''; ?></p>

						</div>

						<div
							role="tabpanel"
							tabindex="0"
							id="tab-content-features-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-content"
							aria-labelledby="tab-features-<?php echo esc_attr( $pid ); ?>"
							hidden
						>

							<?php foreach ( $features as $group => $feature ) : ?>

								<ul>

									<?php foreach ( $feature as $item ) : ?>

										<li>
											<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
											<?php echo esc_html( $item ); ?>
										</li>

									<?php endforeach; ?>

								</ul>

							<?php endforeach; ?>

						</div>

						<div
							role="tabpanel"
							tabindex="0"
							id="tab-content-changelog-<?php echo esc_attr( $pid ); ?>"
							class="sui-tab-content"
							aria-labelledby="tab-changelog-<?php echo esc_attr( $pid ); ?>"
							hidden
						>

							<?php
							foreach ( $res->changelog as $idx => $log ) {
								if ( isset( $log['version'] ) ) {
									?>

									<div class="forminator-addon-changelog">

										<div class="forminator-addon-changelog--header">

											<?php
											$title = '<h4>';
												/* translators: Log version */
												$title .= '<span class="sui-tag sui-tag-sm sui-tag-purple">' . sprintf( esc_html__( 'Version %s', 'forminator' ), esc_attr( $log['version'] ) ) . '</span>';
											if ( $log['version'] === $res->version_latest ) {
												$title .= '<span class="sui-tag sui-tag-sm">' . esc_html__( 'Current', 'forminator' ) . '</span>';
											}
											$title .= '</h4>';

											echo wp_kses_post( $title );
											?>

											<p><?php echo esc_html( date( 'F j, Y', $log['time'] ) ); ?></p>

										</div>

										<div class="forminator-addon-changelog--body">
											<?php echo wp_kses_post( $log['log'] ); ?>
										</div>

									</div>

									<?php
								}
							}
							?>

						</div>

					</div>

				</div>

			</div>

			<div class="sui-box-footer sui-content-separated">

				<a
					href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/?utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_stripe-addon_docs#add-ons"
					target="_blank"
					class="sui-button sui-button-ghost"
				>
					<?php esc_html_e( 'Documentation', 'forminator' ); ?>
				</a>

				<button
					class="sui-button addons-modal-close"
					data-addon="<?php echo esc_attr( $res->pid ); ?>"
					data-element="forminator-modal-addons-details"
				>
					<?php esc_html_e( 'Close', 'forminator' ); ?>
				</button>

			</div>

		</div><!-- END .sui-box -->

	</div><!-- END .sui-modal-content -->

</div><!-- END .sui-modal -->
