<?php
/**
 * Handles the OTP verification logic for FormMaker form.
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
 * This is the Form Maker form class. This class is used to handle all the
 * functionality related to Form Maker. It extends the {@code FormHandler} class
 * and implements the {@code IFormHandler} interface to implement some required
 * functions.
 */
if ( ! class_exists( 'FormMaker' ) ) {
	/**
	 * FormMaker class
	 */
	class FormMaker extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::FORM_MAKER;
			$this->type_phone_tag          = 'mo_form_maker_phone_enable';
			$this->type_email_tag          = 'mo_form_maker_email_enable';
			$this->form_name               = mo_( 'Form Maker Form' );
			$this->form_key                = 'FORM_MAKER';
			$this->is_form_enabled         = get_mo_option( 'formmaker_enable' );
			$this->otp_type                = get_mo_option( 'formmaker_enable_type' );
			$this->form_details            = maybe_unserialize( get_mo_option( 'formmaker_otp_enabled' ) );
			$this->button_text             = get_mo_option( 'formmaker_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->form_documents          = MoFormDocs::FORMMAKER;
			parent::__construct();

			if ( $this->is_form_enabled ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'register_fm_button_script' ) );
			}
		}

		/**
		 * *@throws ReflectionException Add exception.
		 */
		public function handle_form() {
			$this->routeData();
		}
		/**
		 * To route data to or from the script via ajax calls
		 *
		 * @throws ReflectionException Add exception.
		 * @todo Change this wp_ajax_{action} hook instead
		 */
		private function routeData() {
			if ( ! array_key_exists( 'mo_frm_option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}
			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ), MoConstants::ERROR_JSON_TYPE ) );
			}
			$option = isset( $_GET['mo_frm_option'] ) ? sanitize_text_field( wp_unslash( $_GET['mo_frm_option'] ) ) : ''; // phpcs:ignore -- false positive.
			$data   = MoUtility::mo_sanitize_array( $_POST );
			switch ( trim( $option ) ) {
				case 'miniorange-fm-ajax-verify':
					$this->mo_send_otp_fm_ajax_verify( $data );
					break;
				case 'miniorange-fm-verify-code':
					$this->mo_validate_otp( $data );
					break;
			}
		}
		/**
		 * Calls the {@code mo_validate_otp} hook to validate the otp
		 * being submitted with the form.
		 *
		 * @param string $post post values.
		 */
		private function mo_validate_otp( $post ) {
			$this->validate_challenge( $this->get_verification_type(), null, sanitize_text_field( $post['otp_token'] ) );
		}
		/**
		 * Initializes session and decides what type of OTP Verification
		 * needs to be done based on the OTP Type set by the admin
		 * in the backend.
		 *
		 * @param String $data Contains Email/Phone number.
		 * @throws ReflectionException Adds exception.
		 */
		private function mo_send_otp_fm_ajax_verify( $data ) {

			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->mo_send_fm_ajax_otp_to_phone( $data );
			} else {
				$this->mo_send_fm_ajax_otp_to_email( $data );
			}
		}
		/**
		 * Makes call to start OTP verification for Phone Type.
		 *
		 * @param String $data Phone number.
		 * @throws ReflectionException Adds exception.
		 */
		private function mo_send_fm_ajax_otp_to_phone( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_phone', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_PHONE ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->sendOTP( trim( $data['user_phone'] ), null, trim( $data['user_phone'] ), VerificationType::PHONE );
			}
		}
		/**
		 * Makes call to start OTP verification for Email Type.
		 *
		 * @param String $data Email id.
		 * @throws ReflectionException Adds exception.
		 */
		private function mo_send_fm_ajax_otp_to_email( $data ) {
			if ( ! MoUtility::sanitize_check( 'user_email', $data ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::ENTER_EMAIL ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->sendOTP( $data['user_email'], $data['user_email'], null, VerificationType::EMAIL );
			}
		}
		/**
		 * Return the Session Variable for which the OTP verification is to
		 * be enabled based on the OTP Type.
		 *
		 * @param string $field_values field values.
		 * @return bool
		 */
		private function checkPhoneOrEmailIntegrity( $field_values ) {
			if ( $this->get_verification_type() === VerificationType::PHONE ) {
				return SessionUtils::is_phone_verified_match( $this->form_session_var, $field_values );
			} else {
				return SessionUtils::is_email_verified_match( $this->form_session_var, $field_values );
			}
		}
		/**
		 * Sets the phone or email value to be authenticated in session and
		 * initiates the process for OTP Verification.
		 *
		 * @param String $session_value the email or phone to send OTP to.
		 * @param String $user_email the email of the user.
		 * @param String $phone_umber the phone number of the user.
		 * @param String $otp_type the OTP type set by the admin.
		 * @throws ReflectionException Adds exception.
		 */
		private function sendOTP( $session_value, $user_email, $phone_umber, $otp_type ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( VerificationType::PHONE === $otp_type ) {
				SessionUtils::add_phone_verified( $this->form_session_var, $session_value );
			} else {
				SessionUtils::add_email_verified( $this->form_session_var, $session_value );
			}
			$this->send_challenge( '', $user_email, null, $phone_umber, $otp_type );
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
			wp_send_json( MoUtility::create_json( MoUtility::get_invalid_otp_method(), MoConstants::ERROR_JSON_TYPE ) );
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
		 * */
		public function handle_post_verification( $redirect_to, $user_login, $user_email, $password, $phone_number, $extra_data, $otp_type ) {
			$sub_field = ! empty( sanitize_text_field( wp_unslash( $_POST['sub_field'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['sub_field'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( $this->checkPhoneOrEmailIntegrity( sanitize_text_field( $sub_field ) ) ) {
				$this->unset_otp_session_variables();
				wp_send_json( MoUtility::create_json( self::VALIDATED, MoConstants::SUCCESS_JSON_TYPE ) );
			} elseif ( $this->otp_type === $this->type_phone_tag ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
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
		 * @param  array $selector  the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			if ( $this->is_form_enabled() && $this->get_verification_type() === VerificationType::PHONE ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}
		/**
		 * Register the javascript to be added to the frontend which will handle
		 * making server side calls for OTP Verification.
		 *
		 * @todo Add nonce
		 */
		public function register_fm_button_script() {
			wp_register_script( 'fmotpbuttonscript', MOV_URL . 'includes/js/formmaker.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'fmotpbuttonscript',
				'mofmvar',
				array(
					'siteURL'     => site_url(),
					'otpType'     => $this->otp_type,
					'formDetails' => $this->form_details,
					'nonce'       => wp_create_nonce( $this->nonce ),
					'buttontext'  => mo_( $this->button_text ),
					'imgURL'      => MOV_URL . 'includes/images/loader.gif',
				)
			);
			wp_enqueue_script( 'fmotpbuttonscript' );
		}

		/**
		 * To update the form options after saving the settings in admin page
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			$form = $this->parseFormDetails( $data );

			$this->form_details    = ! empty( $form ) ? $form : '';
			$this->otp_type        = $this->sanitize_form_post( 'fm_enable_type' );
			$this->is_form_enabled = $this->sanitize_form_post( 'fm_enable' );
			$this->button_text     = $this->sanitize_form_post( 'fm_button_text' );

			if ( $this->basic_validation_check( BaseMessages::FORMMAKER_CHOOSE ) ) {
				update_mo_option( 'formmaker_enable', $this->is_form_enabled );
				update_mo_option( 'formmaker_enable_type', $this->otp_type );
				update_mo_option( 'formmaker_otp_enabled', maybe_serialize( $this->form_details ) );
				update_mo_option( 'formmaker_button_text', $this->button_text );
			}
		}
		/**
		 * To parse the form data to store in db
		 *
		 * @param array $data the data posted while savig the form.
		 *
		 * @return array form array to be saved
		 */
		private function parseFormDetails( $data ) {
			$form = array();
			if ( ! array_key_exists( 'formmaker_form', $data ) ) {
				return array();
			}
			$form_maker_data = isset( $data['formmaker_form']['form'] ) ? MoUtility::mo_sanitize_array( wp_unslash( $data['formmaker_form']['form'] ) ) : '';
			foreach ( array_filter( $form_maker_data ) as $key => $value ) {
				$value          = sanitize_text_field( $value );
				$form[ $value ] = array(
					'emailkey'    => isset( $data['formmaker_form']['emailkey'][ $key ] ) ? $this->mo_get_efield_id( sanitize_text_field( wp_unslash( $data['formmaker_form']['emailkey'][ $key ] ) ), $value ) : '',
					'phonekey'    => isset( $data['formmaker_form']['phonekey'][ $key ] ) ? $this->mo_get_efield_id( sanitize_text_field( wp_unslash( $data['formmaker_form']['phonekey'][ $key ] ) ), $value ) : '',
					'verifyKey'   => isset( $data['formmaker_form']['verifyKey'][ $key ] ) ? $this->mo_get_efield_id( sanitize_text_field( wp_unslash( $data['formmaker_form']['verifyKey'][ $key ] ) ), $value ) : '',
					'phone_show'  => isset( $data['formmaker_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formmaker_form']['phonekey'][ $key ] ) ) : '',
					'email_show'  => isset( $data['formmaker_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formmaker_form']['emailkey'][ $key ] ) ) : '',
					'verify_show' => isset( $data['formmaker_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formmaker_form']['verifyKey'][ $key ] ) ) : '',
				);
			}

			return $form;
		}
		/**
		 * To get the id associated with the label from the Form Maker
		 * database.
		 *
		 * @param string $label  Contains the label of the field.
		 * @param string $form   Contains form id.
		 * @return string Id of the mentioned field.
		 */
		private function mo_get_efield_id( $label, $form ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}formmaker where `id` = %s", array( $form ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.

			if ( MoUtility::is_blank( $row ) ) {
				return '';
			}

			$fields = explode( '*:*new_field*:*', $row->form_fields );
			$ids    = array();
			$types  = array();
			$labels = array();

			foreach ( $fields as $field ) {
				$temp = explode( '*:*id*:*', $field );
				if ( ! MoUtility::is_blank( $temp ) ) {
					array_push( $ids, $temp[0] );
					if ( array_key_exists( 1, $temp ) ) {
						$temp = explode( '*:*type*:*', $temp[1] );
						array_push( $types, $temp[0] );
						$temp = explode( '*:*w_field_label*:*', $temp[1] );
					}
					array_push( $labels, $temp[0] );
				}
			}
			$key = array_search( $label, $labels, true );
			return '#wdform_' . $ids[ $key ] . '_element' . $form;
		}
	}
}
