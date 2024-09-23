<?php
/**
 * Load admin view for WC Profile Form.
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

/**
 * This is the WC Profile form class. This class handles all the
 * functionality related to Woocommerce Plugin. It extends the
 * FormInterface class to implement some much needed functions.
 */
if ( ! class_exists( 'WcProfileForm' ) ) {
	/**
	 * WcProfileForm class
	 */
	class WcProfileForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Verification field Key
		 *
		 * @var string Verification field Key
		 */
		private $verify_field_key;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::WC_PROFILE_UPDATE;
			$this->type_phone_tag          = 'mo_wc_profile_phone_enable';
			$this->type_email_tag          = 'mo_wc_profile_email_enable';
			$this->form_key                = 'WC_AC_FORM';
			$this->verify_field_key        = 'verify_field';
			$this->form_name               = mo_( 'WooCommerce Account Details Form' );
			$this->is_form_enabled         = get_mo_option( 'wc_profile_enable' );
			$this->restrict_duplicates     = get_mo_option( 'wc_profile_restrict_duplicates' );
			$this->button_text             = get_mo_option( 'wc_profile_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->phone_key               = get_mo_option( 'wc_profile_phone_key' );
			$this->phone_key               = $this->phone_key ? $this->phone_key : 'billing_phone';
			$this->form_documents          = MoFormDocs::WC_PROFILE_UPDATE_FORM_LINK;
			$this->phone_form_id           = '#billing_phone';
			$this->generate_otp_action     = 'miniorange_wc_ac_otp';
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
			$this->otp_type = get_mo_option( 'wc_profile_enable_type' );
			add_action( 'woocommerce_edit_account_form', array( $this, 'mo_add_phone_field_account_form' ) );
			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'startOtpVerificationProcess' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'startOtpVerificationProcess' ) );
			add_action( 'woocommerce_save_account_details_errors', array( $this, 'verifyOtpEntered' ), 10, 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_wc_ac_script' ) );
		}

		/** This function starts verification process of the user
		 *
		 * @param object $errors  -Errors in form submission.
		 */
		public function verifyOtpEntered( $errors ) {

			$verificationkey = strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'billing_phone' : 'account_email';

			if ( $this->getUserData( $this->phone_key ) !== ( isset( $_POST[ $verificationkey ] ) ? sanitize_text_field( wp_unslash( $_POST[ $verificationkey ] ) ) : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				$this->checkIfOTPSent( $errors );
				if ( ! empty( $errors->errors ) ) {
					return $errors;
				}
				$this->checkIntegrityAndValidateOTP( $errors );
			} else {
				return;
			}
		}

		/** This function checks if the OTP was initialized
		 *
		 * @param object $errors  -Errors in form submission.
		 */
		private function checkIfOTPSent( $errors ) {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$errors->add( 'billing_user_need_to_verify_error', MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ) );
				return $errors;
			}
		}

		/**
		 * Check Integrity of the email or phone number. i.e. Ensure that the Email or
		 * Phone that the OTP was sent to is the same Email or Phone that is being submitted
		 * with the form.
		 * <br/<br/>
		 * Once integrity check passes validate the OTP to ensure that the user has entered
		 * the correct OTP.
		 *
		 * @param object $errors -Errors in form submission.
		 */
		private function checkIntegrityAndValidateOTP( $errors ) {
			$this->checkIntegrity( $errors );
			$this->validate_challenge( $this->get_verification_type(), null, isset( $_POST['enter_otp'] ) ? sanitize_text_field( wp_unslash( $_POST['enter_otp'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( ! empty( $errors->errors ) ) {
				return $errors;
			}
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				if ( $this->get_verification_type() === VerificationType::PHONE ) {
					SessionUtils::add_phone_submitted( $this->form_session_var, isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					$user_id = get_current_user_id();
					update_user_meta( $user_id, 'billing_phone', isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					$this->unset_otp_session_variables();
				}
				if ( $this->get_verification_type() === VerificationType::EMAIL ) {
					SessionUtils::add_email_submitted( $this->form_session_var, isset( $_POST['account_email'] ) ? sanitize_email( wp_unslash( $_POST['account_email'] ) ) : '' );  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					$user_id      = get_current_user_id();
					$update_email = array(
						'ID'         => $user_id,
						'user_email' => sanitize_email( wp_unslash( $_POST['account_email'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					);

					wp_update_user( $update_email );
					$this->unset_otp_session_variables();
				}
			} else {
				$errors->add( 'billing_invalid_otp_error', MoMessages::showMessage( MoMessages::INVALID_OTP ) );
				return $errors;
			}
		}

		/**
		 * This function checks the integrity of the phone or email value that was submitted
		 * with the form. It needs to match with the email or value that the OTP was
		 * initially sent to.
		 *
		 * @param object $errors -Errors in form submission.
		 */
		private function checkIntegrity( $errors ) {
			if ( $this->get_verification_type() === VerificationType::PHONE ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '' ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					$errors->add( 'billing_phone_mismatch_error', MoMessages::showMessage( MoMessages::PHONE_MISMATCH ) );
					return $errors;
				}
			}
			if ( $this->get_verification_type() === VerificationType::EMAIL ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, isset( $_POST['account_email'] ) ? sanitize_email( wp_unslash( $_POST['account_email'] ) ) : '' ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					$errors->add( 'billing_email_mismatch_error', MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ) );
					return $errors;
				}
			}
		}

		/**
		 * Register the Profile/Account Page script which will add the OTP button and field.
		 */
		public function miniorange_wc_ac_script() {
			wp_register_script( 'mowcac', MOV_URL . 'includes/js/mowcac.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'mowcac',
				'mowcac',
				array(
					'siteURL'     => wp_ajax_url(),
					'otpType'     => $this->otp_type === $this->type_phone_tag ? 'phone' : 'email',
					'nonce'       => wp_create_nonce( $this->nonce ),
					'buttontext'  => mo_( $this->button_text ),
					'imgURL'      => MOV_LOADER_URL,
					'generateURL' => $this->generate_otp_action,
					'fieldValue'  => $this->getUserData( $this->phone_key ),
					'phoneKey'    => $this->phone_key,
				)
			);
			wp_enqueue_script( 'mowcac' );
		}

		/**
		 * Get the user data in question from the WC database.
		 *
		 * @param string $key the usermeta key.
		 * @return string
		 */
		private function getUserData( $key ) {
			$current_user = wp_get_current_user();

			if ( $this->otp_type === $this->type_phone_tag ) {
				global $wpdb;
				$results = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `user_id` = %d", array( $key, $current_user->ID ) ) );//phpcs:ignore
				return ( isset( $results ) ) ? $results->meta_value : '';
			} else {
				return $current_user->user_email;
			}
		}

		/**
		 * Initialize function to send OTP.
		 *
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		public function startOtpVerificationProcess() {
			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ), MoConstants::ERROR_JSON_TYPE ) );
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->processPhoneAndSendOTP( $data );
			} else {
				$this->processEmailAndSendOTP( $data );
			}
		}

		/** Fetch the phone number entered by the user and start the otp verification process
		 *
		 * @param array $data the data posted by the user.
		 */
		private function processPhoneAndSendOTP( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_input', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->checkDuplicates( sanitize_text_field( $data['user_input'] ), $this->phone_key );
				SessionUtils::add_phone_verified( $this->form_session_var, sanitize_text_field( $data['user_input'] ) );
				$this->send_challenge( '', null, null, sanitize_text_field( $data['user_input'] ), VerificationType::PHONE );
			}
		}

		/** Fetch the Email address entered by the user and start the otp verification process
		 *
		 * @param array $data the data posted by the user.
		 */
		private function processEmailAndSendOTP( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_input', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, sanitize_email( $data['user_input'] ) );
				$this->send_challenge( '', sanitize_text_field( $data['user_input'] ), null, null, VerificationType::EMAIL );
			}
		}

		/**
		 * Check if admin has set the option where each user needs to have a unique
		 * phone number. If the option is set then make sure the phone number entered
		 * by the user is unique.
		 *
		 * @param string $value  Value to check against.
		 * @param string $key Key against against wich value is stored.
		 */
		private function checkDuplicates( $value, $key ) {
			if ( $this->restrict_duplicates && $this->isPhoneNumberAlreadyInUse( $value, $key ) ) {
				$message = MoMessages::showMessage( MoMessages::PHONE_EXISTS );
				wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
			}
		}

		/**
		 * This functions makes a database call to check if the phone number already exists for another user.
		 *
		 * @param string $phone - the user's phone number.
		 * @param string $key - meta_key to search for.
		 * @return bool
		 */
		private function isPhoneNumberAlreadyInUse( $phone, $key ) {
			global $wpdb;
			MoUtility::process_phone_number( $phone );
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return ! MoUtility::is_blank( $results );
		}

		/**
		 * Register the Profile/Account Page script which will add the OTP button and field.
		 */
		public function mo_add_phone_field_account_form() {

			woocommerce_form_field(  // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default WC function.
				'billing_phone',
				array(
					'type'     => 'text',
					'required' => true,
					'label'    => 'Phone Number',
				),
				get_user_meta( get_current_user_id(), 'billing_phone', true )
			);

			woocommerce_form_field(  // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default WC function.
				'enter_otp',
				array(
					'type'     => 'text',
					'required' => false,
					'label'    => 'Enter OTP',
				),
				get_user_meta( get_current_user_id(), 'enter_otp', true )
			);
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
		public function handle_post_verification( $redirect_to, $user_login, $user_email, $password, $phone_number,
											$extra_data, $otp_type ) {
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && ( $this->otp_type === $this->type_phone_tag ) ) {
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

			$this->is_form_enabled     = $this->sanitize_form_post( 'wc_profile_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'wc_profile_enable_type' );
			$this->button_text         = $this->sanitize_form_post( 'wc_profile_button_text' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'wc_profile_restrict_duplicates' );
			$this->phone_key           = $this->sanitize_form_post( 'wc_profile_phone_key' );

			update_mo_option( 'wc_profile_enable', $this->is_form_enabled );
			update_mo_option( 'wc_profile_enable_type', $this->otp_type );
			update_mo_option( 'wc_profile_button_text', $this->button_text );
			update_mo_option( 'wc_profile_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'wc_profile_phone_key', $this->phone_key );
		}
	}
}
