<?php
/**
 * Load view for premium SMS Notifications List
 *
 * @package miniorange-otp-verification/controllers
 */

use OTP\Helper\MoUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$premium_notifications = array(
	'dokannotif' => array(
		'subtab'      => 'dokanNotifSubTab',
		'filename'    => 'dokannotif',
		'discription' => 'Enable Order Status Notifications for the Vendors On Dokan Platform. <br><br><b> Dokan Notifications</b> is a WooCommerce plan feature.  Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.</a>',
	),
	'wcfmnotif'  => array(
		'subtab'      => 'wcfmNotifSubTab',
		'filename'    => 'wcfmsmsnotification',
		'discription' => 'Enable Order Status Notifications for the Vendors On WCFM Platform. <br><br> <b>WCFM Notifications</b> is a WooCommerce plan feature.  Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.</a>',
	),
	'formNotif'  => array(
		'subtab'      => 'formNotifSubTab',
		'filename'    => 'formsmsnotification',
		'discription' => 'Enable SMS Notifications on submission of Login, Registration and Contact Forms. <br><br> <b>Forms Notifications</b> is a premium feature. Contact us at <a style="cursor:pointer;" onClick="otpSupportOnClick(\'Hi! I am interested in using Form Notifications feature. Please help me with more information. \');"><u> otpsupport@xecurify.com</u> to know more</a>',
	),
);

foreach ( $premium_notifications as $notif => $notif_subtab ) {
	if ( MoUtility::is_plugin_installed( $notif . '/miniorange-custom-validation.php' ) ) {
		require_once str_replace( MOV_NAME, $notif, realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' ) ) . '/controllers/main-controller.php';
	} else {
		$premium_notif_hidden = $notif_subtab['subtab'] !== $subtab ? 'hidden' : '';
		$premium_notif_id     = $notif_subtab['subtab'] . 'Container';

		if ( is_dir( MOV_DIR . '/notifications/' . $notif_subtab['filename'] ) ) {
			require_once MOV_DIR . 'notifications/' . $notif_subtab['filename'] . '/controllers/main-controller.php';
		} else {
			require MOV_DIR . '/views/premium-notifications.php';
		}
	}
}
