<?php
/**
 * Handler Functions for Custom User Registration Form Builder (Registration Magic) form
 *
 * @package miniorange-otp-verification/handler/forms
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
use WP_Error;

/**
 * This is the Registration Magic form class. This class handles all the
 * functionality related to Classify Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'RegistrationMagicForm' ) ) {
	/**
	 * RegistrationMagicForm class
	 */
	class RegistrationMagicForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::CRF_DEFAULT_REG;
			$this->type_phone_tag          = 'mo_crf_phone_enable';
			$this->type_email_tag          = 'mo_crf_email_enable';
			$this->type_both_tag           = 'mo_crf_both_enable';
			$this->form_key                = 'CRF_FORM';
			$this->form_name               = mo_( 'Custom User Registration Form Builder (Registration Magic)' );
			$this->is_form_enabled         = get_mo_option( 'crf_default_enable' );
			$this->phone_form_id           = array();
			$this->form_documents          = MoFormDocs::CRF_FORM_ENABLE;
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
			$this->otp_type            = get_mo_option( 'crf_enable_type' );
			$this->restrict_duplicates = get_mo_option( 'crf_restrict_duplicates' );
			$this->form_details        = maybe_unserialize( get_mo_option( 'crf_otp_enabled' ) );
			if ( empty( $this->form_details ) ) {
				return;
			}
			foreach ( $this->form_details as $key => $value ) {
				array_push( $this->phone_form_id, 'input[name=' . $this->getFieldID( $value['phonekey'], $key ) . ']' );
			}

			if ( ! $this->checkIfPromptForOTP() ) {
				return;
			}
			$this->handle_crf_form_submit( $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need for nonce verification as the function is called on third party plugin hook
		}

		/**
		 * This function checks if the the POST data needs to be forwarded
		 * to make OTP Verification Happen. Checks if the POST data contains
		 * certain keys.
		 *
		 * @throws ReflectionException Adds exception.
		 */
		private function checkIfPromptForOTP() {
			if ( array_key_exists( 'option', $_POST ) || ! array_key_exists( 'rm_form_sub_id', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				return false;
			}
			foreach ( $this->form_details as $key => $value ) {
				if ( strpos( sanitize_text_field( wp_unslash( $_POST['rm_form_sub_id'] ) ), 'form_' . $key . '_' ) !== false ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					MoUtility::initialize_transaction( $this->form_session_var );
					SessionUtils::set_form_or_field_id( $this->form_session_var, $key );
					return true;
				}
			}
			return false;
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Registration Magic Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * EMAIL Verification has been enabled by the admin for Registration Magic Registration
		 * form.
		 */
		private function isEmailVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::EMAIL === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}


		/**
		 * This function is used to get the email and phone value entered by
		 * the user in the form post by first fetching the field id and
		 * creating the post name value to fetch the form post value form
		 * the $_REQUEST global variable. Forward the values for OTP processing.
		 *
		 * @param array $request_data - the $_REQUEST data.
		 * @throws ReflectionException Adds exception.
		 */
		private function handle_crf_form_submit( $request_data ) {
			$email = $this->isEmailVerificationEnabled() ? $this->getCRFEmailFromRequest( $request_data ) : '';
			$phone = $this->isPhoneVerificationEnabled() ? $this->getCRFPhoneFromRequest( $request_data ) : '';
			$this->miniorange_crf_user( $email, isset( $request_data['user_name'] ) ? sanitize_text_field( $request_data['user_name'] ) : null, $phone );
			$this->checkIfValidated();
		}


		/**
		 * Check if the formSessionVariable has been set as validated. This is to make sure
		 * that the OTP Verification process doesn't kick in again after OTP Verification is
		 * done so that the normal functioning of the CRF form can kick in.
		 */
		private function checkIfValidated() {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
			}
		}


		/**
		 * This function is used to get the email value entered by the user in the
		 * form post by first fetching the field id and creating the post name value
		 * to fetch the form post value form the $_REQUEST global variable.
		 *
		 * @param array $request_data - the $_REQUEST data.
		 * @return string
		 */
		private function getCRFEmailFromRequest( $request_data ) {
			$form_id   = SessionUtils::get_form_or_field_id( $this->form_session_var );
			$email_key = $this->form_details[ $form_id ]['emailkey'];
			return $this->getFormPostSubmittedValue( $this->getFieldID( $email_key, $form_id ), $request_data );
		}


		/**
		 * This function is used to get the Phone value entered by the user in the
		 * form post by first fetching the field id and creating the post name value
		 * to fetch the form post value form the $_REQUEST global variable.
		 *
		 * @param array $request_data - the $_REQUEST data.
		 * @return string
		 */
		private function getCRFPhoneFromRequest( $request_data ) {
			$form_id  = SessionUtils::get_form_or_field_id( $this->form_session_var );
			$phonekey = $this->form_details[ $form_id ]['phonekey'];
			return $this->getFormPostSubmittedValue( $this->getFieldID( $phonekey, $form_id ), $request_data );
		}


		/**
		 * Loop through the database value and return the form post value based on
		 * the field id saved in the database and the key provided by the admin.
		 *
		 * @param string $reg1 - the name attribute of the field.
		 * @param array  $request_data - the $_REQUEST data.
		 * @return string
		 */
		private function getFormPostSubmittedValue( $reg1, $request_data ) {
			return isset( $request_data[ $reg1 ] ) ? $request_data[ $reg1 ] : '';
		}


		/**
		 * This function is used to get the field data details from the CRF
		 * database . This is required to form the form data name value so
		 * that we can get the phone number from the post data.
		 *
		 * @param  string $key - the email or phone key saved by the admin.
		 * @param string $form_id - the form_id against which the email or phone field is saved.
		 * @return string
		 */
		private function getFieldID( $key, $form_id ) {
			global $wpdb;
			$row1 = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}rm_fields` where `form_id` = %s and `field_label` =%s", array( $form_id, $key ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return isset( $row1 ) ? ( 'Mobile' === $row1->field_type ? $row1->field_type : 'Email' ) . '_' . $row1->field_id : 'null';
		}


		/**
		 * This function is called to start the OTP Verification process for
		 * Registration Magic Form. This function initializes the session
		 * variable and decides which otp verification needs to be done.
		 *
		 * @param string $user_email - refers to the user's email in the form post.
		 * @param string $user_name - refers to the user's username in the form post.
		 * @param string $phone_number - refers to the user's phone number in the form post.
		 * @throws ReflectionException Adds exception.
		 */
		private function miniorange_crf_user( $user_email, $user_name, $phone_number ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			$errors = new WP_Error();
			if ( $this->isPhoneNumberAlreadyInUse( $phone_number ) ) {
				miniorange_site_otp_validation_form(
					'',
					'',
					'',
					'Phone number already in use. Please Enter a different Phone number.',
					'',
					''
				);
			}
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, $errors, $phone_number, VerificationType::PHONE );
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				$this->send_challenge( $user_name, $user_email, $errors, $phone_number, VerificationType::BOTH );
			} else {
				$this->send_challenge( $user_name, $user_email, $errors, $phone_number, VerificationType::EMAIL );
			}
		}

		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the phone number value to search.
		 * @return bool
		 */
		private function isPhoneNumberAlreadyInUse( $phone ) {
			if ( $this->restrict_duplicates ) {
				global $wpdb;
				$phone   = MoUtility::process_phone_number( $phone );
				$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_value` = %s", $phone ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
				return ! MoUtility::is_blank( $results );
			}
			return false;
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
		 * push the form_id to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Registration Magic related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			$form = $this->parseFormDetails( $data );

			$this->form_details        = ! empty( $form ) ? $form : '';
			$this->is_form_enabled     = $this->sanitize_form_post( 'crf_default_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'crf_enable_type' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'crf_restrict_duplicates' );

			update_mo_option( 'crf_default_enable', $this->is_form_enabled );
			update_mo_option( 'crf_enable_type', $this->otp_type );
			update_mo_option( 'crf_otp_enabled', maybe_serialize( $this->form_details ) );
			update_mo_option( 'crf_restrict_duplicates', $this->restrict_duplicates );
		}

		/**
		 * Handles checking all the Registration Magic related options by the admin.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function parseFormDetails( $data ) {
			$form = array();
			if ( ! array_key_exists( 'crf_form', $data ) && empty( $data['crf_form']['form'] ) ) {
				return $form;
			}
			foreach ( array_filter( $data['crf_form']['form'] ) as $key => $value ) {
				$form[ sanitize_text_field( $value ) ] = array(
					'emailkey'   => sanitize_text_field( $data['crf_form']['emailkey'][ $key ] ),
					'phonekey'   => sanitize_text_field( $data['crf_form']['phonekey'][ $key ] ),
					'email_show' => sanitize_text_field( $data['crf_form']['emailkey'][ $key ] ),
					'phone_show' => sanitize_text_field( $data['crf_form']['phonekey'][ $key ] ),
				);
			}
			return $form;
		}
	}
}
