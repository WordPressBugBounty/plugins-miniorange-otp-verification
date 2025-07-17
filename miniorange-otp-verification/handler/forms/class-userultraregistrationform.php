<?php
/**
 * Load admin view for Userulta Registration form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use XooUserRegister;
use XooUserRegisterLite;

/**
 * This is the User Ultra Registration Form class. This class handles all the
 * functionality related to User Ultra Registration. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'UserUltraRegistrationForm' ) ) {
	/**
	 * UserUltraRegistrationForm class
	 */
	class UserUltraRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::UULTRA_REG;
			$this->type_phone_tag          = 'mo_uultra_phone_enable';
			$this->type_email_tag          = 'mo_uultra_email_enable';
			$this->type_both_tag           = 'mo_uultra_both_enable';
			$this->form_key                = 'UULTRA_FORM';
			$this->form_name               = mo_( 'User Ultra Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'uultra_default_enable' );
			$this->form_documents          = MoFormDocs::UULTRA_FORM_LINK;
			parent::__construct();
		}
		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException .
		 */
		public function handle_form() {

			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			if ( ! MoUtility::sanitize_check( 'xoouserultra-register-form', $data ) ) {
				return;
			}
			$this->phone_key     = get_mo_option( 'uultra_phone_key' );
			$this->otp_type      = get_mo_option( 'uultra_enable_type' );
			$this->phone_form_id = 'input[name=' . $this->phone_key . ']';
			$otp_ver_type        = $this->get_verification_type();
			$phone               = $this->isPhoneVerificationEnabled() ? sanitize_text_field( $data[ $this->phone_key ] ) : null;
			$this->mo_handle_uultra_form_submit( sanitize_text_field( $data['user_login'] ), sanitize_email( $data['user_email'] ), $phone, $data );
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for UsersUltra Registration
		 * form.
		 */
		public function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}

		/**
		 * Called to process the form being submitted and start the OTP Verification process.
		 *
		 * @param array $user_name - username submitted by the user.
		 * @param array $user_email - email submitted by the user.
		 * @param array $phone - phone submitted by the user.
		 * @param array $data - post data.
		 * @throws ReflectionException .
		 */
		public function mo_handle_uultra_form_submit( $user_name, $user_email, $phone, $data ) {

			$xo_user = class_exists( 'XooUserRegisterLite' ) ? new XooUserRegisterLite() : new XooUserRegister();

			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}

			$xo_user->uultra_prepare_request( $data );
			$xo_user->uultra_handle_errors();

			if ( MoUtility::is_blank( $xo_user->errors ) ) {
				$data['no_captcha'] = 'yes';
				$this->mo_handle_otp_verification_uultra( $user_name, $user_email, null, $phone );
			}
		}

		/**
		 * Function is called to start the OTP Verification process based on the settings set by the admin in the plugin.
		 *
		 * @param string $user_name - username submitted by the user.
		 * @param string $user_email - email submitted by the user.
		 * @param array  $errors - array containing all form related errors.
		 * @param string $phone - phone submitted by the user.
		 * @throws ReflectionException .
		 */
		public function mo_handle_otp_verification_uultra( $user_name, $user_email, $errors, $phone ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, $errors, $phone, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, $errors, $phone, VerificationType::BOTH );
			} else {
				$this->send_challenge( $user_name, $user_email, $errors, $phone, VerificationType::EMAIL );
			}
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

			$otp_ver_type = $this->get_verification_type();
			$from_both    = VerificationType::BOTH === $otp_ver_type ? true : false;
			miniorange_site_otp_validation_form(
				$user_login,
				$user_email,
				$phone_number,
				MoUtility::get_invalid_otp_method(),
				$otp_ver_type,
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

			$this->unset_otp_session_variables();
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
		 * @param  array $selector   the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the User Ultra Registration related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'uultra_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'uultra_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'uultra_phone_field_key' );

			update_mo_option( 'uultra_default_enable', $this->is_form_enabled );
			update_mo_option( 'uultra_enable_type', $this->otp_type );
			update_mo_option( 'uultra_phone_key', $this->phone_key );
		}
	}
}
