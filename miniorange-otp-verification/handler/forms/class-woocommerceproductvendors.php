<?php
/**
 * Load admin view for WooCommerce Product Vendor form.
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
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Error;

/**
 * This is the WooCommerce Product Vendor form class. This class handles all the
 * functionality related to WooCommerce Product Vendor Plugin by WooCommerce.
 * It extends the FormHandler and implements the IFormHandler class to
 * implement some much needed functions.
 */
if ( ! class_exists( 'WooCommerceProductVendors' ) ) {
	/**
	 * WooCommerceProductVendors class
	 */
	class WooCommerceProductVendors extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->form_session_var        = FormSessionVars::WC_PRODUCT_VENDOR;
			$this->is_ajax_form            = true;
			$this->type_phone_tag          = 'mo_wc_pv_phone_enable';
			$this->type_email_tag          = 'mo_wc_pv_email_enable';
			$this->phone_form_id           = '#reg_billing_phone';
			$this->form_key                = 'WC_PV_REG_FORM';
			$this->form_name               = mo_( 'WooCommerce Product Vendor Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'wc_pv_default_enable' );
			$this->button_text             = get_mo_option( 'wc_pv_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->form_documents          = MoFormDocs::WC_PRODUCT_VENDOR;
			parent::__construct();
		}


		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type            = get_mo_option( 'wc_pv_enable_type' );
			$this->restrict_duplicates = get_mo_option( 'wc_pv_restrict_duplicates' );
			add_action( 'wcpv_registration_form', array( $this, 'mo_add_phone_field' ), 1 );
			add_action( 'wp_ajax_nopriv_miniorange_wc_vp_reg_verify', array( $this, 'sendAjaxOTPRequest' ) );
			add_filter( 'wcpv_shortcode_registration_form_validation_errors', array( $this, 'reg_fields_errors' ), 1, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_wc_script' ) );
		}

		/**
		 * This function handles the send ajax otp request. Initializes the session,
		 * checks to see if phone or email transaction have been enabled and sends
		 * OTP to the appropriate delivery point.
		 *
		 * @throws ReflectionException .
		 */
		public function sendAjaxOTPRequest() {

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
			MoUtility::initialize_transaction( $this->form_session_var );
			$mobile_number = MoUtility::sanitize_check( 'user_phone', $data );
			$user_email    = MoUtility::sanitize_check( 'user_email', $data );
			if ( $this->otp_type === $this->type_phone_tag ) {
				SessionUtils::add_phone_verified( $this->form_session_var, MoUtility::process_phone_number( $mobile_number ) );
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $user_email );
			}
			$error = $this->processFormFields( null, $user_email, new WP_Error(), null, $mobile_number );
			if ( $error->get_error_code() ) {
				wp_send_json( MoUtility::create_json( $error->get_error_message(), MoConstants::ERROR_JSON_TYPE ) );
			}
		}



		/**
		 * Hooks into the wcpv_shortcode_registration_form_validation_errors hook to validate
		 * the form fields and the OTP that the user received on his phone or email.
		 *
		 * @param array $errors       - array of errors.
		 * @param array $form_items   - the form data.
		 * @return array
		 */
		public function reg_fields_errors( $errors, $form_items ) {
			if ( ! empty( $errors ) ) {
				return $errors;
			}

			$this->assertOTPField( $errors, $form_items );
			$this->checkIfOTPWasSent( $errors );
			return $this->checkIntegrityAndValidateOTP( $form_items, $errors );
		}


		/**
		 * Verify OTP field to ensure that the user has entered
		 * a verification code. Return a error message if user
		 * has note entered a verification code.
		 *
		 * @param array $errors       - wp_error object.
		 * @param array $form_items   - form data.
		 */
		private function assertOTPField( &$errors, $form_items ) {
			if ( ! MoUtility::sanitize_check( 'moverify', $form_items ) ) {
				$errors[] = MoMessages::showMessage( MoMessages::REQUIRED_OTP );
			}
		}


		/**
		 * Check to ensure if user has initiated OTP Verification.
		 * Makes sure user is not submitting the form w/o
		 * sending OTP to his phone or email.
		 *
		 * @param array $errors   - wp_error object.
		 */
		private function checkIfOTPWasSent( &$errors ) {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$errors[] = MoMessages::showMessage( MoMessages::PLEASE_VALIDATE );
			}
		}


		/**
		 * Check to make sure user is submitting the form with the phone number or
		 * email that the otp was sent to and make sure that the otp is a valid otp.
		 *
		 * @param array $data        - form data submitted.
		 * @param array $errors      - the error array.
		 * @return WP_Error|array
		 */
		private function checkIntegrityAndValidateOTP( $data, array $errors ) {
			if ( ! empty( $errors ) ) {
				return $errors;
			}
			$data['billing_phone'] = MoUtility::process_phone_number( $data['billing_phone'] );
			$errors                = $this->checkIntegrity( $data, $errors );
			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}
			$otp_ver_type = $this->get_verification_type();
			$this->validate_challenge( $otp_ver_type, null, sanitize_text_field( $data['moverify'] ) );
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				$errors[] = MoUtility::get_invalid_otp_method();
			} else {
				$this->unset_otp_session_variables();
			}
			return $errors;
		}




		/**
		 * Check the integrity of the phone or email being submitted and ensure that
		 * it's the same phone or email that the OTP was sent to.
		 *
		 * @param array $data        - for data.
		 * @param array $errors      - error array.
		 * @return WP_Error|array
		 */
		private function checkIntegrity( $data, array $errors ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, MoUtility::process_phone_number( $data['billing_phone'] ) ) ) {
					$errors[] = MoMessages::showMessage( MoMessages::PHONE_MISMATCH );
				}
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $data['email'] ) ) ) {
					$errors[] = MoMessages::showMessage( MoMessages::EMAIL_MISMATCH );
				}
			}
			return $errors;
		}


		/**
		 * This function checks and validates the phone, email fields and
		 * starts the otp verification process. If phone or email fields were
		 * NULL then throws an error and returns it.
		 *
		 * @param array $username - the username provided by the user.
		 * @param array $email    - the email provided by the user.
		 * @param array $errors   - the error object which represents a WP_ERROR.
		 * @param array $password - the password provided by the user.
		 * @param array $phone    - the phone number to send otp to.
		 * @return WP_Error
		 */
		public function processFormFields( $username, $email, $errors, $password, $phone ) {

			global $phone_logic;
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				if ( ! isset( $phone ) || ! MoUtility::validate_phone_number( $phone ) ) {
					return new WP_Error(
						'billing_phone_error',
						str_replace( '##phone##', $phone, $phone_logic->get_otp_invalid_format_message() )
					);
				} elseif ( $this->restrict_duplicates && $this->isPhoneNumberAlreadyInUse( $phone, 'billing_phone' ) ) {
					return new WP_Error( 'billing_phone_error', MoMessages::showMessage( MoMessages::PHONE_EXISTS ) );
				}
				$this->send_challenge( $username, $email, $errors, $phone, VerificationType::PHONE, $password );
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$phone = isset( $phone ) ? $phone : '';
				$this->send_challenge( $username, $email, $errors, $phone, VerificationType::EMAIL, $password );
			}
			return $errors;
		}


		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param array $phone - the phone number value to search.
		 * @param array $key - meta_key to search for.
		 * @return bool
		 */
		public function isPhoneNumberAlreadyInUse( $phone, $key ) {
			global $wpdb;
			$phone   = MoUtility::process_phone_number( $phone );
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` = %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return ! MoUtility::is_blank( $results );
		}


		/**
		 * This function registers the js file for enabling OTP Verification
		 * for Ultimate Member using AJAX calls.
		 */
		public function miniorange_register_wc_script() {
			wp_register_script( 'mowcpvreg', MOV_URL . 'includes/js/wcpvreg.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'mowcpvreg',
				'mowcpvreg',
				array(
					'siteURL'    => wp_ajax_url(),
					'otpType'    => $this->otp_type,
					'nonce'      => wp_create_nonce( $this->nonce ),
					'buttontext' => mo_( $this->button_text ),
					'field'      => $this->otp_type === $this->type_phone_tag ? 'reg_vp_billing_phone' : 'wcpv-confirm-email',
					'imgURL'     => MOV_LOADER_URL,
					'codeLabel'  => mo_( 'Enter Verification Code' ),
				)
			);
			wp_enqueue_script( 'mowcpvreg' );
		}

		/**
		 * This function hooks into the wcpv_registration_form hook to add
		 * phone number field to the registration form if SMS Verification has
		 * been enabled by the admin.
		 */
		public function mo_add_phone_field() {
			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			echo '<p class="form-row form-row-wide">
					<label for="reg_vp_billing_phone">
					    ' . esc_html( mo_( 'Phone' ) ) . '
					    <span class="required">*</span>
                    </label>
					<input type="text" class="input-text" 
					        name="billing_phone" id="reg_vp_billing_phone" 
					        value="' . ( ! empty( sanitize_text_field( $data['billing_phone'] ) ) ? esc_attr( sanitize_text_field( $data['billing_phone'] ) ) : '' ) . '" />	
			  	  </p>';
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
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the User Ultra Registration related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled     = $this->sanitize_form_post( 'wc_pv_default_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'wc_pv_enable_type' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'wc_pv_restrict_duplicates' );
			$this->button_text         = $this->sanitize_form_post( 'wc_pv_button_text' );

			update_mo_option( 'wc_pv_default_enable', $this->is_form_enabled );
			update_mo_option( 'wc_pv_enable_type', $this->otp_type );
			update_mo_option( 'wc_pv_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'wc_pv_button_text', $this->button_text );
		}
	}
}
