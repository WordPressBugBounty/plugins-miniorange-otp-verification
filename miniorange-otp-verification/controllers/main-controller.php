<?php
/**
 * Loads the tab view and initializes other controllers.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Handler\MoActionHandlerHandler;
use OTP\Helper\MoUtility;
use OTP\Objects\TabDetails;
use OTP\Objects\SubTabDetails;
use OTP\Helper\MoConstants;

$registered           = MoUtility::micr();
$activated            = MoUtility::mclv();
$gatewayconfigured    = MoUtility::is_gateway_config();
$plan                 = MoUtility::micv();
$mle                  = Moutility::mllc();
$disabled             = ( ( ( $registered && $activated ) || ( strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0 ) ) && ! $mle['STATUS'] ) ? '' : 'disabled';
$mo_current_user      = wp_get_current_user();
$email                = get_mo_option( 'admin_email' );
$phone                = get_mo_option( 'admin_phone' );
$controller           = MOV_DIR . 'controllers/';
$admin_handler        = MoActionHandlerHandler::instance();
$is_sms_notice_closed = get_mo_option( 'mo_hide_sms_notice' );


$tab_details = TabDetails::instance();

$sub_tab_details = SubTabDetails::instance();

echo '
<div id="mo-main-outer-div">';

	require_once $controller . 'titlebar.php';

echo "  
    <div class='w-full flex'>";
		require_once $controller . 'navbar.php';
echo '  <div class="flex-1 p-mo-sm">';
		require_once $controller . 'admin-messagebar.php';
echo '      <div class="bg-mo-primary-bg rounded-mo-smooth mo-main-content">
                <div id="moblock" class="mo_customer_validation-modal-backdrop dashboard">' .
					'<img src="' . esc_url( MOV_LOADER_URL ) . '">' .
				'</div>';

				require $controller . 'subtabs.php';

echo '          <div>';
if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.

	foreach ( $tab_details->tab_details as $mo_tabs ) {
		if ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $mo_tabs->menu_slug ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
			require_once $controller . $mo_tabs->view;
		}
	}
	do_action( 'mo_otp_verification_add_on_controller' );
}
echo '           </div>
            </div>
        </div>
    </div>
</div>';
require $controller . 'contactus.php';

