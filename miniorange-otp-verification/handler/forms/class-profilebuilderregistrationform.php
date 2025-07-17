<?php
/**
 * Handler Functions for Profile Builder Registration Form
 *
 * @package miniorange-otp-verification/handler/forms
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
use WP_Error;

/**
 * This is the Profile Builder Registration class. This class handles all the
 * functionality related to Profile Builder Registration Form. It extends the
 * FormInterface class to implement some much needed functions.
 */
if ( ! class_exists( 'ProfileBuilderRegistrationForm' ) ) {
	/**
	 * ProfileBuilderRegistrationForm class
	 */
	class ProfileBuilderRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::PB_DEFAULT_REG;
			$this->type_phone_tag          = 'mo_pb_phone_enable';
			$this->type_email_tag          = 'mo_pb_email_enable';
			$this->type_both_tag           = 'mo_pb_both_enable';
			$this->form_key                = 'PB_DEFAULT_FORM';
			$this->form_name               = mo_( 'Profile Builder Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'pb_default_enable' );
			$this->form_documents          = MoFormDocs::PB_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type      = get_mo_option( 'pb_enable_type' );
			$this->phone_key     = get_mo_option( 'pb_phone_meta_key' );
			$this->phone_form_id = 'input[name=' . $this->phone_key . ']';
			add_filter( 'wppb_output_field_errors_filter', array( $this, 'formbuilder_site_registration_errors' ), 99, 4 );
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Profile Builder Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}


		/**
		 * The function hooks into the wppb_build_userdata action to process the
		 * form post values and start the OTP Verification process.
		 *
		 * @param string $field_errors - the field error variable that needs to be sent back.
		 * @param array  $field_args - the field args array containing the form field information.
		 * @param array  $global_request - the type args array containing the global request information.
		 * @param array  $type_args - the type args array containing the form type information.
		 * @throws array ReflectionException.
		 */
		public function formbuilder_site_registration_errors( $field_errors, $field_args, $global_request, $type_args ) {

			/** Return field error if there's an existing error */
			if ( ! empty( $field_errors ) ) {
				return $field_errors;
			}
			if ( 'register' === $global_request['action'] ) {
				if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
					$this->unset_otp_session_variables();
					return $field_errors;
				}
				$this->startOTPVerificationProcess( $field_errors, $global_request );
			}
			return $field_errors;
		}


		/**
		 * This function initializes all the values needed to start
		 * the OTP Verification process and starts it.
		 *
		 * @param string $field_errors - the field error variable that needs to be sent back.
		 * @param array  $data - the data submitted by the user.
		 */
		private function startOTPVerificationProcess( $field_errors, $data ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			$args = $this->extractArgs( $data, $this->phone_key );
			$this->send_challenge(
				$args['username'],
				$args['email'],
				new WP_Error(),
				$args['phone'],
				$this->get_verification_type(),
				$args['passw1'],
				array()
			);
		}


		/**
		 * Extracts required data out of the arguments passed in the hook and returns an array.
		 * This is used instead of extract as extract doesn't work properly when we obfuscate
		 * the plugin.
		 *
		 * @param array  $args Post Parameters.
		 * @param string $phone_key the phone field key.
		 * @return array
		 */
		private function extractArgs( $args, $phone_key ) {
			return array(
				'username' => $args['username'],
				'email'    => $args['email'],
				'passw1'   => $args['passw1'],
				'phone'    => MoUtility::sanitize_check( $phone_key, $args ),
			);
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

			miniorange_site_otp_validation_form(
				$user_login,
				$user_email,
				$phone_number,
				MoUtility::get_invalid_otp_method(),
				$this->get_verification_type(),
				false
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
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Profile Builder related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'pb_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'pb_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'pb_phone_field_key' );

			update_mo_option( 'pb_default_enable', $this->is_form_enabled );
			update_mo_option( 'pb_enable_type', $this->otp_type );
			update_mo_option( 'pb_phone_meta_key', $this->phone_key );
		}
	}
}
