<?php
/**
 * Handler Functions for Real Estate 7 Pro Theme
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Objects\BaseMessages;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;

/**
 * This is the Real Estate 7 Form Handler class. This class handles all the
 * functionality related to Real Estate 7 theme. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'RealEstate7' ) ) {
	/**
	 * RealEstate7 class
	 */
	class RealEstate7 extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_key                = 'REAL_ESTATE_7';
			$this->form_session_var        = FormSessionVars::REALESTATE_7;
			$this->is_form_enabled         = get_mo_option( 'realestate_enable' );
			$this->type_phone_tag          = 'mo_realestate_contact_phone_enable';
			$this->type_email_tag          = 'mo_realestate_contact_email_enable';
			$this->form_name               = mo_( 'Real Estate 7 Pro Theme' );
			$this->form_documents          = MoFormDocs::REALESTATE7_THEME_LINK;
			parent::__construct();

		}


		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->phone_form_id       = '#mo_ct_user_phone';
			$this->generate_otp_action = 'miniorange-real-estate-7-send-otp';
			$this->validate_otp_action = 'miniorange-real-estate-7-verify-code';
			$this->otp_type            = get_mo_option( 'realestate_otp_type' );
			$this->form_documents      = MoFormDocs::REALESTATE7_THEME_LINK;
			$this->button_text         = $this->setButtonText();
			$this->verify_button_text  = get_mo_option( 'realestate_verify_button_text' );
			$this->verify_button_text  = ! MoUtility::is_blank( $this->verify_button_text ) ? $this->verify_button_text : mo_( 'Verify OTP' );

			add_action( 'wp_enqueue_scripts', array( $this, 'addPhoneFieldScript' ) );

			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'send_otp' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'send_otp' ) );
			add_action( "wp_ajax_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_nopriv_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			$this->form_details = array(
				'ct_registration_form' => array(
					'phonekey' => 'mo_ct_user_phone',
					'emailkey' => 'ct_user_email',
				),
			);

			if ( ! isset( $_POST['option'] ) ) { // phpcs:ignore -- false positive.
				return;
			}

			switch ( trim( sanitize_text_field( wp_unslash( $_POST['option'] ) ) ) ) { // phpcs:ignore -- false positive.
				case 'realestate_register':
					if ( ! isset( $_POST['ct_register_nonce'] ) ) { // phpcs:ignore -- false positive.
						ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( BaseMessages::INVALID_OP ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
					}
					if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ct_register_nonce'] ) ), 'ct-register-nonce' ) ) { // phpcs:ignore -- false positive.
						ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( BaseMessages::INVALID_OP ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
					}
					$data = MoUtility::mo_sanitize_array( $_POST );
					$this->sanitizeAndRouteData( $data );
					break;
			}

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {

				$this->unset_otp_session_variables();
				return;

			}

		}

		/**
		 * Function to set the Send OTP Button text
		 */
		private function setButtonText() {
			if ( strcasecmp( get_mo_option( 'realestate_otp_type' ), $this->type_phone_tag ) === 0 ) {
				return mo_( 'Send OTP to Phone' );
			} else {
				return mo_( 'Send OTP to Email' );
			}
		}

		/**
		 * Function to sanitize data
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function sanitizeAndRouteData( $data ) {
			$id = key( $this->form_details );
			if ( ! array_key_exists( $id, $this->form_details ) ) {
				return;
			}

			if ( 0 === strcasecmp( $this->otp_type, $this->type_phone_tag ) || 0 === strcasecmp( $this->otp_type, $this->type_both_tag ) ) {

				$this->processPhone( sanitize_text_field( $data['mo_ct_user_phone'] ) );
			}
			if ( 0 === strcasecmp( $this->otp_type, $this->type_email_tag ) || 0 === strcasecmp( $this->otp_type, $this->type_both_tag ) ) {

				$this->processEmail( sanitize_email( $data['ct_user_email'] ) );
			}
		}

		/**
		 * Function to send OTP.
		 */
		public function send_otp() {

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( BaseMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->processPhoneAndSendOTP( $data );
			} else {
				$this->processEmailAndSendOTP( $data );
			}
		}


		/**
		 * The function is used to check if user has provided an email
		 * address in the form to initiate email verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function processEmailAndSendOTP( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$user_email = sanitize_email( $data['user_email'] );
				SessionUtils::add_email_verified( $this->form_session_var, $user_email );
				$this->send_challenge( '', $user_email, null, null, VerificationType::EMAIL );
			}
		}


		/**
		 * The function is used to check if user has provided an phone number
		 * address in the form to initiate SMS verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function processPhoneAndSendOTP( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$user_phone = sanitize_text_field( $data['user_phone'] );
				SessionUtils::add_phone_verified( $this->form_session_var, $user_phone );
				$this->send_challenge( '', null, null, $user_phone, VerificationType::PHONE );
			}
		}

		/**
		 * Checks if the verification has started or not and then validates the
		 * OTP submitted.
		 */
		public function processFormAndValidateOTP() {
			if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			$this->checkIfOTPSent();
			$this->checkIntegrityAndValidateOTP( $data );
		}

		/**
		 * Checks if the verification has started or not.
		 */
		private function checkIfOTPSent() {

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {

				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}

		/**
		 * Check Integrity of the email or phone number. i.e. Ensure that the Email or
		 * Phone that the OTP was sent to is the same Email or Phone that is being submitted
		 * with the form.
		 * Once integrity check passes validate the OTP to ensure that the user has entered
		 * the correct OTP.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function checkIntegrityAndValidateOTP( $data ) {

			$this->checkIntegrity( $data );

			$this->validate_challenge( sanitize_text_field( $data['otpType'] ), null, sanitize_text_field( $data['otp_token'] ) );

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, sanitize_text_field( $data['otpType'] ) ) ) {

				wp_send_json(
					MoUtility::create_json(
						MoConstants::SUCCESS_JSON_TYPE,
						MoConstants::SUCCESS_JSON_TYPE
					)
				);
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::INVALID_OTP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}

		/**
		 * This function checks the integrity of the phone or email value that was submitted
		 * with the form. It needs to match with the email or value that the OTP was
		 * initially sent to.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function checkIntegrity( $data ) {
			if ( sanitize_text_field( $data['otpType'] ) === 'phone' ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( sanitize_text_field( $data['user_phone'] ) ) ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			} elseif ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( sanitize_email( $data['user_email'] ) ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);

			}
		}

		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			Sessionutils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
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



		/** Fetch the phone number entered by the user and check if valid
		 *
		 * @param string $phone - gives the phone number entered by the user.
		 */
		private function processPhone( $phone ) {

			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::PHONE ) ) {
				ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
			}
			if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $phone ) ) ) {
				 ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( MoMessages::PHONE_MISMATCH ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
			}
		}



		/** Fetch the email address entered by the user and check if valid
		 *
		 * @param string $email - gives the email address entered by the user.
		 */
		private function processEmail( $email ) {
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::EMAIL ) ) {
				ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
			}
			if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_text_field( $email ) ) ) {
				ct_errors()->add( 'Please Validate', __( MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ), 'contempo' ) ); //phpcs:ignore --Default function of Real Estate from.
			}
		}


		/** Add the Multisite js file to the frontend so that we can add phone number field */
		public function addPhoneFieldScript() {
			wp_register_script( 'realEstate7Script', MOV_URL . 'includes/js/realEstate7Script.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'realEstate7Script',
				'realEstate7Script',
				array(
					'siteURL'     => wp_ajax_url(),
					'otpType'     => $this->ajax_processing_fields(),
					'formDetails' => $this->form_details,
					'buttontext'  => $this->button_text,
					'validated'   => $this->getSessionDetails(),
					'imgURL'      => MOV_LOADER_URL,
					'fieldText'   => mo_( 'Enter OTP here' ),
					'verifyText'  => $this->verify_button_text,
					'gnonce'      => wp_create_nonce( $this->nonce ),
					'nonceKey'    => wp_create_nonce( $this->nonce_key ),
					'vnonce'      => wp_create_nonce( $this->nonce ),
					'gaction'     => $this->generate_otp_action,
					'vaction'     => $this->validate_otp_action,
				)
			);
			wp_enqueue_script( 'realEstate7Script' );
		}

		/**Checks if OTP is validated
		 *
		 * @return array
		 */
		private function getSessionDetails() {
			return array(
				VerificationType::EMAIL => SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::EMAIL ),
				VerificationType::PHONE => SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::PHONE ),
			);
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

			if ( self::is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Real Estate 7 related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled    = $this->sanitize_form_post( 'realestate_enable' );
			$this->otp_type           = $this->sanitize_form_post( 'realestate_contact_type' );
			$this->verify_button_text = $this->sanitize_form_post( 'realestate_verify_button_text' );

			update_mo_option( 'realestate_enable', $this->is_form_enabled );
			update_mo_option( 'realestate_otp_type', $this->otp_type );
			update_mo_option( 'realestate_verify_button_text', $this->verify_button_text );

		}
	}
}
