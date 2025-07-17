<?php
/**Load Class WooCommerce Premium Tags
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Addons\WcSMSNotification\Helper\WooCommercePremiumTags;

if ( ! class_exists( 'WcPremiumTags' ) ) {
	/**
	 * WcPremiumTags class
	 */
	class WcPremiumTags {

		const BILLING_FIRST_NAME = 'billing-firstName';
		const BILLING_PHONE      = 'billing-phone';
		const BILLING_EMAIL      = 'billing-email';
		const BILLING_ADDRESS    = 'billing-address';
		const BILLING_CITY       = 'billing-city';
		const BILLING_STATE      = 'billing-state';
		const BILLING_POSTCODE   = 'billing-postcode';
		const BILLING_COUNTRY    = 'billing-country';

		const SHIPPING_FIRST_NAME = 'shipping-firstName';
		const SHIPPING_PHONE      = 'shipping-phone';
		const SHIPPING_ADDRESS    = 'shipping-address';
		const SHIPPING_CITY       = 'shipping-city';
		const SHIPPING_STATE      = 'shipping-state';
		const SHIPPING_POSTCODE   = 'shipping-postcode';
		const SHIPPING_COUNTRY    = 'shipping-country';
	}

}
