<?php
/**
 * Initializes plugin data.
 * Contains defination of common functions.
 *
 * @package miniorange-otp-verification
 */

use OTP\Helper\FormList;
use OTP\Helper\FormSessionData;
use OTP\Helper\MoUtility;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\MoOTPSplClassLoader;
use OTP\LicenseLibrary\Classes\Mo_License_Library;
use OTP\Helper\MoConstants;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOV_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOV_URL', plugin_dir_url( __FILE__ ) );

$package_data = json_decode( initialize_package_json() );

define( 'MOV_VERSION', $package_data->version );
define( 'MOV_TYPE', $package_data->type );
define( 'MOV_HOST', $package_data->hostname );
define( 'MOV_PORTAL', $package_data->portal );
define( 'MOV_DEFAULT_CUSTOMERKEY', $package_data->dcustomerkey );
define( 'MOV_DEFAULT_APIKEY', $package_data->dapikey );
define( 'MOV_SSL_VERIFY', $package_data->sslverify );
define( 'MOV_CSS_URL', MOV_URL . 'includes/css/mo_customer_validation_style.min.css?version=' . MOV_VERSION );
define( 'MOV_FORM_CSS', MOV_URL . 'includes/css/mo_forms_css.min.css?version=' . MOV_VERSION );
define( 'MO_INTTELINPUT_CSS', MOV_URL . 'includes/css/intlTelInput.min.css?version=' . MOV_VERSION );
define( 'MOV_JS_URL', MOV_URL . 'includes/js/settings.min.js?version=' . MOV_VERSION );
define( 'VALIDATION_JS_URL', MOV_URL . 'includes/js/formValidation.min.js?version=' . MOV_VERSION );
define( 'MO_INTTELINPUT_JS', MOV_URL . 'includes/js/intlTelInput.min.js?version=' . MOV_VERSION );
define( 'MO_DROPDOWN_JS', MOV_URL . 'includes/js/dropdown.min.js?version=' . MOV_VERSION );
define( 'MOV_LOADER_URL', MOV_URL . 'includes/images/loader.gif' );
define( 'MOV_DONATE', MOV_URL . 'includes/images/donate.png' );
define( 'MOV_PAYPAL', MOV_URL . 'includes/images/paypal.png' );
define( 'MOV_FIREBASE', MOV_URL . 'includes/images/firebase.png' );
define( 'MOV_NETBANK', MOV_URL . 'includes/images/netbanking.png' );
define( 'MOV_CARD', MOV_URL . 'includes/images/card.png' );
define( 'MOV_LOGO_URL', MOV_URL . 'includes/images/logo.png' );
define( 'MOV_ICON', MOV_URL . 'includes/images/miniorange_icon.png' );
define( 'MOV_ICON_GIF', MOV_URL . 'includes/images/mo_icon.gif' );
define( 'MO_CUSTOM_FORM', MOV_URL . 'includes/js/customForm.js?version=' . MOV_VERSION );
define( 'MOV_ADDON_DIR', MOV_DIR . 'addons/' );
define( 'MOV_USE_POLYLANG', true );
define( 'MO_TEST_MODE', $package_data->testmode );
define( 'MO_FAIL_MODE', $package_data->failmode );
define( 'MOV_SESSION_TYPE', $package_data->session );
define( 'MOV_MAIL_LOGO', MOV_URL . 'includes/images/mo_support_icon.png' );
define( 'MOV_OFFERS_LOGO', MOV_URL . 'includes/images/mo_sale_icon.png' );
define( 'MOV_FEATURES_GRAPHIC', MOV_URL . 'includes/images/mo_features_graphic.png' );
define( 'MOV_TYPE_PLAN', $package_data->typeplan );
define( 'MOV_LICENSE_NAME', $package_data->licensename );

define( 'MOV_MAIN_CSS', MOV_URL . 'includes/css/mo-main.min.css' );

require 'class-mootpsplclassloader.php';

