<?php
/**
 * Plugin Name: 3D Product configurator for WooCommerce
 * Description: Complex visualisation and configuration made simple
 * Plugin URI: https://wordpress.org/plugins/expivi/
 * Version: 2.7.6
 * Author: Expivi
 * Author URI: https://www.expivi.com/
 * Text Domain: expivi
 *
 * WC requires at least: 4.5.0
 * WC tested up to: 7.1.0
 *
 * Copyright: Expivi
 *
 * @package Expivi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Define path to plugin file.
 */
if ( ! defined( 'XPV_PLUGIN_FILE' ) ) {
	define( 'XPV_PLUGIN_FILE', __FILE__ );
}

require_once dirname( XPV_PLUGIN_FILE ) . '/classes/class-autoloader.php';

/**
 * Load packages.
 */
if ( ! AutoLoader::init() ) {
	die( 'Error: Expivi could not load packages!' );
}

require_once dirname( XPV_PLUGIN_FILE ) . '/classes/class-expivi.php';

/**
 * Expivi core function.
 */
function XPV() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return Expivi::instance();
}

/**
 * Register instance in globals.
 */
$GLOBALS['expivi'] = XPV();
