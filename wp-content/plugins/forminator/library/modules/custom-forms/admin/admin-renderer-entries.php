<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_Renderer_Entries
 *
 * @since 1.0.5
 */
class Forminator_CForm_Renderer_Entries extends Forminator_CForm_View_Page {

	/** @noinspection PhpMissingParentConstructorInspection
	 *
	 * Construct Entries Renderer
	 *
	 * @since 1.0.5
	 *
	 * @param string $folder
	 */
	public function __construct( $folder ) {
		$this->entries_construct( $folder );
	}
}
