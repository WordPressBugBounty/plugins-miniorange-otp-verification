<?php
/**
 * Load admin view for Ultimate Member SMS Notification.
 *
 * @package miniorange-otp-verification/umsmsnotification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Notifications\UmSMSNotification\Helper\UltimateMemberSMSNotificationUtility;

echo '				<div id="MoumNotifSubTabContainer" class="mo-subpage-container ' . esc_attr( $um_hidden ) . '">		
						<form name="f" method="post" action="" id="mo_um_sms_notif_settings">
							<input type="hidden" name="option" value="mo_um_sms_notif_settings" />';
							wp_nonce_field( $nonce );
echo '						<div class="mo-header">
								<p class="mo-heading flex-1">' . esc_html( mo_( 'Ultimate Member Notification Settings' ) ) . '</p>
								<input type="submit" name="save" id="save" ' . esc_attr( $disabled ) . '
											class="mo-button inverted" value="' . esc_attr( mo_( 'Save Settings' ) ) . '">
							</div>
							<table class="mo-wcnotif-table bg-white">
								<thead>
									<tr>
										<th>SMS Type</th>
										<th>Recipient</th>
										<th></th>
										<th>SMS Body</th>			
									</tr>
								</thead>
								<tbody>';
									show_um_notifications_table( $notification_settings );
echo '							</tbody>
							</table>
						</form>
					</div>';
