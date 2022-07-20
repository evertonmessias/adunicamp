<?php
$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
?>
<div class="sui-row-with-sidenav">

	<div class="sui-sidenav">

		<ul class="sui-vertical-tabs sui-sidenav-hide-md">

			<li class="sui-vertical-tab <?php echo esc_attr( 'dashboard' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="dashboard"><?php esc_html_e( 'General', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'accessibility' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="accessibility"><?php esc_html_e( 'Accessibility', 'forminator' ); ?></a>
			</li>
			<li class="sui-vertical-tab <?php echo esc_attr( 'appearance-presets' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="appearance-presets"><?php esc_html_e( 'Appearance Presets', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'data' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="data"><?php esc_html_e( 'Data', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'captcha' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="captcha"><?php esc_html_e( 'CAPTCHA', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'import' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="import"><?php esc_html_e( 'Import', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'submissions' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></a>
			</li>

			<li class="sui-vertical-tab <?php echo esc_attr( 'payments' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="payments"><?php esc_html_e( 'Payments', 'forminator' ); ?></a>
			</li>

		</ul>

		<div class="sui-sidenav-settings">

			<div class="sui-form-field sui-sidenav-hide-lg">

				<label class="sui-label"><?php esc_html_e( 'Navigate', 'forminator' ); ?></label>

				<select id="forminator-sidenav" class="sui-select sui-mobile-nav">
					<option value="dashboard"><?php esc_html_e( 'General', 'forminator' ); ?></option>
					<option value="accessibility"><?php esc_html_e( 'Accessibility', 'forminator' ); ?></option>
					<option value="appearance-presets"><?php esc_html_e( 'Appearance Presets', 'forminator' ); ?></option>
					<option value="data"><?php esc_html_e( 'Data', 'forminator' ); ?></option>
					<option value="captcha"><?php esc_html_e( 'CAPTCHA', 'forminator' ); ?></option>
					<option value="import"><?php esc_html_e( 'Import', 'forminator' ); ?></option>
					<option value="submissions"><?php esc_html_e( 'Submissions', 'forminator' ); ?></option>
					<option value="payments"><?php esc_html_e( 'Payments', 'forminator' ); ?></option>
				</select>

			</div>

		</div>

	</div>

	<?php $this->template( 'settings/tab-general' ); ?>
	<?php $this->template( 'settings/tab-recaptcha' ); ?>
	<?php $this->template( 'settings/tab-appearance-presets' ); ?>
	<?php $this->template( 'settings/tab-data' ); ?>
	<?php $this->template( 'settings/tab-submissions' ); ?>
	<?php $this->template( 'settings/tab-payments' ); ?>
	<?php $this->template( 'settings/tab-accessibility' ); ?>
	<?php $this->template( 'settings/tab-import' ); ?>

</div>
