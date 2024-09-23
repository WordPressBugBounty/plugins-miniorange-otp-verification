<?php
/**
 * Loads admin view for WhatsApp functionality.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$tooltip_disabled = ( ( $registered && $activated ) ) ? true : false;
$disabled         = ( ( $registered && $activated ) ) ? '' : 'disabled';

require_once MOV_DIR . 'views/mowhatsapp.php';
