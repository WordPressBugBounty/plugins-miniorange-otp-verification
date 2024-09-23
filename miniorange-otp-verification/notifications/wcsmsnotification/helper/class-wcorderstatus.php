<?php
/**
 * Order Statuses for Woocommerce Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use \ReflectionClass;

/**
 * Class declares all woocommerce order related status
 * which is being used plugin wide .
 */
if ( ! class_exists( 'WcOrderStatus' ) ) {
	/**
	 * WcOrderStatus class
	 */
	final class WcOrderStatus {

		const PROCESSING   = 'processing';
		const ON_HOLD      = 'on-hold';
		const CANCELLED    = 'cancelled';
		const PENDING      = 'pending';
		const FAILED       = 'failed';
		const COMPLETED    = 'completed';
		const REFUNDED     = 'refunded';
		const OUT_OF_STOCK = 'out-of-stock';
		const LOW_STOCK    = 'low-stock';


		/**
		 * Return list of all status as an array
		 */
		public static function get_all_status() {
			$refl = new ReflectionClass( self::class );
			return array_values( $refl->getConstants() );
		}
	}
}
