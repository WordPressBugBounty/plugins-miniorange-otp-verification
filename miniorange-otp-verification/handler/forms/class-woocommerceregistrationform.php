<?php
/**
 * Handles the OTP verification logic for WooCommerceRegistrationForm form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoConstants;
use OTP\Helper\MoException;
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
 * This is the WooCommerce Registration form class. This class handles all the
 * functionality related to WooCommerce Registration form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WooCommerceRegistrationForm' ) ) {
	/**
	 * WooCommerceRegistrationForm class
	 */
	class WooCommerceRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * The option which tells which page the user
		 * should be redirected to after registration.
		 *
		 * @var string
		 */
		private $redirect_to_page;

		/**
		 * To check if admin has set up user redirection after registration.
		 *
		 * @var bool
		 */
		private $redirect_after_registration;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->form_session_var        = FormSessionVars::WC_DEFAULT_REG;
			$this->type_phone_tag          = 'mo_wc_phone_enable';
			$this->type_email_tag          = 'mo_wc_email_enable';
			$this->type_both_tag           = 'mo_wc_both_enable';
			$this->phone_form_id           = '#reg_billing_phone';
			$this->form_key                = 'WC_REG_FORM';
			$this->form_name               = mo_( 'WooCommerce Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'wc_default_enable' );
			$this->button_text             = get_mo_option( 'wc_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->form_documents          = MoFormDocs::WC_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->is_ajax_form                = get_mo_option( 'wc_is_ajax_form' );
			$this->otp_type                    = get_mo_option( 'wc_enable_type' );
			$this->redirect_to_page            = get_mo_option( 'wc_redirect' );
			$this->redirect_after_registration = get_mo_option( 'wcreg_redirect_after_registration' );
			$this->restrict_duplicates         = get_mo_option( 'wc_restrict_duplicates' );

			add_filter( 'woocommerce_process_registration_errors', array( $this, 'woocommerce_site_registration_errors' ), 99, 4 );
			add_action( 'woocommerce_created_customer', array( $this, 'register_woocommerce_user' ), 1, 3 );
			add_filter( 'woocommerce_registration_redirect', array( $this, 'custom_registration_redirect' ), 99, 1 );
			if ( $this->isPhoneVerificationEnabled() ) {

				add_action( 'woocommerce_register_form', array( $this, 'mo_add_phone_field' ), 1 );
				add_action( 'wcmp_vendor_register_form', array( $this, 'mo_add_phone_field' ), 1 );
			}
			if ( $this->is_ajax_form && $this->otp_type !== $this->type_both_tag ) {
				add_action( 'woocommerce_register_form', array( $this, 'mo_add_verification_field' ), 1 );
				add_action( 'wcmp_vendor_register_form', array( $this, 'mo_add_verification_field' ), 1 );
				add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_wc_script' ) );
				$this->routeData();
			}
		}


		/**
		 * Checks the option set in the GET and initialzes the OTP verification functionality.
		 *
		 * @throws ReflectionException .
		 */
		private function routeData() {

			if ( ! array_key_exists( 'mo_wcreg_option', $_GET ) ) { //phpcs:ignore -- false positive.
				return;
			}

			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ), MoConstants::ERROR_JSON_TYPE ) );
			}
			$data                  = MoUtility::mo_sanitize_array( $_POST );
			$send_otp_check_option = isset( $_GET['mo_wcreg_option'] ) ? sanitize_text_field( wp_unslash( $_GET['mo_wcreg_option'] ) ) : ''; //phpcs:ignore -- false positive.

			switch ( trim( $send_otp_check_option ) ) {
				case 'miniorange-wc-reg-verify':
					$this->sendAjaxOTPRequest( $data );
					break;
			}
		}


		/**
		 * This function handles the send ajax otp request. Initializes the session,
		 * checks to see if phone or email transaction have been enabled and sends
		 * OTP to the appropriate delivery point.
		 *
		 * @param array $data - the post data on send OTP request.
		 * @throws ReflectionException .
		 */
		private function sendAjaxOTPRequest( $data ) {

			MoUtility::initialize_transaction( $this->form_session_var );

			$mobile_number = MoUtility::sanitize_check( 'user_phone', $data );
			$user_email    = MoUtility::sanitize_check( 'user_email', $data );
			if ( $this->otp_type === $this->type_phone_tag ) {
				SessionUtils::add_phone_verified( $this->form_session_var, MoUtility::process_phone_number( $mobile_number ) );
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $user_email );
			}
			$error = $this->processFormFields( null, $user_email, new WP_Error(), null, $mobile_number, $data );
			if ( $error->get_error_code() ) {
				wp_send_json( MoUtility::create_json( $error->get_error_message(), MoConstants::ERROR_JSON_TYPE ) );
			}
		}


		/**
		 * This function registers the js file for enabling OTP Verification
		 * for WooCommerce using AJAX calls.
		 */
		public function miniorange_register_wc_script() {
			wp_register_script( 'mowcreg', MOV_URL . 'includes/js/wcreg.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'mowcreg',
				'mowcreg',
				array(
					'siteURL'    => site_url(),
					'otpType'    => $this->otp_type,
					'nonce'      => wp_create_nonce( $this->nonce ),
					'buttontext' => mo_( $this->button_text ),
					'field'      => $this->otp_type === $this->type_phone_tag ? 'reg_billing_phone' : 'reg_email',
					'imgURL'     => MOV_LOADER_URL,
				)
			);
			wp_enqueue_script( 'mowcreg' );
		}


		/**
		 * This function hooks into the woocommerce_registration_redirect hook to alter the page
		 * where the user should be redirected to after registration. This option is set by the
		 * admin under the WooCommerce Form Settings.
		 *
		 * @param string $var - redirect link.
		 * @return false|string
		 */
		public function custom_registration_redirect( $var ) {

			if ( $this->redirect_after_registration && get_mo_option( 'wc_default_enable' ) ) {
				return get_permalink(
					get_posts(
						array(
							'title'     => $this->redirect_to_page,
							'post_type' => 'page',
						)
					)[0]->ID
				);
			}
			return $var;
		}


		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for WooCommerce Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otpver_type = $this->get_verification_type();
			return VerificationType::BOTH === $otpver_type || VerificationType::PHONE === $otpver_type;
		}


		/**
		 * This is part WooCommerce functionality and has been ported over here for validation.
		 * If Ajax mode is enabled then process and validate the otp entered
		 *
		 * @param WP_Error $errors the error object which represents a WP_ERROR.
		 * @param string   $username the username provided by the user.
		 * @param string   $password the password provided by the user.
		 * @param string   $email the email provided by the user.
		 * @return WP_Error
		 * @throws ReflectionException .
		 */
		public function woocommerce_site_registration_errors( WP_Error $errors, $username, $password, $email ) {

			if ( ! MoUtility::is_blank( array_filter( $errors->errors ) ) ) {
				return $errors;
			}
			if ( $this->is_ajax_form ) {
				$this->assertOTPField( $errors, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$this->checkIfOTPWasSent( $errors );
				return $this->checkIntegrityAndValidateOTP( $_POST, $errors ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			} else {
				return $this->processFormAndSendOTP( $username, $password, $email, $errors );
			}
		}


		/**
		 * Verify OTP field to ensure that the user has entered
		 * a verification code. Return a error message if user
		 * has note entered a verification code.
		 *
		 * @param WP_Error $errors wp_error object.
		 * @param array    $form_items form data.
		 */
		private function assertOTPField( &$errors, $form_items ) {
			if ( ! MoUtility::sanitize_check( 'moverify', $form_items ) ) {
				$errors = new WP_Error(
					'registration-error-otp-needed',
					MoMessages::showMessage( MoMessages::REQUIRED_OTP )
				);
			}
		}


		/**
		 * Check to ensure if user has initiated OTP Verification.
		 * Makes sure user is not submitting the form w/o
		 * sending OTP to his phone or email.
		 *
		 * @param WP_ERROR $errors   - wp_error object.
		 */
		private function checkIfOTPWasSent( &$errors ) {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$errors = new WP_Error(
					'registration-error-need-validation',
					MoMessages::showMessage( MoMessages::PLEASE_VALIDATE )
				);
			}
		}


		/**
		 * Check to make sure user is submitting the form with the phone number or
		 * email that the otp was sent to and make sure that the otp is a valid otp.
		 *
		 * @param array    $data    - form data submitted.
		 * @param WP_Error $errors  - the error object.
		 * @return WP_Error
		 */
		private function checkIntegrityAndValidateOTP( $data, WP_Error $errors ) {
			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}
			if ( isset( $data['billing_phone'] ) ) {
				$data['billing_phone'] = MoUtility::process_phone_number( $data['billing_phone'] );
			}
			$errors = $this->checkIntegrity( $data, $errors );
			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}

			$otpver_type = $this->get_verification_type();
			$this->validate_challenge( $otpver_type, null, sanitize_text_field( $data['moverify'] ) );

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otpver_type ) ) {
				$this->unset_otp_session_variables();
			} else {
				return new WP_Error( 'registration-error-invalid-otp', MoUtility::get_invalid_otp_method() );
			}
			return $errors;
		}


		/**
		 * Check the integrity of the phone or email being submitted and ensure that
		 * it's the same phone or email that the OTP was sent to.
		 *
		 * @param array    $data    - form data submitted.
		 * @param WP_Error $errors      - WP_Error object.
		 * @return WP_Error
		 */
		private function checkIntegrity( $data, WP_Error $errors ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				if ( ! Sessionutils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $data['billing_phone'] ) ) ) {
					return new WP_Error(
						'billing_phone_error',
						MoMessages::showMessage( MoMessages::PHONE_MISMATCH )
					);
				}
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $data['email'] ) ) ) {
					return new WP_Error(
						'registration-error-invalid-email',
						MoMessages::showMessage( MoMessages::EMAIL_MISMATCH )
					);
				}
			}
			return $errors;
		}


		/**
		 * Process the registration form and start OTP Verification process if no errors are found.
		 * Assert the username, password and email submitted by the user.
		 *
		 * @param string   $username - username of the user to be registered.
		 * @param string   $password - password of the user to be registered.
		 * @param string   $email - email of the user to be registered.
		 * @param WP_Error $errors - - WP_Error object.
		 * @return WP_Error
		 * @throws ReflectionException .
		 */
		private function processFormAndSendOTP( $username, $password, $email, WP_Error $errors ) {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
				return $errors;
			}

			$phone_number = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			MoUtility::initialize_transaction( $this->form_session_var );

			try {
				$this->assertUserName( $username );
				$this->assertPassword( $password );
				$this->assertEmail( $email );
			} catch ( MoException $e ) {
				return new WP_Error( $e->getmo_code(), $e->getMessage() );
			}
			do_action( 'woocommerce_register_post', $username, $email, $errors );
			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			return $errors->get_error_code() ? $errors
			: $this->processFormFields( $username, $email, $errors, $password, $phone_number, $data );

		}



		/**
		 * Assert WooCommerce password. Throws an exception if password is invalid.
		 *
		 * @param string $password - password of the user.
		 * @throws MoException Throws MoException if password is blank.
		 */
		private function assertPassword( $password ) {
			if ( get_mo_option( 'woocommerce_registration_generate_password', '' ) === 'no' ) {
				if ( MoUtility::is_blank( $password ) ) {
					throw new MoException(
						'registration-error-invalid-password',
						mo_( 'Please enter a valid account password.' ),
						204
					);
				}
			}
		}

		/**
		 * Assert WooCommerce Email. Throws an exception if email is invalid
		 *
		 * @param string $email - email of the user.
		 * @throws MoException Throws MoException if email is blank or invalid.
		 */
		private function assertEmail( $email ) {
			if ( MoUtility::is_blank( $email ) || ! is_email( $email ) ) {
				throw new MoException(
					'registration-error-invalid-email',
					mo_( 'Please enter a valid email address.' ),
					202
				);
			}
			if ( email_exists( $email ) ) {
				throw new MoException(
					'registration-error-email-exists',
					mo_( 'An account is already registered with your email address. Please login.' ),
					203
				);
			}
		}


		/**
		 * Assert WooCommerce UserName. Throw an exception if username is invalid.
		 *
		 * @param string $username - username of the user.
		 * @throws MoException Throws MoException if email is blank or invalid.
		 */
		private function assertUserName( $username ) {
			if ( get_mo_option( 'woocommerce_registration_generate_username', '' ) === 'no' ) {
				if ( MoUtility::is_blank( $username ) || ! validate_username( $username ) ) {
					throw new MoException(
						'registration-error-invalid-username',
						mo_( 'Please enter a valid account username.' ),
						200
					);
				}
				if ( username_exists( $username ) ) {
					throw new MoException(
						'registration-error-username-exists',
						mo_( 'An account is already registered with that username. Please choose another.' ),
						201
					);
				}
			}
		}


		/**
		 * This function checks and validates the phone, email fields and
		 * starts the otp verification process. If phone or email fields were
		 * NULL then throws an error and returns it.
		 *
		 * @param string $username - the username provided by the user.
		 * @param string $email    - the email provided by the user.
		 * @param string $errors   - the error object which represents a WP_ERROR.
		 * @param string $password - the password provided by the user.
		 * @param string $phone    - the phone number to send otp to.
		 * @param array  $data - the post data on send OTP request.
		 * @return WP_Error
		 */
		private function processFormFields( $username, $email, $errors, $password, $phone, $data ) {

			global $phone_logic;
			$phone_number = isset( $data['billing_phone'] ) ? sanitize_text_field( wp_unslash( $data['billing_phone'] ) ) : '';
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
			} elseif ( strcasecmp( $this->otp_type, $this->type_both_tag ) === 0 ) {
				if ( ! isset( $phone ) || ! MoUtility::validate_phone_number( $phone ) ) {
					return new WP_Error(
						'billing_phone_error',
						str_replace( '##phone##', $phone_number, $phone_logic->get_otp_invalid_format_message() )
					);
				} elseif ( $this->restrict_duplicates && $this->isPhoneNumberAlreadyInUse( $phone, 'billing_phone' ) ) {
					return new WP_Error( 'billing_phone_error', MoMessages::showMessage( MoMessages::PHONE_EXISTS ) );
				}
				$this->send_challenge( $username, $email, $errors, $phone_number, VerificationType::BOTH, $password );
			}
			return $errors;
		}


		/**
		 * Called after successful OTP Verification to complete the user registration process.
		 * This is just called to update the phone number that was validated against the
		 * user in the database.
		 *
		 * @param int    $customer_id - user id of the user that was created.
		 * @param array  $new_customer_data - the user data.
		 * @param string $password_generated - the password provided by the user.
		 */
		public function register_woocommerce_user( $customer_id, $new_customer_data, $password_generated ) {

			if ( isset( $_POST['billing_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$phone = MoUtility::sanitize_check( 'billing_phone', $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				update_user_meta( $customer_id, 'billing_phone', MoUtility::process_phone_number( $phone ) );
			}
		}


		/**
		 * This function hooks into the woocommerce_register_form hook to add
		 * phone number field to the registration form if SMS Verification has
		 * been enabled by the admin.
		 */
		public function mo_add_phone_field() {
			if ( ! did_action( 'woocommerce_register_form' ) || ! did_action( 'wcmp_vendor_register_form' ) ) {
				echo '<p class="form-row form-row-wide">
                <label for="reg_billing_phone">
                    ' . esc_html( mo_( 'Phone' ) ) . '
                    <span class="required">*</span>
                </label>
                <input type="text" class="input-text" 
                        name="billing_phone" id="reg_billing_phone" 
                        value="' . ( isset( $_POST['billing_phone'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : '' ) . '" /></p>'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			}
		}


		/**
		 * This function hooks into the woocommerce_register_form hook to add
		 * a verification field to the registration form if AJAX mode is
		 * enabled by the admin.
		 */
		public function mo_add_verification_field() {
			if ( ! did_action( 'woocommerce_register_form' ) || ! did_action( 'wcmp_vendor_register_form' ) ) {
				echo '<p class="form-row form-row-wide">
                <label for="reg_verification_phone">
                    ' . esc_html( mo_( 'Enter Code' ) ) . '
                    <span class="required">*</span>
                </label>
                <input type="text" class="input-text" name="moverify" 
                        id="reg_verification_field" 
                        value="" />
              </p>';
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

			if ( $this->is_ajax_form ) {
				SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
			} else {
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
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array $selector - the Jquery selector to be modified.
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the phone number value to search.
		 * @param string $key - meta_key to search for.
		 * @return bool
		 */
		private function isPhoneNumberAlreadyInUse( $phone, $key ) {
			global $wpdb;
			$phone   = MoUtility::process_phone_number( $phone );
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return ! MoUtility::is_blank( $results );
		}


		/**
		 * Handles saving all the woocommerce Registration form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->is_form_enabled             = $this->sanitize_form_post( 'wc_default_enable' );
			$this->otp_type                    = $this->sanitize_form_post( 'wc_enable_type' );
			$this->restrict_duplicates         = $this->sanitize_form_post( 'wc_restrict_duplicates' );
			$this->redirect_to_page            = isset( $_POST['mo_wc_reg_page_id'] ) ? get_the_title( sanitize_text_field( wp_unslash( $_POST['mo_wc_reg_page_id'] ) ) ) : 'My Account'; //phpcs:ignore -- false positive.
			$this->is_ajax_form                = $this->sanitize_form_post( 'wc_is_ajax_form' );
			$this->button_text                 = $this->sanitize_form_post( 'wc_button_text' );
			$this->redirect_after_registration = $this->sanitize_form_post( 'wcreg_redirect_after_registration' );

			update_mo_option( 'wcreg_redirect_after_registration', $this->redirect_after_registration );
			update_mo_option( 'wc_default_enable', $this->is_form_enabled );
			update_mo_option( 'wc_enable_type', $this->otp_type );
			update_mo_option( 'wc_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'wc_redirect', $this->redirect_to_page );
			update_mo_option( 'wc_is_ajax_form', $this->is_ajax_form );
			update_mo_option( 'wc_button_text', $this->button_text );
		}

		/**
		 * Returns page on which redirection to be done after registration.
		 *
		 * @return string
		 */
		public function redirectToPage() {
			return $this->redirect_to_page; }

		/**
		 * Returns whether admin enabled redirection after registration.
		 *
		 * @return string
		 */
		public function isredirectToPageEnabled() {
			return $this->redirect_after_registration;}
	}
}
