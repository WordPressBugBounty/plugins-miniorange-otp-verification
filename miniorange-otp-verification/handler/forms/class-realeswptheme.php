<?php
/**
 * Handler Functions for Reales WP Theme Registration Form
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Objects\BaseMessages;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the RealesWP Theme class. This class handles all the
 * functionality related to RealesWP Theme. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'RealesWPTheme' ) ) {
	/**
	 * RealesWPTheme class
	 */
	class RealesWPTheme extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::REALESWP_REGISTER;
			$this->type_phone_tag          = 'mo_reales_phone_enable';
			$this->type_email_tag          = 'mo_reales_email_enable';
			$this->phone_form_id           = '#phoneSignup';
			$this->form_key                = 'REALES_REGISTER';
			$this->form_name               = mo_( 'Reales WP Theme Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'reales_enable' );
			$this->form_documents          = MoFormDocs::REALES_THEME;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException Adds exception.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'reales_enable_type' );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_on_page' ) );
			$this->routeData();
		}


		/**
		 * * @throws ReflectionException Adds exception.
		 */
		private function routeData() {
			if ( ! array_key_exists( 'option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}
			switch ( trim( sanitize_text_field( wp_unslash( $_GET['option'] ) ) ) ) { // phpcs:ignore -- false positive.
				case 'miniorange-realeswp-verify':
					if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoMessages::showMessage( BaseMessages::INVALID_OP ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
						exit;
					}
					$this->send_otp_realeswp_verify( MOUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'miniorange-validate-realeswp-otp':
					if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoMessages::showMessage( BaseMessages::INVALID_OP ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
						exit;
					}
					$this->reales_validate_otp( MOUtility::mo_sanitize_array( $_POST ) );
					break;
			}
		}


		/**
		 * This function is used to enqueue script on the frontend to facilitate
		 * OTP Verification for the RealesWP Theme Registration form. This function
		 * also localizes certain values required by the script.
		 */
		public function enqueue_script_on_page() {
			wp_register_script( 'realeswpScript', MOV_URL . 'includes/js/realeswp.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'realeswpScript',
				'movars',
				array(
					'imgURL'      => MOV_URL . 'includes/images/loader.gif',
					'fieldname'   => $this->otp_type === $this->type_phone_tag ? 'phone number' : 'email',
					'field'       => $this->otp_type === $this->type_phone_tag ? 'phoneSignup' : 'emailSignup',
					'siteURL'     => site_url(),
					'insertAfter' => $this->otp_type === $this->type_phone_tag ? '#phoneSignup' : '#emailSignup',
					'placeHolder' => mo_( 'OTP Code' ),
					'buttonText'  => mo_( 'Validate and Sign Up' ),
					'ajaxurl'     => wp_ajax_url(),
				)
			);
			wp_enqueue_script( 'realeswpScript' );
		}


		/**
		 * This function is used to send OTP to the user's email or phone
		 * by checking what kind of otp has been enabled by the admin.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 * @throws ReflectionException Adds exception.
		 */
		private function send_otp_realeswp_verify( $data ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_otp_to_phone( $data );
			} else {
				$this->send_otp_to_email( $data );
			}
		}


		/**
		 * This function is used to send OTP to the user's phone and setting it in
		 * session for future validation.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		private function send_otp_to_phone( $data ) {
			if ( array_key_exists( 'user_phone', $data ) && ! MoUtility::is_blank( sanitize_text_field( $data['user_phone'] ) ) ) {
				SessionUtils::add_phone_verified( $this->form_session_var, trim( $data['user_phone'] ) );
				$this->send_challenge( 'test', '', null, trim( $data['user_phone'] ), VerificationType::PHONE );
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}


		/**
		 * This function is used to send OTP to the user's email and setting it in
		 * session for future validation.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		private function send_otp_to_email( $data ) {
			if ( array_key_exists( 'user_email', $data ) && ! MoUtility::is_blank( sanitize_email( $data['user_email'] ) ) ) {
				SessionUtils::add_email_verified( $this->form_session_var, $data['user_email'] );
				$this->send_challenge( 'test', $data['user_email'], null, $data['user_email'], VerificationType::EMAIL );
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}


		/**
		 * This function is called to validate the OTP entered by the user. Processes
		 * the form post and checks if OTP Verification has started and start the
		 * OTP Verification process.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		private function reales_validate_otp( $data ) {

			$mo_otp = ! isset( $data['otp'] ) ? sanitize_text_field( $data['otp'] ) : '';

			$this->checkIfOTPVerificationHasStarted();
			$this->validateSubmittedFields( $data );
			$this->validate_challenge( null, $mo_otp );
		}


		/**
		 * Validate if the phone or email submitted by the form is the same value
		 * where the OTP was sent to. Throw an error if the values aren't the same.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		private function validateSubmittedFields( $data ) {
			$otp_ver_type = $this->get_verification_type();
			if ( VerificationType::EMAIL === $otp_ver_type
			&& ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $data['user_email'] ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				die();
			} elseif ( VerificationType::PHONE === $otp_ver_type
			&& ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				die();
			}
		}


		/**
		 * This function checks if OTP Verification was started by checking
		 * the session variable is set or note. Sends a error response if session
		 * is not set.
		 */
		private function checkIfOTPVerificationHasStarted() {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				die();
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

			wp_send_json(
				MoUtility::create_json(
					MoUtility::get_invalid_otp_method(),
					MoConstants::ERROR_JSON_TYPE
				)
			);
			die();
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
			wp_send_json( MoUtility::create_json( MoMessages::REG_SUCCESS, MoConstants::SUCCESS_JSON_TYPE ) );
			die();
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
		 * @param  array $selector  the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the RealesWp related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'reales_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'reales_enable_type' );

			update_mo_option( 'reales_enable', $this->is_form_enabled );
			update_mo_option( 'reales_enable_type', $this->otp_type );
		}
	}
}
