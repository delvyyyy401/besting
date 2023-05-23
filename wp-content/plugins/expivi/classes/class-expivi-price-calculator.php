<?php
/**
 * Expivi Price Calculator
 *
 * @package Expivi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class for price calculation approaches.
 */
abstract class Expivi_Price_Calculator {
	/**
	 * Required function to calculate price.
	 *
	 * @param mixed $configured_product A single configured product.
	 *
	 * @return int
	 */
	abstract public function calculate( $configured_product ): int;
}
