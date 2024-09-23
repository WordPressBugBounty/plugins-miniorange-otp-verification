<?php
/**
 * Load admin view for User WooCommerce Billing form.
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
 * This is the WooCommerce Billing class. This class handles all the
 * functionality related to Billing Profile details. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WooCommerceBilling' ) ) {
	/**
	 * WooCommerceBilling class
	 */
	class WooCommerceBilling extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::WC_BILLING;
			$this->type_phone_tag          = 'mo_wcb_phone_enable';
			$this->type_email_tag          = 'mo_wcb_email_enable';
				$this->phone_form_id       = '#billing_phone';
			$this->form_key                = 'WC_BILLING_FORM';
			$this->form_name               = mo_( 'WooCommerce Billing Address Form' );
			$this->is_form_enabled         = get_mo_option( 'wc_billing_enable' );
			$this->button_text             = get_mo_option( 'wc_billing_button_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text : mo_( 'Click Here to send OTP' );
			$this->form_documents          = MoFormDocs::WC_BILLING_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->restrict_duplicates = get_mo_option( 'wc_billing_restrict_duplicates' );
			$this->otp_type            = get_mo_option( 'wc_billing_type_enabled' );
			if ( $this->otp_type === $this->type_email_tag ) {
				add_filter( 'woocommerce_process_myaccount_field_billing_email', array( $this, 'mo_wc_user_account_update' ), 99, 1 );
			} else {
				add_filter( 'woocommerce_process_myaccount_field_billing_phone', array( $this, 'mo_wc_user_account_update' ), 99, 1 );
			}
		}


		/**
		 * This function is hooked to process and start the otp verification process.
		 * It hooks to WooCommerce billing field update hook when user saves the details.
		 * <ol>
		 *      <li>woocommerce_process_myaccount_field_billing_email</li>
		 *      <li>woocommerce_process_myaccount_field_billing_phone</li>
		 * </ol>
		 *
		 * @param string $value email/phone number value that was updated.
		 * @return mixed.
		 * @throws ReflectionException .
		 */
		public function mo_wc_user_account_update( $value ) {

			$value = $this->otp_type === $this->type_phone_tag ? MoUtility::process_phone_number( $value ) : $value;
			$type  = $this->get_verification_type();

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $type ) ) {
				$this->unset_otp_session_variables();
				return $value;
			}

			if ( $this->userHasNotChangeData( $value ) ) {
				return $value;
			}

			if ( $this->otp_type === $this->type_phone_tag && $this->restrict_duplicates && $this->isDuplicate( $value, $type ) ) {
				return $value;
			}

			MoUtility::initialize_transaction( $this->form_session_var );
			$billing_email = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			$billing_phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			$this->send_challenge( null, $billing_email, null, $billing_phone, $type );
			return $value;
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
			$otp_ver_type = $this->get_verification_type();
			$form_both    = VerificationType::BOTH === $otp_ver_type ? true : false;
			miniorange_site_otp_validation_form(
				$user_login,
				$user_email,
				$phone_number,
				MoUtility::get_invalid_otp_method(),
				$otp_ver_type,
				$form_both
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
			SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
		}



		/**
		 * Checks if the data has been changed or not as in saved data
		 *
		 * @param  string $value   phone/email.
		 * @return boolean
		 */
		private function userHasNotChangeData( $value ) {
			$data = $this->getUserData();
			return strcasecmp( $data, $value ) === 0;
		}

		/**
		 * Fetches user phone or email of the current logged in user from db
		 *
		 * @return string  the meta key of the field if exists
		 */
		private function getUserData() {
			global $wpdb;
			$current_user = wp_get_current_user();
			$key          = ( $this->otp_type === $this->type_phone_tag ) ? 'billing_phone' : 'billing_email';
			$results      = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `user_id` = %s", array( $key, $current_user->ID ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return isset( $results ) ? $results->meta_value : '';
		}

		/**
		 * Checks for duplicate/existing account with entered email/phone
		 *
		 * @param  string $value  Phone/Email entered.
		 * @param  string $type   Whether its phone or email verification.
		 * @return bool
		 */
		private function isDuplicate( $value, $type ) {
			global $wpdb;
			$key     = 'billing_' . $type;
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %d", array( $key, $value ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.

			if ( isset( $results ) ) {
				if ( VerificationType::PHONE === $type ) {
					wc_add_notice( MoMessages::showMessage( MoMessages::PHONE_EXISTS ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				} elseif ( VerificationType::EMAIL === $type ) {
					wc_add_notice( MoMessages::showMessage( MoMessages::EMAIL_EXISTS ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				}
				return true;
			}
			return false;
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

			if ( $this->is_form_enabled && ( $this->otp_type === $this->type_phone_tag ) ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the visual Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			$this->is_form_enabled     = $this->sanitize_form_post( 'wc_billing_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'wc_billing_type_enabled' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'wc_billing_restrict_duplicates' );

			if ( $this->basic_validation_check( BaseMessages::WC_BILLING_CHOOSE ) ) {
				update_mo_option( 'wc_billing_enable', $this->is_form_enabled );
				update_mo_option( 'wc_billing_type_enabled', $this->otp_type );
				update_mo_option( 'wc_billing_restrict_duplicates', $this->restrict_duplicates );
			}
		}
	}
}
