<?php
/**
 * Load admin view for Multisite Registration Form.
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
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This class handles all the stuff related to Multi-site Registration Form. This class handles
 * all the functionality related to Multi-site Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'MultiSiteFormRegistration' ) ) {
	/**
	 * MultiSiteFormRegistration class
	 */
	class MultiSiteFormRegistration extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::MULTISITE;
			$this->phone_form_id           = '#multisite_user_phone';
			$this->type_phone_tag          = 'mo_multisite_contact_phone_enable';
			$this->type_email_tag          = 'mo_multisite_contact_email_enable';
			$this->form_key                = 'WP_SIGNUP_FORM';
			$this->form_name               = mo_( 'WordPress Multisite SignUp Form' );
			$this->is_form_enabled         = get_mo_option( 'multisite_enable' );
			$this->phone_key               = 'telephone';
			$this->form_documents          = MoFormDocs::MULTISITE_REG_FORM;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'multisite_otp_type' );

			add_action( 'wp_enqueue_scripts', array( $this, 'add_multisite_scripts' ) );
			add_action( 'user_register', array( $this, 'savePhoneNumber' ), 10, 1 );
			$this->routeData();

		}

		/**
		 * Function to map action
		 */
		public function routeData() {

			$multisite_nonce = wp_create_nonce( 'multisite_nonce' );
			if ( ! wp_verify_nonce( $multisite_nonce, 'multisite_nonce' ) === 1 ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			if ( ! array_key_exists( 'option', $data ) ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				return;
			}
			switch ( trim( sanitize_text_field( wp_unslash( $data['option'] ) ) ) ) {
				case 'multisite_register':
					$this->sanitizeAndRouteData( $data );
					break;
				case 'miniorange-validate-otp-form':
					$this->startValidation();
					break;
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
			$this->unset_otp_session_variables();
		}

		/**
		 * This function hooks into the user_register hook. This function is called to
		 * save the phone number posted by the user during registration in the usermeta
		 * for that user after successful registration.
		 *
		 *  @param string $user_id - the user id of the new user that was created.
		 */
		public function savePhoneNumber( $user_id ) {
			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( $phone_number ) {
				update_user_meta( $user_id, $this->phone_key, $phone_number );
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

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
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
		 * Initialize function to send OTP.
		 *
		 * @param array $get_data the data posted by the user.
		 * @return bool
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		private function sanitizeAndRouteData( $get_data ) {
			$result = wpmu_validate_user_signup( isset( $get_data['user_name'] ) ? wp_unslash( $get_data['user_name'] ) : '', isset( $get_data['user_email'] ) ? wp_unslash( $get_data['user_email'] ) : '' );

			$errors = $result['errors'];
			if ( $errors->get_error_code() ) {
				return false;
			}

			MoUtility::initialize_transaction( $this->form_session_var );

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->processPhone( $get_data );
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$this->processEmail( $get_data );
			}
			return false;
		}

		/** This function starts verification process of the user */
		private function startValidation() {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
			$otp_ver_type = $this->get_verification_type();
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				return;
			}
			$this->validate_challenge( $otp_ver_type );
		}

		/** Add the Multisite js file to the frontend so that we can phone number field */
		public function add_multisite_scripts() {
			wp_register_script( 'multisitescript', MOV_URL . 'includes/js/multisite.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'multisitescript',
				'multisitescript',
				array(
					'phoneEnabled' => ( 'phone' === $this->get_verification_type() ) ? true : false,
				)
			);
			wp_enqueue_script( 'multisitescript' );

		}

		/** Fetch the phone number entered by the user and start the otp verification process
		 *
		 * @param array $get_data the data posted by the user.
		 */
		private function processPhone( $get_data ) {
			if ( ! isset( $get_data['multisite_user_phone_miniorange'] ) ) {
				return;
			}
			$this->send_challenge( '', '', null, trim( $get_data['multisite_user_phone_miniorange'] ), VerificationType::PHONE );
		}

		/** Fetch the Email address entered by the user and start the otp verification process
		 *
		 * @param array $get_data the data posted by the user.
		 */
		private function processEmail( $get_data ) {
			if ( ! isset( $get_data['user_email'] ) ) {
				return;
			}
			$this->send_challenge( '', $get_data['user_email'], null, null, VerificationType::EMAIL, '' );
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

			if ( self::is_form_enabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the Default WordPress Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'multisite_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'multisite_contact_type' );

			update_mo_option( 'multisite_enable', $this->is_form_enabled );
			update_mo_option( 'multisite_otp_type', $this->otp_type );
		}
	}
}
