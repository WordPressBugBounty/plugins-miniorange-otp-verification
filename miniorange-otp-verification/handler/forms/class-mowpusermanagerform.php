<?php
/**
 * Load admin view for WP User Manager Registration Form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

use OTP\Helper\FormSessionVars;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use \WP_Error;

/**
 * This is the WordPress User Manager Form class. This class handles all the
 * functionality related to User manager form registration. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'MoWPUserManagerForm' ) ) {
	/**
	 * MoWPUserManagerForm class
	 */
	class MoWPUserManagerForm extends FormHandler implements IFormHandler {

		use Instance;

		/** Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::WP_USER_MANAGER;
			$this->type_email_tag          = 'mo_wp_user_manager_email_enable';
			$this->form_key                = 'WP_USER_MANAGER';
			$this->form_name               = mo_( 'WP User Manager Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'wp_user_manager_enable' );
			$this->form_documents          = MoFormDocs::WP_USER_MANAGER_FORMS_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'wp_user_manager_enable_type' );
			add_filter( 'submit_wpum_form_validate_fields', array( $this, 'mo_send_otp_before_registration' ), 99, 4 );
		}

		/**
		 * The function hooks into the authenticate hook of WordPress to
		 * start the OTP Verification process.
		 *
		 * @param array $errors WP_Error|WP_User.
		 * @param array $username - username of the user trying to log in.
		 * @param array $password - password of the user trying to log in.
		 * @param array $email - password of the user trying to log in.
		 */
		public function mo_send_otp_before_registration( $errors, $username, $password, $email ) {
			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::EMAIL ) ) {
				$this->unset_otp_session_variables();
				return $errors;
			} else {
				MoUtility::initialize_transaction( $this->form_session_var );
				$user_email = $data['user_email'];
				$this->send_challenge( $username, $user_email, $errors, null, VerificationType::EMAIL );
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && VerificationType::PHONE ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the WordPress Login Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'wp_user_manager_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'wp_user_manager_enable_type' );

			update_mo_option( 'wp_user_manager_enable_type', $this->otp_type );
			update_mo_option( 'wp_user_manager_enable', $this->is_form_enabled );
		}

	}
}