$idp_class_loader = new MoOTPSplClassLoader( 'OTP', realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' ) );
$idp_class_loader->register();
require_once 'views/common-elements.php';
initialize_forms();

if ( file_exists( MOV_DIR . MoConstants::LICENCE_SERVICE_FILE ) ) {
	new Mo_License_Library();
}

/**
 * Initializes hanlders of forms.
 */
function initialize_forms() {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( MOV_DIR . 'handler/forms', RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $iterator as $it ) {
		$filename   = $it->getFilename();
		$filename   = str_replace( 'class-', '', $filename );
		$class_name = 'OTP\\Handler\\Forms\\' . str_replace( '.php', '', $filename );

		$handler_list = FormList::instance();

		$form_handler = $class_name::instance();
		$handler_list->add( $form_handler->get_form_key(), $form_handler );
	}
}



/**
 * Returns admin post url.
 */
function admin_post_url() {
	return admin_url( 'admin-post.php' ); }

/**
 * Returns wp ajax url.
 */
function wp_ajax_url() {
	return admin_url( 'admin-ajax.php' ); }

/**
 * Used for transalating the string
 *
 * @param string $string - option name to be deleted.
 */
function mo_( $string ) {

	$string = preg_replace( '/\s+/S', ' ', $string );
	return is_scalar( $string )
			? ( MoUtility::is_polylang_installed() && MOV_USE_POLYLANG ? pll__( $string ) : __( $string, 'miniorange-otp-verification' ) ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText --common function for string translation.
			: $string;
}

/**
 * Updates the option set in the wp_option table.
 *
 * @param string $string - option name to be deleted.
 * @param string $type - value of the option.
 */
function mo_esc_string( $string, $type ) {

	if ( 'attr' === $type ) {
		return esc_attr( $string );
	} elseif ( 'url' === $type ) {
		return esc_url( $string );
	}

	return esc_attr( $string );

}

/**
 * Retrieved the value of the option in the wp_option table.
 *
 * @param string $string - option name to be deleted.
 * @param string $prefix - prefix of the option.
 */
function get_mo_option( $string, $prefix = null ) {
	$string = ( null === $prefix ? 'mo_customer_validation_' : $prefix ) . $string;
	return apply_filters( 'get_mo_option', get_site_option( $string ) );
}

/**
 * Updates the option set in the wp_option table.
 *
 * @param string $string - option name to be deleted.
 * @param string $value - value of the option.
 * @param string $prefix - prefix of the option.
 */
function update_mo_option( $string, $value, $prefix = null ) {
	$string = ( null === $prefix ? 'mo_customer_validation_' : $prefix ) . $string;
	update_site_option( $string, apply_filters( 'update_mo_option', $value, $string ) );
}

/**
 * Deletes the option set in the wp_option table.
 *
 * @param string $string - option name to be deleted.
 * @param string $prefix - prefix of the option.
 */
function delete_mo_option( $string, $prefix = null ) {
	$string = ( null === $prefix ? 'mo_customer_validation_' : $prefix ) . $string;
	delete_site_option( $string );
}

/**
 * Returns the plugin details like version, plan name.
 *
 * @param object $obj - object of the class.
 */
function get_mo_class( $obj ) {
	$namespace_class = get_class( $obj );
	return substr( $namespace_class, strrpos( $namespace_class, '\\' ) + 1 );
}

/**
 * To check if package.json file can be found through WP site URL or not.
 * BuildScript.php updates the package.json file content in the below function instead of package.json to be used further in autoload.php
 * example package.json string ["name"=>"miniorange-otp-verification","version"=>"3.5","type"=>"MiniOrangeGateway","testMode"=>false,"failMode"=>false,"hostname"=>"https:\/\/login.xecurify.com","dCustomerKey"=>"16555","dApiKey"=>"fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq","sslVerify"=>false,"session"=>"SESSION"]
 */
function initialize_package_json() {
	$package = wp_json_encode(
		array(
			'name'         => 'miniorange-otp-verification',
			'version'      => '5.2.6',
			'type'         => 'MiniOrangeGateway',
			'testmode'     => false,
			'failmode'     => false,
			'hostname'     => 'https://login.xecurify.com',
			'portal'       => 'https://portal.miniorange.com',
			'dcustomerkey' => '16555',
			'dapikey'      => 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq',
			'sslverify'    => false,
			'session'      => 'TRANSIENT',
			'typeplan'     => 'wp_otp_verification_basic_plan',
			'licensename'  => 'WP_OTP_VERIFICATION_PLUGIN',
		)
	);
	return $package;
}

