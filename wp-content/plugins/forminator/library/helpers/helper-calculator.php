<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Pull fields from formula
 *
 * @param string $formula Formula.
 * @return array
 */
function forminator_calculator_pull_fields( $formula ) {
	$field_types             = Forminator_Core::get_field_types();
	$increment_field_pattern = sprintf( '(%s)-\d+', implode( '|', $field_types ) );
	$pattern                 = '/\{(' . $increment_field_pattern . ')(\-[A-Za-z-_]+)?\}/';
	preg_match_all( $pattern, $formula, $matches );

	return $matches;
}
