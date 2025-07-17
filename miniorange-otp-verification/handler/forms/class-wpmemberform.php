<?php
/**
 * Load admin view for WpMemberForm.
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
 * This is the WP Member Form class. This class handles all the
 * functionality related to WP Member Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WpMemberForm' ) ) {
	/**
	 * WpMemberForm class
	 */
	class WpMemberForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::WPMEMBER_REG;
			$this->email_key               = 'user_email';
			$this->phone_key               = get_mo_option( 'wp_member_reg_phone_field_key' );
			$this->phone_form_id           = "input[name=$this->phone_key]";
			$this->form_key                = 'WP_MEMBER_FORM';
			$this->type_phone_tag          = 'mo_wpmember_reg_phone_enable';
			$this->type_email_tag          = 'mo_wpmember_reg_email_enable';
			$this->form_name               = mo_( 'WP-Members' );
			$this->is_form_enabled         = get_mo_option( 'wp_member_reg_enable' );
			$this->form_documents          = MoFormDocs::WP_MEMBER_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException //.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'wp_member_reg_enable_type' );
			add_filter( 'wpmem_register_form_rows', array( $this, 'wpmember_add_button' ), 99, 2 );
			add_action( 'wpmem_pre_register_data', array( $this, 'validate_wpmember_submit' ), 99, 1 );

			if ( ! isset( $_POST['wpmem_security_nonce'] ) || ! array_key_exists( 'option', $_REQUEST ) ) { // phpcs:ignore -- false positive.
				return;
			}
			if ( ! wp_verify_nonce( sanitize_key( $_POST['wpmem_security_nonce'] ), 'wpmem_longform_nonce' ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( BaseMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			$req_data  = MoUtility::mo_sanitize_array( $_REQUEST );
			$this->routeData( $post_data, $req_data );
		}


		/**
		 * Checks whether option parameter is present in $_GET and stat OTP Verification process.
		 *
		 * @param array $post_data - $_POST.
		 * @param array $req_data - $_REQUEST.
		 * @throws ReflectionException //.
		 */
		private function routeData( $post_data, $req_data ) {
			switch ( trim( sanitize_text_field( $req_data['option'] ) ) ) {
				case 'miniorange-wpmember-form':
					$this->handle_wp_member_form( $post_data );
					break;
			}
		}

		/**
		 * This function is used to start the OTP Verification process. Initializes the
		 * required session variables and starts the OTP Verification process.
		 *
		 * @param array $data - the phone of email to which otp needs to be sent to.
		 * @throws ReflectionException //.
		 */
		private function handle_wp_member_form( $data ) {

			MoUtility::initialize_transaction( $this->form_session_var );

			if ( $this->otp_type === $this->type_email_tag ) {
				$this->processEmailAndStartOTPVerificationProcess( $data );
			}
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->processPhoneAndStartOTPVerificationProcess( $data );
			}
		}


		/**
		 * This function is called to check if phone verification has been enabled in the settings
		 * and start the OTP Verification process. Keeps the Email otp was sent to in session so
		 * that it can verified later.
		 *
		 * @param array $data - the data sent in ajax call for otp verification.
		 */
		private function processEmailAndStartOTPVerificationProcess( $data ) {
			if ( MoUtility::sanitize_check( 'user_email', $data ) ) {
				$user_email = sanitize_email( $data['user_email'] );
				SessionUtils::add_email_verified( $this->form_session_var, $user_email );
				$this->send_challenge( null, $user_email, null, '', VerificationType::EMAIL, null, null, false );
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
		 * This function is called to check if email verification has been enabled in the settings
		 * and start the OTP Verification process. Keeps the phone otp was sent to in session so
		 * that it can verified later.
		 *
		 * @param array $data - the data sent in ajax call for otp verification.
		 */
		private function processPhoneAndStartOTPVerificationProcess( $data ) {
			if ( MoUtility::sanitize_check( 'user_phone', $data ) ) {
				$user_phone = sanitize_text_field( $data['user_phone'] );
				SessionUtils::add_phone_verified( $this->form_session_var, $user_phone );
				$this->send_challenge( null, '', null, $user_phone, VerificationType::PHONE, null, null, false );
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
		 * This function hooks into the wpmem_register_form_rows hook to add
		 * HTML and javascript below the phone or email to facilitate OTP
		 * Verification.
		 *
		 * @param array  $rows - an array containing field information.
		 * @param string $tag - tag associated with the form.
		 * @return array
		 */
		public function wpmember_add_button( $rows, $tag ) {
			foreach ( $rows as $key => $field ) {
				if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 && $key === $this->phone_key ) {
					$rows[ $key ]['field'] .= $this->add_shortcode_to_wpmember( 'phone', $field['meta'] );
					break;
				} elseif ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 && $key === $this->email_key ) {
					$rows[ $key ]['field'] .= $this->add_shortcode_to_wpmember( 'email', $field['meta'] );
					break;
				}
			}
			return $rows;
		}


		/**
		 * This function hooks into the wpmem_pre_register_data hook to validate the
		 * OTP entered by the user.
		 *
		 * @param array $fields - an array containing field data.
		 */
		public function validate_wpmember_submit( $fields ) {
			global $wpmem_themsg;
			$otp_type = $this->get_verification_type();
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$wpmem_themsg = MoMessages::showMessage( MoMessages::PLEASE_VALIDATE );
			} elseif ( ! $this->validate_submitted( $fields, $otp_type ) ) {
				return;
			}
			$this->validate_challenge( $otp_type, null, $fields['validate_otp'] );
		}


		/**
		 * This function is called to check if the phone or email the otp was
		 * sent to is the same. Returns True or False. It sets an error message
		 * if the phone or email don't match.
		 *
		 * @param array  $fields an array containing field data.
		 * @param string $otp_type OTP VerificationType enabled for the form.
		 * @return bool
		 */
		private function validate_submitted( $fields, $otp_type ) {
			global $wpmem_themsg;

			if ( VerificationType::EMAIL === $otp_type
			&& ! SessionUtils::is_email_verified_match( $this->form_session_var, $fields[ $this->email_key ] ) ) {
				$wpmem_themsg = MoMessages::showMessage( MoMessages::EMAIL_MISMATCH );
				return false;
			} elseif ( VerificationType::PHONE === $otp_type
			&& ! SessionUtils::is_phone_verified_match( $this->form_session_var, $fields[ $this->phone_key ] ) ) {
				$wpmem_themsg = MoMessages::showMessage( MoMessages::PHONE_MISMATCH );
				return false;
			} else {
				return true;
			}
		}


		/**
		 * The function returns the HTML and script to be appended to the page so that
		 * we can do otp Verification. This shortcode adds a button and script to make
		 * ajax calls on the form page.
		 *
		 * @param string $mo_type - the otp type set by the admin.
		 * @param string $field - the field meta identifier.
		 * @return string
		 */
		private function add_shortcode_to_wpmember( $mo_type, $field ) {
			$img            = "<div class= 'moloader'></div>";
			$field_content  = "<div style='margin-top: 2%;'><button type='button' class='button alt' style='width:100%;height:30px;";
			$field_content .= "font-family: Roboto;font-size: 12px !important;' id='miniorange_otp_token_submit' ";
			$field_content .= "title='Please Enter an '" . $mo_type . "'to enable this.'>Click Here to Verify " . $mo_type . '</button></div>';
			$field_content .= "<div style='margin-top:2%'><div id='mo_message' hidden='' style='background-color: #f7f6f7;padding: ";
			$field_content .= "1em 2em 1em 3.5em;'></div></div>";
			$field_content .= '<script>jQuery(document).ready(function(){$mo=jQuery;$mo("#miniorange_otp_token_submit").click(function(o){ ';
			$field_content .= 'var e=$mo("input[name=' . $field . ']").val(); var n=$mo("#_wpmem_register_nonce").val();$mo("#mo_message").empty(),$mo("#mo_message").append("' . $img . '"),';
			$field_content .= '$mo("#mo_message").show(),$mo.ajax({url:"' . site_url() . '/?option=miniorange-wpmember-form",type:"POST",';
			$field_content .= 'data:{user_' . $mo_type . ':e, wpmem_security_nonce: n},crossDomain:!0,dataType:"json",success:function(o){ ';
			$field_content .= 'if(o.result==="success"){$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),';
			$field_content .= '$mo("#mo_message").css("border-top","3px solid green"),$mo("input[name=email_verify]").focus()}else{';
			$field_content .= '$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").css("border-top","3px solid red")';
			$field_content .= ',$mo("input[name=phone_verify]").focus()} ;},error:function(o,e,n){}})});});</script>';

			return $field_content;
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
			global $wpmem_themsg;

			$wpmem_themsg = MoUtility::get_invalid_otp_method();
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
		 * @param  array $selector  the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the WP Member Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'wp_member_reg_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'wp_member_reg_enable_type' );
			$this->phone_key       = $this->sanitize_form_post( 'wp_member_reg_phone_field_key' );

			if ( $this->basic_validation_check( BaseMessages::WP_MEMBER_CHOOSE ) ) {
				update_mo_option( 'wp_member_reg_phone_field_key', $this->phone_key );
				update_mo_option( 'wp_member_reg_enable', $this->is_form_enabled );
				update_mo_option( 'wp_member_reg_enable_type', $this->otp_type );
			}
		}
	}
}
