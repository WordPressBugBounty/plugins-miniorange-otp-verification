<?php
/**
 * Handler Functions for Ultimate Membership Pro Form
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Objects\BaseMessages;
use OTP\Helper\MoMessages;
use OTP\Helper\MoConstants;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the Ultimate Pro Registration Form class. This class handles all the
 * functionality related to Ultimate Pro Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'UltimateProRegistrationForm' ) ) {
	/**
	 * UltimateProRegistrationForm class
	 */
	class UltimateProRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Undocumented function
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::ULTIMATE_PRO;
			$this->phone_form_id           = 'input[name=phone]';
			$this->form_key                = 'ULTIMATE_MEM_PRO';
			$this->type_phone_tag          = 'mo_ultipro_phone_enable';
			$this->type_email_tag          = 'mo_ultipro_email_enable';
			$this->form_name               = mo_( 'Ultimate Membership Pro Form' );
			$this->is_form_enabled         = get_mo_option( 'ultipro_enable' );
			$this->form_documents          = MoFormDocs::UM_PRO_LINK;
			$this->validate_otp_action     = 'miniorange-ulti-membership-pro-verify-code';
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
			$this->otp_type = get_mo_option( 'ultipro_type' );
			if ( $this->is_form_enabled ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'register_ulti_verify_script' ) );
			}
			add_action( "wp_ajax_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );
			add_action( "wp_ajax_nopriv_{$this->validate_otp_action}", array( $this, 'processFormAndValidateOTP' ) );

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				add_shortcode( 'mo_phone', array( $this, 'phone_shortcode' ) );
			}
			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				add_shortcode( 'mo_email', array( $this, 'email_shortcode' ) );
			}

			$this->routeData();
		}


		/**
		 * * @throws ReflectionException Adds exception.
		 */
		private function routeData() {
			if ( ! array_key_exists( 'option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}
			switch ( trim( sanitize_text_field( wp_unslash( $_GET['option'] ) ) ) ) { // phpcs:ignore -- false positive.
				case 'miniorange-ulti':
					if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoMessages::showMessage( BaseMessages::INVALID_OP ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
						exit;
					}
					$this->handle_ulti_form( MoUtility::mo_sanitize_array( $_POST ) );
					break;
			}
		}

		/**
		 * The HTML and script that needs to be added to the page to make the SMS Verification possible
		 */
		public function phone_shortcode() {
			$img  = "<div class= 'moloader'></div>";
			$div  = "<div style='margin-top: 2%;'><button type='button' class='button alt' style='width:100%;height:30px;";
			$div .= "font-family: Roboto;font-size: 12px !important;' id='miniorange_otp_token_submit' title='Please Enter an phone to enable this.'>";
			$div .= "Click Here to Verify Phone</button></div><div style='margin-top:2%'><div id='mo_message' hidden='' ";
			$div .= "style='background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;''></div></div>";

			$html  = '<script>jQuery(document).ready(function(){$mo=jQuery; var divElement = "' . $div . '"; ';
			$html .= '$mo("input[name=phone]").change(function(){ if(!$mo(this).val()){ $mo("#miniorange_otp_token_submit").prop("disabled",true);';
			$html .= ' }else{ $mo("#miniorange_otp_token_submit").prop("disabled",false); } });';
			$html .= ' $mo(divElement).insertAfter($mo( "input[name=phone]")); $mo("#miniorange_otp_token_submit").click(function(o){ ';
			$html .= 'var e=$mo("input[name=phone]").val(); $mo("#mo_message").empty(),$mo("#mo_message").append("' . $img . '"),';
			$html .= '$mo("#mo_message").show(),$mo.ajax({url:"' . site_url() . '/?option=miniorange-ulti",type:"POST",';
			$html .= 'data:{security:"' . wp_create_nonce( $this->nonce ) . '", user_phone:e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result=="success"){$mo("#mo_message").empty(),';
			$html .= '$mo("#mo_message").append(o.message),$mo("#mo_message").css("border-top","3px solid green"),';
			$html .= '$mo("input[name=email_verify]").focus()}else{$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),';
			$html .= '$mo("#mo_message").css("border-top","3px solid red"),$mo("input[name=phone_verify]").focus()} ;},';
			$html .= 'error:function(o,e,n){}})});});</script>';
			return $html;
		}


		/**
		 * The HTML and script that needs to be added to the page to make the Email Verification possible
		 */
		public function email_shortcode() {
			$img   = "<div class= 'moloader'></div>";
			$div   = "<div style='margin-top: 2%;'><button type='button' disabled='disabled' class='button alt' ";
			$div  .= "style='width:100%;height:30px;font-family: Roboto;font-size: 12px !important;' id='miniorange_otp_token_submit' ";
			$div  .= "title='Please Enter an email to enable this.'>Click Here to Verify your email</button></div><div style='margin-top:2%'>";
			$div  .= "<div id='mo_message' hidden='' style='background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;''></div></div>";
			$html  = '<script>jQuery(document).ready(function(){$mo=jQuery; var divElement = "' . $div . '"; ';
			$html .= '$mo("input[name=user_email]").change(function(){ if(!$mo(this).val()){ ';
			$html .= '$mo("#miniorange_otp_token_submit").prop("disabled",true); }else{ ';
			$html .= '$mo("#miniorange_otp_token_submit").prop("disabled",false); } }); ';
			$html .= '$mo(divElement).insertAfter($mo( "input[name=user_email]")); $mo("#miniorange_otp_token_submit").click(function(o){ ';
			$html .= 'var e=$mo("input[name=user_email]").val(); $mo("#mo_message").empty(),$mo("#mo_message").append("' . $img . '"),';
			$html .= '$mo("#mo_message").show(),$mo.ajax({url:"' . site_url() . '/?option=miniorange-ulti",type:"POST",data:{security:"' . wp_create_nonce( $this->nonce ) . '", user_email:e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result=="success"){$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").css("border-top","3px solid green"),$mo("input[name=email_verify]").focus()}else{$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").css("border-top","3px solid red"),$mo("input[name=phone_verify]").focus()} ;},error:function(o,e,n){}})});});</script>';
			return $html;
		}


		/**
		 * Function to handle Form
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 * @throws ReflectionException Adds exception.
		 */
		private function handle_ulti_form( $data ) {

			MoUtility::initialize_transaction( $this->form_session_var );

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				SessionUtils::add_phone_verified( $this->form_session_var, $data['user_phone'] );
				$this->send_challenge( '', null, null, $data['user_phone'], VerificationType::PHONE );
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $data['user_email'] );
				$this->send_challenge( '', $data['user_email'], null, null, VerificationType::EMAIL );
			}
		}
		/**
		 * This function hooks into the wp_ajax_nopriv_ihc_check_reg_field_ajax or wp_ajax_ihc_check_reg_field_ajax
		 * hook to validate the form fields being submitted. It's also used to validate the OTP token that was
		 * provided by the user on form submission.
		 */
		public function processFormAndValidateOTP() {
			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
			MoPHPSessions::check_session();
			$this->checkIfOTPSent();
			$this->checkIntegrityAndValidateOTP( $_POST ); // phpcs:ignore -- false positive.
		}
		/**
		 * This function is called to validate each of the phone, user_email or validation
		 * field. Validation field has the OTP which needs to be verified and make sure that
		 * the phone or email that's being submitted is the phone or email that the otp was sent to.
		 *
		 * @param array $data - the data submitted on the form.
		 */
		private function checkIntegrityAndValidateOTP( $data ) {
			MoPHPSessions::check_session();
			$data['otpType'] = strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phone' : 'email';
			$this->checkIntegrity( $data );
			$this->validate_challenge( sanitize_text_field( $data['otpType'] ), null, sanitize_text_field( $data['otp_token'] ) );
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $data['otpType'] ) ) {
				if ( VerificationType::PHONE === $data['otpType'] ) {
					SessionUtils::add_phone_submitted( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) );
				}
				if ( VerificationType::EMAIL === $data['otpType'] ) {
					SessionUtils::add_email_submitted( $this->form_session_var, sanitize_email( $data['user_email'] ) );
				}
				$this->unset_otp_session_variables();
				wp_send_json( MoUtility::create_json( MoConstants::SUCCESS_JSON_TYPE, MoConstants::SUCCESS_JSON_TYPE ) );
			} else {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::INVALID_OTP ), MoConstants::ERROR_JSON_TYPE ) );
			}
		}
		/**
		 * Make sure if the OTP was initiated. If not then throw an error.
		 */
		private function checkIfOTPSent() {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE ), MoConstants::ERROR_JSON_TYPE ) );
			}
		}
		/**
		 * Process the phone submitted by the user and make sure it's the same phone
		 * number that the OTP was sent to for verification. If not then throw an error.
		 *
		 * @param array $data - the data submitted on the form.
		 */
		private function checkIntegrity( $data ) {
			if ( VerificationType::PHONE === $data['otpType'] ) {
				if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) ) ) {
					wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::PHONE_MISMATCH ), MoConstants::ERROR_JSON_TYPE ) );
				}
			}
			if ( VerificationType::EMAIL === $data['otpType'] ) {
				if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, is_email( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : sanitize_text_field( $data['user_email'] ) ) ) {
					wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ), MoConstants::ERROR_JSON_TYPE ) );
				}
			}
		}
		/**
		 * Register the javascript to be added to the frontend which will handle
		 * submission of the form.
		 */
		public function register_ulti_verify_script() {
			wp_register_script( 'ultiotpbuttonscript', MOV_URL . 'includes/js/moultipro.min.js', array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'ultiotpbuttonscript',
				'moultivar',
				array(
					'siteURL'     => wp_ajax_url(),
					'nonce'       => wp_create_nonce( $this->nonce ),
					'otpType'     => $this->otp_type,
					'vaction'     => $this->validate_otp_action,
					'formDetails' => $this->form_details,
					'buttontext'  => mo_( $this->button_text ),
					'imgURL'      => MOV_URL . 'includes/images/loader.gif',
				)
			);
			wp_enqueue_script( 'ultiotpbuttonscript' );
		}

		/**
		 * Process and validate the OTP entered by the user
		 *
		 * @param array  $return - the array describing the field and if any error has occurred during validation.
		 * @param string $type - the field type.
		 * @param string $otp_token - OTP token entered by user.
		 */
		private function validateAndProcessOTP( &$return, $type, $otp_token ) {
			$otp_ver_type = $this->get_verification_type();
			$this->validate_challenge( $otp_ver_type, null, $otp_token );
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				$return = array(
					'type'  => $type,
					'value' => MoUtility::get_invalid_otp_method(),
				);
			} else {
				$this->unset_otp_session_variables();
				$return = array(
					'type'  => $type,
					'value' => 1,
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
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
		}


		/**
		 * This function is called by the filter mo_phone_dropdown_selector
		 * to return the Jquery selector of the phone field. The function will
		 * push the formID to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the Ultimate Pro Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'ultipro_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'ultipro_type' );
			update_mo_option( 'ultipro_enable', $this->is_form_enabled );
			update_mo_option( 'ultipro_type', $this->otp_type );
		}
	}
}
