<?php
/**Load Abstract Class SubTabDetails
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

use OTP\Helper\MoUtility;
use OTP\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Subtab details class.
 */
if ( ! class_exists( 'SubTabDetails' ) ) {
	/**
	 * SubTabDetails class
	 */
	final class SubTabDetails {

		use Instance;

		/**
		 * Array of SubtabPageDetails Object detailing
		 * all the page menu options.
		 *
		 * @var array[SubtabPageDetails] $sub_tab_details
		 */
		public $sub_tab_details;
		/**
		 * Array of SubtabPageDetails Object detailing
		 * all the page menu options.
		 *
		 * @var array[SubtabPageDetails] $settings_sub_tab_details
		 */
		public $settings_sub_tab_details;
		/**
		 * Array of SubtabPageDetails Object detailing
		 * all the page menu options.
		 *
		 * @var array[SubtabPageDetails] $notification_sub_tab_details
		 */
		public $notification_sub_tab_details;

		/**
		 * The parent menu slug
		 *
		 * @var string $parent_slug
		 */
		public $parent_slug;

		/** Private constructor to avoid direct object creation */
		private function __construct() {
			$registered        = MoUtility::micr();
			$this->parent_slug = 'mosettings';

			$this->settings_sub_tab_details = array(
				SubTabs::MO_GENERAL_SETTINGS => new SubtabPageDetails(
					'General Settings',
					mo_( 'General Settings' ),
					mo_( 'General Settings' ),
					'general-settings.php',
					'generalSettingsSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_OTP_SETTINGS     => new SubtabPageDetails(
					'OTP Settings',
					mo_( 'OTP Settings' ),
					mo_( 'OTP Settings' ),
					'otpsettings.php',
					'otpSettingsSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_MESSAGE_BOX      => new SubtabPageDetails(
					'OTP Verification - Messages',
					mo_( 'Edit Messages' ),
					mo_( 'Edit Messages' ),
					'messages.php',
					'messagesSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_POPUP_DESIGN     => new SubtabPageDetails(
					'OTP Verification - Design',
					mo_( 'Pop-Up Design' ),
					mo_( 'Pop-Up Design' ),
					'design.php',
					'popDesignSubTab',
					'background:#D8D8D8'
				),
			);

			$this->notification_sub_tab_details = array(
				SubTabs::MO_WC_NOTIF    => new SubtabPageDetails(
					'MoNotifications',
					mo_( 'WooCommerce' ),
					mo_( 'WooCommerce' ),
					'sms-notifications.php',
					'MowcNotifSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_UM_NOTIF    => new SubtabPageDetails(
					'MoNotifications',
					mo_( 'Ultimate Member' ),
					mo_( 'Ultimate Member' ),
					'sms-notifications.php',
					'MoumNotifSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_DOKAN_NOTIF => new SubtabPageDetails(
					'Dokan Notifications',
					mo_( 'Dokan' ),
					mo_( 'Dokan' ),
					'sms-notifications.php',
					'dokanNotifSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_WCFM_NOTIF  => new SubtabPageDetails(
					'WCFM Notifications',
					mo_( 'WCFM' ),
					mo_( 'WCFM' ),
					'sms-notifications.php',
					'wcfmNotifSubTab',
					'background:#D8D8D8'
				),
				SubTabs::MO_FORM_NOTIF  => new SubtabPageDetails(
					'MoNotifications',
					mo_( 'Forms' ),
					mo_( 'Forms' ),
					'sms-notifications.php',
					'formNotifSubTab',
					'background:#D8D8D8'
				),
			);

			$this->sub_tab_details = array(
				'otpsettings'     => $this->settings_sub_tab_details,
				'monotifications' => $this->notification_sub_tab_details,
			);
		}
	}
}
