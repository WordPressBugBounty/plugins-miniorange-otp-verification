<?php
/**
 * Load admin view for WpEmemberForm.
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
use \WP_Error;

/**
 * This is the WP eMember form class. This class handles all the
 * functionality related to WpEmember Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WpEmemberForm' ) ) {
	/**
	 * WpEmemberForm class
	 */
	class WpEmemberForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::EMEMBER;
			$this->type_phone_tag          = 'mo_emember_phone_enable';
			$this->type_email_tag          = 'mo_emember_email_enable';
			$this->type_both_tag           = 'mo_emember_both_enable';
			$this->form_key                = 'WP_EMEMBER';
			$this->form_name               = mo_( 'WP eMember' );
			$this->is_form_enabled         = get_mo_option( 'emember_default_enable' );
			$this->phone_key               = 'wp_emember_phone';
			$this->phone_form_id           = 'input[name=' . $this->phone_key . ']';
			$this->form_documents          = MoFormDocs::EMEMBER_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'emember_enable_type' );
			if ( ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}
			if ( ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'emember-plain-registration-nonce' ) ) {
				return;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			if ( array_key_exists( 'emember_dsc_nonce', $post_data ) && ! array_key_exists( 'option', $post_data ) ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				$this->miniorange_emember_user_registration( $post_data );
			}
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for eMemember Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_type || VerificationType::BOTH === $otp_type;
		}


		/**
		 * The function hooks into the init action hook
		 * to process the form post values and start the OTP Verification process.
		 *
		 * @param array $post_data - $_POST.
		 */
		private function miniorange_emember_user_registration( $post_data ) {

			if ( $this->validatePostFields( $post_data ) ) {
				$phone = array_key_exists( $this->phone_key, $post_data ) ? sanitize_text_field( $post_data[ $this->phone_key ] ) : null;
				$this->startTheOTPVerificationProcess( sanitize_text_field( $post_data['wp_emember_user_name'] ), sanitize_email( $post_data['wp_emember_email'] ), $phone );
			}
		}


		/**
		 * This function starts the OTP Verification process by checking what
		 * type of OTP Verification has been enabled by the Admin for the form.
		 *
		 * @param string $username username submitted by the user.
		 * @param string $user_email user email submitted by the user.
		 * @param string $phone phone number submitted by the user.
		 * @throws ReflectionException //.
		 */
		private function startTheOTPVerificationProcess( $username, $user_email, $phone ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			$errors = new WP_Error();
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $username, $user_email, $errors, $phone, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $username, $user_email, $errors, $phone, VerificationType::BOTH );
			} else {
				$this->send_challenge( $username, $user_email, $errors, $phone, VerificationType::EMAIL );
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
		 * Validates the form post values and makes sure there are no basic
		 * post errors like username / email already been taken etc.
		 *
		 * @param array $post_data - $_POST.
		 * @return TRUE OR FALSE.
		 */
		private function validatePostFields( $post_data ) {
			if ( is_blocked_ip( get_real_ip_addr() ) ) { //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WP Emember Form.
				return false;
			}
			if ( emember_wp_username_exists( sanitize_text_field( $post_data['wp_emember_user_name'] ) ) //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WP Emember Form.
			|| emember_username_exists( sanitize_text_field( $post_data['wp_emember_user_name'] ) ) ) { //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WP Emember Form.
				return false;
			}
			if ( is_blocked_email( sanitize_text_field( $post_data['wp_emember_email'] ) ) || emember_registered_email_exists( sanitize_text_field( $post_data['wp_emember_email'] ) ) //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WP Emember Form.
			|| emember_wp_email_exists( sanitize_text_field( $post_data['wp_emember_email'] ) ) ) { //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WP Emember Form.
				return false;
			}
			if ( isset( $post_data['eMember_Register'] ) && array_key_exists( 'wp_emember_pwd_re', $post_data )
			&& sanitize_text_field( $post_data['wp_emember_pwd'] ) !== sanitize_text_field( $post_data['wp_emember_pwd_re'] ) ) {
				return false;
			}
			return true;
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the WP eMember form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'emember_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'emember_enable_type' );

			update_mo_option( 'emember_default_enable', $this->is_form_enabled );
			update_mo_option( 'emember_enable_type', $this->otp_type );
		}
	}
}
