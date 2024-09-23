<?php
/**
 * Handles the OTP verification logic for MemberPressRegistrationForm form.
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
use OTP\Objects\BaseMessages;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationLogic;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Error;

/**
 * This is the Member-Press Registration class. This class handles all the
 * functionality related to Member-Press Registration. It extends the FormHandler
 * class to implement some much needed functions.
 */
if ( ! class_exists( 'MemberPressRegistrationForm' ) ) {
	/**
	 * MemberPressRegistrationForm class
	 */
	class MemberPressRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::MEMBERPRESS_REG;
			$this->type_phone_tag          = 'mo_mrp_phone_enable';
			$this->type_email_tag          = 'mo_mrp_email_enable';
			$this->type_both_tag           = 'mo_mrp_both_enable';
			$this->form_name               = mo_( 'MemberPress Registration Form' );
			$this->form_key                = 'MEMBERPRESS';
			$this->is_form_enabled         = get_mo_option( 'mrp_default_enable' );
			$this->form_documents          = MoFormDocs::MRP_FORM_LINK;
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
			$this->by_pass_login = get_mo_option( 'mrp_anon_only' );
			$this->phone_key     = get_mo_option( 'mrp_phone_key' );
			$this->otp_type      = get_mo_option( 'mrp_enable_type' );
			$this->phone_form_id = 'input[name=' . $this->phone_key . ']';
			add_filter( 'mepr-validate-signup', array( $this, 'miniorange_site_register_form' ), 99, 1 );
		}


		/**
		 * Member-Press function which hooks into the mepr-validate-signup hook
		 * and gets all the necessary values to start the otp verification process.
		 *
		 * @param array|WP_Error exsisting $errors by the forms.
		 * @return array|WP_Error
		 * @throws ReflectionException .
		 */
		public function miniorange_site_register_form( $errors ) {
			if ( $this->by_pass_login && is_user_logged_in() ) {
				return $errors;
			}

			$usermeta = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.

			$phone_number = '';
			if ( $this->isPhoneVerificationEnabled() ) {
				$phone_number = isset( $_POST[ $this->phone_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->phone_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$errors       = $this->validate_phone_numberField( $errors );
			}

			if ( is_array( $errors ) && ! empty( $errors ) ) {
				return $errors;
			}

			if ( $this->checkIfVerificationIsComplete() ) {
				return $errors;
			}
			MoUtility::initialize_transaction( $this->form_session_var );
			$errors = new WP_Error();

			foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
				if ( 'user_first_name' === $key ) {
					$username = $value;
				} elseif ( 'user_email' === $key ) {
					$email = $value;
				} elseif ( 'mepr_user_password' === $key ) {
					$password = $value;
				} else {
					$extra_data[ $key ] = $value;
				}
			}

			$extra_data['usermeta'] = $usermeta;
			$this->startVerificationProcess( $username, $email, $errors, $phone_number, $password, $extra_data );
			return $errors;
		}


		/**
		 * This function will do the validation of the phone value in the post form.
		 *
		 * @param array $errors Array of errors form the MemberPress form.
		 * @return array array of errors if any for the phone number.
		 */
		private function validate_phone_numberField( $errors ) {

			global $phone_logic;
			if ( ! MoUtility::sanitize_check( $this->phone_key, $_POST ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
				$errors[] = mo_( 'Phone number field can not be blank' );
			} elseif ( ! MoUtility::validate_phone_number( isset( $_POST[ $this->phone_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->phone_key ] ) ) : '' ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
				$errors[] = $phone_logic->get_otp_invalid_format_message();
			}
			return $errors;
		}


		/**
		 * Start the verification process. Check the type of OTP configured by the admin and
		 * start the OTP Verification process.
		 *
		 * @param string $username     - username provided by the user during registration.
		 * @param string $email        - email provided by the user during registration.
		 * @param string $errors       - any error that might have come up.
		 * @param string $phone_number - the phone number provided by the user during registration.
		 * @param string $password     - password provided by the user during registration.
		 * @param array  $extra_data   - other data provided by the user during registration.
		 */
		private function startVerificationProcess( $username, $email, $errors, $phone_number, $password, $extra_data ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::PHONE, $password, $extra_data );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::BOTH, $password, $extra_data );
			} else {
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::EMAIL, $password, $extra_data );
			}
		}

		/**
		 * Checks if the OTP Verification is completed and there were no errors.
		 * Returns TRUE or FALSE indicating if OTP Verification was a success.
		 */
		private function checkIfVerificationIsComplete() {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
				return true;
			}
			return false;
		}


		/**
		 * This function is used to run a database call to get the phone field id
		 * associated with phone field key provided by the user in the plugin settings.
		 * The id is used to check the post field.
		 */
		public function moMRPgetphoneFieldId() {
			global $wpdb;
			return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}bp_xprofile_fields WHERE name = %s", array( $this->phone_key ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
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
		 * This function is called by the filter mo_phone_dropdown_selector
		 * to return the Jquery selector of the phone field. The function will
		 * push the formID to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array $selector - the Jquery selector to be modified.
		 */
		public function get_phone_number_selector( $selector ) {

			if ( self::is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for MemberPress Registration
		 * form
		 */
		private function isPhoneVerificationEnabled() {
			$otp_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_type || VerificationType::BOTH === $otp_type;
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
		}


		/**
		 * Handles saving all the MemberPress form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			if ( isset( $data['mo_customer_validation_mrp_default_enable'] ) ) {
				if ( isset( $data['mo_customer_validation_mrp_single_default_enable'] ) && $data['mo_customer_validation_mrp_single_default_enable'] ) {
					do_action( 'mo_registration_show_message', 'Disable Memberpress Single Checkout form to enable OTP verification on Memberpress Registration Form', 'ERROR' );
					return;
				}
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'mrp_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'mrp_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'mrp_phone_field_key' );
			$this->by_pass_login   = $this->sanitize_form_post( 'mpr_anon_only' );

			if ( $this->basic_validation_check( BaseMessages::MEMBERPRESS_CHOOSE ) ) {
				update_mo_option( 'mrp_default_enable', $this->is_form_enabled );
				update_mo_option( 'mrp_enable_type', $this->otp_type );
				update_mo_option( 'mrp_phone_key', $this->phone_key );
				update_mo_option( 'mrp_anon_only', $this->by_pass_login );
			}
		}
	}
}
