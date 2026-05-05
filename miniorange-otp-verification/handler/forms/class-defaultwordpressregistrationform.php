<?php
/**
 * Handles the OTP verification logic for Default WordPress Registration Form.
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\FormSessionVars;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Error;

/**
 * This is the Default WordPress Registration Form class. This class handles all the
 * functionality related to WordPress Default Registration Form. It extends the FormInterface
 * class to implement some much needed functions.
 */
if ( ! class_exists( 'DefaultWordPressRegistrationForm' ) ) {
	/**
	 * DefaultWordPressRegistrationForm class
	 */
	class DefaultWordPressRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::WP_DEFAULT_REG;
			$this->phone_key               = 'telephone';
			$this->phone_form_id           = '#phone_number_mo';
			$this->form_key                = 'WP_DEFAULT';
			$this->type_phone_tag          = 'mo_wp_default_phone_enable';
			$this->type_email_tag          = 'mo_wp_default_email_enable';
			$this->type_both_tag           = 'mo_wp_default_both_enable';
			$this->form_name               = 'WordPress Default / TML Registration Form';
			$this->is_form_enabled         = get_mo_option( 'wp_default_enable' );
			$this->form_documents          = MoFormDocs::WP_DEFAULT_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type              = get_mo_option( 'wp_default_enable_type' );
			$this->disable_auto_activate = get_mo_option( 'wp_reg_auto_activate' ) ? false : true;
			$this->restrict_duplicates   = get_mo_option( 'wp_reg_restrict_duplicates' );

			add_action( 'register_form', array( $this, 'miniorange_site_register_form' ) );
			add_filter( 'registration_errors', array( $this, 'miniorange_site_registration_errors' ), 99, 3 );
			add_action( 'admin_post_nopriv_validation_goBack', array( $this, '_handle_validation_goBack_action' ) );
			add_action( 'user_register', array( $this, 'miniorange_registration_save' ), 10, 1 );
			add_filter( 'wp_login_errors', array( $this, 'miniorange_custom_reg_message' ), 10, 2 );
			if ( ! $this->disable_auto_activate ) {
				remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
			}
		}

		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Default Registration
		 * form.
		 */
		private function is_phone_verification_enabled() {
			$otp_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_type || VerificationType::BOTH === $otp_type;
		}

		/**
		 * This function changes the custom message shown on the login page after
		 * user is created successfully. This messages hooks into the {@code wp_login_errors}
		 * hook and replaces the registered message if one already exists in the
		 * {@code WP_Error} object.
		 *
		 * @param WP_Error $errors -Errors in the formsubmitted by the user.
		 * @param string   $redirect_to -Redirection link.
		 * @return WP_Error
		 */
		public function miniorange_custom_reg_message( WP_Error $errors, $redirect_to ) {
			if ( ! $this->disable_auto_activate ) {
				if ( in_array( 'registered', $errors->get_error_codes(), true ) ) {
					$errors->remove( 'registered' );
					$errors->add( 'registered', __( 'Registration Complete.', 'miniorange-otp-verification' ), 'message' );
				}
			}
			return $errors;
		}

		/**
		 * This function hooks into the register_form hook. This function is called to add
		 * the phone number field to the registration form so that user can enter his
		 * phone number for SMS Verification. This is called only when the otp type is phone or both.
		 */
		public function miniorange_site_register_form() {
			// Core only fires register_form on wp-login.php registration; output nonce every time so POST always includes it.
			echo '<input type="hidden" name="register_nonce" value="' . esc_attr( wp_create_nonce( 'register_nonce' ) ) . '" />';
			if ( $this->is_phone_verification_enabled() ) {
				echo '<label for="phone_number_mo">' . esc_html( __( 'Phone Number', 'miniorange-otp-verification' ) ) . '<br />
					<input type="text" name="phone_number_mo" id="phone_number_mo" class="input" value="" style=""/></label>';
			}
			if ( ! $this->disable_auto_activate ) {
				echo '<label for="password_mo">' . esc_html( __( 'Password', 'miniorange-otp-verification' ) ) . '<br />
					<input type="password" name="password_mo" id="password_mo" class="input" value="" style=""/></label>';
				echo '<label for="confirm_password_mo">' . esc_html( __( 'Confirm Password', 'miniorange-otp-verification' ) ) . '<br />
					<input type="password" name="confirm_password_mo" id="confirm_password_mo" class="input" value="" style=""/></label>';
				echo '<script>window.onload=function(){ document.getElementById("reg_passmail").remove(); }</script>';
			}
		}

		/**
		 * Verifies the registration form nonce (do not use sanitize_key � it can break wp_verify_nonce()).
		 *
		 * @return bool
		 */
		private function verify_register_submission_nonce() {
			$nonce = filter_input( INPUT_POST, 'register_nonce', FILTER_UNSAFE_RAW );
			if ( ! is_string( $nonce ) || '' === $nonce ) {
				return false;
			}
			$nonce = sanitize_text_field( wp_unslash( $nonce ) );
			return (bool) wp_verify_nonce( $nonce, 'register_nonce' );
		}

		/**
		 * This function hooks into the user_register hook. This function is called to
		 * save the phone number posted by the user during registration in the usermeta
		 * for that user after successful registration.
		 *
		 * @param string $user_id - the user id of the new user that was created.
		 */
		public function miniorange_registration_save( $user_id ) {

			if ( ! $this->verify_register_submission_nonce() ) {
				return;
			}
			$raw_post     = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$raw_post     = is_array( $raw_post ) ? $raw_post : array();
			$data         = MoUtility::mo_sanitize_array( $raw_post );
			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( $phone_number ) {
				add_user_meta( $user_id, $this->phone_key, $phone_number );
			}
			if ( ! $this->disable_auto_activate ) {
				wp_set_password( isset( $data['password_mo'] ) ? wp_unslash( $data['password_mo'] ) : '', $user_id );
				update_user_option( $user_id, 'default_password_nag', false, true );
			}
		}

		/**
		 * This function hooks into the registration_errors hook. This function is called to
		 * check the phone number posted by the user. and pass it to start the OTP
		 * Verification process
		 *
		 * @return WP_Error
		 * @param WP_Error $errors - the errors variable passed by the registration_errors hook.
		 * @param string   $sanitized_user_login - the username passed by the registration_errors hook.
		 * @param string   $user_email - the email passed by the registration_errors hook.
		 * @throws ReflectionException - In case of failures, an exception is thrown.
		 */
		public function miniorange_site_registration_errors( WP_Error $errors, $sanitized_user_login, $user_email ) {

			if ( ! $this->verify_register_submission_nonce() ) {
				MoPHPSessions::unset_session( 'mo_reg_otp_just_verified' );
				$errors->add(
					'invalid_nonce',
					__( 'Security check failed. Please try again.', 'miniorange-otp-verification' )
				);
				return $errors;
			}

			if ( $errors->has_errors() ) {
				MoPHPSessions::unset_session( 'mo_reg_otp_just_verified' );
				return $errors;
			}

			$raw_post     = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
			$raw_post     = is_array( $raw_post ) ? $raw_post : array();
			$data         = MoUtility::mo_sanitize_array( $raw_post );
			$phone_number = isset( $data['phone_number_mo'] ) ? sanitize_text_field( wp_unslash( $data['phone_number_mo'] ) ) : null;
			$password     = isset( $data['password_mo'] ) ? sanitize_text_field( wp_unslash( $data['password_mo'] ) ) : null;
			$confirm_pass = isset( $data['confirm_password_mo'] ) ? sanitize_text_field( wp_unslash( $data['confirm_password_mo'] ) ) : null;
			$this->check_if_phone_number_unique( $errors, $phone_number );
			$this->validate_passwords( $errors, $password, $confirm_pass );

			if ( ! empty( $errors->errors ) ) {
				MoPHPSessions::unset_session( 'mo_reg_otp_just_verified' );
				return $errors;
			}
			if ( ! $this->otp_type ) {
				return $errors;
			}

			$verified_flag = MoPHPSessions::get_session_var( 'mo_reg_otp_just_verified' );
			if ( ! MoUtility::is_blank( $verified_flag ) && $verified_flag === $this->form_session_var ) {
				MoPHPSessions::unset_session( 'mo_reg_otp_just_verified' );
				return $errors;
			}

			return $this->start_otp_transaction( $sanitized_user_login, $user_email, $errors, $phone_number, $data );
		}

		/**
		 * If {@code $_disableAutoActivate} is set then validate the password set by the user
		 * on the registration page. If password and confirm password match and is of relative
		 * strength then pass it along with no errors.
		 *
		 * @param WP_Error $error Errors present in the Form.
		 * @param string   $password New password set by user.
		 * @param string   $confirm_pass Confirm the new password.
		 */
		private function validate_passwords( WP_Error &$error, $password, $confirm_pass ) {
			if ( $this->disable_auto_activate ) {
				return;
			}
			if ( strcasecmp( $password, $confirm_pass ) !== 0 ) {
				$error->add( 'password_mismatch', MoMessages::showMessage( MoMessages::PASS_MISMATCH ) );
			}
		}

		/**
		 * Checks if admin has set the option to keep the phone numbers unique. Also
		 * check if the phone number entered by the user is unique or not.
		 *
		 * @param WP_Error $errors      - registration error.
		 * @param string   $phone_number - phone number entered by the user.
		 */
		private function check_if_phone_number_unique( WP_Error &$errors, $phone_number ) {
			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				return;
			}

			if ( MoUtility::is_blank( $phone_number ) || ! MoUtility::validate_phone_number( $phone_number ) ) {
				$errors->add( 'invalid_phone', MoMessages::showMessage( MoMessages::ENTER_PHONE_DEFAULT ) );
			} elseif ( $this->restrict_duplicates && $this->is_phone_number_already_in_use( trim( $phone_number ), $this->phone_key ) ) {
				$errors->add( 'invalid_phone', MoMessages::showMessage( MoMessages::PHONE_EXISTS ) );
			}
		}

		/**
		 * Retrieves and sanitizes email and phone data from the submitted form.
		 *
		 * @return array {
		 *     @type string $email Sanitized email address from the form.
		 *     @type string $phone Processed phone number of the user.
		 * }
		 */
		public function get_email_phone_data() {
			if ( ! isset( $_POST['mopopup_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mopopup_wpnonce'] ) ), 'mo_popup_options' ) ) {
				return array(
					'email' => '',
					'phone' => '',
				);
			}
			$data  = MoUtility::mo_sanitize_array( $_POST );
			$phone = isset( $data['phone_number_mo'] ) ? $data['phone_number_mo'] : null;
			$email = isset( $data['user_email'] ) ? $data['user_email'] : null;
			return array(
				'email' => sanitize_email( wp_unslash( $email ?? '' ) ),
				'phone' => MoUtility::process_phone_number( $phone ?? '' ),
			);
		}

		/**
		 * The function is called to start the OTP Transaction based on the OTP Type
		 * set by the admin in the settings.
		 *
		 * @param string $sanitized_user_login - the username passed by the registration_errors hook.
		 * @param string $user_email - the email passed by the registration_errors hook.
		 * @param object $errors - the errors variable passed by the registration_errors hook.
		 * @param string $phone_number - the phone number posted by the user during registration.
		 * @param string $data - the value entered by the user.
		 * @return WP_Error
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		private function start_otp_transaction( $sanitized_user_login, $user_email, $errors, $phone_number, $data ) {
			if ( ! MoUtility::is_blank( array_filter( $errors->errors ) ) ) {
				return $errors;
			}

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::PHONE, null, null, null, $this->form_session_var );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::BOTH, null, null, null, $this->form_session_var );
			} else {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::EMAIL, null, null, null, $this->form_session_var );
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
			MoPHPSessions::add_session_var( 'mo_reg_otp_just_verified', $this->form_session_var );
			$this->unset_otp_session_variables();
		}

		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the user's phone number.
		 * @param string $key - meta_key to search for.
		 * @return bool
		 */
		private function is_phone_number_already_in_use( $phone, $key ) {
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
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->is_phone_verification_enabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the Default WordPress Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option(), 'wp_default_enable' ) ) {
				return;
			}

			$this->is_form_enabled       = $this->sanitize_form_post( 'wp_default_enable' );
			$this->otp_type              = $this->sanitize_form_post( 'wp_default_enable_type' );
			$this->restrict_duplicates   = $this->sanitize_form_post( 'wp_reg_restrict_duplicates_phone' )
				|| $this->sanitize_form_post( 'wp_reg_restrict_duplicates_both' );
			$this->disable_auto_activate = $this->sanitize_form_post( 'wp_reg_auto_activate' ) ? false : true;

			update_mo_option( 'wp_default_enable', $this->is_form_enabled );
			update_mo_option( 'wp_default_enable_type', $this->otp_type );
			update_mo_option( 'wp_reg_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'wp_reg_auto_activate', $this->disable_auto_activate ? false : true );
		}
	}
}
