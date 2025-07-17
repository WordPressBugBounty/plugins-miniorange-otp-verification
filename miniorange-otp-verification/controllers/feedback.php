<?php
/**
 * Loads deactivation feedback form.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\MoActionHandlerHandler;

$message = mo_(
	'We are sad to see you go :( Have you found a bug? Did you feel something was missing? 
                Whatever it is, we\'d love to hear from you and get better.'
);

$submit_message  = mo_( 'Submit & Deactivate' );
$submit_message2 = mo_( 'Submit' );

$admin_handler       = MoActionHandlerHandler::instance();
$nonce               = $admin_handler->get_nonce_value();
$deactivationreasons = $admin_handler->mo_feedback_reasons();

require_once MOV_DIR . 'views/feedback.php';



