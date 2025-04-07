<?php
/**
 * Loads admin view for WhatsApp functionality.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Objects\Tabs;

$tooltip_disabled = ( ( $registered && $activated ) ) ? true : false;
$disabled         = ( ( $registered && $activated ) ) ? '' : 'disabled';
$license_url      = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::PRICING ]->menu_slug ), $request_uri );

require_once MOV_DIR . 'views/mowhatsapp.php';
