<?php
/**
 * Load admin view for Visual Form Builder form.
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
use OTP\Objects\BaseMessages;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the visual Form Builder class. This class handles all the
 * functionality related to visual Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'VisualFormBuilder' ) ) {
	/**
	 * VisualFormBuilder class
	 */
	class VisualFormBuilder extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::VISUAL_FORM;
			$this->type_phone_tag          = 'mo_visual_form_phone_enable';
			$this->type_email_tag          = 'mo_visual_form_email_enable';
			$this->type_both_tag           = 'mo_visual_form_both_enable';
			$this->form_key                = 'VISUAL_FORM';
			$this->form_name               = mo_( 'Visual Form Builder' );
			$this->phone_form_id           = array();
			$this->is_form_enabled         = get_mo_option( 'visual_form_enable' );
			$this->button_text             = get_mo_option( 'visual_form_sendotp_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->enter_otp_text          = get_mo_option( 'visual_form_enterotp_field_text' );
			$this->enter_otp_text          = ! MoUtility::is_blank( $this->enter_otp_text ) ? $this->enter_otp_text : mo_( 'Enter OTP Here' );
			$this->generate_otp_action     = 'miniorange-vf-send-otp';
			$this->validate_otp_action     = 'miniorange-vf-verify-code';
			$this->form_documents          = MoFormDocs::VISUAL_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type     = get_mo_option( 'visual_form_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'visual_form_otp_enabled' ) );
			if ( empty( $this->form_details ) || ! $this->is_form_enabled ) {
				return;
			}
			foreach ( $this->form_details as $key => $value ) {
				array_push( $this->phone_form_id, '#' . $value['phonekey'] );
			}
			add_action( 'wp_enqueue_scripts', array( $this, 'mo_enqueue_vf' ) );
			add_action( "wp_ajax_{$this->generate_otp_action}", array( $this, 'mo_send_otp_vf_ajax()' ) );
			add_action( "wp_ajax_nopriv_{$this->generate_otp_action}", array( $this, 'mo_send_otp_vf_ajax' ) );
			add_action( "wp_ajax_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_nopriv_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
		}

		/**
		 * Function to register script and localize variables and add the script to the frontend
		 */
		public function mo_enqueue_vf() {
			wp_register_script( 'vfscript', MOV_URL . 'includes/js/vfscript.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'vfscript',
				'movfvar',
				array(
					'siteURL'     => wp_ajax_url(),
					'otpType'     => strcasecmp( $this->otp_type, $this->type_phone_tag ),
					'formDetails' => $this->form_details,
					'buttontext'  => $this->button_text,
					'imgURL'      => MOV_LOADER_URL,
					'fieldText'   => mo_( $this->enter_otp_text ),
					'gnonce'      => wp_create_nonce( $this->nonce ),
					'nonceKey'    => wp_create_nonce( $this->nonce_key ),
					'vnonce'      => wp_create_nonce( $this->nonce ),
					'gaction'     => $this->generate_otp_action,
					'vaction'     => $this->validate_otp_action,
				)
			);
			wp_enqueue_script( 'vfscript' );
		}

		/**
		 * To send OTP to email or phone
		 *
		 * @throws ReflectionException .
		 */
		public function mo_send_otp_vf_ajax() {
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
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->mo_send_vf_otp_to_phone( $data );
			} else {
				$this->mo_send_vf_otp_to_email( $data );
			}
		}

		/**
		 * Makes call to start OTP verification for Phone Type.
		 *
		 * @param array $data the posted data in the AJAX call.
		 * @throws ReflectionException .
		 */
		public function mo_send_vf_otp_to_phone( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->startOTPVerification( sanitize_text_field( trim( $data['user_phone'] ) ), null, sanitize_text_field( trim( $data['user_phone'] ) ), VerificationType::PHONE );
			}
		}

		/**
		 * Makes call to start OTP verification for Email Type.
		 *
		 * @param array $data the post data coming in the AJAX call.
		 * @throws ReflectionException .
		 */
		public function mo_send_vf_otp_to_email( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->startOTPVerification( sanitize_email( $data['user_email'] ), sanitize_email( $data['user_email'] ), null, VerificationType::EMAIL );
			}
		}

		/**
		 * Sets the phone or email value to be authenticated in session and
		 * initiates the process for OTP Verification.
		 *
		 * @param string $session_value the email or phone for which the OTP has been sent.
		 * @param string $user_email user email entered to be verified.
		 * @param string $phone_number user phone number entered to be verified.
		 * @param string $otp_type OTP entered to verify.
		 * @throws ReflectionException .
		 */
		private function startOTPVerification( $session_value, $user_email, $phone_number, $otp_type ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( VerificationType::PHONE === $otp_type ) {
				SessionUtils::add_phone_verified( $this->form_session_var, $session_value );
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $session_value );
			}
			$this->send_challenge( '', $user_email, null, $phone_number, $otp_type );
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
			$this->checkIfVerificationNotStarted();
			$this->checkIntegrityAndValidateOTP( $data );
		}

		/**
		 * Checks if verification not started
		 */
		public function checkIfVerificationNotStarted() {
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
		 * Checks the integrity and calls the {@code mo_validate_otp} hook to validate
		 * the otp being submitted with the form.
		 *
		 * @param array $post   the post data containing the otp.
		 */
		private function checkIntegrityAndValidateOTP( $post ) {

			$this->checkIntegrity( $post );
			$this->validate_challenge( $this->get_verification_type(), null, sanitize_text_field( $post['otp_token'] ) );
		}

		/**
		 * Checks if the submitted phone number or email is same as the one
		 * to which OTP was sent.
		 *
		 * @param array $post   the post data containing the otp.
		 */
		private function checkIntegrity( $post ) {
			if ( $this->isPhoneVerificationEnabled() ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $post['sub_field'] ) ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			} elseif ( ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_text_field( $post['sub_field'] ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
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

			wp_send_json(
				MoUtility::create_json(
					MoUtility::get_invalid_otp_method(),
					MoConstants::ERROR_JSON_TYPE
				)
			);
		}
		/**
		 * This function is called to handle what needs to be done if OTP
		 * entered by the user is validated successfully. Calls an action
		 * which could be hooked into to process this elsewhere. Check each
		 * handle_post_verification of each form handler.
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
			wp_send_json(
				MoUtility::create_json(
					MoConstants::SUCCESS,
					MoConstants::SUCCESS_JSON_TYPE
				)
			);
		}

		/**
		 * Unset all session variables used
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
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled && $this->isPhoneVerificationEnabled() ) {
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * To check if phone verification is enabled
		 *
		 * @return boolean
		 */
		private function isPhoneVerificationEnabled() {
			$otp_ver_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_ver_type || VerificationType::BOTH === $otp_ver_type;
		}


		/**
		 * Handles saving all the visual Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'visual-form-builder/visual-form-builder.php' ) ) {
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );
			$form = $this->parseFormDetails( $data );

			$this->is_form_enabled = $this->sanitize_form_post( 'visual_form_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'visual_form_enable_type' );
			$this->form_details    = ! empty( $form ) ? $form : '';
			$this->button_text     = $this->sanitize_form_post( 'visual_form_sendotp_button_text' );
			$this->enter_otp_text  = $this->sanitize_form_post( 'visual_form_enterotp_field_text' );

			if ( $this->basic_validation_check( BaseMessages::VISUAL_FORM_CHOOSE ) ) {
				update_mo_option( 'visual_form_sendotp_button_text', $this->button_text );
				update_mo_option( 'visual_form_enterotp_field_text', $this->enter_otp_text );
				update_mo_option( 'visual_form_enable', $this->is_form_enabled );
				update_mo_option( 'visual_form_enable_type', $this->otp_type );
				update_mo_option( 'visual_form_otp_enabled', maybe_serialize( $this->form_details ) );
			}
		}



		/**
		 * To parse the form details from settings page
		 *
		 * @param array $data post data.
		 * @return array
		 */
		public function parseFormDetails( $data ) {
			$form = array();
			if ( ! array_key_exists( 'visual_form', $data ) ) {
				return array();
			}

			$data = MoUtility::mo_sanitize_array( $data );

			foreach ( array_filter( $data['visual_form']['form'] ) as $key => $value ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				$form[ $value ] = array(
					'emailkey'   => $this->getFieldID( sanitize_text_field( $data['visual_form']['emailkey'][ $key ] ), $value ),
					'phonekey'   => $this->getFieldID( sanitize_text_field( $data['visual_form']['phonekey'][ $key ] ), $value ),
					'phone_show' => sanitize_text_field( $data['visual_form']['phonekey'][ $key ] ),
					'email_show' => sanitize_text_field( $data['visual_form']['emailkey'][ $key ] ),
				);
			}
			return $form;
		}

		/**
		 * Returns ID of the field from db
		 *
		 * @param  string $key       Label of the field.
		 * @param  string $form_id   Form ID.
		 * @return string
		 */
		private function getFieldID( $key, $form_id ) {
			global $wpdb;

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}visual_form_builder_fields` WHERE `field_name` = %s AND `form_id` = %d;", array( $key, intval( $form_id ) ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.			
			return ! MoUtility::is_blank( $result ) ? 'vfb-' . $result->field_id : '';
		}

	}
}
