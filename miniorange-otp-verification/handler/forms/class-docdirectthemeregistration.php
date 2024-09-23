<?php
/**
 * Load admin view for Doc Direct Theme Registration Form.
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
use OTP\Objects\BaseMessages;

/**
 * This is the DocDirect Theme Registration class. This class handles all the
 * functionality related to DocDirect Theme Registration. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'DocDirectThemeRegistration' ) ) {
	/**
	 * DocDirectThemeRegistration class
	 */
	class DocDirectThemeRegistration extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::DOCDIRECT_REG;
			$this->type_phone_tag          = 'mo_docdirect_phone_enable';
			$this->type_email_tag          = 'mo_docdirect_email_enable';
			$this->form_key                = 'DOCDIRECT_THEME';
			$this->form_name               = mo_( 'Doc Direct Theme by ThemoGraphics' );
			$this->is_form_enabled         = get_mo_option( 'docdirect_enable' );
			$this->phone_form_id           = 'input[name=phone_number]';
			$this->form_documents          = MoFormDocs::DOCDIRECT_THEME;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 **/
		public function handle_form() {
			$this->otp_type = get_mo_option( 'docdirect_enable_type' );
			add_action( 'wp_enqueue_scripts', array( $this, 'addScriptToRegistrationPage' ) );
			add_action( 'wp_ajax_docdirect_user_registration', array( $this, 'mo_validate_docdirect_user_registration' ), 1 );
			add_action( 'wp_ajax_nopriv_docdirect_user_registration', array( $this, 'mo_validate_docdirect_user_registration' ), 1 );
			$this->routeData();
		}
		/**
		 * Function to map action
		 */
		public function routeData() {
			if ( ! array_key_exists( 'option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}
			switch ( trim( sanitize_text_field( wp_unslash( $_GET['option'] ) ) ) ) { // phpcs:ignore -- false positive.
				case 'miniorange-docdirect-verify':
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
					$this->startOTPVerificationProcess( $data );
					break;
			}
		}

		/**
		 * Function hooks into the doc direct user_authentication shortcode to append
		 * scripts to the page necessary to make the OTP Verification work. The script
		 * adds a button under the phone number or email fields and a verification field
		 * for entering the verification code.
		 */
		public function addScriptToRegistrationPage() {
			wp_register_script( 'docdirect', MOV_URL . 'includes/js/docdirect.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'docdirect',
				'modocdirect',
				array(
					'imgURL'      => MOV_URL . 'includes/images/loader.gif',
					'buttonText'  => mo_( 'Click Here to Verify Yourself' ),
					'insertAfter' => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'input[name=phone_number]' : 'input[name=email]',
					'placeHolder' => mo_( 'OTP Code' ),
					'siteURL'     => site_url(),
				)
			);
			wp_enqueue_script( 'docdirect' );
		}



		/**
		 * This function is used to send OTP to the user's email or phone
		 * by checking what kind of otp has been enabled by the admin.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		public function startOtpVerificationProcess( $data ) {

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
		public function send_otp_to_phone( $data ) {
			if ( array_key_exists( 'user_phone', $data ) && ! MoUtility::is_blank( $data['user_phone'] ) ) {
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
		public function send_otp_to_email( $data ) {
			if ( array_key_exists( 'user_email', $data ) && ! MoUtility::is_blank( $data['user_email'] ) ) {
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
		 * Process the form being submitted. Check for the verification
		 * code if entered any by the user.
		 */
		public function mo_validate_docdirect_user_registration() {
			$data = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			$this->checkIfVerificationNotStarted( $data );
			$this->checkIfVerificationCodeNotEntered( $data );
			$this->handle_otp_token_submitted( $data );
		}

		/**
		 * This function checks if verification codes was entered in the form
		 * by the user and handles what needs to be done if verification code
		 * was not entered by the user.
		 */
		public function checkIfVerificationNotStarted() {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				echo wp_json_encode(
					array(
						'type'    => 'error',
						'message' => MoMessages::showMessage( MoMessages::DOC_DIRECT_VERIFY ),
					)
				);
				die();
			}
		}

		/**
		 * Checks if the verification code was not entered by the user.
		 * If no verification code was entered then throw an error to the user.
		 *
		 *  @param string $data - sanitized data from post request.
		 */
		public function checkIfVerificationCodeNotEntered( $data ) {
			if ( ! array_key_exists( 'mo_verify', $data ) || MoUtility::is_blank( sanitize_text_field( $data['mo_verify'] ) ) ) {
				echo wp_json_encode(
					array(
						'type'    => 'error',
						'message' => MoMessages::showMessage( MoMessages::DCD_ENTER_VERIFY_CODE ),
					)
				);
				die();
			}
		}


		/**
		 * Validate if the phone number or email otp was sent to and
		 * the phone number and email in the final submission are the
		 * same. If not then throw an error.
		 *
		 *  @param string $data - sanitized data from post request.
		 */
		public function handle_otp_token_submitted( $data ) {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->process_phone_number( $data );
			} else {
				$this->processEmail( $data );
			}
			$this->validate_challenge( $this->get_verification_type(), 'mo_verify', null );
		}

		/**
		 * Check to see if email address OTP was sent to and the phone number
		 * submitted in the final form submission are the same.
		 *
		 * @param array $data post data.
		 */
		public function process_phone_number( $data ) {

			if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $data['phone_number'] ) ) ) {
				echo wp_json_encode(
					array(
						'type'    => 'error',
						'message' => MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
					)
				);
				die();
			}
		}

		/**
		 * Check to see if email address OTP was sent to and the phone number
		 * submitted in the final form submission are the same.
		 *
		 * @param array $data post data.
		 */
		public function processEmail( $data ) {

			if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $data['email'] ) ) ) {
				echo wp_json_encode(
					array(
						'type'    => 'error',
						'message' => MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
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

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
			echo wp_json_encode(
				array(
					'type'    => 'error',
					'message' => MoUtility::get_invalid_otp_method(),
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
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array The selector
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && ( $this->otp_type === $this->type_phone_tag ) ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the DocDirect Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->otp_type        = $this->sanitize_form_post( 'docdirect_enable_type' );
			$this->is_form_enabled = $this->sanitize_form_post( 'docdirect_enable' );

			update_mo_option( 'docdirect_enable', $this->is_form_enabled );
			update_mo_option( 'docdirect_enable_type', $this->otp_type );
		}
	}
}
