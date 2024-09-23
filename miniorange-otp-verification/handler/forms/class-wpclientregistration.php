<?php
/**
 * Load admin view for WPClientRegistrationForm.
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
use WP_Error;

/**
 * This is the Default WPClient Registration Form class. This class handles all the
 * functionality related to WPClient Registration Form. It extends the FormInterface
 * class to implement some much needed functions.
 */
if ( ! class_exists( 'WPClientRegistration' ) ) {
	/**
	 * WPClientRegistration class
	 */
	class WPClientRegistration extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::WP_CLIENT_REG;
			$this->phone_key               = 'wp_contact_phone';
			$this->phone_form_id           = '#wpc_contact_phone';
			$this->form_key                = 'WP_CLIENT_REG';
			$this->type_phone_tag          = 'mo_wp_client_phone_enable';
			$this->type_email_tag          = 'mo_wp_client_email_enable';
			$this->type_both_tag           = 'mo_wp_client_both_enable';
			$this->form_name               = mo_( 'WP Client Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'wp_client_enable' );
			$this->form_documents          = MoFormDocs::WP_CLIENT_FORM;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type            = get_mo_option( 'wp_client_enable_type' );
			$this->restrict_duplicates = get_mo_option( 'wp_client_restrict_duplicates' );
			add_filter( 'wpc_client_registration_form_validation', array( $this, 'miniorange_client_registration_verify' ), 99, 1 );
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_type || VerificationType::BOTH === $otp_type;
		}

		/**
		 * The function is called to start the OTP Transaction based on the OTP Type
		 * set by the admin in the settings.
		 *
		 * @throws ReflectionException //.
		 * @param array $errors - contains the error of the form.
		 */
		public function miniorange_client_registration_verify( $errors ) {
			$wp_client_reg_nonce = wp_create_nonce( 'wp_client_reg_nonce' );
			if ( ! wp_verify_nonce( $wp_client_reg_nonce, 'wp_client_reg_nonce' ) ) {
				return;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );

			$otp_type             = $this->get_verification_type();
			$phone_number         = MoUtility::sanitize_check( 'contact_phone', $post_data );
			$user_email           = MoUtility::sanitize_check( 'contact_email', $post_data );
			$sanitized_user_login = MoUtility::sanitize_check( 'contact_username', $post_data );

			if ( $this->restrict_duplicates && $this->isPhoneNumberAlreadyInUse( $phone_number, $this->phone_key ) ) {
				$errors .= mo_( 'Phone number already in use. Please Enter a different Phone number.' );
			}

			if ( ! MoUtility::is_blank( $errors ) ) {
				return $errors;
			}

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				MoUtility::initialize_transaction( $this->form_session_var );
			} elseif ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type ) ) {
				$this->unset_otp_session_variables();
				return $errors;
			}
			return $this->startOTPTransaction( $sanitized_user_login, $user_email, $errors, $phone_number );
		}

		/**
		 * The function is called to start the OTP Transaction based on the OTP Type
		 * set by the admin in the settings.
		 *
		 * @param string $sanitized_user_login - the username passed by the registration_errors hook.
		 * @param string $user_email - the email passed by the registration_errors hook.
		 * @param array  $errors - the errors variable passed by the registration_errors hook.
		 * @param string $phone_number - the phone number posted by the user during registration.
		 *
		 * @return WP_Error
		 */
		private function startOTPTransaction( $sanitized_user_login, $user_email, $errors, $phone_number ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::BOTH );
			} else {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::EMAIL );
			}

			return $errors;
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
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the user's phone number.
		 * @param string $key - meta_key to search for.
		 * @return bool
		 */
		private function isPhoneNumberAlreadyInUse( $phone, $key ) {
			global $wpdb;
			$phone   = MoUtility::process_phone_number( $phone );
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.	
			return ! MoUtility::is_blank( $results );
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
		 * @return mixed
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the WPClient Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled     = $this->sanitize_form_post( 'wp_client_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'wp_client_enable_type' );
			$this->restrict_duplicates = $this->get_verification_type() === VerificationType::PHONE
			? $this->sanitize_form_post( 'wp_client_restrict_duplicates' ) : false;

			update_mo_option( 'wp_client_enable', $this->is_form_enabled );
			update_mo_option( 'wp_client_enable_type', $this->otp_type );
			update_mo_option( 'wp_client_restrict_duplicates', $this->restrict_duplicates );
		}
	}
}
