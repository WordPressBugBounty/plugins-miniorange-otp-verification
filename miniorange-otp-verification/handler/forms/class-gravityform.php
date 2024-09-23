<?php
/**
 * Load admin view for Form Craft Premium Form.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use GF_Field;
use GFAPI;
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

/**
 * This is the Gravity Form class. This class handles all the
 * functionality related to Gravity Forms. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'GravityForm' ) ) {
	/**
	 * GravityForm class
	 */
	class GravityForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::GF_FORMS;
			$this->type_phone_tag          = 'mo_gf_contact_phone_enable';
			$this->type_email_tag          = 'mo_gf_contact_email_enable';
			$this->form_key                = 'GRAVITY_FORM';
			$this->form_name               = mo_( 'Gravity Form' );
			$this->is_form_enabled         = get_mo_option( 'gf_contact_enable' );
			$this->phone_form_id           = '.ginput_container_phone';
			$this->button_text             = get_mo_option( 'gf_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->form_documents          = MoFormDocs::GF_FORM_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type     = get_mo_option( 'gf_contact_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'gf_otp_enabled' ) );
			if ( empty( $this->form_details ) ) {
				return;
			}
			add_filter( 'gform_field_content', array( $this, 'add_scripts' ), 1, 5 );
			add_filter( 'gform_field_validation', array( $this, 'validate_form_submit' ), 1, 5 );
			add_action( 'gform_pre_submission', array( $this, 'mo_gravity_unset_session' ) );
			$this->routeData();
		}
		/**
		 * * @throws ReflectionException
		 */
		private function routeData() {

			if ( ! array_key_exists( 'mo_gravity_option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}

			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ), MoConstants::ERROR_JSON_TYPE ) );
			}
			$data                  = MoUtility::mo_sanitize_array( $_POST );
			$send_otp_check_option = isset( $_GET['mo_gravity_option'] ) ? sanitize_text_field( wp_unslash( $_GET['mo_gravity_option'] ) ) : ''; // phpcs:ignore -- false positive.
			switch ( trim( $send_otp_check_option ) ) {
				case 'miniorange-gf-contact':
					$this->handleGfForm( $data );
					break;
			}
		}
		/**
		 * This function is used to start the OTP Verification process. Initializes the
		 * required session variables and starts the OTP Verification process.
		 *
		 * @param string $get_data  array   the post variable.
		 * * @throws ReflectionException.
		 */
		public function handleGfForm( $get_data ) {

			MoUtility::initialize_transaction( $this->form_session_var );

			if ( $this->otp_type === $this->type_email_tag ) {
				$this->processEmailAndStartOTPVerificationProcess( $get_data );
			}
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->processPhoneAndStartOTPVerificationProcess( $get_data );
			}
		}
		/**
		 * This function is called to check if email verification has been enabled in the settings
		 * and start the OTP Verification process. Keeps the email otp was sent to in session so
		 * that it can verified later.
		 *
		 * @param string $get_data array the data sent in ajax call for otp verification.
		 */
		private function processEmailAndStartOTPVerificationProcess( $get_data ) {
			if ( MoUtility::sanitize_check( 'user_email', $get_data ) ) {
				SessionUtils::add_email_verified( $this->form_session_var, $get_data['user_email'] );
				$this->send_challenge( '', $get_data['user_email'], null, $get_data['user_email'], VerificationType::EMAIL );
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
		 * This function is called to check if phone verification has been enabled in the settings
		 * and start the OTP Verification process. Keeps the phone otp was sent to in session so
		 * that it can verified later.
		 *
		 * @param string $get_data - the data sent in ajax call for otp verification.
		 */
		private function processPhoneAndStartOTPVerificationProcess( $get_data ) {
			if ( MoUtility::sanitize_check( 'user_phone', $get_data ) ) {
				SessionUtils::add_phone_verified( $this->form_session_var, trim( $get_data['user_phone'] ) );
				$this->send_challenge( '', '', null, trim( $get_data['user_phone'] ), VerificationType::PHONE );
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
		 * This function hooks into the gform_field_content hook and decides which field
		 * the the script and verify button need to be added to based on admin settings.
		 *
		 * @param string $field_content - variable denotes what's to be added to the gravity form field.
		 * @param object $field - the entire field variable having all field related information.
		 * @param string $value - denotes the value of the field being submitted.
		 * @param string $zero -.
		 * @param string $form_id - the id of the form being submitted.
		 * @return string.
		 */
		public function add_scripts( $field_content, $field, $value, $zero, $form_id ) {
			if ( ! isset( $this->form_details[ $form_id ] ) ) {
				return $field_content;
			}
			$form_data = $this->form_details[ $form_id ];
			if ( ! MoUtility::is_blank( $form_data ) ) {
				if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0
				&& get_class( $field ) === 'GF_Field_Email'
				&& $field['id'] === $form_data['emailkey'] ) {
					$field_content = $this->add_shortcode_to_form( 'email', $field_content, $field, $form_id );
				}
				if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0
				&& get_class( $field ) === 'GF_Field_Phone'
				&& $field['id'] === $form_data['phonekey'] ) {
					$field_content = $this->add_shortcode_to_form( 'phone', $field_content, $field, $form_id );
				}
			}
			return $field_content;
		}
		/**
		 * This function is called to add the Verify Button below the relevant gravity
		 * form field and the required scripts to make sending of OTPs possible for the
		 * gravity form.
		 *
		 * @param string $mo_type - variable denotes what type of OTP Verification is taking place.
		 * @param string $field_content - variable denotes what's to be added to the gravity form field.
		 * @param object $field - the entire field variable having all field related information.
		 * @param string $form_id - the form id for which otp verification was enabled.
		 * @return string
		 */
		private function add_shortcode_to_form( $mo_type, $field_content, $field, $form_id ) {
			$img            = "<div class= 'moloader'></div>";
			$field_content .= "<div style='margin-top: 2%;'><input type='button' class='gform_button button medium' ";
			$field_content .= "id='miniorange_otp_token_submit' title='Please Enter an " . $mo_type . " to enable this' ";
			$field_content .= "value= '" . mo_( $this->button_text ) . "'><div style='margin-top:2%'>";
			$field_content .= "<div id='mo_message' style='width:auto; display: none; font-size: 15px; padding: 10px 20px;border-radius: 10px; margin-top: 16px;'></div></div></div>";
			$field_content .= '<style>@media only screen and (min-width: 641px) { #mo_message { width: calc(50% - 8px);}}</style>';
			$field_content .= '<script>jQuery(document).ready(function(){$mo=jQuery;$mo("#gform_' . $form_id . ' #miniorange_otp_token_submit").click(function(o){';
			$field_content .= 'var e=$mo("#input_' . $form_id . '_' . $field->id . '").val(); $mo("#gform_' . $form_id . ' #mo_message").empty(),$mo("#gform_' . $form_id . ' #mo_message").append("' . $img . '")';
			$field_content .= ',$mo("#gform_' . $form_id . ' #mo_message").show(),$mo.ajax({url:"' . site_url() . '/?mo_gravity_option=miniorange-gf-contact",type:"POST",data:{security:"' . wp_create_nonce( $this->nonce ) . '",user_';
			$field_content .= $mo_type . ':e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result==="success"){$mo("#gform_' . $form_id . ' #mo_message").empty()';
			$field_content .= ',$mo("#gform_' . $form_id . ' #mo_message").append(o.message),$mo("#gform_' . $form_id . ' #mo_message").css({"background-color":"#dbfff7","color":"#008f6e"}),$mo("';
			$field_content .= '#gform_' . $form_id . ' input[name=email_verify]").focus()}else{$mo("#gform_' . $form_id . ' #mo_message").empty(),$mo("#gform_' . $form_id . ' #mo_message").append(o.message),';
			$field_content .= '$mo("#gform_' . $form_id . ' #mo_message").css({"background-color":"#ffefef","color":"#ff5b5b"}),$mo("#gform_' . $form_id . ' input[name=phone_verify]").focus()} ;},';
			$field_content .= 'error:function(o,e,n){}})});});</script>';
			return $field_content;
		}
		/**
		 * This function is used to unset OTP session after all the validations of the form are completed.
		 *
		 * @param object $form - the entire form variable having all form related information.
		 */
		public function mo_gravity_unset_session( $form ) {
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
					$this->unset_otp_session_variables();
			}
		}
		/**
		 * This function hooks into the gform_field_validation hook to verify the
		 * form submission and validate the otp being submitted. This hook is called
		 * for each field on the form for validation.
		 *
		 * @param string $error - denotes if there are any form related errors for the field.
		 * @param string $value - denotes the value of the field being submitted.
		 * @param string $form - the entire form variable having all form related information.
		 * @param object $field - the entire field variable having all the field information.
		 * @return array
		 */
		public function validate_form_submit( $error, $value, $form, $field ) {
			$form_id           = 'formId';
			$form_datails      = MoUtility::sanitize_check( $field->$form_id, $this->form_details );
			$submitted_form_id = isset( $_POST['gform_submit'] ) ? sanitize_text_field( wp_unslash( ( $_POST['gform_submit'] ) ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.

			if ( $form_datails && true === $error['is_valid'] && array_key_exists( $submitted_form_id, $this->form_details ) ) {
				if ( strpos( $field->label, $form_datails['verifyKey'] ) !== false
				&& SessionUtils::is_otp_initialized( $this->form_session_var ) && ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
					$error = $this->validate_otp( $error, $value );
				} elseif ( $this->isEmailOrPhoneField( $field, $form_datails ) ) {
					if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
						$error = $this->validate_submitted_email_or_phone( $error['is_valid'], $value, $error );
					} else {
						$error = array(
							'is_valid' => null,
							'message'  => MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ),
						);
					}
				}
			}
			return $error;
		}
		/**
		 * This function is used to validate the form being submitted and
		 * check if the OTP entered by the user is valid.
		 *
		 * @param string $error shows form error.
		 * @param string $value to give values.
		 *
		 * @return array
		 */
		private function validate_otp( $error, $value ) {
			$otp_type = $this->get_verification_type();
			if ( MoUtility::is_blank( $value ) ) {
				$error = array(
					'is_valid' => null,
					'message'  => MoUtility::get_invalid_otp_method(),
				);
			} else {
				$this->validate_challenge( $otp_type, null, $value );
				if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type ) ) {
					$error = array(
						'is_valid' => null,
						'message'  => MoUtility::get_invalid_otp_method(),
					);
				}
			}
			return $error;
		}
		/**
		 * This function is called to validate that the number or the email
		 * validation otp was sent to and the final number or the email being
		 * submitted are the same.
		 *
		 * @param string $is_valid - this variable denotes if the form field is valid.
		 * @param string $value - this variable has the value of the field.
		 * @param string $error - this variable denotes if there's any error in form submission.
		 * @return array
		 */
		private function validate_submitted_email_or_phone( $is_valid, $value, $error ) {
			$otp_type = $this->get_verification_type();
			if ( $is_valid ) {
				if ( VerificationType::EMAIL === $otp_type && ! SessionUtils::is_email_verified_match( $this->form_session_var, $value ) ) {
					return array(
						'is_valid' => null,
						'message'  => MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
					);
				} elseif ( VerificationType::PHONE === $otp_type && ! SessionUtils::is_phone_verified_match( $this->form_session_var, $value ) ) {
					return array(
						'is_valid' => null,
						'message'  => MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
					);
				}
			}
			return $error;
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				foreach ( $this->form_details as $key => $form_detail ) {
					$phone_field = sprintf( '%s_%d_%d', 'input', $key, $form_detail['phonekey'] );
					array_push( $selector, sprintf( '%s #%s', $this->phone_form_id, $phone_field ) );
				}
			}
			return $selector;
		}
		/**
		 * Handles saving all the Gravity form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! MoUtility::get_active_plugin_version( 'Gravity Forms' ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			$data                  = MoUtility::mo_sanitize_array( $_POST );
			$this->is_form_enabled = $this->sanitize_form_post( 'gf_contact_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'gf_contact_type' );
			$this->button_text     = $this->sanitize_form_post( 'gf_button_text' );
			$forms                 = $this->parseform_datails( $data );

			$this->form_details = is_array( $forms ) ? $forms : '';

			update_mo_option( 'gf_otp_enabled', maybe_serialize( $this->form_details ) );
			update_mo_option( 'gf_contact_enable', $this->is_form_enabled );
			update_mo_option( 'gf_contact_type', $this->otp_type );
			update_mo_option( 'gf_button_text', $this->button_text );
		}
		/**
		 * Parse the form details and create an array containing form details for
		 * OTP Verification.
		 *
		 * @param array $data the data posted while savig the form.
		 *
		 * @return array
		 */
		private function parseform_datails( $data ) {
			$forms         = array();
			$get_field_key = function( $field_details, $field_label, $type ) {
				foreach ( $field_details as $field ) {
					if ( get_class( $field ) === $type
					&& $field['label'] === $field_label ) {
						return $field['id'];
					}
				}
				return null;
			};

			$form = null;
			if ( ! array_key_exists( 'gravity_form', $data ) || ! $this->is_form_enabled ) {
				return array();
			}
			$data = MoUtility::mo_sanitize_array( $data );
			foreach ( array_filter( $data['gravity_form']['form'] ) as $key => $value ) {
				$form_data                              = GFAPI::get_form( $value );
				$email_key                              = isset( $data['gravity_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['emailkey'][ $key ] ) ) : '';
				$phone_key                              = isset( $data['gravity_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['phonekey'][ $key ] ) ) : '';
				$forms[ sanitize_text_field( $value ) ] = array(
					'emailkey'    => $get_field_key( $form_data['fields'], $email_key, 'GF_Field_Email' ),
					'phonekey'    => $get_field_key( $form_data['fields'], $phone_key, 'GF_Field_Phone' ),
					'verifyKey'   => isset( $data['gravity_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['verifyKey'][ $key ] ) ) : '',
					'phone_show'  => isset( $data['gravity_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['phonekey'][ $key ] ) ) : '',
					'email_show'  => isset( $data['gravity_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['emailkey'][ $key ] ) ) : '',
					'verify_show' => isset( $data['gravity_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['gravity_form']['verifyKey'][ $key ] ) ) : '',
				);
			}
			return $forms;
		}
		/**
		 * Checks if the field passed is an email or phone field
		 *
		 * @param object $field    GF_Field   Gravity forms field object.
		 * @param string $f        array      FormDetails saved by the admin for otp verification.
		 * @return bool true or false
		 */
		private function isEmailOrPhoneField( $field, $f ) {
			return ( $this->otp_type === $this->type_phone_tag && $field->id === $f['phonekey'] )
			|| ( $this->otp_type === $this->type_email_tag && $field->id === $f['emailkey'] );
		}
	}
}
