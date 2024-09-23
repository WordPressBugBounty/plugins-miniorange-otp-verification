<?php
/**
 * Handler functions for Woocommerce Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnMessages;
use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnUtility;
use OTP\Notifications\WcSMSNotification\Helper\WcOrderStatus;
use OTP\Notifications\WcSMSNotification\Helper\WooCommerceNotificationsList;
use OTP\Helper\MoConstants;
use OTP\Helper\MoUtility;
use OTP\Helper\MoFormDocs;
use OTP\Objects\BaseAddOnHandler;
use OTP\Traits\Instance;
use OTP\Objects\BaseMessages;
use \WC_Emails;
use \WC_Order;
use OTP\Helper\MoMessages;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * The class is used to handle all woocommerce notification related functionality.
 * This class hooks into all the available notification hooks and filters of
 * woocommerce to provide the possibility of SMS notifications.
 */
if ( ! class_exists( 'WooCommerceNotifications' ) ) {
	/**
	 * WooCommerceNotifications class
	 */
	class WooCommerceNotifications extends BaseAddOnHandler {

		use Instance;

		/**
		 * The list of all the Notification Settings
		 *
		 * @var WooCommerceNotificationsList
		 */
		private $notification_settings;

		/**
		 * Constructor checks if add-on has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make the add-on functionality work.
		 */
		protected function __construct() {
			parent::__construct();
			if ( ! $this->moAddOnV() ) {
				return;
			}
			$this->notification_settings = get_wc_option( 'notification_settings_option' )
			? get_wc_option( 'notification_settings_option' ) : WooCommerceNotificationsList::instance();

			if ( empty( get_wc_option( 'notification_settings_option' ) ) && ! empty( get_wc_option( 'notification_settings' ) ) ) {
				$old_notification_settings = get_option( 'mo_wc_sms_notification_settings' );
				foreach ( $old_notification_settings as $notification_name => $property ) {
					if ( ! empty( $property ) ) {
						$sms_settings                = $this->notification_settings->$notification_name;
						$sms_settings->is_enabled    = $property['is_enabled'];
						$sms_settings->sms_body      = $property['sms_body'];
						$sms_settings->recipient     = $property['recipient'];
						$sms_settings->sms_tags      = $property['sms_tags'];
						$sms_settings->template_name = $property['template_name'];
					}
				}
					update_wc_option( 'notification_settings_option', $this->notification_settings );
			} elseif ( ! empty( get_wc_option( 'notification_settings_option' ) ) ) {
				$notification_settings = get_wc_option( 'notification_settings_option' );
				foreach ( $notification_settings as $notification_name => $property ) {
					if ( ! empty( $property ) ) {
						$sms_settings                = $this->notification_settings->$notification_name;
						$sms_settings->sms_tags      = $property->sms_tags;
						$sms_settings->template_name = $property->template_name;
					}
				}
				update_wc_option( 'notification_settings_option', $this->notification_settings );

			}

			add_action( 'woocommerce_created_customer_notification', array( $this, 'mo_send_new_customer_sms_notif' ), 1, 3 );
			add_action( 'woocommerce_new_customer_note_notification', array( $this, 'mo_send_new_customer_sms_note' ), 1, 1 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'mo_send_admin_order_sms_notif' ), 1, 3 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'mo_customer_order_status_sms_notification' ), 1, 3 );
			if ( file_exists( MSN_DIR . 'helper/notifications/class-woocommerceproductlowstocknotification.php' ) ) {
				add_action( 'woocommerce_low_stock', array( $this, 'mo_send_admin_low_stock_notif' ), 11, 1 );
			}
			if ( file_exists( MSN_DIR . 'helper/notifications/class-woocommerceproductoutofstocknotification.php' ) ) {
				add_action( 'woocommerce_no_stock', array( $this, 'mo_send_admin_out_of_stock_notif' ), 10, 1 );
			}
			add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
			add_action( 'admin_init', array( $this, 'check_wc_notifications_options' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_custom_msg_meta_box' ), 1 );

		}
		/**
		 * Checks and save the notifications
		 */
		public function check_wc_notifications_options() {
			if ( ! ( isset( $_POST['option'] ) && 'mo_wc_sms_notif_settings' === sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) ) { //phpcs:ignore -- false positive.
				return;
			}
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'mo_admin_actions' ) ) {
				wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			foreach ( $this->notification_settings as $notification_name => $notification_setting ) {
				if ( ! $notification_setting ) {
					continue;
				}
				$textarea_tag      = $notification_name . '_smsbody';
				$recipient_tag     = $notification_name . '_recipient';
				$template_name_tag = $notification_name . '_template_name';
				$sms_tags_tag      = $notification_name . '_sms_tags';
				$notification      = $this->notification_settings->$notification_name;

				$textar_tag      = isset( $_POST [ $textarea_tag ] ) ? sanitize_textarea_field( wp_unslash( $_POST [ $textarea_tag ] ) ) : null; //phpcs:ignore -- false positive.
				$sms             = MoUtility::is_blank( $textar_tag ) ? $notification->default_sms_body : MoUtility::sanitize_check( $textarea_tag, $_POST );
				$recipient_value = MoUtility::sanitize_check( $recipient_tag, $_POST );
				$template_name   = MoUtility::sanitize_check( $template_name_tag, $_POST );
				$sms_tags        = MoUtility::sanitize_check( $sms_tags_tag, $_POST );
				$notification    = $this->notification_settings->$notification_name;
				$notification->set_is_enabled( isset( $_POST[ $notification_name ] ) ); //phpcs:ignore -- false positive.
				$notification->set_recipient( $recipient_value );
				$notification->set_sms_body( $sms );
				$notification->set_template_name( $template_name );
				$notification->set_sms_tags( $sms_tags );
			}
				update_wc_option( 'notification_settings_option', $this->notification_settings );
		}

		/**
		 * This function hooks into the admin_init WordPress hook. This function
		 * checks the form being posted and routes the data to the correct function
		 * for processing. The 'option' value in the form post is checked to make
		 * the diversion.
		 */
		public function handle_admin_actions() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( array_key_exists( 'mo_send_custome_msg_option', $_POST ) && ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) { //phpcs:ignore -- false positive.
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( BaseMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$data    = MoUtility::mo_sanitize_array( $_POST );
			$getdata = MoUtility::mo_sanitize_array( $_GET );

			if ( isset( $getdata['mo_send_custome_msg_option'] ) && sanitize_text_field( $getdata['mo_send_custome_msg_option'] ) === 'mo_send_order_custom_msg' ) {
				$this->send_custom_order_msg( $data );
			}
		}

		/**
		 * This function hooks into the woocommerce_low_stock hook
		 * to send an SMS notification to the admin when the stock inventry is low.
		 *
		 * @param array $product - product stock quantity.
		 * @return void
		 */
		public function mo_send_admin_low_stock_notif( $product ) {
			$this->notification_settings->get_wc_product_low_stock_notif()->send_sms(
				array(
					'product_details' => $product,
				)
			);
		}
		/**
		 * This function hooks into the woocommerce_low_stock hook
		 * to send an SMS notification to the admin when the stock inventry is zero.
		 *
		 * @param array $product - product stock quantity.
		 * @return void
		 */
		public function mo_send_admin_out_of_stock_notif( $product ) {
			$this->notification_settings->get_wc_product_out_of_stock_notif()->send_sms(
				array(
					'product_details' => $product,
				)
			);
		}

		/**
		 * This function hooks into the woocommerce_created_customer_notification hook
		 * to send an SMS notification to the user when he successfully creates an
		 * account using the checkout or registration page.
		 *
		 * @param  string $customer_id        id of the customer.
		 * @param  array  $new_customer_data  array of customer data.
		 * @param  bool   $password_generated was password is automatically generated.
		 */
		public function mo_send_new_customer_sms_notif( $customer_id, $new_customer_data = array(), $password_generated = false ) {
			$this->notification_settings->get_wc_new_customer_notif()->send_sms(
				array(
					'customer_id'        => $customer_id,
					'new_customer_data'  => $new_customer_data,
					'password_generated' => $password_generated,
				)
			);
		}


		/**
		 * This function hooks into the woocommerce_new_customer_note_notification hook
		 * to send an SMS notification to the user when the admin successfully adds a
		 * note to the order that the user has ordered.
		 *
		 * @param  array $args array Customer note details.
		 */
		public function mo_send_new_customer_sms_note( $args ) {
			$this->notification_settings->get_wc_customer_note_notif()->send_sms(
				array( 'orderDetails' => wc_get_order( $args['order_id'] ) )
			);
		}


		/**
		 * This function hooks into woocommerce_order_status_changed hook
		 * to send an SMS notification to the admin that a order needs to be
		 * processed as its status has changed.
		 *
		 * @param int    $order_id string the id of the order.
		 * @param string $old_status string the old status of the order.
		 * @param string $new_status string the new status of the order.
		 */
		public function mo_send_admin_order_sms_notif( $order_id, $old_status, $new_status ) {
			$order = new WC_Order( $order_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}
			$this->notification_settings->get_wc_admin_order_status_notif()->send_sms(
				array(
					'orderDetails' => $order,
					'new_status'   => $new_status,
					'old_status'   => $old_status,
				)
			);
		}


		/**
		 * This function hooks into all of the On-Hold notification Woocommerce
		 * hook to send an SMS notification to the customer that a order has been
		 * put on hold
		 *
		 * @param int    $order_id string the id of the order.
		 * @param string $old_status string the old status of the order.
		 * @param string $new_status string the new status of the order.
		 */
		public function mo_customer_order_status_sms_notification( $order_id, $old_status, $new_status ) {
			$order = new WC_Order( $order_id );
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			if ( strcasecmp( $new_status, WcOrderStatus::ON_HOLD ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_on_hold_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::PROCESSING ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_processing_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::COMPLETED ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_completed_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::REFUNDED ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_refunded_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::CANCELLED ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_cancelled_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::FAILED ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_failed_notif();
			} elseif ( strcasecmp( $new_status, WcOrderStatus::PENDING ) === 0 ) {
				$notification = $this->notification_settings->get_wc_order_pending_notif();
			} else {
				return;
			}
			$notification->send_sms( array( 'orderDetails' => $order ) );
		}


		/**
		 * Unhook all the emails that we will be sending sms notifications for.
		 *
		 * @param WC_Emails $email_class the class to disable notification for.
		 */
		private function unhook( $email_class ) {
			$new_order_email  = array( $email_class->emails['WC_Email_New_Order'], 'trigger' );
			$processing_order = array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' );
			$completed_order  = array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' );
			$new_customer     = array( $email_class->emails['WC_Email_Customer_Note'], 'trigger' );

			remove_action( 'woocommerce_low_stock_notification', array( $email_class, 'low_stock' ) );
			remove_action( 'woocommerce_no_stock_notification', array( $email_class, 'no_stock' ) );
			remove_action( 'woocommerce_product_on_backorder_notification', array( $email_class, 'backorder' ) );
			remove_action( 'woocommerce_order_status_pending_to_processing_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_pending_to_completed_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_failed_to_processing_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_failed_to_completed_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', $new_order_email );
			remove_action( 'woocommerce_order_status_pending_to_processing_notification', $processing_order );
			remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', $processing_order );
			remove_action( 'woocommerce_order_status_completed_notification', $completed_order );
			remove_action( 'woocommerce_new_customer_note_notification', $new_customer );
		}


		/**
		 * Add a meta box in the order page so that customer can send custom messages to
		 * the users if he wants to.
		 */
		public function add_custom_msg_meta_box() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
				? wc_get_page_screen_id( 'shop-order' )
				: 'shop_order';

			add_meta_box(
				'mo_wc_custom_sms_meta_box',
				'Custom SMS',
				array( $this, 'mo_show_send_custom_msg_box' ),
				$screen,
				'side',
				'default'
			);
		}


		/**
		 * This function is a call back to our meta box hook so that we
		 * can design our metabox and provide our own frontend to the
		 * metabox. In this cases its being used to show a box where
		 * admin can send custom messages to the user.
		 *
		 * @param  array $post_or_order_object - the post or order object passed by WordPress.
		 */
		public function mo_show_send_custom_msg_box( $post_or_order_object ) {
			$order_details = is_a( $post_or_order_object, 'WP_Post' ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
			$phone_numbers = MoWcAddOnUtility::get_customer_number_from_order( $order_details );
			include MSN_DIR . 'views/custom-order-msg.php';
		}


		/**
		 * This function is used to send custom SMS messages to the user
		 * from the order page of WooCommer using our meta box.
		 *
		 * @param array $data the posted data.
		 */
		private function send_custom_order_msg( $data ) {
			if ( ! array_key_exists( 'numbers', $data ) || MoUtility::is_blank( sanitize_text_field( $data['numbers'] ) ) ) {
				MoUtility::create_json(
					MoWcAddOnMessages::showMessage( MoWcAddOnMessages::INVALID_PHONE ),
					MoConstants::ERROR_JSON_TYPE
				);
			} else {
				foreach ( explode( ';', $data['numbers'] ) as $number ) {
					if ( MoUtility::send_phone_notif( $number, $data['msg'] ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoWcAddOnMessages::showMessage( MoWcAddOnMessages::SMS_SENT_SUCCESS ),
								MoConstants::SUCCESS_JSON_TYPE
							)
						);
					} else {
						wp_send_json(
							MoUtility::create_json(
								MoWcAddOnMessages::showMessage( MoWcAddOnMessages::ERROR_SENDING_SMS ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
					}
				}
			}
		}


		/** Set Addon Key */
		public function set_addon_key() {
			$this->add_on_key = 'wc_sms_notification_addon';
		}

		/** Set AddOn Desc */
		public function set_add_on_desc() {
			$this->add_on_desc = mo_(
				'Allows your site to send order and WooCommerce notifications to buyers, '
				. 'sellers and admins. Click on the settings button to the right to see the list of notifications '
				. 'that go out.'
			);
		}

		/** Set an AddOnName */
		public function set_add_on_name() {
			$this->addon_name = mo_( 'WooCommerce SMS Notification' );
		}

		/** Set an Addon Docs link */
		public function set_add_on_docs() {
			$this->add_on_docs = MoFormDocs::WOCOMMERCE_SMS_NOTIFICATION_LINK['guideLink'];
		}

		/** Set an Addon Video link */
		public function set_add_on_video() {
			$this->add_on_video = MoFormDocs::WOCOMMERCE_SMS_NOTIFICATION_LINK['videoLink'];
		}

		/** Set Settings Page URL */
		public function set_settings_url() {
			$request_url        = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; //phpcs:ignore -- false positive.
			$this->settings_url = add_query_arg( array( 'addon' => 'woocommerce_notif' ), $request_url );
		}

	}
}
