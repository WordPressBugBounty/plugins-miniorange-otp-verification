<?php
/**
 * Form Action Handler.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoMessages;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\BaseActionHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;

/**
 * This is the Custom Form class. This class handles all the
 * functionality related to Custom Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'FormActionHandler' ) ) {
	/**
	 * FormActionHandler class
	 */
	class FormActionHandler extends BaseActionHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			parent::__construct();
			add_action( 'init', array( $this, 'handle_formActions' ), 1 );
			add_action( 'mo_validate_otp', array( $this, 'validateOTP' ), 1, 3 );
			add_action( 'mo_generate_otp', array( $this, 'challenge' ), 2, 8 );
			add_filter( 'mo_filter_phone_before_api_call', array( $this, 'filterPhone' ), 1, 1 );
		}

		/**
		 * This function is called from every form handler class to start the OTP
		 * Verification process. Keeps certain variables in session and start the
		 * OTP Verification process.
		 *
		 * @param string $user_login    username submitted by the user.
		 * @param string $user_email    email submitted by the user.
		 * @param string $errors        error variable ( currently not being used ).
		 * @param string $phone_number  phone number submitted by the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $password      password submitted by the user.
		 * @param array  $extra_data    an array containing all the extra data submitted by the user.
		 * @param bool   $from_both     denotes if user has a choice between email and phone verification.
		 */
		public function challenge( $user_login, $user_email, $errors, $phone_number = null,
		$otp_type = 'email', $password = '', $extra_data = null, $from_both = false ) {

			$phone_number = MoUtility::process_phone_number( $phone_number );
			MoPHPSessions::add_session_var( 'current_url', MoUtility::current_page_url() );
			MoPHPSessions::add_session_var( 'user_email', $user_email );
			MoPHPSessions::add_session_var( 'user_login', $user_login );
			MoPHPSessions::add_session_var( 'user_password', $password );
			MoPHPSessions::add_session_var( 'phone_number_mo', $phone_number );
			MoPHPSessions::add_session_var( 'extra_data', $extra_data );
			$this->handleOTPAction( $user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data );
		}


		/**
		 * This function is called to handle the resend OTP Verification process.
		 *
		 * @param string $otp_type  email or sms verification.
		 * @param string $from_both denotes if user has a choice between email and phone verification.
		 */
		private function handleResendOTP( $otp_type, $from_both ) {

			$user_email   = MoPHPSessions::get_session_var( 'user_email' );
			$user_login   = MoPHPSessions::get_session_var( 'user_login' );
			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			$extra_data   = MoPHPSessions::get_session_var( 'extra_data' );
			do_action( 'mo_generate_otp', $user_login, $user_email, null, $phone_number, $otp_type, null, $extra_data, $from_both );
			$this->handleOTPAction( $user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data );
		}

		/**
		 * This function starts the email or sms verification depending on the otp type.
		 *
		 * @param string $user_login    username submitted by the user.
		 * @param string $user_email    email submitted by the user.
		 * @param string $phone_number  phone number submitted by the user.
		 * @param string $otp_type      email or sms verification.
		 * @param string $from_both     denotes if user has a choice between email and phone verification.
		 * @param array  $extra_data    an array containing all the extra data submitted by the user.
		 */
		private function handleOTPAction( $user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data ) {
			do_action( 'mo_generate_or_resend_otp', $user_login, $user_email, $phone_number, $otp_type, $from_both, $extra_data );
			global $phone_logic, $email_logic;
			switch ( $otp_type ) {
				case VerificationType::PHONE:
					$phone_logic->handle_logic( $user_login, $user_email, $phone_number, $otp_type, $from_both );
					break;
				case VerificationType::EMAIL:
					$email_logic->handle_logic( $user_login, $user_email, $phone_number, $otp_type, $from_both );
					break;
				case VerificationType::BOTH:
					miniorange_verification_user_choice(
						$user_login,
						$user_email,
						$phone_number,
						MoMessages::showMessage( MoMessages::CHOOSE_METHOD ),
						$otp_type
					);
					break;
				case VerificationType::EXTERNAL:
					mo_external_phone_validation_form(
						$extra_data['curl'],
						$user_email,
						$extra_data['message'],
						$extra_data['form'],
						$extra_data['data']
					);
					break;
			}
		}


		/**
		 * This function handles which page to redirect the user to when he
		 * clicks on the go back link on the OTP Verification pop up.
		 */
		private function handleGoBackAction() {

			$url = MoPHPSessions::get_session_var( 'current_url' );
			do_action( 'unset_session_variable' );
			header( 'location:' . $url );
		}


		/**
		 * This function is called from each form class to validate the otp entered by the
		 * user.
		 *
		 * @param string $otp_type OTPType for which validation needs to be done.
		 * @param string $request_var otp token key.
		 * @param string $otp otp token submitted.
		 * @return void
		 */
		public function validateOTP( $otp_type, $request_var, $otp ) {
			$user_login   = MoPHPSessions::get_session_var( 'user_login' );
			$user_email   = MoPHPSessions::get_session_var( 'user_email' );
			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			$password     = MoPHPSessions::get_session_var( 'user_password' );
			$extra_data   = MoPHPSessions::get_session_var( 'extra_data' );

			$tx_id = Sessionutils::get_transaction_id( $otp_type );
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active( 'mowhatsapp/miniorange-custom-validation.php' ) && get_mo_option( 'mo_whatsapp_enable', 'mo_wp_sms_' ) && ( MoPHPSessions::get_session_var( 'sent_type' ) !== 'SMS' ) ) {
				$tx_id = MoPHPSessions::get_session_var( 'mo_wp_otp_site_tx_id' );
			}
			$token = MoUtility::sanitize_check( $request_var, MoUtility::mo_sanitize_array( $_REQUEST ) );// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need for nonce verification as the function is called on third party plugin hook.
			$token = ! $token ? $otp : $token;
			if ( ! is_null( $tx_id ) ) {

				$gateway           = GatewayFunctions::instance();
				$content           = $gateway->mo_validate_otp_token( $tx_id, $token );
				$validation_status = 'SUCCESS' === $content['status'] ? 'OTP_VERIFIED' : 'VERIFICATION_FAILED';
				apply_filters( 'mo_update_reporting', $tx_id, $validation_status );
				switch ( $content['status'] ) {
					case 'SUCCESS':
						$this->onValidationSuccess( $user_login, $user_email, $password, $phone_number, $extra_data, $otp_type );
						break;
					default:
						$this->onValidationFailed( $user_login, $user_email, $phone_number, $otp_type );
						break;
				}
			}
		}


		/**
		 * This function is called to handle what needs to be done if OTP
		 * entered by the user is validated successfully. Calls an action
		 * which could be hooked into to process this elsewhere. Check each
		 * handle_post_verification of each form handler.
		 *
		 * @param string $user_login username submitted by the user.
		 * @param string $user_email email submitted by the user.
		 * @param string $password password submitted by the user.
		 * @param string $phone_number phone number submitted by the user.
		 * @param string $extra_data an array containing all the extra data submitted by the user.
		 * @param string $otp_type The VerificationType.
		 */
		private function onValidationSuccess( $user_login, $user_email, $password, $phone_number, $extra_data, $otp_type ) {
			$redirect_to = array_key_exists( 'redirect_to', $_POST ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			do_action( 'otp_verification_successful', $redirect_to, $user_login, $user_email, $password, $phone_number, $extra_data, $otp_type );
		}


		/**
		 * This function is called to handle what needs to be done if OTP
		 * entered by the user is not a valid OTP and fails the verification.
		 * Calls an action which could be hooked into to process this elsewhere.
		 * Check each handle_post_verification of each form handler.
		 *
		 * @param string $user_login username submitted by the user.
		 * @param string $user_email email submitted by the user.
		 * @param string $phone_number phone number submitted by the user string.
		 * @param string $otp_type The VerificationType.
		 */
		private function onValidationFailed( $user_login, $user_email, $phone_number, $otp_type ) {
			do_action( 'otp_verification_failed', $user_login, $user_email, $phone_number, $otp_type );
		}


		/**
		 * This function starts the OTP verification process based on user input.
		 * starts Email or Phone Verification based on user input.
		 *
		 * @param String $post_data  the data posted.
		 */
		private function handleOTPChoice( $post_data ) {

			$user_login = MoPHPSessions::get_session_var( 'user_login' );
			$user_email = MoPHPSessions::get_session_var( 'user_email' );
			$user_phone = MoPHPSessions::get_session_var( 'phone_number_mo' );
			$user_pass  = MoPHPSessions::get_session_var( 'user_password' );
			$extra_data = MoPHPSessions::get_session_var( 'extra_data' );

			$otp_ver_type = strcasecmp( $post_data['mo_customer_validation_otp_choice'], 'user_email_verification' ) === 0
			? VerificationType::EMAIL : VerificationType::PHONE;

			$this->challenge( $user_login, $user_email, null, $user_phone, $otp_ver_type, $user_pass, $extra_data, true );
		}


		/**
		 * This function filters the phone number before making any api calls.
		 * This is mostly used in the on-prem plugin to filter the phone number
		 * before the api call is made to send OTPs.
		 *
		 * @param String $phone the phone number to be processed.
		 * @return String
		 */
		public function filterPhone( $phone ) {
			return str_replace( '+', '', $phone );
		}


		/**
		 * This function hooks into the init WordPress hook. This function processes the
		 * form post data and calls the correct function to process the posted data.
		 * This mostly handles all the plugin related functionality.
		 */
		public function handle_formActions() {

			if ( ( ! isset( $_POST['mopopup_wpnonce'] ) || ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mopopup_wpnonce'] ) ), 'mo_popup_options' ) ) ) ) { // phpcs:ignore -- false positive.
				return;
			}
			if ( array_key_exists( 'option', $_REQUEST ) ) { // phpcs:ignore -- false positive.

				$from_both    = MoUtility::sanitize_check( 'from_both', $_POST );
				$otp_type     = MoUtility::sanitize_check( 'otp_type', $_POST );
				$data         = MoUtility::mo_sanitize_array( $_POST );
				$request_data = MoUtility::mo_sanitize_array( $_REQUEST );

				switch ( trim( sanitize_text_field( wp_unslash( $_REQUEST['option'] ) ) ) ) { // phpcs:ignore -- false positive.
					case 'validation_goBack':
						$this->handleGoBackAction();
						break;
					case 'miniorange-validate-otp-form':
						$this->validateOTP( $otp_type, 'mo_otp_token', null );
						break;
					case 'verification_resend_otp':
						$this->handleResendOTP( $otp_type, $from_both );
						break;
					case 'miniorange-validate-otp-choice-form':
						$this->handleOTPChoice( $data );
						break;
				}
			}
		}
	}
}
