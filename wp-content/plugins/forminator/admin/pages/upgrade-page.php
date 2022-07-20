<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Dashboard_Page
 *
 * @since 1.0
 */
class Forminator_Upgrade_Page extends Forminator_Admin_Page {

	public function render() {
		$modules = forminator_get_modules();
		$this->template(
			'upgrade/upgrade-content',
			array(
				'modules' => $modules,
			)
		);
	}

}
