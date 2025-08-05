<?php
/**
 * Deletes options saved in the plugin.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}
	delete_site_option( 'mo_rc_sms_otp_control_enable' );
	delete_site_option( 'mo_rc_sms_otp_control_limit' );
	delete_site_option( 'mo_rc_sms_otp_control_block_time' );
	delete_site_option( 'mo_rc_sms_otp_timer_enable' );
	delete_site_option( 'mo_rc_sms_otp_timer' );

