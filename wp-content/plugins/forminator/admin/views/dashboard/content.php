<section class="wpmudev-dashboard-section">

	<?php $this->template( 'dashboard/widgets/widget-resume' ); ?>

	<div class="sui-row">

		<div class="sui-col-md-6">

			<?php $this->template( 'dashboard/widgets/widget-cform' ); ?>

			<?php $this->template( 'dashboard/widgets/widget-poll' ); ?>

		</div>

		<div class="sui-col-md-6">

			<?php
			if ( ! FORMINATOR_PRO ) {
				$this->template( 'dashboard/widgets/widget-upgrade' );
			}
			?>

			<?php $this->template( 'dashboard/widgets/widget-quiz' ); ?>

		</div>

	</div>

	<?php
	$notice_dismissed = get_option( 'forminator_dismiss_feature_1170', false );
	$version_upgraded = get_option( 'forminator_version_upgraded', false );

	if ( ! $notice_dismissed && $version_upgraded && forminator_is_show_documentation_link() && ! isset( $_GET['createnew'] ) ) {
		$this->template( 'dashboard/new-feature-notice' );
	}
	?>

</section>
