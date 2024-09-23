<?php
/**
 * Load admin view for Custom form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the Custom Form class. This class handles all the
 * functionality related to Custom Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'CustomForm' ) ) {
	/**
	 * CustomForm class
	 */
	class CustomForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Action variable for form submission.
		 *
		 * @var string
		 */
		protected $check_validated_on_submit;

		/**
		 * Action variable for OTP Verification
		 *
		 * @var string
		 */
		protected $validated;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form   = false;
			$this->is_ajax_form              = true;
			$this->is_add_on_form            = true;
			$this->form_session_var          = FormSessionVars::CUSTOMFORM;
			$this->type_phone_tag            = 'mo_customForm_phone_enable';
			$this->type_email_tag            = 'mo_customForm_email_enable';
			$this->is_form_enabled           = get_mo_option( 'cf_submit_id', 'mo_otp_' ) ? true : false;
			$this->phone_form_id             = stripslashes( get_mo_option( 'cf_field_id', 'mo_otp_' ) );
			$this->generate_otp_action       = 'miniorange-customForm-send-otp';
			$this->validate_otp_action       = 'miniorange-customForm-verify-code';
			$this->check_validated_on_submit = 'miniorange-customForm-verify-submit';
			$this->otp_type                  = get_mo_option( 'cf_enable_type', 'mo_otp_' );
			$this->button_text               = get_mo_option( 'cf_button_text', 'mo_otp_' );
			$this->button_text               = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );

			$this->validated = false;
			parent::__construct();
			$this->handle_form();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes.
		 * all the class variables. This function also defines all the hooks to.
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException .
		 */
		public function handle_form() {
			MoPHPSessions::check_session();
			if ( ! $this->is_form_enabled ) {
				return;
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'mo_enqueue_form_script' ) );
			add_action( 'login_enqueue_scripts', array( $this, 'mo_enqueue_form_script' ) );
			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'mo_send_otp' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'mo_send_otp' ) );
			add_action( "wp_ajax_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_nopriv_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_{$this->check_validated_on_submit}", array( $this, 'mo_check_validated_on_submit' ) );
			add_action( "wp_ajax_nopriv_{$this->check_validated_on_submit}", array( $this, 'mo_check_validated_on_submit' ) );

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->validated = true;
				$this->unset_otp_session_variables();
				return;

			}
		}

		/**
		 * This function is used to enqueue script on the frontend to facilitate
		 * OTP Verification for the custom form. This function
		 * also localizes certain values required by the script.
		 */
		public function mo_enqueue_form_script() {
			wp_register_script( $this->form_session_var, MOV_URL . 'includes/js/' . $this->form_session_var . '.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				$this->form_session_var,
				$this->form_session_var,
				array(
					'siteURL'        => wp_ajax_url(),
					'otpType'        => $this->get_verification_type(),
					'formDetails'    => $this->form_details,
					'buttontext'     => $this->button_text,
					'imgURL'         => MOV_LOADER_URL,
					'fieldText'      => mo_( 'Enter OTP here' ),
					'gnonce'         => wp_create_nonce( $this->nonce ),
					'nonceKey'       => wp_create_nonce( $this->nonce_key ),
					'vnonce'         => wp_create_nonce( $this->nonce ),
					'gaction'        => $this->generate_otp_action,
					'vaction'        => $this->validate_otp_action,
					'fieldSelector'  => stripcslashes( get_mo_option( 'cf_field_id', 'mo_otp_' ) ),
					'submitSelector' => stripcslashes( get_mo_option( 'cf_submit_id', 'mo_otp_' ) ),
					'siteURL'        => wp_ajax_url(),
					'saction'        => $this->check_validated_on_submit,
				)
			);
			wp_enqueue_script( $this->form_session_var );
			wp_enqueue_style( 'mo_forms_css', MOV_FORM_CSS, MOV_VERSION, true );

		}

		/**
		 * Calls the Gateway specific mo_send_otp_token function
		 *
		 * @return void
		 */
		public function mo_send_otp() {
			if ( ! check_ajax_referer( $this->nonce, 'action', false ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			MoPHPSessions::check_session();
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				MoUtility::initialize_transaction( $this->form_session_var );
			}
			if ( MoUtility::sanitize_check( 'otpType', $data ) === VerificationType::PHONE ) {
				$this->mo_processPhoneAndSendOTP( $data );
			}
			if ( MoUtility::sanitize_check( 'otpType', $data ) === VerificationType::EMAIL ) {
				$this->mo_processEmailAndSendOTP( $data );
			}
		}

		/**
		 * Check the form fields validation on the submission.
		 */
		public function mo_check_validated_on_submit() {

			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) || $this->validated ) {
				wp_send_json(
					MoUtility::create_json(
						self::VALIDATED,
						MoConstants::SUCCESS_JSON_TYPE
					)
				);
			} elseif ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) && ! $this->validated ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}

		/**
		 * The function is used to check if user has provided an email
		 * address in the form to initiate email verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function mo_processEmailAndSendOTP( $data ) {
			MoPHPSessions::check_session();
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, sanitize_email( $data['user_email'] ) );
				$this->send_challenge( '', sanitize_email( $data['user_email'] ), null, null, VerificationType::EMAIL );
			}
		}


		/**
		 * The function is used to check if user has provided an phone number
		 * address in the form to initiate SMS verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function mo_processPhoneAndSendOTP( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
					SessionUtils::add_phone_verified( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) );
				$this->send_challenge( '', null, null, sanitize_text_field( $data['user_phone'] ), VerificationType::PHONE );
			}
		}

		/**
		 * The function is used to check if user has provided correct
		 * form details in the form to initiate OTP verification.
		 */
		public function processFormAndValidateOTP() {
			if ( ! check_ajax_referer( $this->nonce, 'action', false ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			MoPHPSessions::check_session();
			$this->checkIfOTPSent();
			$this->checkIntegrityAndValidateOTP( $data );
		}

		/**
		 * The function is used to check if user has provided
		 * OTP to initiate OTP verification.
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
		 * <br/<br/>
		 * Once integrity check passes validate the OTP to ensure that the user has entered
		 * the correct OTP.
		 *
		 * @param Form  array $data .
		 */
		private function checkIntegrityAndValidateOTP( $data ) {
			MoPHPSessions::check_session();
			$this->checkIntegrity( $data );
			$this->validate_challenge( sanitize_text_field( $data['otpType'] ), null, sanitize_text_field( $data['otp_token'] ) );
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, sanitize_text_field( $data['otpType'] ) ) ) {
				if ( VerificationType::PHONE === $data['otpType'] ) {
					SessionUtils::add_phone_submitted( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) );
				}
				if ( VerificationType::EMAIL === $data['otpType'] ) {
					SessionUtils::add_email_submitted( $this->form_session_var, sanitize_email( $data['user_email'] ) );
				}
												wp_send_json(
													MoUtility::create_json(
														MoMessages::showMessage( MoMessages::CUSTOM_FORM_MESSAGE ),
														MoConstants::ERROR_JSON_TYPE
													)
												);
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::CUSTOM_FORM_MESSAGE ),
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
		 * @param array $data .
		 */
		private function checkIntegrity( $data ) {
			if ( VerificationType::PHONE === $data['otpType'] ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			}
			if ( VerificationType::EMAIL === $data['otpType'] ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $data['user_email'] ) ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			}
		}

		/**
		 * This function hooks into the otp_verification_failed hook. This function is
		 * details what needs to be done if OTP Verification is failed.
		 *
		 * @param array $user_login the username posted by the user.
		 * @param array $user_email the email posted by the user.
		 * @param array $phone_number the phone number posted by the user.
		 * @param array $otp_type the verification type.
		 * @return void
		 */
		public function handle_failed_verification( $user_login, $user_email, $phone_number, $otp_type ) {
			MoPHPSessions::check_session();
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
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
			MoPHPSessions::check_session();
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
			SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			MoPHPSessions::check_session();
			SessionUtils::unset_session( array( $this->form_session_var, $this->tx_session_id ) );
		}


		/**
		 * This function is called by the filter mo_phone_dropdown_selector.
		 * to return the Jquery selector of the phone field. The function will.
		 * push the formID to the selector array if OTP Verification for the.
		 * form has been enabled.
		 *
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->isPhoneEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Function check if Phone verification is enabled.
		 */
		private function isPhoneEnabled() {
			return $this->get_verification_type() === VerificationType::PHONE ? true : false;
		}

		/**
		 * This function is not required as the values are getting updated through MoActionHandlerHandler.php file.
		 */
		public function handle_form_options() {

		}

		/**
		 * Function submits the field key details.
		 */
		public function getSubmitKeyDetails() {
			return stripcslashes( get_mo_option( 'cf_submit_id', 'mo_otp_' ) ); }

		/**
		 * Funtion used to fetch the form key details.
		 */
		public function get_field_key_details() {
			return stripcslashes( get_mo_option( 'cf_field_id', 'mo_otp_' ) ); }
	}
}
