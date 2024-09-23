<?php
/**
 * Handles the OTP verification logic for FormidableForm form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use mysql_xdevapi\Session;
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationLogic;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use stdClass;

/**
 * This is the Simplr Registration form class. This class handles all the
 * functionality related to Simplr Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'SimplrRegistrationForm' ) ) {
	/**
	 * SimplrRegistrationForm class
	 */
	class SimplrRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::SIMPLR_REG;
			$this->type_phone_tag          = 'mo_phone_enable';
			$this->type_email_tag          = 'mo_email_enable';
			$this->type_both_tag           = 'mo_both_enable';
			$this->form_key                = 'SIMPLR_FORM';
			$this->form_name               = mo_( 'Simplr User Registration Form Plus' );
			$this->is_form_enabled         = get_mo_option( 'simplr_default_enable' );
			$this->form_documents          = MoFormDocs::SIMPLR_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->form_key      = get_mo_option( 'simplr_field_key' );
			$this->otp_type      = get_mo_option( 'simplr_enable_type' );
			$this->phone_form_id = 'input[name=' . $this->form_key . ']';
			add_filter( 'simplr_validate_form', array( $this, 'simplr_site_registration_errors' ), 10, 1 );
		}
		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Simplr Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}

		/**
		 * This function hooks into the simplr_validate_form hook to process the data
		 * submitted and start the OTP verification process.
		 *
		 * @param string $errors - the error variable denoting any form post error.
		 * @return mixed
		 * @throws ReflectionException Add exception.
		 */
		private function simplr_site_registration_errors( $errors ) {
			$password     = '';
			$phone_number = '';
			$fbuser_id    = isset( $_POST['fbuser_id'] ) ? sanitize_text_field( wp_unslash( $_POST['fbuser_id'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( ! empty( $errors ) || $fbuser_id ) {
				return $errors;
			}
			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			foreach ( $data as $key => $value ) {
				if ( 'username' === $key ) {
					$username = $value;
				} elseif ( 'email' === $key ) {
					$email = $value;
				} elseif ( 'password' === $key ) {
					$password = $value;
				} elseif ( $key === $this->form_key ) {
					$phone_number = $value;
				} else {
					$extra_data[ $key ] = $value;
				}
			}
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0
			&& ! $this->processPhone( $phone_number, $errors ) ) {
				return $errors;
			}
			$this->processAndStartOTPVerificationProcess( $username, $email, $errors, $phone_number, $password, $extra_data );
			return $errors;
		}

		/**
		 * Process the phone and see if it is a valid phone number. If it is
		 * then return TRUE or return FALSE.
		 *
		 * @param string $phone_number - Phone number to be validated and submitted by the user.
		 * @param string $errors - passed by reference to add an error if phone number is not valid.
		 * @return bool
		 */
		private function processPhone( $phone_number, &$errors ) {
			if ( ! MoUtility::validate_phone_number( $phone_number ) ) {

				global $phone_logic;
				$errors[] .= str_replace( '##phone##', $phone_number, $phone_logic->get_otp_invalid_format_message() );
				add_filter( $this->form_key . '_error_class', '_sreg_return_error' );
				return false;
			}
			return true;
		}
		/**
		 * Process and start the OTP Verification process based on the settings set by the
		 * admin.
		 *
		 * @param string $username - username submitted by the user.
		 * @param string $email - email submitted by the user.
		 * @param string $errors - an array denoting all errors which might have come up.
		 * @param string $phone_number - phone number submitted by the user.
		 * @param string $password - password submitted by the user.
		 * @param string $extra_data - all the extra data which might have been submitted by the user.
		 * @throws ReflectionException Add exception.
		 */
		private function processAndStartOTPVerificationProcess( $username, $email, $errors, $phone_number, $password, $extra_data ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::PHONE, $password, $extra_data );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::BOTH, $password, $extra_data );
			} else {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::EMAIL, $password, $extra_data );
			}
		}
		/**
		 * This function is called if the otp verification was successful to register the user.
		 *
		 * @param string $user_login - username submitted by the user.
		 * @param string $user_email - email submitted by the user.
		 * @param string $password - password submitted by the user.
		 * @param string $phone_number - phone number submitted by the user.
		 * @param string $extra_data - all the extra data which might have been submitted by the user.
		 */
		private function register_simplr_user( $user_login, $user_email, $password, $phone_number, $extra_data ) {
			$data = array();
			global $sreg;
			if ( ! $sreg ) {
				$sreg = new stdClass();
			}
			$data['username'] = $user_login;
			$data['email']    = $user_email;
			$data['password'] = $password;
			if ( $this->form_key ) {
				$data[ $this->form_key ] = $phone_number;
			}
			$data         = array_merge( $data, $extra_data );
			$atts         = $extra_data['atts'];
			$sreg->output = simplr_setup_user( $atts, $data );
			if ( MoUtility::is_blank( $sreg->errors ) ) {
				$this->checkMessageAndRedirect( $atts );
			}
		}
		/**
		 * Check what kind of message needs to be shown to the user and
		 * redirect him to the page accordingly.
		 *
		 * @param string $atts - user attributes.
		 */
		private function checkMessageAndRedirect( $atts ) {
			global $sreg,$simplr_options;

			$page = isset( $atts['thanks'] ) ? get_permalink( $atts['thanks'] )
				: ( ! MoUtility::is_blank( $simplr_options->thank_you ) ? get_permalink( $simplr_options->thank_you ) : '' );
			if ( MoUtility::is_blank( $page ) ) {
				$sreg->success = $sreg->output;
			} else {
				wp_safe_redirect( $page );
				exit;
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

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
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
			$this->register_simplr_user( $user_login, $user_email, $password, $phone_number, $extra_data );
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
		 * Handles saving all the Simplr Registration related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'simplr_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'simplr_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'simplr_phone_field_key' );

			update_mo_option( 'simplr_default_enable', $this->is_form_enabled );
			update_mo_option( 'simplr_enable_type', $this->otp_type );
			update_mo_option( 'simplr_field_key', $this->phone_key );
		}
	}
}
