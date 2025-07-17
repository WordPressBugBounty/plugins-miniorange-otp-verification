<?php
/**
 * Load admin view for UserProfile Made Easy Registration form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationLogic;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the User Profile Made Easy Registration Form class. This class handles all the
 * functionality related to User Profile Made Easy Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'UserProfileMadeEasyRegistrationForm' ) ) {
	/**
	 * UserProfileMadeEasyRegistrationForm class
	 */
	class UserProfileMadeEasyRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::UPME_REG;
			$this->type_phone_tag          = 'mo_upme_phone_enable';
			$this->type_email_tag          = 'mo_upme_email_enable';
			$this->type_both_tag           = 'mo_upme_both_enable';
			$this->form_key                = 'UPME_FORM';
			$this->form_name               = mo_( 'UserProfile Made Easy Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'upme_default_enable' );
			$this->form_documents          = MoFormDocs::UPME_FORM_LINK;
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
			$this->otp_type      = get_mo_option( 'upme_enable_type' );
			$this->phone_key     = get_mo_option( 'upme_phone_key' );
			$this->phone_form_id = 'input[name=' . $this->phone_key . ']';

			add_filter( 'insert_user_meta', array( $this, 'miniorange_upme_insert_user' ), 1, 3 );
			add_filter( 'upme_registration_custom_field_type_restrictions', array( $this, 'miniorange_upme_check_phone' ), 1, 2 );

			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
			} elseif ( array_key_exists( 'upme-register-form', $data ) && ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				$this->mo_handle_upme_form_submit( $data );
			}
		}

		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for User Profile Made Easy
		 * Registration form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}

		/**
		 * This function is called to process the user data being
		 * submitted.
		 *
		 * @param array $data - the $_POST array.
		 * @throws ReflectionException .
		 */
		private function mo_handle_upme_form_submit( $data ) {
			$mobile_number = '';
			foreach ( $data as $key => $value ) {
				if ( $key === $this->phone_key ) {
					$mobile_number = $value;
					break;
				}
			}
			$this->miniorange_upme_user( sanitize_text_field( $data['user_login'] ), sanitize_email( $data['user_email'] ), $mobile_number, $data );
		}

		/**
		 * This function hooks into the insert_user_meta filter to check.
		 * if file was uploaded and update the usermeta with URL.
		 *
		 * @param array  $meta -meta value passed on by the filter .
		 * @param object $user -user object passed on by the filter .
		 * @param array  $update -update object passed on by the filter .
		 * @return mixed
		 */
		public function miniorange_upme_insert_user( $meta, $user, $update ) {

			$file_upload = MoPHPSessions::get_session_var( 'file_upload' );
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) || ! $file_upload ) {
				return $meta;
			}
			foreach ( $file_upload as $key => $value ) {
				$current_field_url = get_user_meta( $user->ID, $key, true );
				if ( '' !== $current_field_url ) {
					upme_delete_uploads_folder_files( $current_field_url );  //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default UserProfileMadeEasy Form function.
				}
				update_user_meta( $user->ID, $key, $value );
			}
			return $meta;
		}

		/**
		 * This function hooks into the upme_registration_custom_field_type_restrictions filter to
		 * process and validate the phone number submitted by the user. Return an error object
		 * if phone number is not valid.
		 *
		 * @param object $errors - error object passed by the filter to denote any error for a field.
		 * @param object $fields - field object containing all the field information.
		 * @return array
		 */
		public function miniorange_upme_check_phone( $errors, $fields ) {

			global $phone_logic;
			if ( empty( $errors ) ) {
				if ( $fields['meta'] === $this->phone_key ) {
					if ( ! MoUtility::validate_phone_number( $fields['value'] ) ) {
						$errors[] = str_replace( '##phone##', $fields['value'], $phone_logic->get_otp_invalid_format_message() );
					}
				}
			}
			return $errors;
		}

		/**
		 * This function processes the user post data and processes the file upload before
		 * starting the OTP Verification process.
		 *
		 * @param array $user_name - username submitted by the user.
		 * @param array $user_email - email submitted by the user.
		 * @param array $phone_number - phone number submitted by the user.
		 * @param array $data - sanitized post data.
		 * @throws ReflectionException .
		 */
		private function miniorange_upme_user( $user_name, $user_email, $phone_number, $data ) {
			global $upme_register;
			$upme_register->prepare( $data );
			$upme_register->handle();
			$file_upload = array();

			if ( ! MoUtility::is_blank( $upme_register->errors ) ) {
				return;
			}

			MoUtility::initialize_transaction( $this->form_session_var );

			$this->processFileUpload( $file_upload );
			MoPHPSessions::add_session_var( 'file_upload', $file_upload );
			$this->processAndStartOTPVerification( $user_name, $user_email, $phone_number );
		}

		/**
		 * Process the file uploaded by the user before starting the
		 * OTP Verification process. The file is upload in a temporary
		 * folder and a URL assigned before starting the OTP Verification process.
		 *
		 * @param array $file_upload .
		 */
		private function processFileUpload( &$file_upload ) {
			if ( empty( $_FILES ) ) {
				return;
			}

			$upload_dir  = wp_upload_dir();
			$target_path = $upload_dir['basedir'] . '/upme/';
			if ( ! is_dir( $target_path ) ) {
				mkdir( $target_path, 0777 );
			}

			foreach ( $_FILES as $key => $array ) {
				$base_name   = sanitize_file_name( basename( $array['name'] ) );
				$target_path = $target_path . time() . '_' . $base_name;
				$nice_url    = $upload_dir['baseurl'] . '/upme/';
				$nice_url    = $nice_url . time() . '_' . $base_name;
				move_uploaded_file( $array['tmp_name'], $target_path );
				$file_upload[ $key ] = $nice_url;
			}
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
			SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
		}

		/**
		 * The function is called to start the OTP Transaction based on the OTP Type
		 * set by the admin in the settings.
		 *
		 * @param array $user_name    - the username passed by the registration_errors hook.
		 * @param array $user_email   - the email passed by the registration_errors hook.
		 * @param array $phone_number - the phone number posted by the user during registration.
		 */
		private function processAndStartOTPVerification( $user_name, $user_email, $phone_number ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, null, $phone_number, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, null, $phone_number, VerificationType::BOTH );
			} else {
				$this->send_challenge( $user_name, $user_email, null, $phone_number, VerificationType::EMAIL );
			}
		}

		/**
		 * Handles saving all the User Profile Made Easy Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'upme_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'upme_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'upme_phone_field_key' );

			update_mo_option( 'upme_default_enable', $this->is_form_enabled );
			update_mo_option( 'upme_enable_type', $this->otp_type );
			update_mo_option( 'upme_phone_key', $this->phone_key );
		}
	}
}
