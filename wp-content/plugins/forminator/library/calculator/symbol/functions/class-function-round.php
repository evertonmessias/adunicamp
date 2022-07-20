<?php

/**
 * PHP round() function aka rounds a float.
 * Expects one or three parameters. The first
 * parameter is the value to round, the second
 * is the number of decimal digits to round to.
 * third = PHP_ROUND_HALF_UP
 * It defaults to 0.
 *
 * @see http://php.net/manual/en/ref.math.php
 */
class Forminator_Calculator_Symbol_Function_Round extends Forminator_Calculator_Symbol_Function_Abstract {

	/**
	 * @inheritdoc
	 */
	protected $identifiers = array( 'round' );

	/**
	 * @inheritdoc
	 * @throws Forminator_Calculator_Exception
	 */
	public function execute( $arguments ) {
		$count = count( $arguments );
		if ( ! in_array( $count, array( 1, 2 ), true ) ) {
			throw new Forminator_Calculator_Exception( 'Error: Expected one or two arguments, got ' . count( $arguments ) );
		}

		$number    = $arguments[0];
		$precision = isset( $arguments[1] ) ? (int) $arguments[1] : 0;

		return round( $number, $precision );
	}

}
