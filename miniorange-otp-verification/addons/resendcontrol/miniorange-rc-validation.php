<?php //phpcs:ignore -- legacy plugin
/**
 * Plugin Name: Resend OTP Verification addon
 * Plugin URI: http://miniorange.com
 * Description: Allows to add timer for resend OTP.
 * Version: 3.2.0
 * Author: miniOrange
 * Author URI: http://miniorange.com
 * Text Domain: miniorange-otp-verification
 * License: MIT/Expat
 * License URI: https://docs.miniorange.com/mit-license
 *
 * @package resendcontrol
 */

use OTP\MoInit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'MO_ROC_PLUGIN_NAME', plugin_basename( __FILE__ ) );
$dir_name = substr( MO_ROC_PLUGIN_NAME, 0, strpos( MO_ROC_PLUGIN_NAME, '/' ) );
if ( 'resendcontrol' !== $dir_name ) {
	$dir_name = 'resendcontrol';
}
define( 'MO_ROC_NAME', $dir_name );
require_once 'rc_autoload.php';
