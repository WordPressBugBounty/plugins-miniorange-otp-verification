<?php
/**
 * Load admin view for Classify Registration Form.
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
use OTP\Traits\Instance;
use OTP\Objects\BaseMessages;
use ReflectionException;

/**
 * This is the Classify Theme Registration class. This class handles all the
 * functionality related to Classify Theme Registration form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'ClassifyRegistrationForm' ) ) {
	/**
	 * ClassifyRegistrationForm class
	 */
	class ClassifyRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::CLASSIFY_REGISTER;
			$this->type_phone_tag          = 'classify_phone_enable';
			$this->type_email_tag          = 'classify_email_enable';
			$this->form_key                = 'CLASSIFY_REGISTER';
			$this->form_name               = mo_( 'Classify Theme Registration Form' );
			$this->is_form_enabled         = get_mo_option( 'classify_enable' );
			$this->phone_form_id           = 'input[name=phone]';
			$this->form_documents          = MoFormDocs::CLASSIFY_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 **/
		public function handle_form() {
			$this->otp_type = get_mo_option( 'classify_type' );

			add_action( 'wp_enqueue_scripts', array( $this, 'show_phone_field_on_page' ) );
			add_action( 'user_register', array( $this, 'save_phone_number' ), 10, 1 );

			$this->routeData();
		}

		/**
		 * Function to map action
		 */
		public function routeData() {
			if ( ! wp_verify_nonce( MoUtility::sanitize_check( 'mo_classify_theme_nonce', $_POST ), $this->nonce ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
			} elseif ( MoUtility::sanitize_check( 'option', $data ) === 'verify_user_classify' ) {
				$this->handle_classify_theme_form_post( $data );
			}
		}

		/**
		 * Function hooks into the Classify scripts to the page necessary to make the OTP Verification work. The script
		 * adds a button under the phone number or email fields and a verification field
		 * for entering the verification code.
		 */
		public function show_phone_field_on_page() {
			wp_register_script( 'classifyscript', MOV_URL . 'includes/js/classify.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'classifyscript',
				'classifyscript',
				array(
					'nonce' => wp_create_nonce( $this->nonce ),
				)
			);
			wp_enqueue_script( 'classifyscript' );
		}

		/**
		 * This function is used to send OTP to the user's phone and setting it in
		 * session for future validation.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 */
		public function handle_classify_theme_form_post( $data ) {
			$username = sanitize_text_field( $data['username'] );
			$email_id = sanitize_email( $data['email'] );
			$phone    = sanitize_text_field( $data['phone'] );

			if ( username_exists( $username ) !== false ) {
				return;
			}
			if ( email_exists( $email_id ) !== false ) {
				return;
			}

			MoUtility::initialize_transaction( $this->form_session_var );

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->send_challenge( sanitize_text_field( $data['username'] ), $email_id, null, $phone, 'phone', null, null );
			} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$this->send_challenge( sanitize_text_field( $data['username'] ), $email_id, null, null, 'email', null, null );
			} else {
				$this->send_challenge( sanitize_text_field( $data['username'] ), $email_id, null, $phone, 'both', null, null );
			}
		}

		/**
		 * Function to save phone number in database.
		 *
		 * @param string $user_id fetching userid.
		 **/
		public function save_phone_number( $user_id ) {

			$phone_number = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( $phone_number ) {
				update_user_meta( $user_id, 'phone', $phone_number );
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
			$otp_ver_type = strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phone'
						: ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ? 'email' : 'both' );
			$from_both    = strcasecmp( $otp_ver_type, 'both' ) === 0 ? true : false;
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
			SessionUtils::unset_session( array( $this->form_session_var, $this->tx_session_id ) );
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

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the Classify Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->otp_type        = $this->sanitize_form_post( 'classify_type' );
			$this->is_form_enabled = $this->sanitize_form_post( 'classify_enable' );

			update_mo_option( 'classify_enable', $this->is_form_enabled );
			update_mo_option( 'classify_type', $this->otp_type );
		}
	}
}
