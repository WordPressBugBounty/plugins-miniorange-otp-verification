<?php
/**
 * Controller of Ultimate member SMS notifications.
 *
 * @package miniorange-otp-verification/addons/umsmsnotification/controllers
 */

use OTP\Notifications\UmSMSNotification\Helper\UltimateMemberNotificationsList;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$notification_settings = maybe_unserialize( get_umsn_option( 'notification_settings_option' ) );
$notification_settings = $notification_settings ? $notification_settings : UltimateMemberNotificationsList::instance();
$sms                   = '';
$um_hidden             = 'MoumNotifSubTab' !== $subtab ? 'hidden' : '';

require_once UMSN_DIR . '/views/um-sms-notification.php';



/**
 * Display the Ultimate Member SMS Notification table
 *
 * @param UltimateMemberNotificationsList $notifications - contains all the data of the ultimate member notifications notifications.
 */
function show_um_notifications_table( UltimateMemberNotificationsList $notifications ) {
	$form_options = 'mo_um_sms_notif_settings';

	foreach ( $notifications as $notification => $property ) {
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
										include UMSN_DIR . $path;

		echo '                       </div>
								</td>
							</tr>

					</td>';

	}
}
