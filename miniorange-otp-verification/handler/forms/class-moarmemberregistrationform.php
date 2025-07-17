<?php
/**
 * Handles the OTP verification logic for Armember Form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
use ReflectionException;
use WP_Error;

/**
 * This is the MoARMemberRegistrationFormn class. This class handles all the
 * functionality related to ARMember Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'MoARMemberRegistrationForm' ) ) {
	/**
	 * MoARMemberRegistrationForm class
	 */
	class MoARMemberRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::AR_MEMBER_FORM;
			$this->type_phone_tag          = 'mo_armember_phone_enable';
			$this->type_email_tag          = 'mo_armember_email_enable';
			$this->form_key                = 'AR_MEMBER_FORM';
			$this->form_name               = mo_( 'ARMember Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'armember_enable' );
			$this->button_text             = get_mo_option( 'armember_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->phone_form_id           = array();
			$this->form_documents          = MoFormDocs::AR_MEMBER_FORM_LINK;
			$this->generate_otp_action     = 'miniorange_armember_generate_otp';
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type     = get_mo_option( 'armember_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'armember_forms' ) );

			if ( empty( $this->form_details ) ) {
				return;
			}

			foreach ( $this->form_details as $key => $value ) {

				array_push( $this->phone_form_id, 'input[name=' . $value['phonekey'] );
				add_filter( 'register_post', array( $this, 'validateForm' ), 99, 3 );
			}

			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'mo_send_otp' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'mo_send_otp' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_armember_script' ) );
		}

		/**
		 * This function registers the js file for enabling OTP Verification
		 */
		public function miniorange_register_armember_script() {
			wp_register_script( 'moarmember', MOV_URL . 'includes/js/armember.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'moarmember',
				'moarmember',
				array(
					'siteURL'     => wp_ajax_url(),
					'otp_type'    => $this->otp_type,
					'formkey'     => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phonekey' : 'emailkey',
					'nonce'       => wp_create_nonce( $this->nonce ),
					'buttontext'  => mo_( $this->button_text ),
					'imgURL'      => MOV_LOADER_URL,
					'forms'       => $this->form_details,
					'generateURL' => $this->generate_otp_action,
				)
			);
			wp_enqueue_script( 'moarmember' );
		}


		/**
		 * The function is used to process the email or phone number provided
		 * and send OTP to it for verification. This is called from the form
		 * using AJAX calls.
		 *
		 * @throws ReflectionException .
		 */
		public function mo_send_otp() {

			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
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
		 * The function is used to check if user has provided an email
		 * address in the form to initiate email verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function mo_processEmailAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_EMAIL ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( $data['user_email'], $data['user_email'], null, VerificationType::EMAIL );
			}
		}

		/**
		 * The function is used to check if user has provided an phone number
		 * address in the form to initiate SMS verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function mo_processPhoneAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_PHONE ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( trim( $data['user_phone'] ), null, trim( $data['user_phone'] ), VerificationType::PHONE );
			}
		}

		/**
		 * This function is used to set session variables and start the
		 * OTP Verification process.
		 *
		 * @param array $session_value - the session value which is usually the email or phone number.
		 * @param array $user_email    - the email provided by the user.
		 * @param array $phone_number  - the phone number provided by the user.
		 * @param array $otp_type     - the otp type denoting the type of otp verification. Can be phone or email.
		 */
		private function setSessionAndStartOTPVerification( $session_value, $user_email, $phone_number, $otp_type ) {
			SessionUtils::add_email_or_phone_verified( $this->form_session_var, $session_value, $otp_type );
			$this->send_challenge( '', $user_email, null, $phone_number, $otp_type );
		}


		/**
		 * This function hooks validate the form being submitted.
		 * This function checks if the email
		 * and phone number values are consistent and if the OTP entered by the
		 * user is valid.
		 *
		 * @param array $sanitized_user_login - the value entered by the user.
		 * @param array $user_email - field data passed by armember hook.
		 * @param array $arm_errors - the form data passed by armember hook.
		 * @return void|WP_Error
		 */
		public function validateForm( $sanitized_user_login, $user_email, $arm_errors ) {
			if ( ! empty( $arm_errors ) ) {
				if ( $arm_errors->get_error_code() ) {
					return;
				}
			}
			$posted_data = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			$id        = $posted_data['arm_form_id'];
			$form_data = $this->form_details[ $id ];

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$arm_errors->add( 'arm_reg_error', MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ) );
				return $arm_errors;
			}
			if ( array_key_exists( $form_data['verifyKey'], $posted_data ) ) {
				$arm_errors = $this->processMismatch( $arm_errors, $posted_data, $form_data );
				if ( ! is_null( $arm_errors ) ) {
					if ( $arm_errors->get_error_code() ) {
						return $arm_errors;
					}
				}
				$arm_errors = $this->processOTPEntered( $posted_data[ $form_data['verifyKey'] ], $arm_errors );
			}
			return $arm_errors;
		}

		/**
		 * Process mismatched number
		 *
		 * @param array  $arm_errors .
		 * @param string $posted_data .
		 * @param string $form_data .
		 * @return $arm errrors
		 */
		public function processMismatch( $arm_errors, $posted_data, $form_data ) {
			$otp_ver_type = $this->get_verification_type();
			if ( 'phone' === $otp_ver_type ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $posted_data [ $form_data['phonekey'] ] ) ) ) {
					$arm_errors->add( 'arm_reg_error', MoMessages::showMessage( MoMessages::PHONE_MISMATCH ) );
				}
			} elseif ( ( 'email' === $otp_ver_type ) ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $posted_data [ $form_data['emailkey'] ] ) ) ) {
					$arm_errors->add( 'arm_reg_error', MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ) );
				}
			}
			return $arm_errors;
		}

			/**
			 * Process and validate the OTP entered by the user
			 *
			 * @param array $entry - the value entered by the user.
			 * @param array $arm_errors - errors.
			 * @return WP_Error
			 */
		private function processOTPEntered( $entry, $arm_errors ) {
			$otp_ver_type = $this->get_verification_type();
			$this->validate_challenge( $otp_ver_type, null, $entry );

			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				$arm_errors->add( 'arm_reg_error', 'Invalid OTP' );
			} else {
				$this->unset_otp_session_variables();
			}
			return $arm_errors;
		}



		/**
		 * The function is used to process email to send the OTP to
		 * and return the data associated with the form.
		 *
		 * @param array $entry - the value entered by the user.
		 * @return WP_Error
		 */
		private function processEmail( $entry ) {
			return SessionUtils::is_email_verified_match( $this->form_session_var, $entry ) ? $entry :
			new WP_Error( 'EMAIL_MISMATCH', MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ) );
		}




		/**
		 * The function is used to process the phone number to send the OTP to
		 * and return the data associated with the form
		 *
		 * @param array $entry - the value entered by the user.
		 * @return WP_Error
		 */
		private function processPhone( $entry ) {
			return SessionUtils::is_phone_verified_match( $this->form_session_var, $entry ) ? $entry :
			new WP_Error( 'PHONE_MISMATCH', MoMessages::showMessage( MoMessages::PHONE_MISMATCH ) );
		}



		/**
		 * This function hooks into the otp_verification_failed hook. This function
		 * details what is done if the OTP verification fails.
		 *
		 * @param string array $user_login the username posted by the user.
		 * @param string array $user_email the email posted by the user.
		 * @param string array $phone_number the phone number posted by the user.
		 * @param string array $otp_type the verification type.
		 */
		public function handle_failed_verification( $user_login, $user_email, $phone_number, $otp_type ) {
			SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
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
			SessionUtils::unset_session( array( $this->form_session_var, $this->tx_session_id ) );
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
			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the Armember Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$data                  = MoUtility::mo_sanitize_array( $_POST );
			$this->is_form_enabled = $this->sanitize_form_post( 'armember_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'armember_enable_type' );
			$this->button_text     = $this->sanitize_form_post( 'armember_button_text' );

			$form = $this->parseFormDetails( $data );

			$this->form_details = ! empty( $form ) ? $form : '';

			update_mo_option( 'armember_enable', $this->is_form_enabled );
			update_mo_option( 'armember_enable_type', $this->otp_type );
			update_mo_option( 'armember_button_text', $this->button_text );
			update_mo_option( 'armember_forms', maybe_serialize( $this->form_details ) );
		}


		/**
		 * This function will parse the Form Details and return an array to be
		 * stored in the database.
		 *
		 * @param array $data this is the armemeber form variable containing the form data.
		 * @return array
		 */
		protected function parseFormDetails( $data ) {
			$form = array();

			if ( ! array_key_exists( 'armember_form', $data ) || ! $this->is_form_enabled ) {
				return $form;
			}
			foreach ( array_filter( $data['armember_form']['form'] ) as $key => $value ) {
				$key                                   = sanitize_text_field( $key );
				$form[ sanitize_text_field( $value ) ] = array(
					'emailkey'    => sanitize_text_field( $data['armember_form']['emailkey'][ $key ] ),
					'phonekey'    => sanitize_text_field( $data['armember_form']['phonekey'][ $key ] ),
					'verifyKey'   => sanitize_text_field( $data['armember_form']['verifyKey'][ $key ] ),
					'phone_show'  => sanitize_text_field( $data['armember_form']['phonekey'][ $key ] ),
					'email_show'  => sanitize_text_field( $data['armember_form']['emailkey'][ $key ] ),
					'verify_show' => sanitize_text_field( $data['armember_form']['verifyKey'][ $key ] ),
				);
			}
			return $form;
		}

	}
}
