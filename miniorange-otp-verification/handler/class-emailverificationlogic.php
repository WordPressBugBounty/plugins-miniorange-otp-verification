<?php
/**
 * Comman handler to handle the email logic during phone verification.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\VerificationLogic;
use OTP\Traits\Instance;
use OTP\LicenseLibrary\Mo_License_Service;

/**
 * This class handles all the email related logic for OTP Verification
 * Process the email address and starts the Email verification process.
 */
if ( ! class_exists( 'EmailVerificationLogic' ) ) {
	/**
	 * EmailVerificationLogic class
	 */
	final class EmailVerificationLogic extends VerificationLogic {

		use Instance;

		/**
		 * This function is called to handle Email Verification request. Processes
		 * the request and starts the OTP Verification process.
		 *
		 * @param string $user_login    username of the user.
		 * @param string $user_email    email of the user.
		 * @param string $phone_number  phone number of the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     has user enabled from both.
		 */
		public function handle_logic( $user_login, $user_email, $phone_number, $otp_type, $from_both ) {
			$this->checkIfUserRegistered( $otp_type, $from_both );
			$mle                     = MoUtility::mllc();
			$license_expired_message = MoMessages::showMessage( MoMessages::ERROR_OTP_EMAIL );
			if ( $mle['STATUS'] ) {
				if ( $this->is_ajax_form() ) {
					wp_send_json( MoUtility::create_json( $license_expired_message, MoConstants::ERROR_JSON_TYPE ) );
				} else {
					miniorange_site_otp_validation_form( null, null, null, $license_expired_message, $otp_type, $from_both );
				}
			}
			if ( is_email( $user_email ) ) {
				$this->handle_matched( $user_login, $user_email, $phone_number, $otp_type, $from_both );
			} else {
				$this->handle_not_matched( $user_email, $otp_type, $from_both );
			}
		}


		/**
		 * Funtion checks if the user is registered with miniorange and show the error message if not registered.
		 *
		 * @param array $otp_type email or sms verification.
		 * @param array $from_both has user enabled from both.
		 * @return void
		 */
		private function checkIfUserRegistered( $otp_type, $from_both ) {
			if ( ! MoUtility::micr() ) {
				$message = MoMessages::showMessage( MoMessages::NEED_TO_REGISTER );
				if ( $this->is_ajax_form() ) {

					wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
				} else {
					miniorange_site_otp_validation_form( null, null, null, $message, $otp_type, $from_both );
				}
			}
		}

		/**
		 * This function starts the OTP Verification process if email address matches the
		 * correct format and is not blocked by the admin.
		 *
		 * @param string $user_login    username of the user.
		 * @param string $user_email    email of the user.
		 * @param string $phone_number  phone number of the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     has user enabled from both option.
		 */
		public function handle_matched( $user_login, $user_email, $phone_number, $otp_type, $from_both ) {
			$message = str_replace( '##email##', $user_email, $this->get_is_blocked_message() );
			if ( $this->is_blocked( $user_email, $phone_number ) ) {
				if ( $this->is_ajax_form() ) {
					wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
				} else {
					miniorange_site_otp_validation_form( null, null, null, $message, $otp_type, $from_both );
				}
			} else {
				$this->start_otp_verification( $user_login, $user_email, $phone_number, $otp_type, $from_both );
			}
		}


		/**
		 * This function handles what message needs to be shown to the user if email
		 * doesn't match the correct format. Check if admin has set any message, and
		 * check if the form is an ajax form to show the message in the correct format.
		 *
		 * @param string $user_email    the phone number being processed.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     has user enabled from both.
		 */
		public function handle_not_matched( $user_email, $otp_type, $from_both ) {
			$message = str_replace( '##email##', $user_email, $this->get_otp_invalid_format_message() );
			if ( $this->is_ajax_form() ) {
				wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
			} else {
				miniorange_site_otp_validation_form( null, null, null, $message, $otp_type, $from_both );
			}
		}


		/**
		 * This function is called to handle Email Verification request. Processes
		 * the request and starts the OTP Verification process to send OTP to user's
		 * email address.
		 *
		 * @param string $user_login    username of the user.
		 * @param string $user_email    email of the user.
		 * @param string $phone_number  phone number of the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     string has user enabled from both.
		 */
		public function start_otp_verification( $user_login, $user_email, $phone_number, $otp_type, $from_both ) {
			$gateway = GatewayFunctions::instance();
			$content = $gateway->mo_send_otp_token( 'EMAIL', $user_email, '' );
			switch ( $content['status'] ) {
				case 'SUCCESS':
					$this->handle_otp_sent( $user_login, $user_email, $phone_number, $otp_type, $from_both, $content );
					break;
				default:
					$this->handle_otp_sent_failed( $user_login, $user_email, $phone_number, $otp_type, $from_both, $content );
					break;
			}
		}

		/**
		 * This function is called to handle what needs to be done when OTP sending is successful.
		 * Checks if the current form is an AJAX form and decides what message has to be
		 * shown to the user.
		 *
		 * @param string $user_login    username of the user.
		 * @param string $user_email    email of the user.
		 * @param string $phone_number  phone number of the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     has user enabled from both.
		 * @param array  $content        string the json decoded response from server.
		 */
		public function handle_otp_sent( $user_login, $user_email, $phone_number, $otp_type, $from_both, $content ) {
			SessionUtils::set_email_transaction_id( $content['txId'] );
			$message = str_replace( '##email##', $user_email, $this->get_otp_sent_message() );
			apply_filters( 'mo_start_reporting', $content['txId'], $user_email, $user_email, $otp_type, $message, 'OTP_SENT' );
			if ( $this->is_ajax_form() ) {
				wp_send_json( MoUtility::create_json( $message, MoConstants::SUCCESS_JSON_TYPE ) );
			} else {
				miniorange_site_otp_validation_form( $user_login, $user_email, $phone_number, $message, $otp_type, $from_both );
			}
		}

		/**
		 * This function is called to handle what needs to be done when OTP sending fails.
		 * Checks if the current form is an AJAX form and decides what message has to be
		 * shown to the user.
		 *
		 * @param string $user_login    username of the user.
		 * @param string $user_email    email of the user.
		 * @param string $phone_number  phone number of the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     has user enabled from both.
		 * @param array  $content       the json decoded response from server.
		 */
		public function handle_otp_sent_failed( $user_login, $user_email, $phone_number, $otp_type, $from_both, $content ) {
			$message = str_replace( '##email##', $user_email, $this->get_otp_sent_failed_message() );

			if ( $this->is_ajax_form() ) {
				wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
			} else {
				miniorange_site_otp_validation_form( null, null, null, $message, $otp_type, $from_both );
			}
		}

		/**
		 * Get the success message to be shown to the user when OTP was sent
		 * successfully. If admin has set his own unique message then
		 * show that to the user instead of the default one.
		 */
		public function get_otp_sent_message() {
			$sent_msg = get_mo_option( 'success_email_message', 'mo_otp_' );
			return $sent_msg ? mo_( $sent_msg ) : MoMessages::showMessage( MoMessages::OTP_SENT_EMAIL );
		}

		/**
		 * Get the error message to be shown to the user when there was an
		 * error sending OTP. If admin has set his own unique message then
		 * show that to the user instead of the default one.
		 */
		public function get_otp_sent_failed_message() {
			$failed_msg = get_mo_option( 'error_email_message', 'mo_otp_' );
			return $failed_msg ? mo_( $failed_msg ) : MoMessages::showMessage( MoMessages::ERROR_OTP_EMAIL );
		}

		/**
		 * This function checks if the email domain has been blocked by the admin
		 *
		 * @param string $user_email    user email.
		 * @param string $phone_number  phone number.
		 * @return bool
		 */
		public function is_blocked( $user_email, $phone_number ) {
			$blocked_email_domains = explode( ';', get_mo_option( 'blocked_domains' ) );
			$blocked_email_domains = apply_filters( 'mo_blocked_email_domains', $blocked_email_domains );
			return in_array( MoUtility::get_domain( $user_email ), $blocked_email_domains, true );
		}

		/**
		 * Function decides what message needs to be shown to the user when he enters a
		 * blocked email domain. It checks if the admin has set any message in the
		 * plugin settings and returns that instead of the default one.
		 */
		public function get_is_blocked_message() {
			$blocked_emails = get_mo_option( 'blocked_email_message', 'mo_otp_' );
			return $blocked_emails ? mo_( $blocked_emails ) : MoMessages::showMessage( MoMessages::ERROR_EMAIL_BLOCKED );
		}

		/**
		 * Function decides what message needs to be sent to the user when the
		 * email does not match the required format. It checks if the admin
		 * has set any message in the plugin settings and returns that instead
		 * of the string default one.
		 */
		public function get_otp_invalid_format_message() {
			$message = get_mo_option( 'invalid_email_message', 'mo_otp_' );
			return $message ? mo_( $message ) : MoMessages::showMessage( MoMessages::ERROR_EMAIL_FORMAT );
		}
	}
}
