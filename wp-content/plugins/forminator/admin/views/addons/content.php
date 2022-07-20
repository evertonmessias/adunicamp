<?php
global $current_user;
$projects = $this->get_addons_by_action();
?>

<div id="forminator-addons" class="sui-tabs">

	<div role="tablist" class="sui-tabs-menu">

		<button
			type="button"
			role="tab"
			id="all-addons"
			class="sui-tab-item active"
			aria-controls="all-addons-content"
			aria-selected="true"
		>
			<?php esc_html_e( 'All', 'forminator' ); ?>
		</button>

		<?php
		// !!! WARNING.
		// Enable when there are more than 2 add-ons.
		//
		// <button.
		// type="button".
		// role="tab".
		// id="installed-addons".
		// class="sui-tab-item".
		// aria-controls="installed-addons-content".
		// aria-selected="false".
		// tabindex="-1".
		// >
		// Installed.
		// </button>.

		// <button.
		// type="button".
		// role="tab".
		// id="available-addons".
		// class="sui-tab-item".
		// aria-controls="available-addons-content".
		// aria-selected="false".
		// tabindex="-1".
		// >
		// Available.
		// </button>.
		?>

		<button
			type="button"
			role="tab"
			id="updates-addons"
			class="sui-tab-item"
			aria-controls="updates-addons-content"
			aria-selected="false"
			tabindex="-1"
		>
			<?php esc_html_e( 'Updates', 'forminator' ); ?>
			<?php if ( 0 === count( $projects['update'] ) ) { ?>
				<span class="sui-tag sui-tag-sm" style="pointer-events: none;"><?php echo esc_html( count( $projects['update'] ) ); ?></span>
			<?php } else { ?>
				<span class="sui-tag sui-tag-yellow sui-tag-sm" style="pointer-events: none;"><?php echo esc_html( count( $projects['update'] ) ); ?></span>
			<?php } ?>
		</button>

		<?php
		// !!! WARNING.
		// Enable when there are more than 2 add-ons.
		//
		// <div class="sui-tab-search">.
		// <div class="sui-control-with-icon">.
		// <input.
		// class="sui-form-control".
		// placeholder="Search".
		// >
		// <span class="sui-icon-magnifying-glass-search" aria-hidden="true"></span>.
		// </div>.
		// </div>.
		?>
	
	</div>

	<div class="sui-tabs-content">

		<div
			role="tabpanel"
			tabindex="0"
			id="all-addons-content"
			class="sui-tab-content active"
			aria-labelledby="all-addons"
		>

			<?php
			if ( empty( $projects['all'] ) ) {
				Forminator_Admin_Addons_page::get_instance()->render_template(
					'admin/views/addons/content-empty',
					array(
						'title'       => esc_html__( 'No Add-Ons', 'forminator' ),
						'description' => esc_html__( 'We couldn\'t find any add-on listed. Perhaps refresh the page and try again?', 'forminator' ),
					)
				);
			} else {
				?>

				<div class="sui-row">
					
					<?php
					foreach ( $projects['all'] as $idx => $addons ) {
						if ( ! empty( $addons ) ) {
							$idx ++;

							Forminator_Admin_Addons_page::get_instance()->addons_render( 'addons-list', $addons->pid, $addons );

							// !!! WARNING.
							// Enable when there are more than 2 add-ons.
							//
							// Close current row and open a new one.
							// if ( 0 === $idx % 2 ) :.
							// echo '</div><div class="sui-row">';.
							// endif;.
						}
					}
					?>

				</div>

			<?php } ?>

		</div>

		<?php
		// !!! WARNING.
		// Enable when there are more than 2 add-ons.
		//
		// <div.
		// role="tabpanel".
		// tabindex="0".
		// id="installed-addons-content".
		// class="sui-tab-content".
		// aria-labelledby="installed-addons".
		// hidden.
		// >Installed Add-ons</div>.

		// <div.
		// role="tabpanel".
		// tabindex="0".
		// id="available-addons-content".
		// class="sui-tab-content".
		// aria-labelledby="available-addons".
		// >Available Add-ons</div>.
		?>

		<div
			role="tabpanel"
			tabindex="0"
			id="updates-addons-content"
			class="sui-tab-content"
			aria-labelledby="updates-addons"
		>

			<?php
			if ( empty( $projects['update'] ) ) {
				Forminator_Admin_Addons_page::get_instance()->render_template(
					'admin/views/addons/content-empty',
					array(
						'title'       => sprintf( esc_html__( 'All good, %s!', 'forminator' ), $current_user->display_name ),
						'description' => esc_html__( 'No add-ons require an update at this time. Use the button below to check again.', 'forminator' ),
						'refresh'     => true,
					)
				);
			} else {
				?>

				<div class="sui-row">
					
					<?php
					foreach ( $projects['update'] as $idx => $addons ) {
						if ( ! empty( $addons ) ) {
							$idx ++;
							Forminator_Admin_Addons_page::get_instance()->addons_render( 'addons-list', $addons->pid, $addons );

							// !!! WARNING.
							// Enable when there are more than 2 add-ons.
							//
							// Close current row and open a new one.
							// if ( 0 === $idx % 2 ) :.
							// echo '</div><div class="sui-row">';.
							// endif;.
						}
					}
					?>

				</div>

			<?php } ?>

		</div>

	</div>

</div>

<?php
if ( ! empty( $projects['all'] ) ) {
	foreach ( $projects['all'] as $slug => $addons ) {
		if ( ! empty( $addons ) ) {
			Forminator_Admin_Addons_page::get_instance()->addons_render( 'addons-activate-popup', $addons->pid, $addons );
			Forminator_Admin_Addons_page::get_instance()->addons_render( 'addon-details', $addons->pid, $addons );
		}
	}
}
?>
