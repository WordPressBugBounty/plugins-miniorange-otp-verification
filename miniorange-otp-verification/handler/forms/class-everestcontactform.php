<?php
/**
 * Handles the OTP verification logic for EverestContactForm form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Objects\BaseMessages;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Error;
/**
 * This is the CalderForms Form class. This class handles all the
 * functionality related to CalderForms. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'EverestContactForm' ) ) {
	/**
	 * EverestContactForm class
	 */
	class EverestContactForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::EVEREST_CONTACT;
			$this->type_phone_tag          = 'mo_everest_contact_phone_enable';
			$this->type_email_tag          = 'mo_everest_contact_email_enable';
			$this->form_key                = 'EVEREST_CONTACT';
			$this->form_name               = mo_( 'Everest Contact Form' );
			$this->is_form_enabled         = get_mo_option( 'everest_contact_enable' );
			$this->phone_form_id           = array();
			$this->form_documents          = MoFormDocs::EVEREST_CONTACT_FORM_LINK;
			$this->generate_otp_action     = 'miniorange_everest_contact_generate_otp';
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			parent::__construct();
		}
		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException Add exception.
		 */
		public function handle_form() {
			$this->otp_type     = get_mo_option( 'everest_contact_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'everest_contact_forms' ) );
			$this->button_text  = get_mo_option( 'everest_contact_button_text' );
			if ( empty( $this->form_details ) ) {
				return;
			}
			foreach ( $this->form_details as $key => $value ) {
				array_push( $this->phone_form_id, '#evf-' . $key . '-field_' . $value['phonekey'] );
			}
			add_filter( 'everest_forms_process_initial_errors', array( $this, 'validateForm' ), 99, 2 );
			add_filter( 'everest_forms_process_after_filter', array( $this, 'unset_sessionVariable' ), 99, 3 );

			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'send_otp' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'send_otp' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_everest_contact_script' ) );
		}
		/**
		 * This function hooks into the everest_contact_forms_submit_return_transient hook
		 * to unset the final session variable if the OTP was successful.
		 *
		 * @param string $form_fields array Array containing form/field data for Everest Contact.
		 * @param string $entry containing form/field entries.
		 * @param string $form_data containing form/field data.
		 * @return array
		 */
		public function unset_sessionVariable( $form_fields, $entry, $form_data ) {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
			}
			return $form_fields;
		}
		/**
		 * This function registers the js file for enabling OTP Verification
		 * for Everest Contact Form using AJAX calls. Moving over to a script to make sure there are no
		 * javascript conflicts or jquery not defined errors.
		 */
		public function miniorange_register_everest_contact_script() {
			wp_register_script( 'moeverestcontact', MOV_URL . 'includes/js/moeverestcontact.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'moeverestcontact',
				'moeverestcontact',
				array(
					'siteURL'     => wp_ajax_url(),
					'otpType'     => $this->otp_type,
					'formkey'     => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phonekey' : 'emailkey',
					'nonce'       => wp_create_nonce( $this->nonce ),
					'buttontext'  => mo_( $this->button_text ),
					'imgURL'      => MOV_LOADER_URL,
					'forms'       => $this->form_details,
					'generateURL' => $this->generate_otp_action,
				)
			);
			wp_enqueue_script( 'moeverestcontact' );
		}
		/**
		 * The function is used to process the email or phone number provided
		 * and send OTP to it for verification. This is called from the form
		 * using AJAX calls.
		 *
		 * @throws ReflectionException Add exception.
		 */
		public function send_otp() {
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

			$this->validate_ajax_request();
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->process_phone_and_start_otp_verification_process( $data );
			} else {
				$this->process_email_and_start_otp_verification_process( $data );
			}
		}
		/**
		 * The function is used to check if user has provided an email
		 * address in the form to initiate email verification.
		 *
		 * @param string $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function process_email_and_start_otp_verification_process( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_EMAIL ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( sanitize_email( $data['user_email'] ), sanitize_email( $data['user_email'] ), null, VerificationType::EMAIL );
			}
		}
		/**
		 * The function is used to check if user has provided an phone number
		 * address in the form to initiate SMS verification.
		 *
		 * @param string $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function process_phone_and_start_otp_verification_process( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_PHONE ), MoConstants::ERROR_JSON_TYPE ) );
			} else {
				$this->setSessionAndStartOTPVerification( trim( sanitize_text_field( $data['user_phone'] ) ), null, trim( sanitize_text_field( $data['user_phone'] ) ), VerificationType::PHONE );
			}
		}
		/**
		 * This function is used to set session variables and start the
		 * OTP Verification process.
		 *
		 * @param string $session_value - the session value which is usually the email or phone number.
		 * @param string $user_email    - the email provided by the user.
		 * @param string $phone_number - the phone number provided by the user.
		 * @param string $otp_type - the otp type denoting the type of otp verification. Can be phone or email.
		 */
		private function setSessionAndStartOTPVerification( $session_value, $user_email, $phone_number, $otp_type ) {
			SessionUtils::add_email_or_phone_verified( $this->form_session_var, $session_value, $otp_type );
			$this->send_challenge( '', $user_email, null, $phone_number, $otp_type );
		}
		/**
		 * This function hooks into the everest_forms_process_initial_errors hook to
		 * validate the form being submitted. This function checks if the email
		 * and phone number values are consistent and if the OTP entered by the
		 * user is valid.
		 *
		 * @param string $errors - errors passed by everest contact hook.
		 * @param array  $form_data - the form data passed by everest contact hook.
		 * @return $error
		 */
		public function validateForm( $errors, $form_data ) {
			$id = $form_data['id'];
			if ( ! empty( $errors[ $id ]['header'] ) ) {
				return $errors;
			}
			if ( isset( $this->form_details ) && ! array_key_exists( $id, $this->form_details ) ) {
				return $errors;
			}

			$form_data = $this->form_details[ $id ];

			if ( ! isset( $_POST[ '_wpnonce' . $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$errors[ $id ]['header'] = MoMessages::showMessage( BaseMessages::INVALID_OP );
			}
			if ( ! sanitize_key( wp_unslash( $_POST[ '_wpnonce' . $id ] ), 'everest-forms_process_submit' ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$errors[ $id ]['header'] = MoMessages::showMessage( BaseMessages::INVALID_OP );
			}
			$data = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			$errors = $this->checkIfOtpVerificationStarted( $errors, $data );

			if ( ! empty( $errors[ $id ]['header'] ) ) {
				return $errors;
			}

			if ( isset( $form_data ) && strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$errors = $this->processEmail( $data, $errors, $form_data );
			} elseif ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$errors = $this->processPhone( $data, $errors, $form_data );
			}

			if ( is_wp_error( $errors ) ) {
				return $errors;
			}
			if ( isset( $form_data ) && empty( $errors ) ) {
				$errors = $this->processOTPEntered( $data, $errors, $form_data );
			}
			return $errors;
		}
		/**
		 * Process and validate the OTP entered by the user
		 *
		 * @param string $data - the value entered by the user.
		 * @param string $errors - shows error values.
		 * @param string $form_data - the value of the form submitted.
		 * @return WP_Error
		 */
		private function processOTPEntered( $data, $errors, $form_data ) {
			$id           = $data['everest_forms']['id'];
			$otp_ver_type = $this->get_verification_type();
			$this->validate_challenge( $otp_ver_type, null, $data['everest_forms']['form_fields'][ $form_data['verifyKey'] ] );
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				$errors[ $id ]['header'] = MoUtility::get_invalid_otp_method();
			}
			return $errors;
		}
		/**
		 * This function checks if  OTP verification has been started by checking
		 * if the session variable has been set in session.
		 *
		 * @param string $errors - the error values.
		 * @param string $data - the value entered by the user.
		 * @return WP_Error|string
		 */
		private function checkIfOtpVerificationStarted( $errors, $data ) {
			$id = $data['everest_forms']['id'];
			if ( ! ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) ) {
				$errors[ $id ]['header'] = MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE );
			}
			return $errors;
		}
		/**
		 * The function is used to process email to send the OTP to
		 * and return the data associated with the form
		 *
		 * @param string $data - the value entered by the user.
		 * @param string $errors - error data.
		 * @param string $form_data - form data.
		 * @return WP_Error
		 */
		private function processEmail( $data, $errors, $form_data ) {
			$id = sanitize_text_field( $data['everest_forms']['id'] );
			if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, $data['everest_forms']['form_fields'][ $form_data['emailkey'] ] ) ) {
				$errors[ $id ]['header'] = MoMessages::showMessage( MoMessages::EMAIL_MISMATCH );
			}
			return $errors;
		}
		/**
		 * The function is used to process the phone number to send the OTP to
		 * and return the data associated with the form
		 *
		 * @param string $data - the value entered by the user.
		 * @param string $errors - error data.
		 * @param string $form_data - form data.
		 * @return WP_Error
		 */
		private function processPhone( $data, $errors, $form_data ) {
			$id = sanitize_text_field( $data['everest_forms']['id'] );
			if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, $data['everest_forms']['form_fields'][ $form_data['phonekey'] ] ) ) {
				$errors[ $id ]['header'] = MoMessages::showMessage( MoMessages::PHONE_MISMATCH );
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
		 * @param  string $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}
		/**
		 * This function will parse the Form Details and return an array to be
		 * stored in the database.
		 *
		 * @return array
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			$this->is_form_enabled = $this->sanitize_form_post( 'everest_contact_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'everest_contact_enable_type' );
			$this->button_text     = $this->sanitize_form_post( 'everest_contact_button_text' );

			$form = $this->parseFormDetails( $data );

			$this->form_details = ! empty( $form ) ? $form : '';

			update_mo_option( 'everest_contact_enable', $this->is_form_enabled );
			update_mo_option( 'everest_contact_enable_type', $this->otp_type );
			update_mo_option( 'everest_contact_button_text', $this->button_text );
			update_mo_option( 'everest_contact_forms', maybe_serialize( $this->form_details ) );
		}

		/**
		 * This function will parse the Form Details and return an array to be
		 * stored in the database.
		 *
		 * @param  array $data - $_POST.
		 * @return array
		 */
		public function parseFormDetails( $data ) {
			$form = array();

			if ( ! array_key_exists( 'everest_contact_form', $data ) || ! $this->is_form_enabled ) {
				return $form;
			}

			$is_set_form = isset( $data['everest_contact_form']['form'] ) ? MoUtility::mo_sanitize_array( wp_unslash( $data['everest_contact_form']['form'] ) ) : '';
			foreach ( array_filter( $is_set_form ) as $key => $value ) {
				$form[ sanitize_text_field( $value ) ] = array(
					'emailkey'    => isset( $data['everest_contact_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['emailkey'][ $key ] ) ) : '',
					'phonekey'    => isset( $data['everest_contact_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['phonekey'][ $key ] ) ) : '',
					'verifyKey'   => isset( $data['everest_contact_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['verifyKey'][ $key ] ) ) : '',
					'phone_show'  => isset( $data['everest_contact_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['phonekey'][ $key ] ) ) : '',
					'email_show'  => isset( $data['everest_contact_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['emailkey'][ $key ] ) ) : '',
					'verify_show' => isset( $data['everest_contact_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['everest_contact_form']['verifyKey'][ $key ] ) ) : '',
				);
			}
			return $form;
		}

	}
}
