<?php
/**
 * Load admin view for Paid Membership Form.
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\FormSessionVars;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Helper\MoMessages;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;

/**
 * This is the Paid Membership Pro class. This class handles all the
 * functionality related to Paid Membership Pro Plugin. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'OTP\Handler\Forms\PaidMembershipForm' ) ) {
	/**
	 * PaidMembershipForm class
	 */
	class PaidMembershipForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = false;
			$this->form_session_var        = FormSessionVars::PMPRO_REGISTRATION;
			$this->form_key                = 'PM_PRO_FORM';
			$this->form_name               = 'Paid MemberShip Pro Registration Form';
			$this->phone_form_id           = 'input[name=phone_paidmembership]';
			$this->type_phone_tag          = 'pmpro_phone_enable';
			$this->type_email_tag          = 'pmpro_email_enable';
			$this->is_form_enabled         = get_mo_option( 'pmpro_enable' );
			$this->form_documents          = MoFormDocs::PAID_MEMBERSHIP_PRO;
			$this->restrict_duplicates     = get_mo_option( 'pmpro_restrict_duplicates' );
			parent::__construct();
		}



		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type = get_mo_option( 'pmpro_otp_type' );
			add_action( 'wp_enqueue_scripts', array( $this, 'show_phone_field_on_page' ) );
			add_action( 'pmpro_checkout_before_submit_button', array( $this, 'add_pmpro_registration_nonce' ) );
			add_filter( 'pmpro_registration_checks', array( $this, 'paid_membership_pro_registration_check' ), 1, 1 );
			add_action( 'user_register', array( $this, 'miniorange_registration_save' ), 10, 1 );
		}

		/**
		 * Outputs a nonce on the PM Pro checkout form so we can verify the request in miniorange_registration_save.
		 */
		public function add_pmpro_registration_nonce() {
			if ( ! $this->is_form_enabled ) {
				return;
			}
			wp_nonce_field( 'mo_pmpro_save_phone', 'mo_pmpro_save_phone_nonce' );
		}

		/**
		 * Saves the phone number to user meta when a user registers via Paid Membership Pro checkout.
		 * Runs on the user_register hook after PM Pro creates the user. Only saves when the request
		 * includes our nonce (i.e. came from the PM Pro checkout form) and phone_paidmembership is present.
		 *
		 * @param int $user_id The ID of the newly registered user.
		 */
		public function miniorange_registration_save( $user_id ) {
			if ( ! isset( $_POST['mo_pmpro_save_phone_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mo_pmpro_save_phone_nonce'] ) ), 'mo_pmpro_save_phone' ) ) {
				return;
			}
			if ( isset( $_POST['phone_paidmembership'] ) ) {
				update_user_meta( $user_id, 'mo_phone_number', MoUtility::process_phone_number( sanitize_text_field( wp_unslash( $_POST['phone_paidmembership'] ) ) ) );
			}
		}

		/**
		 * Hooks into the pmpro_checkout_before_processing hook of PaidMembership Form
		 * to check if otp verification needs to start. Checks for any paid membership
		 * pro form validation errors and then checks
		 *
		 * @param bool $continue_registration Whether to continue with registration (from PM Pro's pmpro_registration_checks filter).
		 * @throws ReflectionException .
		 */
		public function paid_membership_pro_registration_check( $continue_registration ) {
			global $pmpro_msgt;

			if ( 'pmpro_error' === $pmpro_msgt ) {
				return $continue_registration;
			}

			if ( ! isset( $_POST['mo_pmpro_save_phone_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mo_pmpro_save_phone_nonce'] ) ), 'mo_pmpro_save_phone' ) ) {
				if ( ! $this->is_form_enabled ) {
					return $continue_registration;
				}
				global $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling;
				$message              = MoMessages::showMessage( MoMessages::NONCE_FAILED );
				$pmpro_msgt           = 'pmpro_error';
				$pmpro_requirebilling = false;
				$pmpro_msg            = apply_filters( 'pmpro_set_message', $message, $pmpro_msgt );
				return false;
			}

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$verified_contact_matches = $this->otp_type === $this->type_phone_tag
					? SessionUtils::is_phone_verified_match( $this->form_session_var, isset( $_POST['phone_paidmembership'] ) ? MoUtility::process_phone_number( trim( sanitize_text_field( wp_unslash( $_POST['phone_paidmembership'] ) ) ) ) : '' )
					: SessionUtils::is_email_verified_match( $this->form_session_var, isset( $_POST['bemail'] ) ? sanitize_email( wp_unslash( $_POST['bemail'] ) ) : '' );
				if ( $verified_contact_matches ) {
					$this->unset_otp_session_variables();
					return $continue_registration;
				}
			}

			if ( $this->get_verification_type() === VerificationType::PHONE && ! $this->validate_phone( $_POST ) ) {
				return false;
			}
			MoUtility::initialize_transaction( $this->form_session_var );
			$this->start_otp_verification_process( $_POST );
		}

		/**
		 * This function starts the appropriate OTP Verification process based on the
		 * type of OTP Verification set by the admin in the plugin settings.
		 *
		 * @param object $data form data.
		 */
		private function start_otp_verification_process( $data ) {
			if ( $this->otp_type === $this->type_phone_tag ) {
				$this->send_challenge( '', '', null, trim( sanitize_text_field( $data['phone_paidmembership'] ) ), 'phone', null, null, null, $this->form_session_var );
			} elseif ( $this->otp_type === $this->type_email_tag ) {
				$this->send_challenge( '', sanitize_email( $data['bemail'] ), null, sanitize_email( $data['bemail'] ), 'email', null, null, null, $this->form_session_var );
			}
		}


		/**
		 * Validate Phone Number being submitted with the phone data. If the phone number is
		 * not in the correct format then throw an error using paid membership pro
		 * functions and global variables. The function returns if an existing error is
		 * found.
		 *
		 * Uses globals (defined outside this file):
		 * - $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling: Paid Membership Pro checkout
		 *   variables. We set them so PM Pro displays our error and stops registration.
		 * - $phone_logic: Instance of PhoneVerificationLogic (set in MoInit::initialize_globals()).
		 *   Used to get the invalid-phone message template via get_otp_invalid_format_message().
		 *
		 * @param object $data form data.
		 * @return bool True if validation passes, false otherwise.
		 */
		public function validate_phone( $data ) {
			global $pmpro_msg, $pmpro_msgt, $phone_logic, $pmpro_requirebilling;

			$phone_value = isset( $data['phone_paidmembership'] ) ? sanitize_text_field( $data['phone_paidmembership'] ) : '';
			if ( $this->restrict_duplicates ) {
				global $wpdb;
				$existing_user = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching is implemented above.
					$wpdb->prepare(
						"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'mo_phone_number' AND meta_value = %s",
						$phone_value
					)
				);
				if ( $existing_user ) {
					$message              = MoMessages::showMessage( MoMessages::PHONE_EXISTS );
					$pmpro_msgt           = 'pmpro_error';
					$pmpro_requirebilling = false;
					$pmpro_msg            = apply_filters( 'pmpro_set_message', $message, $pmpro_msgt );
					return false;
				}
			}

			if ( ! MoUtility::validate_phone_number( $phone_value ) ) {
				$message              = str_replace( '##phone##', esc_html( $phone_value ), $phone_logic->get_otp_invalid_format_message() );
				$pmpro_msgt           = 'pmpro_error';
				$pmpro_requirebilling = false;
				$pmpro_msg            = apply_filters( 'pmpro_set_message', $message, $pmpro_msgt );
				return false;
			}
			return true;
		}

		/**
		 * This function is called to append a script on the page that would
		 * append the phone field on the form.
		 */
		public function show_phone_field_on_page() {
			if ( $this->otp_type === $this->type_phone_tag ) {
				wp_enqueue_script(
					'paidmembershipscript',
					MOV_URL . 'includes/js/paidmembershippro.js',
					array( 'jquery' ),
					MOV_VERSION,
					false
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

			$otp_ver_type = $this->get_verification_type();
			$from_both    = VerificationType::BOTH === $otp_ver_type;
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
		 * Retrieves sanitized email and phone number data from the form.
		 *
		 * @return array {
		 *     @type string $email Sanitized email address.
		 *     @type string $phone Sanitized phone number.
		 * }
		 */
		public function get_email_phone_data() {
			if ( ! isset( $_POST['mopopup_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mopopup_wpnonce'] ) ), 'mo_popup_options' ) ) {
				return array(
					'email' => '',
					'phone' => '',
				);
			}
			$data  = MoUtility::mo_sanitize_array( $_POST );
			$phone = isset( $data['phone_paidmembership'] ) ? trim( $data['phone_paidmembership'] ) : '';
			$email = isset( $data['bemail'] ) ? sanitize_email( $data['bemail'] ) : '';
			return array(
				'email' => $email,
				'phone' => MoUtility::process_phone_number( $phone ),
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

			if ( self::is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				$selector[] = $this->phone_form_id;
			}
			return $selector;
		}


		/**
		 * Handles saving all the Classify Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option(), 'pmpro_enable' ) ) {
				return;
			}

			$this->is_form_enabled     = $this->sanitize_form_post( 'pmpro_enable' );
			$this->otp_type            = $this->sanitize_form_post( 'pmpro_contact_type' );
			$this->restrict_duplicates = $this->sanitize_form_post( 'pmpro_restrict_duplicates' );

			update_mo_option( 'pmpro_enable', $this->is_form_enabled );
			update_mo_option( 'pmpro_otp_type', $this->otp_type );
			update_mo_option( 'pmpro_restrict_duplicates', $this->restrict_duplicates );
		}
	}
}
