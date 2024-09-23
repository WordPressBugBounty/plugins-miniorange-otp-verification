<?php
/**
 * Load admin view for Eduma Theme Login Form.
 *
 * @package miniorange-otp-verification/handler
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
use \WP_Error;
use \WP_User;

/**
 * This is the Eduma Theme Login class. This class handles all the
 * functionality related to Eduma Theme Login form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'Edumalog' ) ) {
	/**
	 * Edumalog class
	 */
	class Edumalog extends FormHandler implements IFormHandler {

		use Instance;

		/** Global Variable declaration
		 *
		 * @var by_pass_admin to check if administrator.
		 **/
		private $by_pass_admin;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->form_session_var        = FormSessionVars::EDUMALOG;
			$this->type_phone_tag          = 'mo_edumalog_phone_enable';
			$this->type_email_tag          = 'mo_edumalog_email_enable';
			$this->form_key                = 'EDUMA_LOGIN';
			$this->form_name               = mo_( 'Eduma Theme Login Form' );
			$this->is_form_enabled         = get_mo_option( 'edumalog_enable' );
			$this->phone_form_id           = 'input[name=phone_number]';
			$this->form_documents          = MoFormDocs::EDUMA_LOG;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 **/
		public function handle_form() {

			$this->otp_type      = get_mo_option( 'edumalog_enable_type' );
			$this->phone_key     = get_mo_option( 'edumalog_phone_field_key' );
			$this->by_pass_admin = get_mo_option( 'edumalog_bypass_admin' );

			add_action( 'login_enqueue_scripts', array( $this, 'miniorange_register_login_script' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_login_script' ) );
			add_filter( 'authenticate', array( $this, 'handle_mo_eduma_login' ), 10, 3 );

		}
		/**
		 * The function hooks into the authenticate hook of WordPress to
		 * start the OTP Verification process.
		 *
		 * @param string $user - the WordPress user data object containing all the user information.
		 * @param string $username - username of the user trying to log in.
		 * @param string $password - password of the user trying to log in.
		 * @return WP_Error|WP_User
		 */
		public function handle_mo_eduma_login( $user, $username, $password ) {
			$post_data = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( ! array_key_exists( 'eduma_login_user', $post_data ) ) {
				return;
			}
			if ( ! MoUtility::is_blank( $username ) ) {
				$user      = $this->getUser( $username, $password );
				$user_meta = get_userdata( $user->data->ID );
				$user_role = $user_meta->roles;
				if ( ( $this->by_pass_admin ) && ( in_array( 'administrator', $user_role, true ) ) ) {
					return;
				}
				if ( is_wp_error( $user ) ) {
					return $user;
				}
				$this->startOTPVerificationProcess( $user, $username, $password );
			}
			return $user;
		}
		/**
		 * Function checks the type of verification enabled by the admins and then starts the appropriate
		 * OTP Verification.
		 *
		 * @param WP_User $user the user object of the user who needs to be logged in.
		 * @param string  $username the username provided by the user.
		 * @param string  $password the password provided by the user.
		 */
		public function startOTPVerificationProcess( $user, $username, $password ) {

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, VerificationType::PHONE ) ) {
				$this->unset_otp_session_variables();
				return;
			}

			if ( $this->otp_type === $this->type_phone_tag ) {
				$phone_number = get_user_meta( $user->data->ID, $this->phone_key, true );
				if ( empty( $phone_number ) ) {
					miniorange_site_otp_validation_form( null, null, null, MoMessages::showMessage( MoMessages::PHONE_NOT_FOUND ), null, null );
				}
				$phone_number = $this->check_phone_length( $phone_number );
				$this->fetchPhoneAndStartVerification( $username, $password, $phone_number );
			} elseif ( $this->otp_type === $this->type_email_tag ) {
				$email = $user->data->user_email;
				$this->startEmailVerification( $username, $email );
			}

		}

		/**
		 * This functions is used to  start the otp verification process via email.
		 *
		 * @param string $username - the user's username.
		 * @param string $email - email to send otp to.
		 */
		public function startEmailVerification( $username, $email ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			$this->send_challenge( $username, $email, null, null, VerificationType::EMAIL );
		}
		/**
		 * This functions is used to fetch the phone number from the database and start
		 * the OTP Verification process.
		 *
		 * @param string $username - the user's username.
		 * @param string $password - the password provided by the user.
		 * @param string $phone_number - phone number to send otp to.
		 */
		public function fetchPhoneAndStartVerification( $username, $password, $phone_number ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			$req_data    = MoUtility::mo_sanitize_array( $_REQUEST ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need for nonce verification as the function is called on third party plugin hook.
			$redirect_to = isset( $req_data['redirect_to'] ) ? sanitize_text_field( $req_data['redirect_to'] ) : MoUtility::current_page_url();
			$this->send_challenge( $username, null, null, $phone_number, VerificationType::PHONE, $password, $redirect_to, false );
		}
		/**  Function to check the length of the phone number
		 *
		 * @param string $phone - phone number to check length.
		 * */
		private function check_phone_length( $phone ) {
			$phone_check = MoUtility::process_phone_number( $phone );
			return strlen( $phone_check ) >= 5 ? $phone_check : '';

		}

		/**
		 * This functions checks if user has enabled phone number as a valid username and fetches the user
		 * associated with the phone number. Checks if the skip Password is enabled with feedback to handle
		 * OTP login and normal login.
		 *
		 * @param string $username           the user's username.
		 * @param string $password           the users's password.
		 * @return WP_Error|WP_User
		 */
		public function getUser( $username, $password = null ) {
			$user = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			if ( $this->type_phone_tag && MoUtility::validate_phone_number( $username ) ) {
				$username = MoUtility::process_phone_number( $username );
				$user     = $this->getUserFromPhoneNumber( $username );
			}
			$user = wp_authenticate_username_password( null, $user->data->user_login, $password );

			return $user ? $user : new WP_Error( 'INVALID_USERNAME', mo_( ' <b>ERROR:</b> Invalid UserName. ' ) );
		}
		/**
		 * This functions fetches the user associated with a phone number
		 *
		 * @param string $username  the user's username.
		 * @return bool|WP_User
		 */
		public function getUserFromPhoneNumber( $username ) {
			global $wpdb;
			$results = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
				$wpdb->prepare(
					"SELECT `user_id` FROM `{$wpdb->prefix}usermeta`"
									. 'WHERE `meta_key` = %s AND `meta_value` = %s',
					array( $this->phone_key, $username )
				)
			);
			return ! MoUtility::is_blank( $results ) ? get_userdata( $results->user_id ) : false;
		}
		/**
		 * This function loads login script.
		 */
		public function miniorange_register_login_script() {
			wp_register_script( 'eduumalog', MOV_URL . 'includes/js/edumalog.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'eduumalog',
				'eduumalog',
				array(
					'otp_type' => $this->get_verification_type(),
					'siteURL'  => wp_ajax_url(),
				)
			);
			wp_enqueue_script( 'eduumalog' );
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

			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				miniorange_site_otp_validation_form(
					$user_login,
					$user_email,
					$phone_number,
					MoUtility::get_invalid_otp_method(),
					'phone',
					false
				);
			}
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

			if ( ( ! isset( $_POST['mopopup_wpnonce'] ) || ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mopopup_wpnonce'] ) ), 'mo_popup_options' ) ) ) ) { // phpcs:ignore -- false positive.
				return;
			}
			$data     = MoUtility::mo_sanitize_array( $_POST );
			$username = MoUtility::is_blank( $user_login ) ? MoUtility::sanitize_check( 'log', $data ) : $user_login;
			$username = MoUtility::is_blank( $username ) ? MoUtility::sanitize_check( 'username', $data ) : $username;
			$this->login_wp_user( $username, $extra_data );
		}
		/**
		 * The function is called to login the user
		 *
		 * @param string $user_log - the username of the user logging in.
		 * @param array  $extra_data - array of extra data related to the user.
		 */
		public function login_wp_user( $user_log, $extra_data = null ) {
			$user = is_email( $user_log ) ? get_user_by( 'email', $user_log ) : ( MoUtility::validate_phone_number( $user_log ) ? $this->getUserFromPhoneNumber( MoUtility::process_phone_number( $user_log ) ) : get_user_by( 'login', $user_log ) );
			wp_set_auth_cookie( $user->data->ID );
			$this->unset_otp_session_variables();
			do_action( 'wp_login', $user->user_login, $user );
			$redirect = MoUtility::is_blank( $extra_data ) ? site_url() : $extra_data;
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->form_session_var, $this->tx_session_id ) );
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

			if ( $this->is_form_enabled() && ( $this->otp_type === $this->type_phone_tag ) ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}

		/**
		 * Handles saving all the Eduma Theme form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}

			$this->otp_type        = $this->sanitize_form_post( 'edumalog_enable_type' );
			$this->is_form_enabled = $this->sanitize_form_post( 'edumalog_enable' );
			$this->phone_key       = $this->sanitize_form_post( 'edumalog_phone_field_key' );
			$this->by_pass_admin   = $this->sanitize_form_post( 'edumalog_bypass_admin' );

			update_mo_option( 'edumalog_enable', $this->is_form_enabled );
			update_mo_option( 'edumalog_enable_type', $this->otp_type );
			update_mo_option( 'edumalog_phone_field_key', $this->phone_key );
			update_mo_option( 'edumalog_bypass_admin', $this->by_pass_admin );
		}

		/**
		 * Getter for $_delayOtpInterval
		 *
		 * @return int
		 */
		public function byPassCheckForAdmins() {
			return $this->by_pass_admin; }
	}
}
