<?php
/**
 * Handles the OTP verification logic for PieRegistrationForm form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the Pie Registration form class. This class handles all the
 * functionality related to Pie Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'PieRegistrationForm' ) ) {
	/**
	 * PieRegistrationForm class
	 */
	class PieRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::PIE_REG;
			$this->type_phone_tag          = 'mo_pie_phone_enable';
			$this->type_email_tag          = 'mo_pie_email_enable';
			$this->type_both_tag           = 'mo_pie_both_enable';
			$this->form_key                = 'PIE_FORM';
			$this->form_name               = mo_( 'PIE Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'pie_default_enable' );
			$this->form_documents          = MoFormDocs::PIE_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type      = get_mo_option( 'pie_enable_type' );
			$this->phone_key     = get_mo_option( 'pie_phone_key' );
			$this->phone_form_id = $this->getPhoneFieldKey();
			add_action( 'pieregister_registration_validation_before', array( $this, 'miniorange_pie_user_registration' ), 99, 1 );
		}

		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for PIE Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otpver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otpver_type || VerificationType::BOTH === $otpver_type;
		}


		/**
		 * The function hooks into the pie_register_after_register_validate action
		 * to process the form post values and start the OTP Verification process.
		 *
		 * @throws ReflectionException  .
		 */
		public function miniorange_pie_user_registration() {
			global $errors;

			if ( ! empty( $errors->errors ) ) {
				return;
			}
			if ( $this->checkIfVerificationIsComplete() ) {
				return;
			}
			if ( empty( $_POST[ $this->phone_form_id ] ) && $this->isPhoneVerificationEnabled() ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$errors->add( 'mo_otp_verify', MoMessages::showMessage( MoMessages::ENTER_PHONE_DEFAULT ) );
				return;
			}
			$this->startTheOTPVerificationProcess( isset( $_POST['e_mail'] ) ? sanitize_email( wp_unslash( $_POST['e_mail'] ) ) : '', isset( $_POST[ $this->phone_form_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->phone_form_id ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			if ( ! $this->checkIfVerificationIsComplete() ) {
				$errors->add( 'mo_otp_verify', MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE ) );
			}
		}

		/**
		 * Checks session to make sure if OTP has been validated.
		 * If OTP is validated return TRUE or FALSE.
		 *
		 * @return bool
		 */
		private function checkIfVerificationIsComplete() {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
				return true;
			}
			return false;
		}

		/**
		 * This function starts the OTP Verification process by checking what
		 * type of OTP Verification has been enabled by the Admin for the form.
		 *
		 * @param string $user_email - user email submitted by the user.
		 * @param string $phone - phone number submitted by the user.
		 */
		private function startTheOTPVerificationProcess( $user_email, $phone ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( '', $user_email, null, $phone, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( '', $user_email, null, $phone, VerificationType::BOTH );
			} else {
				$this->send_challenge( '', $user_email, null, $phone, VerificationType::EMAIL );
			}
		}


		/**
		 * This function is used to generate the phone field key based on the
		 * keys and fields provided by the PIE registration form.
		 *
		 * @return string
		 */
		private function getPhoneFieldKey() {
			$pie_fields = get_option( 'pie_fields' );

			if ( empty( $pie_fields ) || ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) ) {
				return '';
			}
			$fields = maybe_unserialize( $pie_fields );
			foreach ( $fields as $key ) {
				if ( strcasecmp( trim( $key['label'] ), $this->phone_key ) === 0 ) {
					return str_replace(
						'-',
						'_',
						sanitize_title(
							$key['type'] . '_'
							. ( isset( $key['id'] ) ? $key['id'] : '' )
						)
					);
				}
			}
			return '';
		}


		/**
		 * This function hooks into the otp_verification_failed hook. This function
		 * details what is done if the OTP verification fails.
		 *
		 * @param string $user_login the username posted by the user.
		 * @param string $user_email the email posted by the user.
		 * @param string $phone_number the phone number posted by the user.
		 * @param string $otp_type the verification type.
		 */
		public function handle_failed_verification( $user_login, $user_email, $phone_number, $otp_type ) {
			$otpver_type = $this->get_verification_type();
			$from_both   = VerificationType::BOTH === $otpver_type ? true : false;
			miniorange_site_otp_validation_form(
				$user_login,
				$user_email,
				$phone_number,
				MoUtility::get_invalid_otp_method(),
				$otpver_type,
				$from_both
			);
		}


		/**
		 * This function hooks into the otp_verification_successful hook. This function is
		 * details what needs to be done if OTP Verification is successful.
		 *
		 * @param string $redirect_to the redirect to URL after new user registration.
		 * @param string $user_login the username posted by the user.
		 * @param string $user_email the email posted by the user.
		 * @param string $password the password posted by the user.
		 * @param string $phone_number the phone number posted by the user.
		 * @param string $extra_data any extra data posted by the user.
		 * @param string $otp_type the verification type.
		 */
		public function handle_post_verification( $redirect_to, $user_login, $user_email, $password, $phone_number, $extra_data, $otp_type ) {

			SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
		}


		/**
		 * This function is called by the filter mo_phone_dropdown_selector
		 * to return the Jquery selector of the phone field. The function will
		 * push the formID to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array $selector the Jquery selector to be modified.
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, 'input#' . $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Pie Registration form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'pie_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'pie_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'pie_phone_field_key' );

			update_mo_option( 'pie_default_enable', $this->is_form_enabled );
			update_mo_option( 'pie_enable_type', $this->otp_type );
			update_mo_option( 'pie_phone_key', $this->phone_key );
		}
	}
}
