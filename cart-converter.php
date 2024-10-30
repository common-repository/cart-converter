<?php
/*
Plugin Name: Cart Converter
Plugin URI: https://wordpress.org/plugins/cart-converter/
Description: A free, cart-abandonment plugin for WooCommerce
Author: John Peden
Version: 1.0.5
Author URI: http://tweakdigital.co.uk/
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('is_plugin_active'))
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// deactive plugin if don't have installed woocommerce 
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	if ( is_plugin_active( plugin_basename( __FILE__ ) ) )
		deactivate_plugins( plugin_basename( __FILE__ ) );

	add_action( 'admin_notices', 'woocommerce_not_active' );
	
} else {

	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'cart_converter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	//defines
	if ( !defined('CART_CONVERTER') )
	    define('CART_CONVERTER', trim(dirname(plugin_basename(__FILE__)), '/'));

	if ( !defined('CART_CONVERTER_BASENAME') )
	    define('CART_CONVERTER_BASENAME', plugin_basename(__FILE__));

	if ( !defined('CART_CONVERTER_DIR') )
	    define('CART_CONVERTER_DIR', WP_PLUGIN_DIR . '/' . CART_CONVERTER);

	if ( !defined('CART_CONVERTER_URL') ){
		define('CART_CONVERTER_URL', plugins_url( "", __FILE__ ) );
	}

	if ( !defined("CART_CONVERTER_NAME") ){
		define("CART_CONVERTER_NAME", "Cart Converter");
	}
	if ( !defined("CART_CONVERTER_PREFIX") ){
		define("CART_CONVERTER_PREFIX", "cart_converter");
	}
	/** Contant for current version */
	if ( !defined('CART_CONVERTER_VERSION') ){
		define('CART_CONVERTER_VERSION', "1.0" );
	}
	if ( !defined('CART_CONVERTER_DB_VERSION') ){
		define( "CART_CONVERTER_DB_VERSION", "1.0" );
	}

	/**
	 * Load all libraries and related
	 */
	if ( !class_exists("MainController") ){
		include_once("autoload.php");
	}

	// action on activate plugin: create tables
	register_activation_hook( __FILE__, array( 'MainController', 'setup_db' ) );
	register_activation_hook( __FILE__, array( 'CronController', 'add_collecting_job' ) );
	register_activation_hook( __FILE__, array( 'CronController', 'add_sendmail_job' ) );

	// deactive plugin: remove cron job
	register_deactivation_hook( __FILE__, array( 'MainController', 'deactivate' ) );
}

if ( ! function_exists( 'woocommerce_not_active' ) ) {
	function woocommerce_not_active() {
		$message = sprintf(
			__( '%sWooCommerce Ultimate Vendor.%s This version requires WooCommerce 2.4 or newer. Please %sinstall WooCommerce version 2.4 or newer%s', 'woorei' ),
			'<strong>',
			'</strong>',
			'<a href="' . admin_url( 'plugin-install.php' ) . '?tab=search&s=woocommerce">',
			'&nbsp;&raquo;</a>' 
		);
		echo sprintf( '<div class="error"><p>%s</p></div>', $message );
	}
}