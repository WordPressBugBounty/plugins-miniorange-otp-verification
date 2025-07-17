<?php
/**Load adminstrator changes for MoUtility
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Traits\Instance;
use OTP\Objects\BaseMessages;

/**
 * This class is to enable Visual Tour and all its functions
 */
if ( ! class_exists( 'MOVisualTour' ) ) {
	/**
	 * MOVisualTour class
	 */
	class MOVisualTour {

		use Instance;
		/** Variable declaration
		 *
		 * @var $nonce
		 */
		protected $nonce;
		/** Variable declaration
		 *
		 * @var $nonce_key
		 */
		protected $nonce_key;
		/** Variable declaration
		 *
		 * @var $tour_ajax_action
		 */
		protected $tour_ajax_action;

		/**Constructor
		 **/
		protected function __construct() {
			$this->nonce            = 'mo_admin_actions';
			$this->nonce_key        = 'security';
			$this->tour_ajax_action = 'miniorange-tour-taken';

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_visual_tour_script' ) );
			add_action( "wp_ajax_{$this->tour_ajax_action}", array( $this, 'update_tour_taken' ) );
			add_action( "wp_ajax_nopriv_{$this->tour_ajax_action}", array( $this, 'update_tour_taken' ) );
		}

		/**
		 * Adds TourTaken variable in Options for the page that has tour completed
		 */
		public function update_tour_taken() {
			if ( ! check_ajax_referer( $this->nonce, 'security' ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( BaseMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
			$page_id   = isset( $_POST['pageID'] ) ? sanitize_text_field( wp_unslash( $_POST['pageID'] ) ) : null; // phpcs:ignore -- false positive.
			$done_tour = isset( $_POST['pageID'] ) ? sanitize_text_field( wp_unslash( $_POST['pageID'] ) ) : null; // phpcs:ignore -- false positive.

			update_mo_option( 'tourTaken_' . $page_id, $done_tour );
			die();
		}

		/**
		 * Checks if the request made is a valid ajax request or not.
		 * Only checks the none value for now.
		 */
		protected function validate_ajax_request() {
			if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
		}

		/**
		 * Function called by Enqueue Hook to register and localize the script and
		 * script variables.
		 */
		public function enqueue_visual_tour_script() {
			wp_register_script( 'tourScript', MOV_URL . 'includes/js/visualTour.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, false );
			$page = MoUtility::sanitize_check( 'page', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the page, doesn't require nonce verification.
			wp_localize_script(
				'tourScript',
				'moTour',
				array(
					'siteURL'     => wp_ajax_url(),
					'currentPage' => $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the page, doesn't require nonce verification.
				'tnonce'          => wp_create_nonce( $this->nonce ),
				'pageID'          => $page,
				'tourData'        => $this->get_tour_data( $page ),
				'tourTaken'       => get_mo_option( 'tourTaken_' . $page ),
				'ajaxAction'      => $this->tour_ajax_action,
				'nonceKey'        => wp_create_nonce( $this->nonce_key ),
				)
			);
			wp_enqueue_script( 'tourScript' );
			wp_enqueue_style( 'mo_visual_tour_style', MOV_URL . 'includes/css/mo-card.min.css', '', MOV_VERSION );
		}

		/**
		 * Tour Data Template
		 *
		 * @param string $target_e        -   jQuery Selector for the target element.
		 * @param string $point_to_sale    -   the direction to point. place the card on the other side.
		 * @param string $titile_html      -   Title of the card, can be string, HTML or empty.
		 * @param string $content_html    -   Description of the card, can be string, HTML or empty.
		 * @param string $button_html     -   text on the Next Button.
		 * @param string $img            -   image name.
		 * @param int    $size           -    size of the card, 0=small, 1=medium, 2=big.
		 * @return array    -   Tour card array
		 */
		public function tour_template( $target_e, $point_to_sale, $titile_html, $content_html, $button_html, $img, $size ) {
			$card_size = array( 'small', 'medium', 'big' );
			return array(
				'targetE'     => $target_e,
				'pointToSide' => $point_to_sale,
				'titleHTML'   => $titile_html,
				'contentHTML' => $content_html,
				'buttonText'  => $button_html,
				'img'         => $img ? MOV_URL . "includes\\images\\tourIcons\\" . $img : $img,
				'cardSize'    => $card_size[ $size ],
			);
		}


		/**
		 * This functions return the array containing the tour elements for the current page
		 *
		 * @param  string $page_id  current page/tab.
		 * @return array tour data for current tab.
		 */
		public function get_tour_data( $page_id ) {

			$tour_data = array(
				'mosettings'      => $this->get_main_page_pointers(),
				'otpsettings'     => $this->get_general_settings_pointers(),
				'monotifications' => $this->get_notification_settings_pointers(),
				'mogateway'       => $this->get_gateway_settings_pointers(),
				'moreporting'     => $this->get_reporting_pointers(), 
				//'mowhatsapp'      => $this->get_whatsapp_pointers(), TODO: Add it once test whatsapp is working
				'addon'           => $this->get_addon_page_pointers(),
				'mootppricing'    => $this->get_pricing_page_pointers(),
			);

			$tabs = $this->get_tabs_pointers();
			//TODO: Add this once Free SMS transaction error is updated
			// if ( MoUtility::micr() && MoUtility::mclv() ) {
			// 	$tour_data['otpaccount'] = $this->gte_account_page_pointers();
			// }
			if ( ! get_mo_option( 'tourTaken_mosettings' ) ) {
				$tour_data['mosettings'] = array_merge( $tour_data['mosettings'], $tabs );
			}
			return MoUtility::sanitize_check( $page_id, $tour_data );
		}

		/**
		 * This functions return the array containing the tab details for the current page
		 *
		 * @return array tab data for current tab.
		 */
		private function get_tabs_pointers() {
			return array(
				$this->tour_template(
					'MoNotifications',
					'left',
					'<h1>Notifications</h1>',
					'Click here to enable SMS Notifications.',
					'Next',
					'emailSmsTemplate.svg',
					1
				),

				$this->tour_template(
					'MoGeneralSettingsTab',
					'left',
					'<h1>Settings</h1>',
					'Click here to update settings like: OTP Settings, <br> Comman Messages, etc.',
					'Next',
					'settingsTab.svg',
					1
				),

				$this->tour_template(
					'MoGatewayTab',
					'left',
					'<h1>Gateway Settings</h1>',
					'Click here to setup your SMS or Email Gateway.',
					'Next',
					'help.svg',
					1
				),

				$this->tour_template(
					'MoReportTab',
					'left',
					'<h1>Transaction Logs</h1>',
					'Click here to check the SMS and Email transactions logs',
					'Next',
					'drop-down-list.svg',
					1
				),

				$this->tour_template(
					'MoWhatsAppTab',
					'left',
					'<h1>WhatsApp</h1>',
					'Click here to check the WhatsApp OTP & Notification integrations.',
					'Next',
					'whatsApp.svg',
					1
				),

				$this->tour_template(
					'MoAddOnsTab',
					'left',
					'<h1>AddOns</h1>',
					'Check out our cool AddOns here.',
					'Next',
					'addOnSetting.svg',
					1
				),

				$this->tour_template(
					'MoAccount',
					'left',
					'<h1>Profile</h1>',
					'Register/Login here to get started.',
					'Next',
					'profile.svg',
					1
				),

				$this->tour_template(
					'LicensingPlanButton',
					'left',
					'<h1>Licensing Plans</h1>',
					'Check our cool Plans for everyone here.',
					'Next',
					'upgrade.svg',
					1
				),

				$this->tour_template(
					'faqButton',
					'left',
					'<h1>Any Questions?</h1>',
					'Check our FAQ page for more information.',
					'Next',
					'faq.svg',
					1
				),

				$this->tour_template(
					'demoButton',
					'left',
					'<h1>Need a Demo?</h1>',
					'Facing difficulty while using the plugin?<br> Click here to request a demo of the plugin. ',
					'Next',
					'help.svg',
					1
				),

				$this->tour_template(
					'mo_contact_us',
					'down',
					'<h1>Any Queries?</h1>',
					'Click here to leave us an email.',
					'Next',
					'help.svg',
					1
				),

				$this->tour_template(
					'restart_tour_button',
					'right',
					'<h1>Thank You!</h1>',
					'Click here to Restart the Tour for current tab.',
					'Next',
					'replay.svg',
					1
				),
			);
		}

		/**This functions return the array containing the main page details
		 *
		 * @return array tab data for starting page.
		 */
		private function get_main_page_pointers() {
			return array(
				$this->tour_template(
					'',
					'',
					'<h1>WELCOME!</h1>',
					'Fasten your seat belts for a quick ride.',
					'Let\'s Go!',
					'startTour.svg',
					2
				),

				$this->tour_template(
					'MoForms',
					'left',
					'<br>',
					'This is Form settings page. <br> Enable/Disable OTP verification for your forms here.',
					'Next',
					'formSettings.svg',
					1
				),

				$this->tour_template(
					'searchForm',
					'up',
					'<br>',
					'Type here to find your Form.<br><br>',
					'Next',
					'searchForm.svg',
					1
				),

				$this->tour_template(
					'formList',
					'right',
					'<br>',
					'Select your Form from the list <br>',
					'Next',
					'choose.svg',
					1
				),
			);
		}

		/**This functions return the array containing the General otp settings page details
		 *
		 * @return array tab data for otp settings page.
		 */
		private function get_general_settings_pointers() {
			return array(

				$this->tour_template(
					'generalSettingsSubTab',
					'up',
					'<h1>General Settings</h1>',
					'Click here to Enable general settings.',
					'Next',
					'settingsTab.svg',
					1
				),

				$this->tour_template(
					'otpSettingsSubTab',
					'up',
					'<h1>OTP Settings</h1>',
					'Click here to Enable OTP Settings like <br>SMS/Email Templates <br> OTP Properties.',
					'Next',
					'emailSmsTemplate.svg',
					1
				),

				$this->tour_template(
					'messagesSubTab',
					'up',
					'<h1>Edit Messages</h1>',
					'Click here to edit common messages like <br>SMS/Email sent message <br> Invalid OTP message.',
					'Next',
					'allMessages.svg',
					1
				),

				$this->tour_template(
					'popDesignSubTab',
					'up',
					'<h1>Pop-up Design</h1>',
					'Click here to edit the Pop-up in the plugin.',
					'Next',
					'design.svg',
					1
				),

				$this->tour_template(
					'country_code_settings',
					'up',
					'<h1>Country Code</h1>',
					'Set your default Country Code here.',
					'Next',
					'flag.svg',
					1
				),

				$this->tour_template(
					'dropdownEnable',
					'up',
					'<br>',
					'Enable this to show country code drop down in the Phone field of the Form.',
					'Next',
					'drop-down-list.svg',
					1
				),

				$this->tour_template(
					'blockedEmailList',
					'right',
					'<h1>Blocked Email Domains</h1>',
					'Add the list of Email Ids you wish to block.',
					'Next',
					'blockedEmail.svg',
					1
				),

				$this->tour_template(
					'blockedPhoneList',
					'right',
					'<h1>Blocked Phone Numbers</h1>',
					'Add the list of Phone numbers you wish to block.',
					'Next',
					'blockPhone.svg',
					1
				),

				$this->tour_template(
					'globallyBannedPhone',
					'right',
					'<h1>Block Globally Banned Phone Numbers</h1>',
					'Enable this to block the Globally banned Phone Numbers.',
					'Next',
					'blockPhone.svg',
					1
				),
			);
		}


		/**
		 * This functions return the array containing the Notification settings page details
		 *
		 * @return array tab data for Notification settings page.
		 */
		private function get_notification_settings_pointers() {
			return array(
				$this->tour_template(
					'MowcNotifSubTab',
					'up',
					'<h1>WooCommerce Notifications</h1>',
					'<br>Enable WooCommerce Notifications for the Order status updates',
					'Next',
					'messages.svg',
					1
				),

				$this->tour_template(
					'MoumNotifSubTab',
					'up',
					'<h1>Ultimate Member Notifications</h1>',
					'<br>Enable Ultimate Member Notifications for Admins and Customers',
					'Next',
					'messages.svg',
					1
				),

				$this->tour_template(
					'dokanNotifSubTab',
					'up',
					'<h1>Dokan Notifications</h1>',
					'<br>Enable Dokan Vendor Notifications here.',
					'Next',
					'messages.svg',
					1
				),

				$this->tour_template(
					'wcfmNotifSubTab',
					'up',
					'<h1>WCFM Notifications</h1>',
					'<br>Enable WCFM (WooCommerce Frontend Manager Plugins) Notifications here.',
					'Next',
					'messages.svg',
					1
				),

				$this->tour_template(
					'formNotifSubTab',
					'up',
					'<h1>Forms Notifications</h1>',
					'<br>Enable Notifications on submission of forms.',
					'Next',
					'messages.svg',
					1
				),

				$this->tour_template(
					'customMsgSubTab',
					'up',
					'<h1>Quick Send</h1>',
					'<br>Send Custom SMS & Email Notifications to your customers.',
					'Next',
					'messages.svg',
					1
				),
			);

		}



		/**This functions return the gateway page pointers
		 */
		private function get_gateway_settings_pointers() {
			$gateway_fn = GatewayFunctions::instance();
			return $gateway_fn->get_config_page_pointers();

		}

		/**This functions return the Transaction log page pointers
		 */
		private function get_reporting_pointers() {

			return array(
				$this->tour_template(
					'mo_transaction_report',
					'right',
					'<h1>Generate Report</h1>',
					'Click here to Generate the report within selected date range.',
					'Next',
					'notepad.svg',
					1
				),
				$this->tour_template(
					'mo_delete_transaction_report',
					'up',
					'<h1>Clear Database</h1>',
					'Click here to delete the previous database entries for the Transaction logs.',
					'Next',
					'delete.svg',
					1
				),
				$this->tour_template(
					'mo_download_transaction_report',
					'up',
					'<h1>Download Report</h1>',
					'Click here to download the transaction reports.',
					'Next',
					'downloadFile.svg',
					1
				),
			);
		}

		/**This functions return the WhatsApp page pointers
		 */
		private function get_whatsapp_pointers() {
			return array(
				$this->tour_template(
					'test_whatsapp_otp',
					'right',
					'<h1>Test WhatsApp</h1>',
					'<br>Click here to test the WhatsApp OTP.<br><br> You must be registered with miniOrange account to test the WhatsApp OTP.',
					'Next',
					'mowhatsapp.png',
					1
				),
				$this->tour_template(
					'whatsapp_pricing_plans',
					'up',
					'<h1>WhatsApp Plans</h1>',
					'Check out our WhatsApp plans and feature list here.',
					'Next',
					'downloadFile.svg',
					1
				),
			);
		}


		/**This functions return the array containing the Pricing page details
		 *
		 * @return array tab data for design settings page.
		 */
		private function get_pricing_page_pointers() {
			return array(
				$this->tour_template(
					'mo_select_gateway_type_div',
					'down',
					'<h1>Gateway</h1>',
					'Choose the SMS Gateway you wish to use and select the best suitable plan for you.',
					'Next',
					'choose.svg',
					1
				),
				$this->tour_template(
					'pricing_plans_div',
					'down',
					'<h1>Pricing Plans</h1>',
					'Check out our cool pricing plans based on your gateway selelction here.',
					'Next',
					'upgrade.svg',
					1
				),
			);
		}

		/**This functions return the array containing the addon settings page details
		 *
		 * @return array tab data for addon settings tab.
		 */
		private function get_addon_page_pointers() {
			return array(
				$this->tour_template(
					'addOnsTable',
					'right',
					'<h1>AddOns</h1>',
					'Check out our cool AddOns here.',
					'Next',
					'addOns.svg',
					1
				),
			);
		}
		/**This functions return the array containing the account settings page details
		 *
		 * @return array tab data for account settings page.
		 */
		private function gte_account_page_pointers() {
			return array(
				$this->tour_template(
					'check_btn',
					'right',
					'<h1>Check Licence</h1>',
					"Don't forget to check your Licence here After Upgrade.",
					'Next',
					'account.svg',
					2
				),
				$this->tour_template(
					'remove_accnt',
					'right',
					'<h1>Log Out</h1>',
					'Click here to Logout your current account.',
					'Next',
					'account.svg',
					2
				),
			);
		}
	}
}
