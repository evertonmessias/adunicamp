<?php
if ( empty( $addons ) || empty( $addons->pid ) ) {
	return;
}

if ( 3953609 === $addons->pid ) {
	$addons_slug = 'stripe';
} else {
	$addons_slug = '';
}

$is_network_active = $addons->is_network_admin && is_plugin_active_for_network( $addons->filename );

$tags = array(
	'label' => esc_html__( 'PRO', 'forminator' ),
	'class' => 'sui-tag sui-tag-sm sui-tag-purple',
);

if ( FORMINATOR_PRO ) {

	if ( $addons->is_installed ) {

		if ( is_plugin_active( $addons->filename ) ) {
			$tags['label'] = esc_html__( 'Active' );
			$tags['class'] = 'sui-tag sui-tag-sm sui-tag-blue';
		} else {
			$tags['label'] = esc_html__( 'Inactive' );
			$tags['class'] = 'sui-tag sui-tag-sm';
		}
	} else {
		$tags['label'] = esc_html__( 'Not Installed' );
		$tags['class'] = 'sui-tag sui-tag-sm';
	}

	$actions      = array();
	$main_actions = array();
	if ( ! $addons->is_installed ) {
		$actions['install'] = array(
			'name'    => __( 'Install', 'forminator' ),
			'icon'    => 'sui-icon-download',
			'class'   => 'sui-button-blue addons-actions',
			'loading' => __( 'Installing', 'forminator' ),
			'nonce'   => wp_create_nonce( 'forminator_popup_addons_actions' ),
			'data'    => array(
				'action' => 'addons-install',
				'addons' => $addons->pid,
			),
		);
	} else {
		if ( ! is_plugin_active( $addons->filename ) ) {
			$actions['activate']    = array(
				'name'    => ( $is_network_active ? __( 'Network Activate', 'forminator' ) : __( 'Activate', 'forminator' ) ),
				'icon'    => 'sui-icon-power-on-off',
				'class'   => 'sui-button-blue addons-actions',
				'loading' => __( 'Activating', 'forminator' ),
				'nonce'   => wp_create_nonce( 'forminator_popup_addons_actions' ),
				'data'    => array(
					'action' => 'addons-activate',
					'addons' => $addons->pid,
				),
			);
			$main_actions['delete'] = array(
				'name'    => __( 'Delete', 'forminator' ),
				'icon'    => 'sui-icon-trash',
				'class'   => 'sui-button-ghost addons-actions',
				'loading' => __( 'Deleting', 'forminator' ),
				'nonce'   => wp_create_nonce( 'forminator_popup_addons_actions' ),
				'data'    => array(
					'action' => 'addons-delete',
					'addons' => $addons->pid,
				),
			);
		} else {
			$actions['configure'] = array(
				'name'     => __( 'Configure', 'forminator' ),
				'icon'     => 'sui-icon-wrench-tool',
				'class'    => 'addons-configure',
				'nonce'    => wp_create_nonce( 'forminator_' . $addons_slug . '_settings_modal' ),
				'loading'  => '',
				'image'    => forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo.png',
				'image_x2' => forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo@2x.png',
				'title'    => sprintf( __( 'Connect %s Account', 'forminator' ), ucfirst( $addons_slug ) ),
				'data'     => array(
					'action' => $addons_slug . '-connect-modal',
					'addons' => $addons->pid,
				),
			);

			$main_actions['deactivate'] = array(
				'name'    => ( $is_network_active ? __( 'Network Deactivate', 'forminator' ) : __( 'Deactivate', 'forminator' ) ),
				'icon'    => 'sui-icon-power-on-off',
				'class'   => 'sui-button-ghost wpmudev-open-modal',
				'loading' => __( 'Deactivating', 'forminator' ),
				'nonce'   => wp_create_nonce( 'forminator_popup_addons_actions' ),
				'data'    => array(
					'action' => 'addons-deactivate',
					'addons' => $addons->pid,
				),
			);
		}
	}
} ?>

