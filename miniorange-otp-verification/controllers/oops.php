<?php
/**
 * Loads admin view if a page is not found.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once MOV_DIR . 'views/oops.php';
