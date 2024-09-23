<?php
/**
 * Load admin view for Form Craft Basic Form.
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
 * This is the FormCraft Basic form class. This class handles all the
 * functionality related to FormCraft. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'FormCraftBasicForm' ) ) {
	/**
	 * FormCraftBasicForm class
	 */
	class FormCraftBasicForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::FORMCRAFT;
			$this->type_phone_tag          = 'mo_formcraft_phone_enable';
			$this->type_email_tag          = 'mo_formcraft_email_enable';
			$this->form_key                = 'FORMCRAFTBASIC';
			$this->form_name               = mo_( 'FormCraft Basic (Free Version)' );
			$this->is_form_enabled         = get_mo_option( 'formcraft_enable' );
			$this->phone_form_id           = array();
			$this->form_documents          = MoFormDocs::FORMCRAFT_BASIC_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			if ( ! $this->isFormCraftPluginInstalled() ) {
				return;
			}
			$this->otp_type     = get_mo_option( 'formcraft_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'formcraft_otp_enabled' ) );
			if ( empty( $this->form_details ) ) {
				return;
			}
			foreach ( $this->form_details as $key => $value ) {
				array_push( $this->phone_form_id, '[data-id=' . $key . '] input[name=' . $value['phonekey'] . ']' );
			}

			add_action( 'wp_ajax_formcraft_basic_form_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );
			add_action( 'wp_ajax_nopriv_formcraft_basic_form_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );

			add_action( 'wp_ajax_unset_formcraft_basic_session', array( $this, 'unset_otp_session_variables' ) );
			add_action( 'wp_ajax_nopriv_unset_formcraft_basic_session', array( $this, 'unset_otp_session_variables' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_on_page' ) );
			$this->routeData();
		}

		/**
		 * Initialize function to send OTP.
		 *
		 * @throws ReflectionException -In case of failures, an exception is thrown.
		 */
		private function routeData() {
			if ( ! array_key_exists( 'mo_formcraft_basic_option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}

			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			switch ( trim( isset( $_GET['mo_formcraft_basic_option'] ) ? sanitize_text_field( wp_unslash( $_GET['mo_formcraft_basic_option'] ) ) : '' ) ) { // phpcs:ignore -- false positive.
				case 'miniorange-formcraft-verify':
					$this->handle_formcraft_form( $data );
					break;
				case 'miniorange-formcraft-form-otp-enabled':
					wp_send_json(
						$this->isVerificationEnabledForThisForm(
							isset( $data['form_id'] ) ? sanitize_text_field( wp_unslash( $data['form_id'] ) ) : ''
						)
					);
					break;
			}
		}

		/**
		 * This function is used to send OTP to the user's email or phone
		 * by checking what kind of otp has been enabled by the admin.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 * @throws ReflectionException -In case of failures, an exception is thrown.
		 */
		private function handle_formcraft_form( $data ) {

			if ( ! $this->isVerificationEnabledForThisForm( isset( $data['form_id'] ) ? sanitize_text_field( wp_unslash( $data['form_id'] ) ) : '' ) ) {
				return;
			}
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
		private function send_otp_to_phone( $data ) {
			if ( array_key_exists( 'user_phone', $data ) && ! MoUtility::is_blank( $data['user_phone'] ) ) {
				SessionUtils::add_phone_verified( $this->form_session_var, $data['user_phone'] );
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
		private function send_otp_to_email( $data ) {
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
		 * Hooks into the wp_ajax_ hook to handle the form validation. Validates if the OTP was
		 * sent to the user and if he has entered the correct OTP in the verification field.
		 * If verification is successful let it go forward or return an error to be thrown
		 * to the user.
		 */
		public function validate_formcraft_form_submit() {

			$formcraft_nonce = wp_create_nonce( 'formcraft_nonce' );
			if ( ! wp_verify_nonce( $formcraft_nonce, 'formcraft_nonce' ) === 1 ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			$id = sanitize_text_field( $data['id'] );
			if ( ! $this->isVerificationEnabledForThisForm( $id ) ) {
				return;
			}

			$this->checkIfVerificationNotStarted( $id );
			$form_data = $this->form_details[ $id ];
			$otp_type  = $this->get_verification_type();

			if ( VerificationType::PHONE === $otp_type
			&& ! SessionUtils::is_phone_verified_match(
				$this->form_session_var,
				isset( $data[ $form_data['phonekey'] ] ) ? sanitize_text_field( wp_unslash( $data[ $form_data['phonekey'] ] ) ) : ''
			) ) {
				$this->sendJSONErrorMessage(
					array(
						'errors' => array(
							$this->form_details[ $id ]['phonekey'] => MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
						),
					)
				);
			} elseif ( VerificationType::EMAIL === $otp_type
			&& ! SessionUtils::is_email_verified_match(
				$this->form_session_var,
				isset( $data[ $form_data['emailkey'] ] ) ? sanitize_text_field( wp_unslash( $data[ $form_data['emailkey'] ] ) ) : ''
			) ) {
				$this->sendJSONErrorMessage(
					array(
						'errors' => array(
							$this->form_details[ $id ]['emailkey'] => MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
						),
					)
				);
			}

			if ( ! MoUtility::sanitize_check( $data, $form_data['verifyKey'] ) ) {
				$this->sendJSONErrorMessage(
					array(
						'errors' => array(
							$this->form_details[ $id ]['verifyKey'] => MoUtility::get_invalid_otp_method(),
						),
					)
				);
			}
			SessionUtils::set_form_or_field_id( $this->form_session_var, $id );

			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type ) ) {

				$this->validate_challenge(
					$otp_type,
					null,
					isset( $data[ $form_data['verifyKey'] ] ) ? sanitize_text_field( wp_unslash( $data[ $form_data['verifyKey'] ] ) ) : ''
				);
			}

		}

		/**
		 * This function is used to enqueue script on the frontend to facilitate
		 * OTP Verification for the FormCraft form. This function
		 * also localizes certain values required by the script.
		 */
		public function enqueue_script_on_page() {
			wp_register_script( 'formcraftscript', MOV_URL . 'includes/js/formcraftbasic.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'formcraftscript',
				'mofcvars',
				array(
					'imgURL'         => MOV_LOADER_URL,
					'formCraftForms' => $this->form_details,
					'siteURL'        => site_url(),
					'ajaxURL'        => wp_ajax_url(),
					'nonce'          => wp_create_nonce( $this->nonce ),
					'otpType'        => $this->otp_type,
					'buttonText'     => mo_( 'Click here to send OTP' ),
					'buttonTitle'    => $this->otp_type === $this->type_phone_tag ?
										mo_( 'Please enter a Phone Number to enable this field.' )
										: mo_( 'Please enter an email address to enable this field.' ),
					'ajaxurl'        => wp_ajax_url(),
					'typePhone'      => $this->type_phone_tag,
					'countryDrop'    => get_mo_option( 'show_dropdown_on_form' ),
				)
			);
			wp_enqueue_script( 'formcraftscript' );
		}
		/**
		 * Checks if otp verification has been started for the form id passed to the
		 * function. Returns true or false.
		 *
		 * @param string $id - The FormCraft form id.
		 * @return boolean
		 */
		public function isVerificationEnabledForThisForm( $id ) {
			return array_key_exists( $id, $this->form_details );
		}
		/**
		 * This function is used to send the JSON error response to the form
		 * so that the user can be presented with the appropriate message.
		 *
		 * @param array $errors - the array defining the error and the field it is associated to.
		 */
		private function sendJSONErrorMessage( $errors ) {
			$response['failed'] = mo_( 'Please correct the errors' );
			$response['errors'] = $errors;
			echo wp_json_encode(
				$response
			);
			die();
		}

		/**
		 * This function is used to check if the verification has started.
		 *
		 * @param string $id - the id of the user.
		 */
		private function checkIfVerificationNotStarted( $id ) {
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}

			$error_message = MoMessages::showMessage( MoMessages::PLEASE_VALIDATE );
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->sendJSONErrorMessage(
					array(
						'errors' => array( $this->form_details[ $id ]['phonekey'] => $error_message ),
					)
				);
			} else {
				$this->sendJSONErrorMessage(
					array(
						'errors' => array( $this->form_details[ $id ]['emailkey'] => $error_message ),
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

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}
			$form_id = SessionUtils::get_form_or_field_id( $this->form_session_var );
			SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
			$this->sendJSONErrorMessage(
				array(
					'errors' => array( $this->form_details[ $form_id ]['verifyKey'] => MoUtility::get_invalid_otp_method() ),
				)
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
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
			wp_send_json(
				MoUtility::create_json(
					'unset variable success',
					MoConstants::SUCCESS_JSON_TYPE
				)
			);
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
				$selector = array_merge( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Checks if the plugin is installed or not. Returns true or false.
		 *
		 * @return boolean
		 */
		private function isFormCraftPluginInstalled() {
			return MoUtility::get_active_plugin_version( 'FormCraft' ) < 3 ? true : false;
		}

		/**
		 * Handles saving all the FormCraft Basic form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			if ( ! $this->isFormCraftPluginInstalled() ) {
				return;
			}         if ( ! array_key_exists( 'formcraft_form', $_POST ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) { // phpcs:ignore -- false positive.
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			foreach ( array_filter( $data['formcraft_form']['form'] ) as $key => $value ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				$value     = sanitize_text_field( $value );
				$form_data = $this->getFormCraftFormDataFromID( $value );
				if ( MoUtility::is_blank( $form_data ) ) {
					continue;
				}
				$field_ids      = $this->getFieldIDs( $data, $key, $form_data );
				$form[ $value ] = array(
					'emailkey'    => $field_ids['emailKey'],
					'phonekey'    => $field_ids['phoneKey'],
					'verifyKey'   => $field_ids['verifyKey'],
					'phone_show'  => isset( $data['formcraft_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formcraft_form']['phonekey'][ $key ] ) ) : '',
					'email_show'  => isset( $data['formcraft_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formcraft_form']['emailkey'][ $key ] ) ) : '',
					'verify_show' => isset( $data['formcraft_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['formcraft_form']['verifyKey'][ $key ] ) ) : '',
				);
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'formcraft_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'formcraft_enable_type' );
			$this->form_details    = ! empty( $form ) ? $form : '';

			update_mo_option( 'formcraft_enable', $this->is_form_enabled );
			update_mo_option( 'formcraft_enable_type', $this->otp_type );
			update_mo_option( 'formcraft_otp_enabled', maybe_serialize( $this->form_details ) );
		}

		/**
		 * Fetches the EmailField Id from the formData based on the
		 * name provided by the user.
		 *
		 * @param array  $data Data recieved on the form submission.
		 * @param string $key Form id of the form.
		 * @param array  $form_data Data recieved on the form submission.
		 * @return array
		 */
		private function getFieldIDs( $data, $key, $form_data ) {
			$field_ids = array(
				'emailKey'  => '',
				'phoneKey'  => '',
				'verifyKey' => '',
			);
			if ( empty( $data ) ) {
				return $field_ids;
			}
			foreach ( $form_data as $form ) {
				if ( strcasecmp( $form['elementDefaults']['main_label'], sanitize_text_field( $data['formcraft_form']['emailkey'][ $key ] ) ) === 0 ) {
					$field_ids['emailKey'] = $form['identifier'];
				}
				if ( strcasecmp( $form['elementDefaults']['main_label'], sanitize_text_field( $data['formcraft_form']['phonekey'][ $key ] ) ) === 0 ) {
					$field_ids['phoneKey'] = $form['identifier'];
				}
				if ( strcasecmp( $form['elementDefaults']['main_label'], sanitize_text_field( $data['formcraft_form']['verifyKey'][ $key ] ) ) === 0 ) {
					$field_ids['verifyKey'] = $form['identifier'];
				}
			}
			return $field_ids;
		}

		/**
		 * Get the form data associated with FormCraft form ID
		 *
		 * @param string $id the ID of the form.
		 * @return array
		 */
		private function getFormCraftFormDataFromID( $id ) {
			global $wpdb,$forms_table;
			$meta = $wpdb->get_var( $wpdb->prepare( "SELECT meta_builder FROM {$wpdb->prefix}formcraft_b_forms WHERE id= %s", array( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			$meta = json_decode( stripcslashes( $meta ), 1 );
			return $meta['fields'];
		}
	}
}
