<?php
/**
 * Load admin view for Eduma Theme Registration Form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoMessages;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Helper\MoConstants;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use \WP_Error;

/**
 * This is the Eduma Theme Registration class. This class handles all the
 * functionality related to Eduma Theme Registration form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'Edumareg' ) ) {
	/**
	 * Edumareg class
	 */
	class Edumareg extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::EDUMAREG;
			$this->type_phone_tag          = 'mo_edumareg_phone_enable';
			$this->type_email_tag          = 'mo_edumareg_email_enable';
			$this->phone_key               = get_mo_option( 'edumareg_phone_key' );
			$this->phone_key               = ( $this->phone_key ) ? $this->phone_key : 'telephone';
			$this->form_key                = 'EDUMAREG_THEME';
			$this->form_name               = mo_( 'Eduma Theme Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'edumareg_enable' );
			$this->phone_form_id           = '#phone_number_mo';
			$this->restrict_duplicates     = get_mo_option( 'edumareg_restrict_duplicates' );
			$this->form_documents          = MoFormDocs::EDUMA_REG;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 **/
		public function handle_form() {
			$this->otp_type = get_mo_option( 'edumareg_enable_type' );
			add_action( 'register_form', array( $this, 'miniorange_add_phonefield' ) );
			add_action( 'user_register', array( $this, 'miniorange_registration_save' ), 10, 1 );
			add_filter( 'registration_errors', array( $this, 'miniorange_site_registration_errors' ), 99, 3 );
		}
		/**
		 * Function to append phone field.
		 **/
		public function miniorange_add_phonefield() {
			echo '<input type="hidden" name="register_nonce" value="register_nonce"/>';
			if ( $this->otp_type === $this->type_phone_tag ) {
				echo '<p><input type="text" name="phone_number_mo" id="phone_number_mo" class="input required" value="" placeholder="Phone Number" style=""/></p><br>';
			}
		}
		/**
		 * Function to save phone number in database.
		 *
		 * @param string $user_id fetching userid.
		 **/
		public function miniorange_registration_save( $user_id ) {
			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( $phone_number ) {
				add_user_meta( $user_id, $this->phone_key, $phone_number );
			}
		}
		/**
		 * This function hooks into the registration_errors hook. This function is called to
		 * check the phone number posted by the user. and pass it to start the OTP
		 * Verification process
		 *
		 * @param WP_Error $errors - the errors variable passed by the registration_errors hook.
		 * @param string   $sanitized_user_login - the username passed by the registration_errors hook.
		 * @param string   $user_email - the email passed by the registration_errors hook.
		 * @return WP_Error
		 */
		public function miniorange_site_registration_errors( WP_Error $errors, $sanitized_user_login, $user_email ) {

			$data         = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			$phone_number = isset( $data['phone_number_mo'] ) ? sanitize_text_field( $data['phone_number_mo'] ) : null;
			$this->checkIfPhoneNumberUnique( $errors, $phone_number );

			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}
			if ( ! $this->otp_type ) {
				return $errors;
			}

			return $this->startOTPTransaction( $data, $sanitized_user_login, $user_email, $errors, $phone_number );
		}
		/**
		 * Checks if admin has set the option to keep the phone numbers unique. Also
		 * check if the phone number entered by the user is unique or not.
		 *
		 * @param  WP_Error $errors      - registration error.
		 * @param  string   $phone_number - phone number entered by the user.
		 */
		public function checkIfPhoneNumberUnique( WP_Error &$errors, $phone_number ) {
			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				return;
			}

			if ( MoUtility::is_blank( $phone_number ) || ! MoUtility::validate_phone_number( $phone_number ) ) {

				$errors->add( 'invalid_phone', MoMessages::showMessage( MoMessages::ENTER_PHONE_DEFAULT ) );
			}

			if ( $this->restrict_duplicates && $this->isPhoneNumberAlreadyInUse( $phone_number, $this->phone_key ) ) {
				$errors->add( 'duplicate_phone', MoMessages::showMessage( MoMessages::PHONE_EXISTS ) );
			}
		}
		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the user's phone number.
		 * @param string $key - meta_key to search for.
		 * @return bool
		 */
		public function isPhoneNumberAlreadyInUse( $phone, $key ) {
			global $wpdb;
			$phone   = MoUtility::process_phone_number( $phone );
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return ! MoUtility::is_blank( $results );
		}
		/**
		 * The function is called to start the OTP Transaction based on the OTP Type
		 * set by the admin in the settings.
		 *
		 * @param string $data - sanitized data from post request.
		 * @param string $sanitized_user_login - the username passed by the registration_errors hook.
		 * @param string $user_email - the email passed by the registration_errors hook.
		 * @param object $errors - the errors variable passed by the registration_errors hook.
		 * @param string $phone_number - the phone number posted by the user during registration.
		 * @return WP_Error
		 */
		private function startOTPTransaction( $data, $sanitized_user_login, $user_email, $errors, $phone_number ) {

			if ( ! MoUtility::is_blank( array_filter( $errors->errors ) ) || ! isset( $data['register_nonce'] ) ) {
				return $errors;
			}

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $sanitized_user_login, $user_email, $errors, $phone_number, VerificationType::PHONE );
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
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array The selector
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && ( $this->otp_type === $this->type_phone_tag ) ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Eduma Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->otp_type            = $this->sanitize_form_post( 'edumareg_enable_type' );
			$this->is_form_enabled     = $this->sanitize_form_post( 'edumareg_enable' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'edumareg_restrict_duplicates' );
			$this->phone_key           = $this->sanitize_form_post( 'edumareg_phone_field_key' );

			update_mo_option( 'edumareg_enable', $this->is_form_enabled );
			update_mo_option( 'edumareg_enable_type', $this->otp_type );
			update_mo_option( 'edumareg_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'edumareg_phone_key', $this->phone_key );
		}
	}
}
