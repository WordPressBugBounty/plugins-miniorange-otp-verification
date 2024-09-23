<?php
/**
 * Handles the OTP verification logic for UserProRegistrationForm form.
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
 * This is the User Pro Registration Form class. This class handles all the
 * functionality related to User Pro Registration Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'UserProRegistrationForm' ) ) {
	/**
	 * UserProRegistrationForm class
	 */
	class UserProRegistrationForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * The post variable value to check if there needs to
		 * be a validation
		 *
		 * @var string
		 */
		private $user_ajax_check;

		/**
		 * The verification field key
		 *
		 * @var string
		 */
		private $user_field_meta;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::USERPRO_FORM;
			$this->type_phone_tag          = 'mo_userpro_registration_phone_enable';
			$this->type_email_tag          = 'mo_userpro_registration_email_enable';
			$this->phone_form_id           = "input[data-label='Phone Number']";
			$this->user_ajax_check         = 'mo_phone_validation';
			$this->user_field_meta         = 'verification_form';
			$this->form_key                = 'USERPRO_FORM';
			$this->form_name               = mo_( 'UserPro Form' );
			$this->is_form_enabled         = get_mo_option( 'userpro_default_enable' );
			$this->form_documents          = MoFormDocs::USERPRO_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException .
		 */
		public function handle_form() {
			$this->otp_type              = get_mo_option( 'userpro_enable_type' );
			$this->disable_auto_activate = get_mo_option( 'userpro_verify' );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				add_action( 'wp_ajax_userpro_side_validate', array( $this, 'validate_userpro_phone' ), 1 );
				add_action( 'wp_ajax_nopriv_userpro_side_validate', array( $this, 'validate_userpro_phone' ), 1 );
			}

			if ( ! $this->is_form_enabled ) {
				return;
			}
			add_filter( 'userpro_register_validation', array( $this, 'process_userpro_form_submit' ), 1, 2 );
			add_action( 'userpro_after_new_registration', array( $this, 'auto_verify_user' ), 1, 1 );
			add_shortcode( 'mo_verify_email_userpro', array( $this, 'userpro_email_shortcode' ) );
			add_shortcode( 'mo_verify_phone_userpro', array( $this, 'userpro_phone_shortcode' ) );

			$this->routeData();
		}


		/**
		 * Checks the option set in GET request and starts OTP verification flow.
		 *
		 * @throws ReflectionException .
		 */
		private function routeData() {
			if ( ! array_key_exists( 'option', $_GET ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the option name, doesn't require nonce verification. 
				return;
			}
			switch ( trim( sanitize_text_field( wp_unslash( $_GET['option'] ) ) ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the option name, doesn't require nonce verification.
				case 'miniorange-userpro-form':
					$this->send_otp( $_POST );  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
					break;
			}
		}


		/**
		 * This function hooks into the userpro_after_new_registration hook and auto
		 * registers the user in WordPress. It updates the users meta value of the user
		 * being registered indicating that the user has verified himself.
		 *
		 * @param int $user_id - the user id .
		 */
		public function auto_verify_user( $user_id ) {
			if ( $this->disable_auto_activate ) {
				update_user_meta( $user_id, 'userpro_verified', 1 );
			}
		}

		/**
		 * This function hooks into the wp_ajax_userpro_side_validate and wp_ajax_nopriv_userpro_side_validate
		 * hooks to validate the phone number being submitted by the user before starting the OTP Verification
		 * process. This is done so that user can be shown an error message on the form itself.
		 */
		public function validate_userpro_phone() {
			if ( $this->checkIfUserHasNotSubmittedTheFormForValidation() ) {
				return;
			}

			$message = MoUtility::get_invalid_otp_method();
			if ( strcasecmp( isset( $_POST['ajaxcheck'] ) ? sanitize_text_field( wp_unslash( $_POST['ajaxcheck'] ) ) : '', $this->user_ajax_check ) !== 0 ) {// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				return;
			}
			if ( ! MoUtility::validate_phone_number( '+' . trim( isset( $_POST['input_value'] ) ? sanitize_text_field( wp_unslash( $_POST['input_value'] ) ) : '' ) ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				wp_send_json( array( 'error' => $message ) );
			}
		}


		/**
		 * The function is called to check if user hasn't submitted for the form for
		 * ajax check. Returns TRUE or FALSE.
		 */
		private function checkIfUserHasNotSubmittedTheFormForValidation() {
			return isset( $_POST['action'] ) && isset( $_POST['ajaxcheck'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				&& isset( $_POST['input_value'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) !== 'userpro_side_validate' ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
		}


		/**
		 * This function is used to start the OTP Verification process. Initializes the
		 * required session variables and starts the OTP Verification process.
		 *
		 * @param array $get_data the phone of email to which otp needs to be sent to.
		 * @throws ReflectionException .
		 */
		private function send_otp( $get_data ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			$this->processEmailAndStartOTPVerificationProcess( $get_data );
			$this->processPhoneAndStartOTPVerificationProcess( $get_data );
			$this->sendErrorMessageIfOTPVerificationNotStarted();
		}


		/**
		 * This function is called to check if phone verification has been enabled in the settings
		 * and start the OTP Verification process. Keeps the phone otp was sent to in session so
		 * that it can verified later.
		 *
		 * @param array $get_data - the data sent in ajax call for otp verification.
		 */
		private function processEmailAndStartOTPVerificationProcess( $get_data ) {
			if ( ! array_key_exists( 'user_email', $get_data ) || ! isset( $get_data['user_email'] ) ) {
				return;
			}
			SessionUtils::add_email_verified( $this->form_session_var, sanitize_email( $get_data['user_email'] ) );
			$this->send_challenge( '', sanitize_email( $get_data['user_email'] ), null, sanitize_email( $get_data['user_email'] ), VerificationType::EMAIL );
		}


		/**
		 * This function is called to send an error message if OTP was not sent to the user.
		 * We assume that the phone or email was missing in the ajax call to send OTP to.
		 *
		 * @param array $get_data - the data sent in ajax call for otp verification.
		 */
		private function processPhoneAndStartOTPVerificationProcess( $get_data ) {
			if ( ! array_key_exists( 'user_phone', $get_data ) || ! isset( $get_data['user_phone'] ) ) {
				return;
			}
			SessionUtils::add_phone_verified( $this->form_session_var, sanitize_text_field( $get_data['user_phone'] ) );
			$this->send_challenge( '', '', null, sanitize_text_field( trim( $get_data['user_phone'] ) ), 'phone' );
		}

		/**
		 * This function is called to send an error message if OTP was not sent to the user.
		 * We assume that the phone or email was missing in the ajax call to send OTP to.
		 */
		private function sendErrorMessageIfOTPVerificationNotStarted() {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
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
		 * This function is called to verify that the form being submitted. Process the
		 * and check if the email or phone otp was sent to has not been changed. After
		 * those verification verify the OTP entered.
		 *
		 * @param array $output - the user pro related array that stores any error message related to the field.
		 * @param array $form - the form array containing all the form information.
		 * @return array
		 */
		public function process_userpro_form_submit( $output, $form ) {

			if ( ! $this->checkIfValidFormSubmition( $output, $form ) ) {
				return $output;
			}
			$otp_ver_type = $this->get_verification_type();

			if ( VerificationType::EMAIL === $otp_ver_type && ! SessionUtils::is_email_verified_match( $this->form_session_var, $form['user_email'] ) ) {
				$output['user_email'] = MoMessages::showMessage( MoMessages::EMAIL_MISMATCH );
			} elseif ( VerificationType::PHONE === $otp_ver_type && ! SessionUtils::is_phone_verified_match( $this->form_session_var, $form['phone_number'] ) ) {
				$output['phone_number'] = MoMessages::showMessage( MoMessages::PHONE_MISMATCH );
			}

			$this->processOTPEntered( $output, $form );
			return $output;
		}


		/**
		 * This function is called to check if the otp verification was started for
		 * the form. If it wasn't then register the error message in the output variable.
		 *
		 * @param array $output - the user pro related array that stores any error message related to the field.
		 * @param array $form - the form array containing all the form information.
		 * @return bool
		 */
		private function checkIfValidFormSubmition( &$output, $form ) {
			if ( array_key_exists( $this->user_field_meta, $form ) && ! Sessionutils::is_otp_initialized( $this->form_session_var ) ) {
				$output[ $this->user_field_meta ] = MoMessages::showMessage( MoMessages::PLEASE_VALIDATE );
				return false;
			}
			return true;
		}


		/**
		 * Process and validate the OTP entered by the user
		 *
		 * @param array $output - the user pro related array that stores any error message related to the field.
		 * @param array $form   - the form array containing all the form information.
		 */
		private function processOTPEntered( &$output, $form ) {
			if ( ! empty( $output ) ) {
				return;
			}
			$otp_ver_type = $this->get_verification_type();
			$this->validate_challenge( $otp_ver_type, null, $form[ $this->user_field_meta ] );
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_ver_type ) ) {
				$output[ $this->user_field_meta ] = MoUtility::get_invalid_otp_method();
			} else {
				$this->unset_otp_session_variables();
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
		 * The function returns the HTML and script to be appended to the page so that
		 * we can do Email Verification. This shortcode adds a button and script to make
		 * ajax calls on the form page.
		 */
		public function userpro_phone_shortcode() {
			$img = "<div class= 'moloader'></div>";

			$html_content = "<div style='margin-top: 2%;'><button type='button' class='button alt'" .
							" style='width:100%;height:30px;font-family: Roboto;font-size: 12px !important;' id='miniorange_otp_token_submit'" .
								" title='" . mo_( 'Please Enter a phone number to enable this' ) . "'>" . mo_( 'Click Here to Verify Phone' ) .
							"</button></div><div style='margin-top:2%'>" .
						"<div id='mo_message' hidden='' style='background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;''></div></div>";

			$script = '    <script>
                            jQuery(document).click(function(e){
                                $mo=jQuery;
                                var unique_id;
                                if($mo("#miniorange_otp_token_submit").length===0){
                                    unique_id=$mo("#unique_id").val();
                                    var phone_field="#phone_number-"+unique_id;
                                    if($mo(phone_field).length) {
                                        $mo("' . $html_content . '").insertAfter(phone_field);
                                    }
                                }
                                if(e.target.id==="miniorange_otp_token_submit"){
                                    unique_id=$mo("#unique_id").val();
                                    var user_phone="phone_number-"+unique_id;
                                    var phone =$mo("input[name="+user_phone+"]").val();
                                    $mo("#mo_message").empty();
                                    $mo("#mo_message").append("' . $img . '");
                                    $mo("#mo_message").show();
                                    $mo.ajax({
                                        url:"' . site_url() . '/?option=miniorange-userpro-form",
                                        type:"POST",data:{user_phone:phone},
                                        crossDomain:!0,
                                        dataType:"json",
                                        success:function(o){
                                            if(o.result==="success"){
                                                $mo("#mo_message").empty();
                                                $mo("#mo_message").append(o.message);
                                                $mo("#mo_message").css("border-top","3px solid green");
                                                $mo("input[name=phone_verify]").focus();
                                            }else{
                                                $mo("#mo_message").empty();
                                                $mo("#mo_message").append(o.message);
                                                $mo("#mo_message").css("border-top","3px solid red");
                                                $mo("input[name=phone_verify]").focus();
                                            }
                                        },
                                        error:function(o,e,n){}
                                    });
                                }
                            });
                        </script>';
			return $script;
		}


		/**
		 * The function returns the HTML and script to be appended to the page so that
		 * we can do Email Verification. This shortcode adds a button and script to make
		 * ajax calls on the form page.
		 */
		public function userpro_email_shortcode() {
			$img          = "<div class= 'moloader'></div>";
			$html_content = "<div style='margin-top: 2%;'><button type='button' class='button alt'" .
							" style='width:100%;height:30px;font-family: Roboto;font-size: 12px !important;' id='miniorange_otp_token_submit'" .
								" title='" . mo_( 'Please Enter a Email address to enable this' ) . "'>" . mo_( 'Click Here to Verify Email' ) .
							"</button></div><div style='margin-top:2%'>" .
						"<div id='mo_message' hidden='' style='background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;''></div></div>";

			$script = '    <script>
                            jQuery(document).click(function(e){
                                $mo=jQuery;
                                var unique_id;
                                if($mo("#miniorange_otp_token_submit").length===0){
                                    unique_id =$mo("#unique_id").val();
                                    var email_field="#user_email-"+unique_id;
                                    if($mo(email_field).length) {
                                        $mo("' . $html_content . '").insertAfter(email_field);
                                    }
                                }
                                if(e.target.id==="miniorange_otp_token_submit"){
                                    unique_id=$mo("#unique_id").val();
                                    var user_email="user_email-"+unique_id;
                                    var email =$mo("input[name="+user_email+"]").val();
                                    $mo("#mo_message").empty();
                                    $mo("#mo_message").append("' . $img . '");
                                    $mo("#mo_message").show();
                                    $mo.ajax({
                                        url:"' . site_url() . '/?option=miniorange-userpro-form",
                                        type:"POST",data:{user_email:email},
                                        crossDomain:!0,
                                        dataType:"json",
                                        success:function(o){
                                            if(o.result==="success"){
                                                $mo("#mo_message").empty();
                                                $mo("#mo_message").append(o.message);
                                                $mo("#mo_message").css("border-top","3px solid green");
                                                $mo("input[name=email_verify]").focus();
                                            }else{
                                                $mo("#mo_message").empty();
                                                $mo("#mo_message").append(o.message);
                                                $mo("#mo_message").css("border-top","3px solid red");
                                                $mo("input[name=email_verify]").focus();
                                            }
                                        },
                                        error:function(o,e,n){}
                                    });
                                }
                            });
                        </script>';
			return $script;
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array $selector - the Jquery selector to be modified.
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the User Pro Registration Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled       = $this->sanitize_form_post( 'userpro_registration_enable' );
			$this->otp_type              = $this->sanitize_form_post( 'userpro_registration_type' );
			$this->disable_auto_activate = $this->sanitize_form_post( 'userpro_verify' );

			update_mo_option( 'userpro_default_enable', $this->is_form_enabled );
			update_mo_option( 'userpro_enable_type', $this->otp_type );
			update_mo_option( 'userpro_verify', $this->disable_auto_activate );
		}
	}
}