<div class="sui-col-md-6 addons-<?php echo esc_attr( $addons->pid ); ?>">

    <div id="forminator-addon-<?php echo esc_attr( $addons->pid ); ?>__card" class="sui-box forminator-addon-card">

		<div class="forminator-addon-card--body">

			<?php if ( forminator_is_show_branding() ) : ?>
				<div class="forminator-addon-card--body-left" aria-hidden="true">
					<div class="forminator-addon-card--thumb" style="background-image: url(<?php echo esc_url( $addons->url->thumbnail ); ?>);"></div>
				</div>
			<?php endif; ?>

		    <div class="forminator-addon-card--body-right">

				<div class="forminator-addon-card--title">

				    <h3><?php echo esc_html( $addons->name ); ?></h3>

				    <div class="forminator-addon-card--tags">

						<span class="<?php echo esc_attr( $tags['class'] ); ?>"><?php echo esc_html( $tags['label'] ); ?></span>

						<?php if ( FORMINATOR_PRO && $addons->is_installed && $addons->has_update ) { ?>
						    <?php /* translators: Plugin latest version */ ?>
						    <span class="sui-tag sui-tag-yellow sui-tag-sm"><?php printf( esc_html__( 'v%s update available', 'forminator' ), esc_html( $addons->version_latest ) ); ?></span>
						<?php } ?>

				    </div>

				</div>

				<p class="sui-description"><?php echo esc_html( $addons->info ); ?></p>

		    </div>

		</div>

		<div class="forminator-addon-card--footer">

		    <div class="forminator-addon-card--footer-left">

				<a
					role="button"
					class="forminator-pseudo-link addons-page-details"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addons_page_details' ) ); ?>"
					data-form-id="<?php echo esc_attr( $addons->pid ); ?>"
					data-modal-title="<?php echo esc_attr( $addons->name ); ?>"
					data-modal-open="forminator-modal-addons-details-<?php echo esc_attr( $addons->pid ); ?>"
					data-modal-mask="false"
					data-modal="addons_page_details"
				>
					<?php esc_html_e( 'Details', 'forminator' ); ?>
				</a>

		    </div>

		    <div class="forminator-addon-card--footer-right">

				<?php if ( FORMINATOR_PRO ) { ?>

				    <?php if ( $addons->is_installed ) { ?>

						<?php
						// BUTTON: Blue.
						if ( $addons->has_update ) {
						    Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
								    'compound' => true,
								    'label'    => esc_html__( 'Update', 'forminator' ),
								    'icon'     => 'update',
								    'color'    => 'blue',
								    'class'    => 'addons-actions',
								    'attrs'    => array(
										'data-action' => 'addons-update',
										'data-addon'  => esc_attr( $addons->pid ),
										'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
										'data-version' => sprintf( esc_html__( 'Version %s', 'forminator' ), esc_html( $addons->version_latest ) ),
								    )
								)
						    );
						}

						if ( is_plugin_active( $addons->filename ) ) {

						    // BUTTON: Configure.
						    Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
								    'compound' => true,
								    'label'    => esc_html__( 'Configure', 'forminator' ),
								    'icon'     => 'wrench-tool',
									'id'       => 'addons-configure__' . $addons_slug,
								    'class'    => 'addons-configure',
								    'attrs'    => array(
										'data-action'		 => esc_attr( $addons_slug . '-connect-modal' ),
										'data-addon'		  => esc_attr( $addons->pid ),
										'data-nonce'		  => esc_attr( wp_create_nonce( 'forminator_' . $addons_slug . '_settings_modal' ) ),
										'data-modal-nonce'    => esc_attr( wp_create_nonce( 'forminator_' . $addons_slug . '_settings_modal' ) ),
										'data-modal-image'    => esc_url( forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo.png' ),
										'data-modal-image-x2' => esc_url( forminator_plugin_url() . 'assets/images/' . $addons_slug . '-logo@2x.png' ),
										'data-modal-title'    => esc_html( sprintf( __( 'Connect %s Account', 'forminator' ), ucfirst( $addons_slug ) ) ),
								    )
								)
						    );

						    // BUTTON: Deactivate.
						    Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
								    'compound' => true,
								    'label'    => ( $is_network_active ? __( 'Network Deactivate', 'forminator' ) : __( 'Deactivate', 'forminator' ) ),
								    'icon'     => 'power-on-off',
								    'ghost'    => true,
								    'class'    => 'wpmudev-open-modal',
								    'attrs'    => array(
										'data-action'      => 'addons-deactivate',
										'data-addon'       => esc_attr( $addons->pid ),
										'data-nonce'       => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
										'data-modal'       => 'addons-deactivate',
										'data-modal-title' => esc_html__( 'Deactivate Add-ons', 'forminator' ),
										'data-modal-content' => sprintf( __( 'You are trying to deactivate <strong>%s</strong> which is being used by the following forms. This can break the functionality of the forms. Are you sure you want to proceed?', 'forminator' ), esc_html( $addons->name ) ),
										'data-addon-slug' => esc_attr( $addons_slug ),
								    ),
								    'disabled' => ( $is_network_active ),
								)
						    );
						} else {

						    // BUTTON: Activate.
						    Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
								    'compound' => true,
								    'label'    => ( $is_network_active ? __( 'Network Activate', 'forminator' ) : __( 'Activate', 'forminator' ) ),
								    'icon'     => 'power-on-off',
								    'color'    => 'blue',
								    'class'    => 'addons-actions',
								    'attrs'    => array(
										'data-action' => 'addons-activate',
										'data-addon'  => esc_attr( $addons->pid ),
										'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
								    )
								)
						    );

						    // BUTTON: Delete.
						    Forminator_Admin_Addons_page::get_instance()->render_template(
								'admin/views/addons/action-button',
								array(
								    'compound' => true,
								    'label'    => esc_html__( 'Delete', 'forminator' ),
								    'icon'     => 'trash',
								    'ghost'    => true,
								    'class'    => 'addons-actions',
								    'attrs'    => array(
										'data-action' => 'addons-delete',
										'data-addon'  => esc_attr( $addons->pid ),
										'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
								    )
								)
						    );
						}
						?>
						<?php
					} else {

						Forminator_Admin_Addons_page::get_instance()->render_template(
						    'admin/views/addons/action-button',
						    array(
								'label' => esc_html__( 'Install', 'forminator' ),
								'icon'  => 'download',
								'color' => 'blue',
								'class' => 'addons-actions',
								'attrs' => array(
								    'data-action' => 'addons-install',
								    'data-addon'  => esc_attr( $addons->pid ),
								    'data-nonce'  => esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ),
								)
						    )
						);

				    }
					?>
				<?php } else { ?>
				    <a
						href="https://wpmudev.com/project/forminator-pro/?coupon=FORMINATOR-SUBSCRIPTIONS&checkout=0&utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_stripe-addon"
						target="_blank"
						class="sui-button sui-button-purple"
				    >
						<?php esc_html_e( 'Try Pro for 10% Off', 'forminator' ); ?>
				    </a>
				<?php } ?>

		    </div>

		</div>

    </div>

</div>
