<?php
/**
 * List of Woocommerce Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceAdminOrderstatusNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceCutomerNoteNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceNewCustomerNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderCancelledNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderCompletedNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderFailedNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderOnHoldNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderPendingNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderProcessingNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceOrderRefundedNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceProductLowStockNotification;
use OTP\Notifications\WcSMSNotification\Helper\Notifications\WooCommerceProductOutOfStockNotification;
use OTP\Traits\Instance;

/**
 * This class is used to list down all the WooCommerce Notifications and initialize
 * each of the Notification classes so that it's accessible plugin wide. This
 * class is basically used to handle all the specific WooCommerce Notification classes.
 */
if ( ! class_exists( 'WooCommerceNotificationsList' ) ) {
	/**
	 * WooCommerceNotificationsList class
	 */
	class WooCommerceNotificationsList {

		use Instance;

		/**
		 * New Customer Notification Class
		 *
		 * @var WooCommerceNewCustomerNotification
		 */
		public $wc_new_customer_notif;

		/**
		 * Customer Note Notification Class
		 *
		 * @var WooCommerceCutomerNoteNotification
		 */
		public $wc_customer_note_notif;

		/**
		 * Admin Order Status Notification Class
		 *
		 * @var WooCommerceAdminOrderstatusNotification
		 */
		public $wc_admin_order_status_notif;

		/**
		 * Order on Hold Notification Class
		 *
		 * @var WooCommerceOrderOnHoldNotification
		 */
		public $wc_order_on_hold_notif;

		/**
		 * Order is processing Notification Class
		 *
		 * @var WooCommerceOrderProcessingNotification
		 */
		public $wc_order_processing_notif;

		/**
		 * Order Completed Notification Class
		 *
		 * @var WooCommerceOrderCompletedNotification
		 */
		public $wc_order_completed_notif;

		/**
		 * Order refunded Notification Class
		 *
		 * @var WooCommerceOrderRefundedNotification
		 */
		public $wc_order_refunded_notif;

		/**
		 * Order Cancelled Notification Class
		 *
		 * @var WooCommerceOrderCancelledNotification
		 */
		public $wc_order_cancelled_notif;

		/**
		 * Order Failed Notification Class
		 *
		 * @var WooCommerceOrderFailedNotification
		 */
		public $wc_order_failed_notif;

		/**
		 * Order Pending Notification Class
		 *
		 * @var WooCommerceOrderPendingNotification
		 */
		public $wc_order_pending_notif;
		/**
		 * Low Stock Notification Class
		 *
		 * @var WooCommerceProductLowStockNotification
		 */
		public $wc_product_is_in_low_stock_notif;
		/**
		 * Out of Stock Notification Class
		 *
		 * @var WooCommerceProductOutOfStockNotification
		 */
		public $wc_product_is_out_of_stock_notif;

		/** Declare Default variables */
		protected function __construct() {

			$this->wc_new_customer_notif       = WooCommerceNewCustomerNotification::getInstance();
			$this->wc_customer_note_notif      = WooCommerceCutomerNoteNotification::getInstance();
			$this->wc_admin_order_status_notif = WooCommerceAdminOrderstatusNotification::getInstance();
			$this->wc_order_on_hold_notif      = WooCommerceOrderOnHoldNotification::getInstance();
			$this->wc_order_processing_notif   = WooCommerceOrderProcessingNotification::getInstance();
			$this->wc_order_completed_notif    = WooCommerceOrderCompletedNotification::getInstance();
			$this->wc_order_refunded_notif     = WooCommerceOrderRefundedNotification::getInstance();
			$this->wc_order_cancelled_notif    = WooCommerceOrderCancelledNotification::getInstance();
			$this->wc_order_failed_notif       = WooCommerceOrderFailedNotification::getInstance();
			$this->wc_order_pending_notif      = WooCommerceOrderPendingNotification::getInstance();

			if ( file_exists( MSN_DIR . 'helper/notifications/class-woocommerceproductlowstocknotification.php' ) ) {
				$this->wc_product_is_in_low_stock_notif = WooCommerceProductLowStockNotification::getInstance();
			}
			if ( file_exists( MSN_DIR . 'helper/notifications/class-woocommerceproductoutofstocknotification.php' ) ) {
				$this->wc_product_is_out_of_stock_notif = WooCommerceProductOutOfStockNotification::getInstance();
			}
		}


		/**
		 * Getter function of the $wc_new_customer_notif. Returns the instance
		 * of the WooCommerceNewCustomerNotification class.
		 */
		public function get_wc_new_customer_notif() {
			return $this->wc_new_customer_notif;
		}


		/**
		 * Getter function of the $wc_customer_note_notif. Returns the instance
		 * of the WooCommerceCutomerNoteNotification class.
		 */
		public function get_wc_customer_note_notif() {
			return $this->wc_customer_note_notif;
		}


		/**
		 * Getter function of the $wc_admin_order_status_notif. Returns the instance
		 * of the WooCommerceAdminOrderstatusNotification class.
		 */
		public function get_wc_admin_order_status_notif() {
			return $this->wc_admin_order_status_notif;
		}

		/**
		 * Getter function of the $wc_order_on_hold_notif. Returns the instance
		 * of the WooCommerceOrderOnHoldNotification class.
		 */
		public function get_wc_order_on_hold_notif() {
			return $this->wc_order_on_hold_notif;
		}

		/**
		 * Getter function of the $wc_order_processing_notif. Returns the instance
		 * of the WooCommerceOrderProcessingNotification class.
		 */
		public function get_wc_order_processing_notif() {
			return $this->wc_order_processing_notif;
		}

		/**
		 * Getter function of the $wc_order_completed_notif. Returns the instance
		 * of the WooCommerceOrderCompletedNotification class.
		 */
		public function get_wc_order_completed_notif() {
			return $this->wc_order_completed_notif;
		}

		/**
		 * Getter function of the $wc_order_refunded_notif. Returns the instance
		 * of the WooCommerceOrderRefundedNotification class.
		 */
		public function get_wc_order_refunded_notif() {
			return $this->wc_order_refunded_notif;
		}

		/**
		 * Getter function of the $wc_order_cancelled_notif. Returns the instance
		 * of the WooCommerceOrderCancelledNotification class.
		 */
		public function get_wc_order_cancelled_notif() {
			return $this->wc_order_cancelled_notif;
		}

		/**
		 * Getter function of the $wc_order_failed_notif. Returns the instance
		 * of the WooCommerceOrderFailedNotification class.
		 */
		public function get_wc_order_failed_notif() {
			return $this->wc_order_failed_notif;
		}

		/**
		 * Getter function of the $wc_order_pending_notif. Returns the instance
		 * of the WooCommerceOrderPendingNotification class.
		 */
		public function get_wc_order_pending_notif() {
			return $this->wc_order_pending_notif;
		}

		/**
		 * Getter function of the $wc_product_is_in_low_stock_notif. Returns the instance
		 * of the WooCommerceProductLowStockNotification class.
		 */
		public function get_wc_product_low_stock_notif() {
			return $this->wc_product_is_in_low_stock_notif;
		}
		/**
		 * Getter function of the $wc_product_is_out_of_stock_notif. Returns the instance
		 * of the WooCommerceProductOutOfStockNotification class.
		 */
		public function get_wc_product_out_of_stock_notif() {
			return $this->wc_product_is_out_of_stock_notif;
		}
	}
}
