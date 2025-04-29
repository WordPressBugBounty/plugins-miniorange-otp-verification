<?php
/**
 * Autoload file for some common functions used all over the addon
 *
 * @package resendcontrol
 */

use ROC\RcSplClassLoader;
use ROC\Handler\ResendControlAddonHandler;
use OTP\Helper\MoUtility;
use OTP\Helper\AddOnList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MO_ROC_DIR', plugin_dir_path( __FILE__ ) );
define( 'MO_ROC_URL', plugin_dir_url( __FILE__ ) );

if ( ! class_exists( 'OTP\Helper\AddOnList' ) ) {
	return;
}
require 'class-rcsplclassloader.php';

$idp_class_loader = new RcSplClassLoader( 'ROC', realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' ) );
$idp_class_loader->register();
$list    = AddOnList::instance();
$handler = ResendControlAddonHandler::instance();
$list->add( $handler->getAddOnKey(), $handler );
add_action( 'mo_otp_verification_add_on_controller', 'mo_show_addon_settings_page', 1, 1 );

/**
 * This function hooks into the mo_otp_verification_add_on_controller
 * hook to show the limit OTP add-on settings page.
 */
function mo_show_addon_settings_page() {
	require MO_ROC_DIR . 'controllers/main-controller.php';
}

/*
|------------------------------------------------------------------------------------------------------
| SOME COMMON FUNCTIONS USED ALL OVER THE ADD-ON
|------------------------------------------------------------------------------------------------------
 */

/**
 * This function is used to handle the add-ons get option call. A separate function has been created so that
 * we can manage getting of database values all from one place. Any changes need to be made can be made here
 * rather than having to make changes in all of the add-on files.
 *
 * Calls the mains plugins get_mo_option function.
 *
 * @param string      $string - option name.
 * @param bool|string $prefix - prefix for database keys.
 * @return String
 */
function get_mo_rc_option( $string, $prefix = null ) {
	$string = ( null === $prefix ? 'mo_rc_sms_' : $prefix ) . $string;
	return apply_filters( 'get_mo_option', get_site_option( $string ) );
}

/**
 * This function is used to handle the add-ons update option call. A separate function has been created so that
 * we can manage getting of database values all from one place. Any changes need to be made can be made here
 * rather than having to make changes in all of the add-on files.
 *
 * Calls the mains plugins get_mo_option function.
 *
 * @param string      $option_name - key of the option name.
 * @param string      $value - value of the option.
 * @param null|string $prefix - prefix of the key for database entry.
 */
function update_mo_rc_option( $option_name, $value, $prefix = null ) {
	$option_name = ( null === $prefix ? 'mo_rc_sms_' : $prefix ) . $option_name;
	update_site_option( $option_name, apply_filters( 'update_mo_option', $value, $option_name ) );
}

/**
 * Used for transalating the string
 *
 * @param string $string - option name to be deleted.
 */
function moroc_( $string ) {
	return $string;
}

/**
 * Returns TRUE or FALSE depending on if the POLYLANG plugin is active.
 * This is used to check if the translation should use the polylang
 * function or the default local translation.
 *
 * @return boolean
 */
function is_morc_polylang_installed() {
	return function_exists( 'pll__' ) && function_exists( 'pll_register_string' );
}

