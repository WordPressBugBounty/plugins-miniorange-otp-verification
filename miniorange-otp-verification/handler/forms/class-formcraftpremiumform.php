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

use mysql_xdevapi\Session;
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
 * This is the FormCraft Premium form class. This class handles all the
 * functionality related to FormCraft Premium Plugin. It extends the
 * FormInterface class to implement some much needed functions.
 */
if ( ! class_exists( 'FormCraftPremiumForm' ) ) {
	/**
	 * FormCraftPremiumForm class
	 */
	class FormCraftPremiumForm extends FormHandler implements IFormHandler {

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
			$this->form_key                = 'FORMCRAFTPREMIUM';
			$this->form_name               = mo_( 'FormCraft (Premium Version)' );
			$this->is_form_enabled         = get_mo_option( 'fcpremium_enable' );
			$this->phone_form_id           = array();
			$this->form_documents          = MoFormDocs::FORMCRAFT_PREMIUM;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException -In case of failures, an exception is thrown.
		 */
		public function handle_form() {
			if ( ! MoUtility::get_active_plugin_version( 'FormCraft' ) ) {
				return;
			}
			$this->otp_type     = get_mo_option( 'fcpremium_enable_type' );
			$this->form_details = maybe_unserialize( get_mo_option( 'fcpremium_otp_enabled' ) );
			if ( empty( $this->form_details ) ) {
				return;
			}
			if ( $this->isFormCraftVersion3Installed() ) {
				foreach ( $this->form_details as $key => $value ) {
					array_push( $this->phone_form_id, 'input[name^=' . $value['phonekey'] . ']' );
				}
			} else {
				foreach ( $this->form_details as $key => $value ) {
					array_push( $this->phone_form_id, '.nform_li input[name^=' . $value['phonekey'] . ']' );
				}
			}

			add_action( 'wp_ajax_formcraft_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );
			add_action( 'wp_ajax_nopriv_formcraft_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );
			add_action( 'wp_ajax_formcraft3_form_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );
			add_action( 'wp_ajax_nopriv_formcraft3_form_submit', array( $this, 'validate_formcraft_form_submit' ), 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_on_page' ) );
			$this->routeData();
		}
		/**
		 * Initialize function to send OTP.
		 *
		 * @throws ReflectionException -In case of failures, an exception is thrown.
		 */
		private function routeData() {
			if ( ! array_key_exists( 'formcraft_prem_option', $_GET ) ) { // phpcs:ignore -- false positive.
				return;
			}

			if ( ! check_ajax_referer( $this->nonce, 'security', false ) ) {
				wp_send_json( MoUtility::create_json( 'Not a valid request !', MoConstants::ERROR_JSON_TYPE ) );
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

			switch ( trim( isset( $_GET['formcraft_prem_option'] ) ? sanitize_text_field( wp_unslash( $_GET['formcraft_prem_option'] ) ) : '' ) ) { // phpcs:ignore -- false positive.
				case 'miniorange-formcraftpremium-verify':
					$this->handle_formcraft_form( $data );
					break;
				case 'miniorange-formcraftpremium-form-otp-enabled':
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
		 * This function is used to send OTP to the user's phone setting it in
		 * session for future validation.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		private function send_otp_to_phone( $data ) {
			if ( array_key_exists( 'user_phone', $data ) && ! MoUtility::is_blank( sanitize_text_field( $data['user_phone'] ) ) ) {
				SessionUtils::add_phone_verified( $this->form_session_var, sanitize_text_field( $data['user_phone'] ) );
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
		 * This function is used to send OTP to the user's email setting it in
		 * session for future validation.
		 *
		 * @param array $data - an array containing the user's phone or email to send OTP to.
		 * @throws ReflectionException In case of failures, an exception is thrown.
		 */
		private function send_otp_to_email( $data ) {
			if ( array_key_exists( 'user_email', $data ) && ! MoUtility::is_blank( sanitize_email( $data['user_email'] ) ) ) {
				SessionUtils::add_email_verified( $this->form_session_var, sanitize_email( $data['user_email'] ) );
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

			$data = Moutility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			$id = sanitize_text_field( isset( $data['id'] ) ? sanitize_text_field( wp_unslash( $data['id'] ) ) : '' );

			if ( ! $this->isVerificationEnabledForThisForm( $id ) ) {
				return;
			}

			$form_data = $this->parseSubmittedData( $data, $id );
			$otp_type  = $this->get_verification_type();

			foreach ( $form_data as $key => $value ) {
				if ( VerificationType::PHONE === $otp_type && null !== $form_data[ $key ]['phone'] ) {
					$phone = $form_data[ $key ]['phone']['value'][0];
					$this->checkIfVerificationNotStarted( $form_data[ $key ]['phone']['field'] );
					if ( ! SessionUtils::is_phone_verified_match( $this->form_session_var, $phone ) ) {
						$this->sendJSONErrorMessage(
							MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
							$form_data[ $key ]['phone']['field']
						);
					}
				} elseif ( VerificationType::EMAIL === $otp_type && null !== $form_data[ $key ]['email'] ) {
					$email = $form_data[ $key ]['email']['value'];
					$this->checkIfVerificationNotStarted( $form_data[ $key ]['email']['field'] );
					if ( ! SessionUtils::is_email_verified_match( $this->form_session_var, $email ) ) {
						$this->sendJSONErrorMessage(
							MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ),
							$form_data[ $key ]['email']['field']
						);
					}
				}
				if ( null !== $form_data[ $key ]['otp'] ) {
					$otp      = $form_data[ $key ]['otp']['value'];
					$otpfield = $form_data[ $key ]['otp']['field'];
				}
			}
			if ( MoUtility::is_blank( $otp ) ) {
				$this->sendJSONErrorMessage( MoUtility::get_invalid_otp_method(), $otpfield );
			}
			SessionUtils::set_form_or_field_id( $this->form_session_var, $otpfield );
			$this->validate_challenge( $otp_type, null, $otp );
		}

		/**
		 * This function is used to enqueue script on the frontend to facilitate
		 * OTP Verification for the FormCraft form. This function
		 * also localizes certain values required by the script.
		 */
		public function enqueue_script_on_page() {
			wp_register_script( 'fcpremiumscript', MOV_URL . 'includes/js/formcraftpremium.min.js?version=' . MOV_VERSION, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'fcpremiumscript',
				'mofcpvars',
				array(
					'imgURL'         => MOV_LOADER_URL,
					'formCraftForms' => $this->form_details,
					'siteURL'        => site_url(),
					'otpType'        => $this->otp_type,
					'nonce'          => wp_create_nonce( $this->nonce ),
					'buttonText'     => mo_( 'Click here to send OTP' ),
					'buttonTitle'    => $this->otp_type === $this->type_phone_tag ?
						mo_( 'Please enter a Phone Number to enable this field.' )
						: mo_( 'Please enter an email address to enable this field.' ),
					'ajaxurl'        => wp_ajax_url(),
					'typePhone'      => $this->type_phone_tag,
					'countryDrop'    => get_mo_option( 'show_dropdown_on_form' ),
					'version3'       => $this->isFormCraftVersion3Installed(),
				)
			);
			wp_enqueue_script( 'fcpremiumscript' );
		}

		/**
		 * This function is used to fetch the values for email, phone and otp fields
		 * based on the form submitted. The field is chosen based off on the label which is
		 * saved by the admin in the plugin settings
		 *
		 * @param  array  $post - the posted data.
		 * @param  string $id - the id of the form submitted.
		 * @return array - an array containing all the values we need.
		 */
		private function parseSubmittedData( $post, $id ) {
			$data = array();
			$form = $this->form_details[ $id ];
			foreach ( $post as $key => $value ) {
				if ( strpos( $key, 'field' ) === false ) {
					continue;
				}
				$emailfieldkey = $this->getValueAndFieldFromPost( $data, 'email', $key, str_replace( ' ', '_', $form['emailkey'] ), $value );
				if ( null !== $emailfieldkey ) {
					array_push( $data, $emailfieldkey );
				}
				$phonefieldkey = $this->getValueAndFieldFromPost( $data, 'phone', $key, str_replace( ' ', '_', $form['phonekey'] ), $value );
				if ( null !== $phonefieldkey ) {
					array_push( $data, $phonefieldkey );
				}
				$otpfieldkey = $this->getValueAndFieldFromPost( $data, 'otp', $key, str_replace( ' ', '_', $form['verifyKey'] ), $value );
				if ( null !== $otpfieldkey ) {
					array_push( $data, $otpfieldkey );
				}
			}
			return $data;
		}
		/**
		 * This function is used to fetch the values for email, phone and otp fields
		 * based on the form submitted.
		 *
		 * @param  array  $data - the posted data.
		 * @param  string $data_key - key of form detail.
		 * @param  string $post_key - the id of the form submitted.
		 * @param  string $check_key - the key.
		 * @param  string $value - value against the form details.
		 * @return array|void - an array containing all the values we need.
		 */
		private function getValueAndFieldFromPost( $data, $data_key, $post_key, $check_key, $value ) {
			if ( is_null( $data[ $check_key ] ) && strpos( $post_key, $check_key, 0 ) !== false ) {
				$data[ $data_key ]['value'] = $this->isFormCraftVersion3Installed() && 'otp' === $data_key ? $value[0] : $value;
				$index                      = strpos( $post_key, 'field', 0 );
				$data[ $data_key ]['field'] = $this->isFormCraftVersion3Installed() ? $check_key
				: substr( $post_key, $index, strpos( $post_key, '_', $index ) - $index );
				return $data;
			} else {
				return;
			}
		}

		/**
		 * Checks if otp verification has been started for the form id passed to the
		 * function. Returns true or false.
		 *
		 * @param string $id - The FormCraft form id.
		 * @return boolean
		 */
		private function isVerificationEnabledForThisForm( $id ) {
			return array_key_exists( $id, $this->form_details );
		}
		/**
		 * This function is used to send the JSON error response to the form
		 * so that the user can be presented with the appropriate message.
		 *
		 * @param  string $errors - the array defining the error and the field it is associated to.
		 * @param  string $field  - the field against which error has to be shown.
		 */
		private function sendJSONErrorMessage( $errors, $field ) {
			if ( $this->isFormCraftVersion3Installed() ) {
				$response['failed']           = mo_( 'Please correct the errors and try again' );
				$response['errors'][ $field ] = $errors;
			} else {
				$response['errors']    = mo_( 'Please correct the errors and try again' );
				$response[ $field ][0] = $errors;
			}
			echo wp_json_encode( $response );
			die();
		}

		/**
		 * This function checks if the OTP is initialized.
		 *
		 * @param string $error_field The field that contains error.
		 */
		private function checkIfVerificationNotStarted( $error_field ) {
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}

			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->sendJSONErrorMessage( MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ), $error_field );
			} else {
				$this->sendJSONErrorMessage( MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ), $error_field );
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
			$this->sendJSONErrorMessage( MoUtility::get_invalid_otp_method(), $form_id );
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
		 * Fetches the EmailField Id from the formData based on the
		 * name provided by the user.
		 *
		 * @param string $data Data recieved on the form submission.
		 * @param array  $form_data Data recieved on the form submission.
		 * @return mixed
		 */
		private function getFieldId( $data, $form_data ) {
			foreach ( $form_data as $form ) {
				if ( $form['elementDefaults']['main_label'] === $data ) {
					return $form['identifier'];
				}
			}
			return null;
		}

		/**
		 * Get the form data associated with FormCraft form ID
		 *
		 * @param string $id the ID of the form.
		 * @return array
		 */
		private function getFormCraftFormDataFromID( $id ) {
			global $wpdb;
			$meta = $wpdb->get_var( $wpdb->prepare( "SELECT meta_builder FROM {$wpdb->prefix}formcraft_3_forms WHERE id= %s", array( $id ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			$meta = json_decode( stripcslashes( $meta ), 1 );
			return isset( $meta['fields'] ) ? $meta['fields'] : '';
		}

		/**
		 * Checks if the plugin is installed or not. Returns true or false.
		 *
		 * @return boolean
		 */
		private function isFormCraftVersion3Installed() {
			return MoUtility::get_active_plugin_version( 'FormCraft' ) === 3 ? true : false;
		}

		/**
		 * Handles saving all the FormCraft Basic form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			if ( ! MoUtility::get_active_plugin_version( 'FormCraft' ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$data = MoUtility::mo_sanitize_array( $_POST );

			$form = array();

			if ( isset( $data['fcpremium_form']['form'] ) ) {
				foreach ( array_filter( $data['fcpremium_form']['form'] ) as $key => $value ) {  //phpcs:ignore -- $data is an array but considered as a string (false positive).
					$value = sanitize_text_field( $value );
					! $this->isFormCraftVersion3Installed() ? $this->processAndGetFormData( $data, $key, $value, $form )
					: $this->processAndGetForm3Data( $data, $key, $value, $form );
				}
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'fcpremium_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'fcpremium_enable_type' );
			$this->form_details    = ! empty( $form ) ? $form : '';

			update_mo_option( 'fcpremium_enable', $this->is_form_enabled );
			update_mo_option( 'fcpremium_enable_type', $this->otp_type );
			update_mo_option( 'fcpremium_otp_enabled', maybe_serialize( $this->form_details ) );
		}

		/**
		 * This function generates the required email,phone and verification field key
		 * parameters to be used to do the OTP generation and verification for version
		 * 2.x or below.
		 *
		 * @param array  $post   The data submitted.
		 * @param string $key    The key to check value against.
		 * @param string $value  The for id of the form.
		 * @param array  $form   The form array.
		 */
		private function processAndGetFormData( $post, $key, $value, &$form ) {
			$form[ $value ] = array(
				'emailkey'    => str_replace( ' ', ' ', sanitize_text_field( $post['fcpremium_form']['emailkey'][ $key ] ) ) . '_email_email_',
				'phonekey'    => str_replace( ' ', ' ', sanitize_text_field( $post['fcpremium_form']['phonekey'][ $key ] ) ) . '_text_',
				'verifyKey'   => str_replace( ' ', ' ', sanitize_text_field( $post['fcpremium_form']['verifyKey'][ $key ] ) ) . '_text_',
				'phone_show'  => sanitize_text_field( $post['fcpremium_form']['phonekey'][ $key ] ),
				'email_show'  => sanitize_text_field( $post['fcpremium_form']['emailkey'][ $key ] ),
				'verify_show' => sanitize_text_field( $post['fcpremium_form']['verifyKey'][ $key ] ),
			);
		}

		/**
		 * This function generates the required email,phone and verification field key
		 * parameters to be used to do the OTP generation and verification for version
		 * 3 or above.
		 *
		 * @param array  $data   this is the Formcraft basics form variable containing the form data.
		 * @param string $key    The key to check value against.
		 * @param string $value  The for id of the form.
		 * @param array  $form   The form array.
		 */
		private function processAndGetForm3Data( $data, $key, $value, &$form ) {
			$form_data = $this->getFormCraftFormDataFromID( $value );
			if ( MoUtility::is_blank( $form_data ) ) {
				return;
			}
			$form[ $value ] = array(
				'emailkey'    => $this->getFieldId( isset( $data['fcpremium_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['emailkey'][ $key ] ) ) : '', $form_data ),
				'phonekey'    => $this->getFieldId( isset( $data['fcpremium_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['phonekey'][ $key ] ) ) : '', $form_data ),
				'verifyKey'   => $this->getFieldId( isset( $data['fcpremium_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['verifyKey'][ $key ] ) ) : '', $form_data ),
				'phone_show'  => isset( $data['fcpremium_form']['phonekey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['phonekey'][ $key ] ) ) : '',
				'email_show'  => isset( $data['fcpremium_form']['emailkey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['emailkey'][ $key ] ) ) : '',
				'verify_show' => isset( $data['fcpremium_form']['verifyKey'][ $key ] ) ? sanitize_text_field( wp_unslash( $data['fcpremium_form']['verifyKey'][ $key ] ) ) : '',
			);
		}
	}
}
