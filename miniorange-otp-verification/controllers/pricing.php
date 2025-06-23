<?php
/**
 * Controller for pricing tab.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoConstants;
use OTP\Helper\MoUtility;

$portal_host = MOV_PORTAL . '/initializePayment';
$vl          = MoUtility::mclv() && ! MoUtility::is_mg();
$nonce       = $admin_handler->get_nonce_value();

$miniorange_gateway_plan_features = array(
	mo_( 'Unlimited Validity on Transactions.' ),
	mo_( 'The SMS/Email transactions will be purchased from miniOrange' ),
	mo_( 'OTP Verification on 50+ forms' ),
);

$custom_gateway_plan_features = array(
	mo_( 'Support HTTP based custom SMS/Email gateways.' ),
	mo_( 'The SMS/Email transactions need to be purchased from your SMS/Email gateway.' ),
	mo_( 'miniOrange Gateway Supported.' ),
	mo_( 'Validate phone number length based on country.' ),
);

$twilio_gateway_plan_features = array(
	mo_( 'Suitable for Twilio SMS gateway users.' ),
	mo_( 'SMS transactions will be purchased from twilio.' ),
	mo_( 'miniOrange Gateway Support.' ),
	mo_( 'All features from Custom Gateway Plan included.' ),
);

$enterprise_plan_features = array(
	mo_( '<b>All features from Custom & Twilio Gateway Plan</b>' ),
	mo_( 'Elementor Form Support' ),
	mo_( 'WCFM Form Support' ),
	mo_( 'Backup Code/Master OTP' ),
	mo_( 'Transaction Report' ),
);

$woocommerce_plan_features = array(
	mo_( 'OTP & Notifications Via WhatsApp' ),
	mo_( 'WooCommerce order status & Stock Notifications.' ),
	'<b><a href="https://plugins.miniorange.com/register-login-account-phone-miniorange-otp" target="_blank">' . mo_( 'Registration & Login using only Phone' ) . '</a></b>',
	mo_( 'WCFM and Dokan Vendor Notifications.' ),
);

// Premium supprted forms.
	$premium_forms_custom_gateway = array(
		mo_( '50+ popular WordPress Forms and Themes supported' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Registration & Login using only Phone' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'WooCommerce Login/Registration Form' )   => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Gravity Forms' )                         => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Ninja Forms' )                           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Elementor Pro' )                         => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'WP Everest User Registration' )          => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Tutor LMS Login & Registration Forms' )  => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Jet Engine Form' )                       => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Checkout WC Form' )                      => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'WooCommerce Frontend Manager Registration Form (WCFM)' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'JetFormBuilder by Crocoblock' )          => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'WS Pro Contact Forms' )                  => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Fluent Conversational Form' )            => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Dokan Registration Form' )               => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Houzez Registration Form' )              => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
	);

	$premium_forms_mo_gateway = array(
		mo_( '50+ popular WordPress Forms and Themes supported' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Login/Registration Form' )   => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Registration & Login using only Phone' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Gravity Forms' )                         => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Ninja Forms' )                           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Elementor Pro' )                         => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'WP Everest User Registration' )          => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Tutor LMS Login & Registration Forms' )  => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Jet Engine Form' )                       => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Checkout WC Form' )                      => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'WooCommerce Frontend Manager Registration Form (WCFM)' ) => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'JetFormBuilder by Crocoblock' )          => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'WS Pro Contact Forms' )                  => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Fluent Conversational Form' )            => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Dokan Registration Form' )               => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Houzez Registration Form' )               => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
	);

	$premium_features_mo = array(
		mo_( 'WooCommerce Order Status SMS Notifications' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Stock Notifications' )        => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'OTP Spam Preventer' )                     => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Ultimate Member SMS Notifications' )      => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Password Reset OTP' )         => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Enable Country Code Dropdown' )           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom SMS & Email Template' )            => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom OTP Length & Validity' )           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Block Email Domains & Phone Numbers' )    => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'OTP Over Call - Twilio' )                 => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Frontend Manager Notifications' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Dokan Vendor Notifications' )             => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Allow OTP for Selected Country' )         => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Enable Alphanumeric OTP Format' )         => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Geolocation Based Country Code Dropdown Addon' ) => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Globally Banned Phone Numbers Blocking' ) => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Validate Phone number length based on Country' ) => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
	);

	$gateways_supported = array(
		mo_( 'Gateways Supported' )             => array(
			'description' => '50+ popular WordPress Forms and Themes supported',
			'feature'     => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ),
			'link'        => 'https://plugins.miniorange.com/supported-sms-email-gateways',
		),
		mo_( 'miniOrange SMS Gateway' )         => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom SMS/SMTP Gateway' )        => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Twilio SMS Gateway' )             => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'MSG-91 Gateway' )                 => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'AWS SNS Gateway' )                => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Test SMS Gateway Configuration' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Backup SMS Gateway' )             => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
	);

	$gateways_supported_mo = array(
		mo_( 'Gateways Supported' )             => array(
			'description' => '50+ popular WordPress Forms and Themes supported',
			'feature'     => array( 'checkmark', 'checkmark', 'checkmark' ),
			'link'        => 'https://plugins.miniorange.com/supported-sms-email-gateways',
		),
		mo_( 'miniOrange SMS Gateway' )         => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom SMS/SMTP Gateway' )        => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Twilio SMS Gateway' )             => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'MSG-91 Gateway' )                 => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'AWS SNS Gateway' )                => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Test SMS Gateway Configuration' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Backup SMS Gateway' )             => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark' ) ),
	);

	$premium_features = array(
		mo_( 'WooCommerce Order Status SMS Notifications' ) => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Stock Notifications' )        => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'OTP Spam Preventer' )                     => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Ultimate Member SMS Notifications' )      => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Password Reset OTP' )         => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Enable Country Code Dropdown' )           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom SMS & Email Template' )            => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Custom OTP Length & Validity' )           => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'Block Email Domains & Phone Numbers' )    => array( 'feature' => array( 'checkmark', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'OTP Over Call - Twilio' )                 => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
		mo_( 'WooCommerce Frontend Manager Notifications' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Dokan Vendor Notifications' )             => array( 'feature' => array( 'red_cross', 'red_cross', 'red_cross', 'checkmark' ) ),
		mo_( 'Allow OTP for Selected Country' )         => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Enable Alphanumeric OTP Format' )         => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Geolocation Based Country Code Dropdown Addon' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Globally Banned Phone Numbers Blocking' ) => array( 'feature' => array( 'red_cross', 'red_cross', 'checkmark', 'checkmark' ) ),
		mo_( 'Validate Phone number length based on Country' ) => array( 'feature' => array( 'red_cross', 'checkmark', 'checkmark', 'checkmark' ) ),
	);

	$whatsapp_plugin_features1 = array(
		mo_( 'OTP Verification on' ) . '<a class="mo_links" href="https://plugins.miniorange.com/otp-verification-forms" target="_blank">' . esc_html( mo_( ' 60+ Forms' ) ) . '</a>',
		mo_( 'WooCommerce Order Status Notifications to Admin, Customer, Vendors' ),
		mo_( 'Use your own Facebook Meta Business account' ),
		mo_( 'miniOrange Buisness Account Supported' ),
	);
	$whatsapp_plugin_features2 = array(
		mo_( 'Fallback to SMS for non-WhatsApp numbers' ),
		mo_( 'miniOrange Login and Registration form' ),
		mo_( 'Custom Redirection on Login Form & Registration Form' ),
		mo_( 'WhatsApp Transaction Logs' ),
	);

	$firebase_plugin_features1 = array(
		mo_( 'Login With Phone' ),
		mo_( 'OTP Verification on Registration Form' ),
		mo_( 'Custom CSS for Login and Registration Forms' ),
	);
	$firebase_plugin_features2 = array(
		mo_( 'OTP Verification on WooCommerce Login, Registration and Checkout Form' ),
		mo_( 'OTP Verification on Ultimate Member and Gravity Form' ),
		mo_( 'User role Selection on registration' ),
	);
	require_once MOV_DIR . 'views/pricing.php';
