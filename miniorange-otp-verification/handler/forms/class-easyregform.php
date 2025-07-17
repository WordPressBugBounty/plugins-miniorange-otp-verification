<?php
/**
 * Load admin view for Elementor Pro Form.
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
 * This is the EasyRegForm class. This class handles all the
 * functionality related to EasyRegForm. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'EasyRegForm' ) ) {
	/**
	 * EasyRegForm class
	 */
	class EasyRegForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::EASY_REG_FORM;
			$this->type_phone_tag          = 'mo_easyreg_phone_enable';
			$this->type_email_tag          = 'mo_easyreg_email_enable';
			$this->form_key                = 'EASY_REG_FORM';
			$this->form_name               = mo_( 'Easy Registration Forms' );
			$this->is_form_enabled         = get_mo_option( 'easyreg_enable' );
			$this->button_text             = get_mo_option( 'easyreg_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->phone_form_id           = array();
			$this->generate_otp_action     = 'miniorange_easyreg_generate_otp';
			$this->validate_otp_action     = 'miniorange_easyreg_verify_otp';
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 **/
		public function handle_form() {
			$this->otp_type     = get_mo_option( 'easyreg_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'easyreg_forms' ) );
			if ( empty( $this->form_details ) ) {
				return;
			}

			foreach ( $this->form_details as $key => $value ) {
				array_push( $this->phone_form_id, '.verify_phone' );
			}
			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'startOtpVerificationProcess' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'startOtpVerificationProcess' ) );
			add_action( 'erf_register_front_scripts', array( $this, 'miniorange_register_easyreg_script' ) );
			if ( isset( $_POST['action'] ) && ( $_POST['action'] === 'erf_submit_form' ) ) { //phpcs:ignore
				if ( ! empty( $this->errors ) ) {
					return true;
				} else {
					$this->unset_otp_session_variables();
					return false;
				}
			}
			add_action( "wp_ajax_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_nopriv_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
		}
		/**
		 * Checks if the verification has started or not and then validates the
		 * OTP submitted.
		 */
		public function processFormAndValidateOTP() {
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
			$this->checkIfOTPSent();
			$this->checkIntegrityAndValidateOTP( $data );
		}
		/**
		 * Checks whether OTP sent or not.
		 */
		private function checkIfOTPSent() {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}

		/**
		 * This function hooks into the hook and checks if
		 * OTP verification has been started by checking if the session variable
		 * has been set in session.
		 *
		 * @param array $data - this is the variable containing the form data.
		 */
		private function checkIntegrityAndValidateOTP( $data ) {
			$this->checkIntegrity( $data );
			$this->validate_challenge( $this->get_verification_type(), null, $data['otp_token'] );
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				if ( $this->get_verification_type() === VerificationType::PHONE ) {
					SessionUtils::add_phone_submitted( $this->form_session_var, $data['user_phone'] );
				}
				if ( $this->get_verification_type() === VerificationType::EMAIL ) {
					SessionUtils::add_email_submitted( $this->form_session_var, $data['user_email'] );
				}
				wp_send_json(
					MoUtility::create_json(
						MoConstants::SUCCESS_JSON_TYPE,
						'success1'
					)
				);
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::INVALID_OTP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}
		/**
		 * Check Integrity of the email or phone number. i.e. Ensure that the Email or
		 * Phone that the OTP was sent to is the same Email or Phone that is being submitted
		 * with the form.
		 * Once integrity check passes validate the OTP to ensure that the user has entered
		 * the correct OTP.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function checkIntegrity( $data ) {
			if ( $this->get_verification_type() === VerificationType::PHONE ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, $data['user_phone'] ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			}
			if ( $this->get_verification_type() === VerificationType::EMAIL ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, $data['user_email'] ) ) {
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
		 * Unset all session variables used
		 *
		 * @param mixed $transdata .
		 */
		public function unset_sessionVariable( $transdata ) {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
			}
			return $transdata;
		}

		/**
		 * Enqueue elementor script
		 *
		 * @return void
		 */
		public function miniorange_register_easyreg_script() {
			wp_register_script( 'moeasyreg', MOV_URL . 'includes/js/moeasyreg.min.js', array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'moeasyreg',
				'moeasyreg',
				array(
					'siteURL'     => wp_ajax_url(),
					'otpType'     => $this->otp_type,
					'formkey'     => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phonekey' : 'emailkey',
					'nonce'       => wp_create_nonce( $this->_nonce ),
					'buttontext'  => mo_( $this->button_text ),
					'fieldID'     => $this->otp_type === $this->type_phone_tag ? 'verify_phone' : 'verify_email',
					'imgURL'      => MOV_LOADER_URL,
					// 'jspath'        => MOV_URL . 'includes/js/moeasyreg1.js',
					'forms'       => $this->form_details,
					'generateURL' => $this->generate_otp_action,
					'vaction'     => $this->validate_otp_action,
				)
			);
			wp_enqueue_script( 'moeasyreg' );
		}

		/**
		 * The function is used to check if user has provided an email
		 * address in the form to initiate email verification.
		 */
		public function startOtpVerificationProcess() {
			if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
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
		public function mo_processEmailAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $data['user_email'] );
				$this->send_challenge( '', $data['user_email'], null, null, VerificationType::EMAIL );
			}
		}
		/**
		 * The function is used to check if user has provided an phone number
		 * address in the form to initiate SMS verification.
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		public function mo_processPhoneAndStartOTPVerificationProcess( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				SessionUtils::add_phone_verified( $this->form_session_var, $data['user_phone'] );
				$this->send_challenge( '', null, null, $data['user_phone'], VerificationType::PHONE );
			}
		}

		/**
		 * This function hooks into the otp_verification_failed hook. This function
		 * details what is done if the OTP verification fails.
		 *
		 * @param string $user_login the username posted by the user.
		 * @param string $user_email the email posted by the user.
		 * @param string $phone_number the phone number posted by the user.
		 * @param string $otp_type the verification type .
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
		 * Unset all session variables used
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
		 * @param string $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the frm Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			if ( ! array_key_exists( 'easyreg_form', $_POST ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			$this->is_form_enabled = $this->sanitize_form_post( 'easyreg_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'easyreg_enable_type' );
			$this->button_text     = $this->sanitize_form_post( 'easyreg_button_text' );

			$form = $this->parseFormDetails( $data );

			$this->form_details = ! empty( $form ) ? $form : '';

			update_mo_option( 'easyreg_enable', $this->is_form_enabled );
			update_mo_option( 'easyreg_enable_type', $this->otp_type );
			update_mo_option( 'easyreg_button_text', $this->button_text );
			update_mo_option( 'easyreg_forms', maybe_serialize( $this->form_details ) );
		}


		/**
		 * To parse the form details from settings page
		 *
		 * @param array $data the data posted while savig the form.
		 *
		 * @return array
		 */
		private function parseFormDetails( $data ) {
			$form = array();
			foreach ( array_filter( $data['easyreg_form']['form'] ) as $key => $value ) {
				$form[ $value ] = array(
					'emailkey'    => $data['easyreg_form']['emailkey'][ $key ],
					'phonekey'    => $data['easyreg_form']['phonekey'][ $key ],
					'verifyKey'   => $data['easyreg_form']['verifyKey'][ $key ],
					'phone_show'  => $data['easyreg_form']['phonekey'][ $key ],
					'email_show'  => $data['easyreg_form']['emailkey'][ $key ],
					'verify_show' => $data['easyreg_form']['verifyKey'][ $key ],
				);
			}
			return $form;
		}

	}
}
