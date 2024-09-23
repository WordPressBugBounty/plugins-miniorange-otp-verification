<?php
/**
 * Load admin view for Paid Membership Form.
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

/**
 * This is the Paid Membership Pro class. This class handles all the
 * functionality related to Paid Membership Pro Plugin. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'PaidMembershipForm' ) ) {
	/**
	 * PaidMembershipForm class
	 */
	class PaidMembershipForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::PMPRO_REGISTRATION;
			$this->form_key                = 'PM_PRO_FORM';
			$this->form_name               = mo_( 'Paid MemberShip Pro Registration Form' );
			$this->phone_form_id           = 'input[name=phone_paidmembership]';
			$this->type_phone_tag          = 'pmpro_phone_enable';
			$this->type_email_tag          = 'pmpro_email_enable';
			$this->is_form_enabled         = get_mo_option( 'pmpro_enable' );
			$this->form_documents          = MoFormDocs::PAID_MEMBERSHIP_PRO;
			parent::__construct();
		}



		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'pmpro_otp_type' );
			add_action( 'wp_enqueue_scripts', array( $this, 'show_phone_field_on_page' ) );
			add_filter( 'pmpro_checkout_before_processing', array( $this, 'paidMembershipProRegistrationCheck' ), 1, 1 );
			add_filter( 'pmpro_checkout_confirmed', array( $this, 'isValidated' ), 99, 2 );
			add_action( 'user_register', array( $this, 'miniorange_registration_save' ), 10, 1 );

		}
		/**
		 * Function to save phone number in database.
		 *
		 * @param string $user_id fetching userid.
		 **/
		public function miniorange_registration_save( $user_id ) {

			update_user_meta( $user_id, 'mo_phone_number', sanitize_text_field( isset( $_POST['phone_paidmembership'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
		}

		/**
		 * Function hooks into the pmpro_checkout_confirmed hook to check if
		 * there are any errors during registration/checkout and return false
		 * if there is any error.
		 *
		 * @param string $pmpro_confirmed fetching order_confirm.
		 * @param string $morder fetching order.
		 */
		public function isValidated( $pmpro_confirmed, $morder ) {
			global $pmpro_msgt;
			return 'pmpro_error' === $pmpro_msgt ? false : $pmpro_confirmed;
		}

		/**
		 * Hooks into the pmpro_checkout_before_processing hook of PaidMembership Form
		 * to check if otp verification needs to start. Checks for any paid membership
		 * pro form validation errors and then checks
		 *
		 * @throws ReflectionException .
		 */
		public function paidMembershipProRegistrationCheck() {
			global $pmpro_msgt;

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
				return;
			}

			$this->validatePhone( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( 'pmpro_error' !== $pmpro_msgt ) {
				MoUtility::initialize_transaction( $this->form_session_var );
				$this->startOTPVerificationProcess( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			}
		}

		/**
		 * This function starts the appropriate OTP Verification process based on the
		 * type of OTP Verification set by the admin in the plugin settings.
		 *
		 * @param object $data form data.
		 */
		private function startOTPVerificationProcess( $data ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( '', '', null, trim( sanitize_text_field( $data['phone_paidmembership'] ) ), 'phone' );
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$this->send_challenge( '', sanitize_email( $data['bemail'] ), null, sanitize_email( $data['bemail'] ), 'email' );
			}
		}


		/**
		 * Validate Phone Number being submitted with the phone data. If the phone number is
		 * not in the correct format then throw an error using paid membership pro
		 * functions and global variables. The function returns if an existing error is
		 * found.
		 *
		 * @param object $data form data.
		 */
		public function validatePhone( $data ) {
			if ( $this->get_verification_type() !== VerificationType::PHONE ) {
				return;
			}

			global $pmpro_msg, $pmpro_msgt,$phone_logic,$pmpro_requirebilling;
			if ( 'pmpro_error' === $pmpro_msgt ) {
				return;
			}
			$phone_value = sanitize_text_field( $data['phone_paidmembership'] );
			if ( ! MoUtility::validate_phone_number( $phone_value ) ) {
				$message              = str_replace( '##phone##', $phone_value, $phone_logic->get_otp_invalid_format_message() );
				$pmpro_msgt           = 'pmpro_error';
				$pmpro_requirebilling = false;
				$pmpro_msg            = apply_filters( 'pmpro_set_message', $message, $pmpro_msgt );
			}
		}

		/**
		 * This function is called to append a script on the page that would
		 * append the phone field on the form.
		 */
		public function show_phone_field_on_page() {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				wp_enqueue_script( 'paidmembershipscript', MOV_URL . 'includes/js/paidmembershippro.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, false );
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
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array The selector
		 */
		public function get_phone_number_selector( $selector ) {

			if ( self::is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Classify Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'pmpro_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'pmpro_contact_type' );

			update_mo_option( 'pmpro_enable', $this->is_form_enabled );
			update_mo_option( 'pmpro_otp_type', $this->otp_type );
		}
	}
}
