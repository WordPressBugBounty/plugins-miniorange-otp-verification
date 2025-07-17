<?php
/**
 * Load view for SMS Notifications List
 *
 * @package miniorange-otp-verification/Notifications
 */

use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnMessages;
use OTP\Notifications\WcSMSNotification\Helper\WooCommerceNotificationsList;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notification_settings = get_wc_option( 'notification_settings_option' );

$notification_settings = $notification_settings ? maybe_unserialize( $notification_settings )
												: WooCommerceNotificationsList::instance();
$sms                   = '';
$wc_hidden             = 'MowcNotifSubTab' !== $subtab ? 'hidden' : '';

require_once MSN_DIR . '/views/wc-sms-notification.php';


/**
 * This function is used to display rows in the notification table for the admin to get an
 * overview of all the SMS notifications that are going out because of the plugin. It displays
 * if the notification is enabled, who the recipient is , the type of SMS notification etc.
 *
 * @param WooCommerceNotificationsList $notifications The list of all notifications for WooCommerce.
 */
function show_wc_notifications_table( WooCommerceNotificationsList $notifications ) {
	$form_options = 'mo_wc_sms_notif_settings';

	foreach ( $notifications as $notification => $property ) {
		if ( ! $property ) {
			continue;
		}
		echo '	<div style="display:flex;"><div>
					
					<tr >
						<td class="mo-wcnotif-table bg-white">
							<a class="mo-title text-primary text-blue-600">' . esc_attr( $property->title ) . '</a>';

		echo '		    </td>

						<td class="msn-table-list-recipient" style="word-wrap: break-word;">
							' . esc_attr( $property->notification_type ) . '
						</td>
					

						<td class="msn-table-list-status-actions">
							<label class="mo-switch">
							  <input class="input" name="' . esc_attr( $notification ) . '" id="' . esc_attr( $notification ) . '" type="checkbox" ' . ( $property->is_enabled ? 'checked' : '' ) . '/>
							  <span class="mo-slider"></span>
							</label>
						</td>';

						$var = $notification;

						$id    = 'sms-body-' . $var;
						$btnid = 'btn-' . $var;

		echo '           <td class="msn-table-edit-body mo_showcontainer">
							<button id="' . esc_attr( $btnid ) . '" type="button" class="mo-button secondary" onClick="edit_button(this)">Edit</button>

							<tr>
								<td colspan="4">
									<div id="' . esc_attr( $id ) . '" style="display:none;" class="p-mo-8">';

										$notif        = $var;
										$len_of_notif = strlen( $notif );
		for ( $i = 0; $i < $len_of_notif; $i++ ) {

			if ( '_' === $notif[ $i ] ) {
				$notif[ $i ] = '-';
			}
		}
										$path = '/controllers/smsnotifications/' . $notif . '.php';
										include MSN_DIR . $path;

		echo '                       </div>
								</td>
							</tr>

					</td>';

	}

}
