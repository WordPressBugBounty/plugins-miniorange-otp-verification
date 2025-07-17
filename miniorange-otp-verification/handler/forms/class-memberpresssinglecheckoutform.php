<?php
/**
 * Handles the OTP verification logic for MemberPressSingleCheckout form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\BaseMessages;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationLogic;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;

/**
 * This is the Member-Press SingleCheckoutForm class. This class handles all the
 * functionality related to Member-Press Checkout. It extends the FormHandler
 * class to implement some much needed functions.
 */
if ( ! class_exists( 'MemberPressSingleCheckoutForm' ) ) {
	/**
	 * MemberPressSingleCheckoutForm class
	 */
	class MemberPressSingleCheckoutForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::MEMBERPRESS_SINGLE_REG;
			$this->type_phone_tag          = 'mo_mrp_single_phone_enable';
			$this->type_email_tag          = 'mo_mrp_single_email_enable';
			$this->type_both_tag           = 'mo_mrp_single_both_enable';
			$this->form_name               = mo_( 'MemberPress Single Checkout Registration Form' );
			$this->form_key                = 'MEMBERPRESSSINGLECHECKOUT';
			$this->is_form_enabled         = get_mo_option( 'mrp_single_default_enable' );
			$this->form_documents          = MoFormDocs::MRP_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @return void
		 */
		public function handle_form() {
			$this->by_pass_login = get_mo_option( 'mrp_single_anon_only' );
			$this->phone_key     = get_mo_option( 'mrp_single_phone_key' );
			$this->otp_type      = get_mo_option( 'mrp_single_enable_type' );
			$this->phone_form_id = 'input[name=' . $this->phone_key . ']';

			$user = wp_get_current_user();
			if ( $user->exists() ) {
				return;
			}

			add_action( 'wp_ajax_momrp_single_send_otp', array( $this, 'mo_send_otp' ) );
			add_action( 'wp_ajax_nopriv_momrp_single_send_otp', array( $this, 'mo_send_otp' ) );

			add_filter( 'mepr-validate-signup', array( $this, 'miniorange_site_register_form' ), 99, 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_single_checkout_register_script' ) );

			add_action( 'user_register', array( $this, 'unsetmeprsinglecheckoutSessionVariables' ), 99, 2 );
		}

		/**
		 * This function is called to send the OTP token to the user.
		 *
		 * @return void
		 */
		public function mo_send_otp() {
			$memberpress_nonce = wp_create_nonce( 'memberpress_nonce' );
			if ( ! wp_verify_nonce( $memberpress_nonce, 'memberpress_nonce' ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->mo_processPhoneAndStartOTPVerificationProcess( $data );
			} else {
				$this->mo_processEmailAndStartOTPVerificationProcess( $data );
			}
		}

		/**
		 * Start the verification process. Check the phone number provided by the user and
		 * start the OTP Verification process.
		 *
		 * @param array $data Data provided by the user.
		 * @return void
		 */
		private function mo_processPhoneAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_PHONE ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( trim( $data['user_phone'] ), null, trim( $data['user_phone'] ), VerificationType::PHONE );
			}
		}

		/**
		 * Start the verification process. Check the email provided by the user and
		 * start the OTP Verification process.
		 *
		 * @param array $data data provided by the user.
		 * @return void
		 */
		private function mo_processEmailAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_EMAIL ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( $data['user_email'], $data['user_email'], null, VerificationType::EMAIL );
			}
		}

		/**
		 * Undocumented function
		 *
		 * @param array $session_value set the session for the user.
		 * @param array $user_email the  email posted by the user.
		 * @param array $phone_number the phone number posted by the user.
		 * @param array $otp_type email and sms verification.
		 * @return void
		 */
		private function setSessionAndStartOTPVerification( $session_value, $user_email, $phone_number, $otp_type ) {
			SessionUtils::add_email_or_phone_verified( $this->form_session_var, $session_value, $otp_type );
			$this->send_challenge( '', $user_email, null, $phone_number, $otp_type );
		}


		/**
		 * This function registers the js file for enabling OTP Verification
		 * for Memberpress Checkout using AJAX calls.
		 *
		 * @return void
		 */
		public function miniorange_single_checkout_register_script() {
			wp_register_script( 'momrpsingle', MOV_URL . 'includes/js/momrpsingle.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'momrpsingle',
				'momrpsingle',
				array(
					'siteURL'    => wp_ajax_url(),
					'otpType'    => $this->otp_type,
					'formkey'    => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? $this->phone_key : 'user_email',
					'nonce'      => wp_create_nonce( $this->nonce ),
					'buttontext' => mo_( 'Click Here to send OTP' ),
					'imgURL'     => MOV_LOADER_URL,
				)
			);
			wp_enqueue_script( 'momrpsingle' );
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @param array $errors checkout errors.
		 */
		public function miniorange_site_register_form( $errors ) {
			if ( $errors ) {
				return $errors;
			}

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
					$errors[ $this->phone_key ] = MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE );
				} else {
					$errors['user_email'] = MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE );
				}
			}

			if ( $errors ) {
				return $errors;
			}
			$email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$mo_phone = isset( $_POST[ $this->phone_key ] ) ? ( sanitize_text_field( wp_unslash( $_POST[ $this->phone_key ] ) ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, $mo_phone ) ) {
					$errors[ $this->phone_key ] = MoMessages::showMessage( MoMessages::PHONE_MISMATCH );
				}
			} elseif ( ! SessionUtils::is_email_verified_match( $this->form_session_var, $email ) ) {
				$errors['user_email'] = MoMessages::showMessage( MoMessages::EMAIL_MISMATCH );
			}

			if ( $errors ) {
				return $errors;
			}

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$otp_type = 'phone';
			} else {
				$otp_type = 'email';
			}

			$mo_verify_otp_field = isset( $_POST['mo_verify_otp_field'] ) ? ( sanitize_text_field( wp_unslash( $_POST['mo_verify_otp_field'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( $mo_verify_otp_field ) {
				$this->validate_challenge( $otp_type, null, $mo_verify_otp_field );
			}

			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type ) ) {
				if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
					$errors[ $this->phone_key ] = MoMessages::showMessage( MoMessages::INVALID_OTP );
				} else {
					$errors['user_email'] = MoMessages::showMessage( MoMessages::INVALID_OTP );
				}
			}

			return $errors;
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
			SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
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
		 * MermberPress Checkout function to register the user.
		 *
		 * @param array $user_id WP User id.
		 * @param array $userdata gives user information.
		 * @return void
		 */
		public function unsetmeprsinglecheckoutSessionVariables( $user_id, $userdata ) {
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
		 * Handles saving all the MemberPress Checkout form related options by the admin.
		 *
		 * @return void
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			if ( isset( $data['mo_customer_validation_mrp_single_default_enable'] ) ) {
				if ( isset( $data['mo_customer_validation_mrp_default_enable'] ) && $data['mo_customer_validation_mrp_default_enable'] ) {
					do_action( 'mo_registration_show_message', 'Disable Memberpress Registration Form to enable OTP verification on Memberpress Checkout Form ', 'ERROR' );
					return;
				}
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'mrp_single_default_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'mrp_single_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'mrp_single_phone_field_key' );
			$this->by_pass_login   = $this->sanitize_form_post( 'mpr_single_anon_only' );

			if ( $this->basic_validation_check( BaseMessages::MEMBERPRESS_CHOOSE ) ) {
				update_mo_option( 'mrp_single_default_enable', $this->is_form_enabled );
				update_mo_option( 'mrp_single_enable_type', $this->otp_type );
				update_mo_option( 'mrp_single_phone_key', $this->phone_key );
				update_mo_option( 'mrp_single_anon_only', $this->by_pass_login );
			}
		}
	}
}
