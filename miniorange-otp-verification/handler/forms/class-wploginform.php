<?php
/**
 * Load admin view for WordPress / WooCommerce / Ultimate Member Login Form.
 *
 * @package miniorange-otp-verification/handler/forms
 */

namespace OTP\Handler\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\FormSessionVars;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoFormDocs;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Error;
use WP_User;

/**
 * This is the WordPress Login Form class. This class handles all the
 * functionality related to WordPress Login. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WPLoginForm' ) ) {
	/**
	 * WPLoginForm class
	 */
	class WPLoginForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Plaintext marker for the admin password-login hint; replaced with HTML after the popup template is built
		 * so nested wp_kses passes inside template parsers do not strip links.
		 *
		 * @var string
		 */
		const MO_OTP_ADMIN_PW_HINT_PLACEHOLDER = '[[MO_OTP_WP_LOGIN_PASSWORD_HINT]]';

		/**
		 * Enable disable saving of phone numbers after verification
		 *
		 * @var string
		 */
		private $save_phone_numbers;

		/**
		 * Allow admins to bypass otp verification
		 *
		 * @var string
		 */
		private $by_pass_admin;

		/**
		 * Allow users to log in with their phone number
		 *
		 * @var String
		 */
		private $allow_login_through_phone;

		/**
		 * Skip Password Check and allow users to log
		 * in using OTP instead
		 *
		 * @var bool
		 */
		private $skip_password_check;

		/**
		 * The Username field label to be shown to the
		 * users.
		 *
		 * @var string
		 */
		private $user_label;

		/**
		 * The option which tells if admins has set the
		 * option to force users to OTP Verification only
		 * in certain intervals.
		 *
		 * @var bool
		 */
		private $delay_otp;

		/**
		 * The interval time if $delay_otp is set.
		 *
		 * @var int
		 */
		private $delay_otp_interval;

		/**
		 * Allow users to fallback to username + password
		 * if they don't wish to do login with OTP
		 *
		 * @var bool
		 */
		private $skip_pass_fallback;

		/**
		 * Create User Action Hook
		 *
		 * @var string
		 */
		private $create_user_action;

		/**
		 * Stores the unix timestamp of when the user did OTP Verification last
		 *
		 * @var string
		 */
		private $time_stamp_meta_key = 'mov_last_verified_dttm';

		/**
		 * Redirect page after Login.
		 *
		 * @var string
		 */
		private $redirect_to_page;
		/**
		 * Redirect user after Login.
		 *
		 * @var boolean
		 */
		private $redirect_after_login;
		/**
		 * Login with OTP button text.
		 *
		 * @var boolean
		 */
		private $login_with_otp_button_text;
		/**
		 * Login with password button text.
		 *
		 * @var boolean
		 */
		private $login_with_pass_button_text;
		/**
		 * Login with password button CSS.
		 *
		 * @var boolean
		 */
		private $login_with_pass_button_css;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form     = true;
			$this->is_ajax_form                = true;
			$this->form_session_var            = FormSessionVars::WP_LOGIN_REG_PHONE;
			$this->form_session_var2           = FormSessionVars::WP_DEFAULT_LOGIN;
			$this->phone_form_id               = '#mo_phone_number';
			$this->type_phone_tag              = 'mo_wp_login_phone_enable';
			$this->type_email_tag              = 'mo_wp_login_email_enable';
			$this->form_key                    = 'WP_DEFAULT_LOGIN';
			$this->form_name                   = 'WordPress / WooCommerce / Ultimate Member Login Form';
			$this->is_form_enabled             = get_mo_option( 'wp_login_enable' );
			$this->user_label                  = get_mo_option( 'wp_username_label_text' );
			$this->user_label                  = $this->user_label ? $this->user_label : '';
			$this->skip_password_check         = get_mo_option( 'wp_login_skip_password' );
			$this->allow_login_through_phone   = get_mo_option( 'wp_login_allow_phone_login' );
			$this->skip_pass_fallback          = get_mo_option( 'wp_login_skip_password_fallback' );
			$this->delay_otp                   = get_mo_option( 'wp_login_delay_otp' );
			$this->delay_otp_interval          = get_mo_option( 'wp_login_delay_otp_interval' );
			$this->login_with_otp_button_text  = get_mo_option( 'wp_login_with_otp_button_text' );
			$this->login_with_pass_button_text = get_mo_option( 'wp_login_with_pass_button_text' );
			$this->login_with_otp_button_text  = ! MoUtility::is_blank( $this->login_with_otp_button_text ) ? $this->login_with_otp_button_text : '';
			$this->login_with_pass_button_text = ! MoUtility::is_blank( $this->login_with_pass_button_text ) ? $this->login_with_pass_button_text : '';
			$this->login_with_pass_button_css  = get_mo_option( 'wp_login_with_pass_button_css' );
			$this->delay_otp_interval          = $this->delay_otp_interval ? $this->delay_otp_interval : 43800;
			$this->form_documents              = MoFormDocs::LOGIN_FORM;

			if ( $this->skip_password_check || $this->allow_login_through_phone ) {
				add_action( 'login_enqueue_scripts', array( $this, 'miniorange_register_login_script' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'miniorange_register_login_script' ) );
			}
			parent::__construct();
		}

		/**
		 * Get the option name for the phone key.
		 *
		 * @return string The option name for the phone key.
		 */
		protected function get_phone_key_option_name() {
			return 'wp_login_key';
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {

			$this->otp_type             = get_mo_option( 'wp_login_enable_type' );
			$this->save_phone_numbers   = get_mo_option( 'wp_login_register_phone' );
			$this->by_pass_admin        = get_mo_option( 'wp_login_bypass_admin' );
			$this->restrict_duplicates  = get_mo_option( 'wp_login_restrict_duplicates' );
			$this->redirect_after_login = get_mo_option( 'wp_login_redirection_enable' );
			$this->redirect_to_page     = get_mo_option( 'login_custom_redirect' );

			add_filter( 'authenticate', array( $this, 'mo_handle_mo_wp_login' ), 99, 3 );
			add_filter( 'wp_login_errors', array( $this, 'mo_login_form_notice_no_phone_require_password' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'mo_wc_queue_no_phone_notice_from_query_arg' ), 5 );
			add_action( 'login_form', array( $this, 'mo_print_force_phone_enrollment_hidden_field' ) );
			add_action( 'woocommerce_login_form', array( $this, 'mo_print_force_phone_enrollment_hidden_field' ), 5 );
			add_action( 'wp_body_open', array( $this, 'mo_output_no_phone_enrollment_notice_wp_body_open' ), 5 );
			add_filter( 'mo_otp_validation_popup_message', array( $this, 'mo_filter_login_otp_popup_message' ), 10, 5 );
			add_filter( 'mo_otp_validation_popup_html_after_kses', array( $this, 'mo_replace_admin_password_hint_after_final_kses' ), 10, 5 );

			if ( class_exists( 'UM' ) ) {
				add_filter( 'wp_authenticate_user', array( $this, 'mo_get_and_return_user' ), 99, 2 );
				add_filter( 'um_custom_authenticate_error_codes', array( $this, 'mo_get_um_form_errors' ), 99, 1 );
				add_action( 'um_before_login_fields', array( $this, 'mo_print_um_no_phone_flash_notice' ), 5 );
				add_action( 'um_before_login_fields', array( $this, 'mo_print_force_phone_enrollment_hidden_field' ), 15 );
			}
			$this->mo_route_data();
		}

		/**
		 * Append a password-login link for administrator accounts when OTP-only login and bypass are enabled.
		 * The link is only shown after a real login attempt has identified the user (no public username oracle).
		 *
		 * @param string $message      Popup message HTML.
		 * @param string $user_login   Username from the flow.
		 * @param string $user_email   Email from the flow.
		 * @param string $phone_number Phone from the flow.
		 * @param string $otp_type     Verification type.
		 * @return string
		 */
		public function mo_filter_login_otp_popup_message( $message, $user_login, $user_email, $phone_number, $otp_type ) {
			if ( ! $this->by_pass_admin || ! $this->skip_password_check || $this->skip_pass_fallback || ! $this->is_form_enabled() ) {
				return $message;
			}
			$current = MoPHPSessions::get_session_var( 'current_form_name' );
			if ( $current !== $this->form_name ) {
				return $message;
			}
			$user = $this->mo_resolve_user_for_admin_login_hint( $user_login, $user_email, $phone_number );
			if ( ! $user instanceof WP_User || ! in_array( 'administrator', $user->roles, true ) ) {
				return $message;
			}
			// Placeholder survives until final output; link HTML is added in mo_replace_admin_password_hint_after_final_kses() after wp_kses.
			return $message . "\n\n" . self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER;
		}


		/**
		 * Replace placeholder with trusted HTML after the last wp_kses on the popup (avoids KSES stripping &lt;a&gt;).
		 *
		 * @param string $html           Full popup HTML (already passed through wp_kses).
		 * @param string $user_login     Username from the flow.
		 * @param string $user_email     Email.
		 * @param string $phone_number   Phone.
		 * @param string $otp_type       Verification type.
		 * @return string
		 */
		public function mo_replace_admin_password_hint_after_final_kses( $html, $user_login, $user_email, $phone_number, $otp_type ) {
			if ( strpos( $html, self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER ) === false ) {
				return $html;
			}
			if ( ! $this->by_pass_admin || ! $this->skip_password_check || $this->skip_pass_fallback || ! $this->is_form_enabled() ) {
				return str_replace( self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER, '', $html );
			}
			$current = MoPHPSessions::get_session_var( 'current_form_name' );
			if ( $current !== $this->form_name ) {
				return str_replace( self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER, '', $html );
			}
			$user = $this->mo_resolve_user_for_admin_login_hint( $user_login, $user_email, $phone_number );
			if ( ! $user instanceof WP_User || ! in_array( 'administrator', $user->roles, true ) ) {
				return str_replace( self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER, '', $html );
			}
			$url       = $this->mo_get_wp_login_password_mode_url();
			$link_text = esc_html__( 'click here', 'miniorange-otp-verification' );
			// Trusted fragment only; not run through wp_kses again (esc_url / translated link text are safe).
			$hint_html = '<p class="mo-otp-admin-password-hint" style="margin-top:1em;">' . sprintf(
				/* translators: %s: link to open password login */
				__( 'If you are having trouble logging in with OTP, %s to sign in with your password instead.', 'miniorange-otp-verification' ),
				'<a href="' . esc_url( $url ) . '">' . $link_text . '</a>'
			) . '</p>';
			return str_replace( self::MO_OTP_ADMIN_PW_HINT_PLACEHOLDER, $hint_html, $html );
		}


		/**
		 * Resolve WP_User for optional admin-only hint text (same session as login flow).
		 *
		 * @param string $user_login   Login identifier.
		 * @param string $user_email   Email.
		 * @param string $phone_number Phone.
		 * @return WP_User|null
		 */
		private function mo_resolve_user_for_admin_login_hint( $user_login, $user_email, $phone_number ) {
			if ( $user_login && 'ajax_phone' !== $user_login ) {
				$user = is_email( $user_login ) ? get_user_by( 'email', $user_login ) : get_user_by( 'login', $user_login );
				if ( $user instanceof WP_User ) {
					return $user;
				}
			}
			$session_user = MoPHPSessions::get_session_var( 'login_user_mo' );
			if ( $session_user ) {
				$user = is_email( $session_user ) ? get_user_by( 'email', $session_user ) : get_user_by( 'login', $session_user );
				if ( $user instanceof WP_User ) {
					return $user;
				}
			}
			if ( $user_email ) {
				$user = get_user_by( 'email', $user_email );
				if ( $user instanceof WP_User ) {
					return $user;
				}
			}
			if ( $phone_number && $this->allow_login_through_phone ) {
				$user = $this->mo_get_user_from_phone_number( $phone_number );
				if ( $user instanceof WP_User ) {
					return $user;
				}
			}
			return null;
		}


		/**
		 * Login URL that opens the password field (handled by loginform.js via mo_pw_login=1).
		 *
		 * @return string
		 */
		private function mo_get_wp_login_password_mode_url() {
			$redirect_raw = MoUtility::get_current_page_parameter_value( 'redirect_to', '' );
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only redirect_to for URL building; value passed through wp_validate_redirect below.
			if ( MoUtility::is_blank( $redirect_raw ) && isset( $_GET['redirect_to'] ) ) {
				$redirect_raw = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( MoUtility::is_blank( $redirect_raw ) ) {
				$session_redirect = MoPHPSessions::get_session_var( 'redirect_to' );
				if ( ! MoUtility::is_blank( $session_redirect ) ) {
					$redirect_raw = $session_redirect;
				}
			}
			$redirect_to = $redirect_raw ? wp_validate_redirect( $redirect_raw, false ) : false;
			// Never send users back to wp-login.php after a successful login (avoids redirect loop).
			if ( ! $redirect_to || $this->mo_is_redirect_target_login_url( $redirect_to ) ) {
				$redirect_to = admin_url();
			}
			$args = array( 'mo_pw_login' => '1' );
			if ( $redirect_to ) {
				$args['redirect_to'] = $redirect_to;
			}
			return add_query_arg( $args, wp_login_url() );
		}


		/**
		 * True if URL points at wp-login.php (invalid as post-login destination).
		 *
		 * @param string $url Validated URL.
		 * @return bool
		 */
		private function mo_is_redirect_target_login_url( $url ) {
			if ( MoUtility::is_blank( $url ) ) {
				return true;
			}
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( ! is_string( $path ) ) {
				return false;
			}
			return ( false !== strpos( $path, 'wp-login.php' ) );
		}


		/**
		 * Function to handle login errors on UM invalid form
		 *
		 * @param Array $errors - Errors.
		 */
		public function mo_get_um_form_errors( $errors ) {
			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'um_login_form' ) || ! wp_verify_nonce( $nonce, 'um-form-nonce' ) || ! wp_verify_nonce( $nonce, 'um_login_nonce' ) ) {
				return $errors;
			}

			$form_id  = MoUtility::sanitize_check( 'form_id', $_POST );
			$username = MoUtility::sanitize_check( 'username-' . $form_id, $_POST );
			$password = MoUtility::sanitize_check( 'user_password-' . $form_id, $_POST );
			$user     = $this->mo_get_user( $username, $password );

			if ( is_wp_error( $user ) ) {
				array_push( $errors, $user->get_error_code() );
			}
			return $errors;
		}

		/**
		 * This function checks what kind of OTP Verification needs to be done.
		 * and starts the otp verification process with appropriate parameters.
		 *
		 * @throws ReflectionException .
		 */
		private function mo_route_data() {

			$popup_option = MoUtility::get_current_page_parameter_value( 'mo_external_popup_option', '' );

			if ( ! check_ajax_referer( 'mo_popup_options', 'mopopup_wpnonce', false ) ) {
				return;
			}

			$post_data = MoUtility::mo_sanitize_array( $_POST );

			// If not present in query string, fallback to POST after nonce verification.
			if ( '' === $popup_option && isset( $post_data['mo_external_popup_option'] ) ) {
				$popup_option = sanitize_text_field( wp_unslash( $post_data['mo_external_popup_option'] ) );
			}

			if ( '' === $popup_option ) {
				return;
			}

			switch ( trim( $popup_option ) ) {
				case 'miniorange-ajax-otp-generate':
					$this->mo_handle_wp_login_ajax_send_otp( $post_data );
					break;
				case 'miniorange-ajax-otp-validate':
					$this->mo_handle_wp_login_ajax_form_validate_action( $post_data );
					break;
				case 'mo_ajax_form_validate':
					$this->mo_handle_wp_login_create_user_action( $post_data );
					break;
			}
		}

		/**
		 * This function registers the js file for enabling OTP Verification
		 * for WP Login using AJAX calls.
		 */
		public function miniorange_register_login_script() {
			wp_register_script( 'mologin', MOV_URL . 'includes/js/loginform.js', array( 'jquery' ), MOV_VERSION, true );
			$otp_btn_text  = ! MoUtility::is_blank( $this->login_with_otp_button_text ) ? $this->login_with_otp_button_text : __( 'Login with OTP', 'miniorange-otp-verification' );
			$pass_btn_text = ! MoUtility::is_blank( $this->login_with_pass_button_text ) ? $this->login_with_pass_button_text : __( 'Login with Password', 'miniorange-otp-verification' );

			wp_localize_script(
				'mologin',
				'movarlogin',
				array(
					'userLabel'             => ( $this->allow_login_through_phone && $this->get_verification_type() === VerificationType::PHONE ) ? $this->user_label : null,
					'skipPwdCheck'          => $this->skip_password_check,
					'skipPwdFallback'       => $this->skip_pass_fallback,
					'phoneOnlyIdentifiers'  => $this->mo_is_login_phone_identifier_only_mode(),
					'phoneOnlyLoginMessage' => __( 'Please log in using your registered phone number only.', 'miniorange-otp-verification' ),
					'loginOTPButtonText'    => $otp_btn_text,
					'loginPassButtonText'   => $pass_btn_text,
					'loginPassButtonCSS'    => $this->login_with_pass_button_css,
				)
			);
			wp_enqueue_script( 'mologin' );
		}


		/**
		 * Return Authenticated User object for Ultimate Member Login.
		 *
		 * @param string|WP_User $username   username of the user.
		 * @param string         $password   password of the user.
		 * @return WP_Error|WP_User
		 */
		public function mo_get_and_return_user( $username, $password ) {

			if ( is_object( $username ) ) {
				return $username;
			}

			$user = $this->mo_get_user( $username, $password );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
			if ( ! class_exists( 'UM' ) ) {
				UM()->login()->auth_id = $user->data->ID;
				UM()->form()->errors   = null;
			}
			return $user;
		}



		/**
		 * Function detects if the user trying to log in is an admin and detects
		 * if admin has set two factor bypass for Admins. Returns True or False
		 *
		 * @param WP_User $user             role or roles of the user trying to log in.
		 * @param bool    $skip_otp_process   skip validating OTP.
		 * @return bool
		 */
		private function mo_by_pass_login( $user, $skip_otp_process ) {
			// User hit "no phone for OTP" for this account: password must continue into OTP/phone enrollment, not plain login.
			if ( $this->mo_pending_phone_enrollment_blocks_password_bypass( $user ) ) {
				return false;
			}
			if ( $skip_otp_process || $this->mo_delay_otp_process( $user->data->ID ) ) {
				return true;
			}
			if (
				$this->by_pass_admin
				&& $this->skip_password_check
				&& ! $this->skip_pass_fallback
				&& 'password' === $this->mo_get_wp_login_intent()
			) {
				$user_meta = get_userdata( $user->data->ID );
				$user_role = $user_meta->roles;
				return in_array( 'administrator', $user_role, true );
			}
			return false;
		}

		/**
		 * True when this login must go through phone OTP enrollment instead of password-only bypass.
		 * Set when we redirect after OTP-without-phone; cleared when the user has a stored phone or OTP session resets.
		 *
		 * @param WP_User $user Current user.
		 * @return bool
		 */
		private function mo_pending_phone_enrollment_blocks_password_bypass( $user ) {
			if ( ! ( $user instanceof WP_User ) ) {
				return false;
			}
			if ( ! $this->mo_post_has_force_phone_enrollment_marker() ) {
				return false;
			}
			if ( ! $this->skip_password_check ) {
				return false;
			}
			if ( VerificationType::PHONE !== $this->get_verification_type() || ! $this->mo_save_phone_numbers() ) {
				return false;
			}
			$pending = MoPHPSessions::get_session_var( 'mo_wp_login_pending_phone_enrollment_uid' );
			if ( MoUtility::is_blank( $pending ) || (string) $user->ID !== (string) $pending ) {
				return false;
			}
			$stored_phone = get_user_meta( $user->data->ID, $this->get_phone_key_details(), true );
			$stored_phone = MoUtility::process_phone_number( $stored_phone );
			return MoUtility::is_blank( $stored_phone );
		}

		/**
		 * Hidden field mo_force_phone_enrollment_after_password is only present after our no-phone redirect (?mo_otp_no_phone=1).
		 *
		 * @return bool
		 */
		private function mo_post_has_force_phone_enrollment_marker() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Hidden marker from same-page GET (?mo_otp_no_phone=1); core/WC login nonces still apply to the form.
			return isset( $_POST['mo_force_phone_enrollment_after_password'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['mo_force_phone_enrollment_after_password'] ) );
		}

		/**
		 * Print hidden input when the login page was loaded from our no-phone redirect so the next POST can prove enrollment is required.
		 *
		 * @return void
		 */
		public function mo_print_force_phone_enrollment_hidden_field() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-secret UX flag from our own redirect (?mo_otp_no_phone=1); sanitized below.
			if ( ! isset( $_GET['mo_otp_no_phone'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['mo_otp_no_phone'] ) ) ) {
				return;
			}
			echo '<input type="hidden" name="mo_force_phone_enrollment_after_password" value="1" />';
		}

		/**
		 * If the user uses a normal password login (no hidden marker), drop stale pending-enrollment so we do not force phone registration.
		 *
		 * @param WP_User $user Resolved user.
		 * @return void
		 */
		private function mo_drop_phone_enrollment_pending_without_submitted_marker( $user ) {
			if ( ! ( $user instanceof WP_User ) ) {
				return;
			}
			$pending = MoPHPSessions::get_session_var( 'mo_wp_login_pending_phone_enrollment_uid' );
			if ( MoUtility::is_blank( $pending ) || (string) $user->ID !== (string) $pending ) {
				return;
			}
			if ( $this->mo_post_has_force_phone_enrollment_marker() ) {
				return;
			}
			MoPHPSessions::unset_session( 'mo_wp_login_pending_phone_enrollment_uid' );
		}

		/**
		 * Clear enrollment-pending flag if the user already has a phone (e.g. added elsewhere).
		 *
		 * @param WP_User|WP_Error $user User or error.
		 * @return void
		 */
		private function mo_clear_pending_phone_enrollment_if_user_has_phone( $user ) {
			if ( ! ( $user instanceof WP_User ) ) {
				return;
			}
			$pending = MoPHPSessions::get_session_var( 'mo_wp_login_pending_phone_enrollment_uid' );
			if ( MoUtility::is_blank( $pending ) || (string) $user->ID !== (string) $pending ) {
				return;
			}
			$stored = MoUtility::process_phone_number( get_user_meta( $user->ID, $this->get_phone_key_details(), true ) );
			if ( ! MoUtility::is_blank( $stored ) ) {
				MoPHPSessions::unset_session( 'mo_wp_login_pending_phone_enrollment_uid' );
			}
		}

		/**
		 * Handle WordPress login user creation after OTP verification
		 *
		 * @param array $post_data - $_POST data.
		 * @return void
		 */
		private function mo_handle_wp_login_create_user_action( $post_data ) {
			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				return;
			}

			// First-time phone bind must follow authenticate-time password verification (prevents account takeover).
			if ( (string) MoPHPSessions::get_session_var( 'mo_wp_login_enrollment_ownership_ok' ) !== '1' ) {
				$this->unset_otp_session_variables();
				wp_die(
					esc_html__( 'Phone registration could not be completed. Please sign in again and enter your account password before verifying your phone number.', 'miniorange-otp-verification' ),
					esc_html__( 'Login error', 'miniorange-otp-verification' ),
					array( 'response' => 403 )
				);
			}

			/**
			 * Anonymous function that returns the user for the email or
			 * username that the user has submitted on the login screen
			 *
			 * @param $post_data
			 * @return bool|WP_User
			 */
			$get_user_from_post = function ( $post_data ) {
				$username = MoPHPSessions::get_session_var( 'login_user_mo' );

				if ( ! $username ) {
					$array    = array_filter(
						$post_data,
						function ( $key ) {
							return strpos( $key, 'username' ) === 0;
						},
						ARRAY_FILTER_USE_KEY
					);
					$username = ! empty( $array ) ? array_shift( $array ) : $username;
				}
				return is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			};

			$user  = $get_user_from_post( $post_data );
			$phone = MoPHPSessions::get_session_var( 'phone_number_mo' );
			$phone = $phone ? $phone : '';
			update_user_meta( $user->data->ID, $this->get_phone_key_details(), $phone );
			$this->login_wp_user( $user->data->user_login, null, $phone );
		}

		/**
		 * The function is called to login the user
		 *
		 * @param string      $user_log - the username of the user logging in.
		 * @param string|null $extra_data - Extra data stored in the session during sending the OTP.
		 * @param string|null $phone_number - Phone number captured in OTP flow.
		 */
		private function login_wp_user( $user_log, $extra_data = null, $phone_number = null ) {
			$user = is_email( $user_log ) ? get_user_by( 'email', $user_log ) : get_user_by( 'login', $user_log );
			if ( ! $user && $this->mo_allow_login_through_phone() ) {
				$user = $this->mo_get_user_from_phone_number( $phone_number );
			}
			if ( $user ) {
				wp_set_auth_cookie( $user->data->ID, true );
				if ( $this->delay_otp && $this->delay_otp_interval > 0 ) {
					update_user_meta( $user->data->ID, $this->time_stamp_meta_key, time() );
				}
				$this->unset_otp_session_variables();
				do_action( 'wp_login', $user->user_login, $user );
			}

			if ( 'redirect_to_the_page' === $this->redirect_after_login ) {
				$target = $this->redirect_to_page ? get_permalink( $this->redirect_to_page ) : site_url();
				if ( ! $target ) {
					$target = site_url();
				}
				wp_safe_redirect( $target );
				exit;
			} else {
				$redirect = MoUtility::is_blank( $extra_data ) ? site_url() : $extra_data;
				wp_safe_redirect( $redirect );
				exit;

			}
		}


		/**
		 * User-visible message when login must use password before first-time phone registration.
		 *
		 * @return string
		 */
		private function mo_get_no_phone_enrollment_message() {
			return __( 'Please sign in with your password first to continue securely.', 'miniorange-otp-verification' );
		}

		/**
		 * On the GET request after redirect (?mo_otp_no_phone=1), re-queue the WooCommerce notice if needed.
		 *
		 * Session notices added during the POST can fail to persist (guest WC session cookie timing, exit before save).
		 * Store Notices / block banners read the same PHP queue as native login errors.
		 *
		 * @return void
		 */
		public function mo_wc_queue_no_phone_notice_from_query_arg() {
			if ( is_admin() ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-secret flag from our own redirect; sanitized below.
			if ( ! isset( $_GET['mo_otp_no_phone'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['mo_otp_no_phone'] ) ) ) {
				return;
			}
			if ( ! function_exists( 'wc_add_notice' ) || ! did_action( 'woocommerce_init' ) ) {
				return;
			}

			$on_wc_notice_surface = ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() );

			if ( ! $on_wc_notice_surface ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$req_path    = '' !== $request_uri ? strtok( $request_uri, '?' ) : '';
				$here        = is_string( $req_path ) && '' !== $req_path ? home_url( $req_path ) : '';
				if ( MoUtility::is_blank( $here ) || ! $this->mo_url_is_woocommerce_notice_context( $here ) ) {
					return;
				}
			}

			$msg = $this->mo_get_no_phone_enrollment_message();
			if ( function_exists( 'wc_has_notice' ) && wc_has_notice( $msg, 'error' ) ) {
				return;
			}
			wc_add_notice( $msg, 'error' );
		}

		/**
		 * Show notice after redirect when user must use password before first-time phone registration.
		 *
		 * @param WP_Error $errors      Login errors object.
		 * @param string   $redirect_to Redirect destination (unused).
		 * @return WP_Error
		 */
		public function mo_login_form_notice_no_phone_require_password( $errors, $redirect_to ) {
			unset( $redirect_to );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Non-secret flag from our own redirect; sanitized below.
			if ( ! isset( $_GET['mo_otp_no_phone'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['mo_otp_no_phone'] ) ) ) {
				return $errors;
			}
			if ( ! ( $errors instanceof WP_Error ) ) {
				$errors = new WP_Error();
			}
			$errors->add(
				'mo_otp_no_phone',
				$this->mo_get_no_phone_enrollment_message(),
				''
			);
			return $errors;
		}

		/**
		 * Print queued notice at top of body for generic theme/login shortcode pages (not WC session, not UM field hook).
		 *
		 * @return void
		 */
		public function mo_output_no_phone_enrollment_notice_wp_body_open() {
			if ( is_admin() ) {
				return;
			}
			if ( function_exists( 'is_login' ) && is_login() ) {
				return;
			}
			if ( function_exists( 'um_is_core_page' ) && um_is_core_page( 'login' ) ) {
				return;
			}
			$msg = MoPHPSessions::get_session_var( 'mo_otp_no_phone_generic_flash' );
			if ( MoUtility::is_blank( $msg ) ) {
				return;
			}
			MoPHPSessions::unset_session( 'mo_otp_no_phone_generic_flash' );
			echo '<div class="mo-otp-no-phone-notice" style="box-sizing:border-box;width:100%;max-width:42em;margin:0 auto 1em;padding:12px 16px;border-left:4px solid #c00;background:#fff5f5;" role="alert">' .
				esc_html( $msg ) .
				'</div>';
		}

		/**
		 * Print notice above Ultimate Member login fields (session set before redirect).
		 *
		 * @return void
		 */
		public function mo_print_um_no_phone_flash_notice() {
			$msg = MoPHPSessions::get_session_var( 'mo_otp_no_phone_um_flash' );
			if ( MoUtility::is_blank( $msg ) ) {
				return;
			}
			MoPHPSessions::unset_session( 'mo_otp_no_phone_um_flash' );
			echo '<div class="um-field um-field-error mo-otp-no-phone-notice" style="width:100%;margin-bottom:1em;padding:12px;border-left:4px solid #c00;background:#fff5f5;">' .
				esc_html( $msg ) .
				'</div>';
		}

		/**
		 * Whether the URL targets WooCommerce My Account or Checkout (session notices appear where WC prints notices).
		 *
		 * @param string $url Absolute URL.
		 * @return bool
		 */
		private function mo_url_is_woocommerce_notice_context( $url ) {
			if ( ! function_exists( 'wc_get_page_permalink' ) ) {
				return false;
			}
			$url_clean = untrailingslashit( strtok( $url, '?' ) );
			$my        = wc_get_page_permalink( 'myaccount' );
			if ( ! MoUtility::is_blank( $my ) && 0 === strpos( $url_clean, untrailingslashit( strtok( $my, '?' ) ) ) ) {
				return true;
			}
			if ( function_exists( 'wc_get_checkout_url' ) ) {
				$checkout = wc_get_checkout_url();
				if ( ! MoUtility::is_blank( $checkout ) && 0 === strpos( $url_clean, untrailingslashit( strtok( $checkout, '?' ) ) ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Whether URL is the Ultimate Member core login page.
		 *
		 * @param string $url Absolute URL.
		 * @return bool
		 */
		private function mo_url_is_um_login_page( $url ) {
			if ( ! function_exists( 'um_get_core_page' ) ) {
				return false;
			}
			$login = um_get_core_page( 'login' );
			if ( MoUtility::is_blank( $login ) ) {
				return false;
			}
			return untrailingslashit( strtok( $url, '?' ) ) === untrailingslashit( strtok( $login, '?' ) );
		}

		/**
		 * Whether URL is wp-login.php (core handles messages via wp_login_errors + GET).
		 *
		 * @param string $url Absolute URL.
		 * @return bool
		 */
		private function mo_url_is_wp_login_page( $url ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			return is_string( $path ) && false !== strpos( $path, 'wp-login.php' );
		}

		/**
		 * Queue WC / UM / generic flash notice so it appears next to native form messages, not in the footer.
		 *
		 * @param string $base_url Redirect target without mo_* query args.
		 * @param string $message  Message text.
		 * @return void
		 */
		private function mo_queue_phone_enrollment_notice_for_destination( $base_url, $message ) {
			if ( $this->mo_url_is_wp_login_page( $base_url ) ) {
				return;
			}

			// Prefer detecting WooCommerce login from the same POST as wp_signon (URL matching can fail on host/scheme/case).
			$wc_login_nonce = isset( $_POST['woocommerce-login-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ) : '';
			$from_wc_login  = '' !== $wc_login_nonce && wp_verify_nonce( $wc_login_nonce, 'woocommerce-login' );

			if ( function_exists( 'wc_add_notice' ) && did_action( 'woocommerce_init' )
				&& ( $from_wc_login || $this->mo_url_is_woocommerce_notice_context( $base_url ) ) ) {
				wc_add_notice( $message, 'error' );
				return;
			}
			if ( $this->mo_url_is_um_login_page( $base_url ) ) {
				MoPHPSessions::add_session_var( 'mo_otp_no_phone_um_flash', $message );
				return;
			}
			MoPHPSessions::add_session_var( 'mo_otp_no_phone_generic_flash', $message );
		}

		/**
		 * Detect which login UI submitted the request (used when Referer is missing).
		 *
		 * @return string wp_login|woocommerce|ultimate_member|unknown
		 */
		private function mo_detect_login_form_submission_context() {
			$wc_login_nonce = isset( $_POST['woocommerce-login-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ) : '';
			if ( '' !== $wc_login_nonce && wp_verify_nonce( $wc_login_nonce, 'woocommerce-login' ) ) {
				return 'woocommerce';
			}
			foreach ( array_keys( $_POST ) as $pk ) {
				if ( is_string( $pk ) && preg_match( '/^username-\d+$/', $pk ) ) {
					return 'ultimate_member';
				}
			}
			if ( isset( $_POST['log'], $_POST['pwd'] ) && isset( $_POST['wp-submit'] ) ) {
				return 'wp_login';
			}
			return 'unknown';
		}

		/**
		 * Resolve the login screen URL to send the user back to (same as the form they used), with safe fallbacks.
		 *
		 * @param string $after_login_redirect Sanitized redirect_to target after successful login.
		 * @return string Absolute URL without mo_pw_login / mo_otp_no_phone (caller adds them).
		 */
		private function mo_get_phone_enrollment_redirect_target_url( $after_login_redirect ) {
			$wp_login_base = wp_login_url( $after_login_redirect );

			$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$from_ref = false;
			if ( ! MoUtility::is_blank( $referer ) ) {
				$validated = wp_validate_redirect( $referer, false );
				if ( false !== $validated && ! MoUtility::is_blank( $validated ) ) {
					$path = wp_parse_url( $validated, PHP_URL_PATH );
					if ( is_string( $path ) && false !== strpos( $path, 'wp-login.php' ) ) {
						return $wp_login_base;
					}
					$from_ref = remove_query_arg( array( 'mo_pw_login', 'mo_otp_no_phone' ), $validated );
				}
			}
			if ( false !== $from_ref && ! MoUtility::is_blank( $from_ref ) ) {
				return untrailingslashit( $from_ref );
			}

			$ctx = $this->mo_detect_login_form_submission_context();
			if ( 'woocommerce' === $ctx && function_exists( 'wc_get_page_permalink' ) ) {
				$my = wc_get_page_permalink( 'myaccount' );
				if ( ! MoUtility::is_blank( $my ) ) {
					return untrailingslashit( $my );
				}
			}
			if ( 'ultimate_member' === $ctx ) {
				if ( function_exists( 'um_get_core_page' ) ) {
					$um_login = um_get_core_page( 'login' );
					if ( ! MoUtility::is_blank( $um_login ) ) {
						return untrailingslashit( $um_login );
					}
				}
				if ( ! MoUtility::is_blank( $referer ) ) {
					$v = wp_validate_redirect( $referer, false );
					if ( false !== $v && ! MoUtility::is_blank( $v ) ) {
						return untrailingslashit( remove_query_arg( array( 'mo_pw_login', 'mo_otp_no_phone' ), $v ) );
					}
				}
			}

			return $wp_login_base;
		}

		/**
		 * Redirect to the same login screen the user came from (Referer / WooCommerce / UM), with password field visible.
		 * Stores session {@see mo_pending_phone_enrollment_blocks_password_bypass} so the next password login for this user cannot skip OTP enrollment.
		 *
		 * @param int $user_id WordPress user ID when known (required for pending-enrollment tracking).
		 * @return void
		 */
		private function mo_redirect_to_login_with_password_for_phone_enrollment( $user_id = 0 ) {
			$redirect_to = '';
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- redirect_to is sanitized with sanitize_url and validated via wp_validate_redirect in target URL builder.
			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = sanitize_url( wp_unslash( $_REQUEST['redirect_to'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $user_id > 0 ) {
				MoPHPSessions::add_session_var( 'mo_wp_login_pending_phone_enrollment_uid', (string) (int) $user_id );
			}
			$base = $this->mo_get_phone_enrollment_redirect_target_url( $redirect_to );
			$this->mo_queue_phone_enrollment_notice_for_destination( $base, $this->mo_get_no_phone_enrollment_message() );
			$url = add_query_arg(
				array(
					'mo_pw_login'     => '1',
					'mo_otp_no_phone' => '1',
				),
				$base
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Require account password before allowing “add phone on first login” enrollment in phone-OTP / passwordless mode.
		 * Prevents unauthenticated takeover by binding an attacker-controlled number to a victim account (username oracle + OTP).
		 *
		 * Filter `mo_wp_login_require_password_for_first_phone_bind` (default true) can disable this for custom integrations
		 * (not recommended on public sites).
		 *
		 * @param WP_User     $user     Resolved user.
		 * @param string|null $password Submitted password.
		 * @return WP_Error|null Return WP_Error to block login, null to continue.
		 */
		private function mo_enforce_password_before_phone_enrollment( $user, $password ) {
			if ( ! apply_filters( 'mo_wp_login_require_password_for_first_phone_bind', true, $user ) ) {
				return null;
			}
			if ( ! ( $user instanceof WP_User ) || VerificationType::PHONE !== $this->get_verification_type() ) {
				return null;
			}
			$stored_phone = get_user_meta( $user->data->ID, $this->get_phone_key_details(), true );
			$stored_phone = MoUtility::process_phone_number( $stored_phone );
			if ( ! MoUtility::is_blank( $stored_phone ) ) {
				MoPHPSessions::unset_session( 'mo_wp_login_enrollment_ownership_ok' );
				return null;
			}
			if ( ! $this->mo_save_phone_numbers() ) {
				return null;
			}
			if ( MoUtility::is_blank( $password ) || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
				$this->mo_redirect_to_login_with_password_for_phone_enrollment( $user->ID );
			}
			MoPHPSessions::add_session_var( 'mo_wp_login_enrollment_ownership_ok', '1' );
			return null;
		}

		/**
		 * The function hooks into the authenticate hook of WordPress to
		 * start the OTP Verification process.
		 *
		 * @param array $user - the WordPress user data object containing all the user information.
		 * @param array $username - username of the user trying to log in.
		 * @param array $password - password of the user trying to log in.
		 * @return WP_Error|WP_User .
		 * @throws ReflectionException .
		 */
		public function mo_handle_mo_wp_login( $user, $username, $password ) {
			if ( ! MoUtility::is_blank( $username ) ) {
				$user = $this->mo_get_user( $username, $password );

				if ( is_wp_error( $user ) ) {
					return $user;
				}

				$this->mo_drop_phone_enrollment_pending_without_submitted_marker( $user );
				$this->mo_clear_pending_phone_enrollment_if_user_has_phone( $user );

				if ( class_exists( 'UM' ) ) {
					$user_id = $user->ID;
					um_fetch_user( $user_id );

					$status = um_user( 'account_status' );
					switch ( $status ) {
						case 'inactive':
						case 'awaiting_admin_review':
						case 'awaiting_email_confirmation':
						case 'rejected':
							um_reset_user();

							wp_safe_redirect( add_query_arg( 'err', esc_attr( $status ), UM()->permalinks()->get_current_url() ) );
							exit;
					}
				}

				$skip_otp_process = $this->skip_otp_process( $password, $user );

				if ( $this->mo_by_pass_login( $user, $skip_otp_process ) ) {
					return $user;
				}

				$enrollment_gate = $this->mo_enforce_password_before_phone_enrollment( $user, $password );
				if ( is_wp_error( $enrollment_gate ) ) {
					return $enrollment_gate;
				}

				apply_filters( 'mo_master_otp_send_user', $user );
				MoPHPSessions::add_session_var( 'login_user_mo', $username );
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
		 * @throws ReflectionException .
		 */
		private function startOTPVerificationProcess( $user, $username, $password ) {
			$otp_type = $this->get_verification_type();

			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type )
			|| SessionUtils::is_status_match( $this->form_session_var2, self::VALIDATED, $otp_type ) ) {
				return;
			}

			if ( VerificationType::PHONE === $otp_type ) {
				$phone_number = get_user_meta( $user->data->ID, $this->get_phone_key_details(), true );
				$phone_number = MoUtility::process_phone_number( $phone_number );
				$this->mo_ask_phone_and_start_verification( $user, $this->get_phone_key_details(), $username, $phone_number );
				$this->mo_fetch_phone_and_start_verification( $username, $password, $phone_number );
			} elseif ( VerificationType::EMAIL === $otp_type ) {
				$email = $user->data->user_email;
				$this->mo_start_email_verification( $username, $email, $password );
			}
		}

		/**
		 * True when phone-login is on, verification is phone, "Login with only OTP" is enabled,
		 * and "Allow users to login with Username and Password" is unchecked � i.e. OTP flow must use a phone identifier.
		 * Password-based login (non-empty password) uses normal username/email/phone resolution.
		 *
		 * @param string|null $password Password submitted with the login form.
		 * @return bool
		 */
		private function mo_require_phone_identifier_for_login( $password ) {
			if ( ! $this->mo_is_login_phone_identifier_only_mode() ) {
				return false;
			}
			return MoUtility::is_blank( $password );
		}

		/**
		 * Shared condition for phone-only identifier mode (does not depend on submitted password).
		 *
		 * @return bool
		 */
		private function mo_is_login_phone_identifier_only_mode() {
			if ( ! $this->allow_login_through_phone || VerificationType::PHONE !== $this->get_verification_type() ) {
				return false;
			}
			if ( ! $this->skip_password_check ) {
				return false;
			}
			if ( $this->skip_pass_fallback ) {
				return false;
			}
			return true;
		}

		/**
		 * This functions checks if user has enabled phone number as a valid username and fetches the user
		 * associated with the phone number. Checks if the skip Password is enabled with feedback to handle
		 * OTP login and normal login.
		 *
		 * @param string $username the user's username.
		 * @param string $password the users's password.
		 * @return WP_Error|WP_User
		 */
		private function mo_get_user( $username, $password = null ) {
			if ( $this->mo_require_phone_identifier_for_login( $password ) ) {
				if ( MoUtility::is_blank( $username ) ) {
					return new WP_Error( 'INVALID_USERNAME', MoMessages::showMessage( MoMessages::INVALID_USERNAME ) );
				}
				if ( is_email( $username ) || ! MoUtility::validate_phone_number( $username ) ) {
					return new WP_Error(
						'PHONE_ONLY_LOGIN',
						__( 'Please log in using your registered phone number only.', 'miniorange-otp-verification' )
					);
				}
				$user = $this->mo_get_user_from_phone_number( $username );
				if ( $user && ! $this->mo_is_login_with_otp( $user->roles, $password ) ) {
					$user = wp_authenticate_username_password( null, $user->data->user_login, $password );
				}
				return $user ? $user : new WP_Error( 'INVALID_USERNAME', MoMessages::showMessage( MoMessages::INVALID_USERNAME ) );
			}

			$user = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			if ( ! $user && $this->allow_login_through_phone && MoUtility::validate_phone_number( $username ) ) {
				$user = $this->mo_get_user_from_phone_number( $username );
			}
			if ( $user && ! $this->mo_is_login_with_otp( $user->roles, $password ) ) {
				$user = wp_authenticate_username_password( null, $user->data->user_login, $password );
			}
			return $user ? $user : new WP_Error( 'INVALID_USERNAME', MoMessages::showMessage( MoMessages::INVALID_USERNAME ) );
		}


		/**
		 * Fetch the user associated with different input formats of the phone number.
		 *
		 * @param string $phone Entered phone number.
		 * @return bool|WP_User
		 */
		private function mo_get_user_from_phone_number( $phone ) {

			// get_user_from_db returns a user ID (int) or false.
			$user_id = $this->get_user_from_db( $phone );

			if ( MoUtility::is_blank( $user_id ) ) {
				if ( ! MoUtility::is_country_code_appended( $phone ) ) {
					$phone   = MoUtility::process_phone_number( $phone );
					$user_id = $this->get_user_from_db( $phone );
				} else {
					$country_code       = MoUtility::get_country_code( $phone );
					$phone_without_code = substr( $phone, strlen( $country_code ) );
					$user_id            = $this->get_user_from_db( $phone_without_code );
				}
			}

			return ! MoUtility::is_blank( $user_id ) ? get_userdata( $user_id ) : false;
		}

		/**
		 * Check if user exists in the database with the phone number.
		 *
		 * @param string $phone Processed phone number.
		 * @return int|false Returns user ID or false when not found.
		 */
		private function get_user_from_db( $phone ) {

			$users = get_users(
				array(
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary to check for duplicate phone numbers. Caching implemented above.
						array(
							'key'     => $this->get_phone_key_details(),
							'value'   => $phone,
							'compare' => '=',
						),
					),
					'number'     => 1,
					'fields'     => 'ID',
				)
			);

			if ( empty( $users ) ) {
				return false;
			}

			return (int) $users[0];
		}


		/**
		 * This functions is used to ask users the phone number and start the otp verification
		 * process.
		 *
		 * @param object $user the WordPress user data object containing all the user information.
		 * @param string $key the phone user_meta key which stores the user's phone number.
		 * @param string $username the user's username.
		 * @param string $phone_number the phone number entered by the user.
		 * @throws ReflectionException .
		 */
		private function mo_ask_phone_and_start_verification( $user, $key, $username, $phone_number ) {
			if ( ! MoUtility::is_blank( $phone_number ) ) {
				return;
			}

			if ( ! $this->mo_save_phone_numbers() ) {
				miniorange_site_otp_validation_form(
					null,
					null,
					null,
					MoMessages::showMessage( MoMessages::PHONE_NOT_FOUND ),
					null,
					null
				);
			} else {
				MoUtility::initialize_transaction( $this->form_session_var );
				$this->send_challenge(
					null,
					$user->data->user_login,
					null,
					null,
					'external',
					null,
					array(
						'data'    => array( 'user_login' => $username ),
						'message' => MoMessages::showMessage( MoMessages::REGISTER_PHONE_LOGIN ),
						'form'    => $key,
						'curl'    => MoUtility::current_page_url(),
					),
					null,
					$this->form_session_var
				);
			}
		}


		/**
		 * This functions is used to fetch the phone number from the database and start
		 * the OTP Verification process.
		 *
		 * @param array $username - the user's username.
		 * @param array $password - the password provided by the user.
		 * @param array $phone_number - phone number to send otp to.
		 * @throws ReflectionException .
		 */
		private function mo_fetch_phone_and_start_verification( $username, $password, $phone_number ) {
			MoUtility::initialize_transaction( $this->form_session_var2 );
			$redirect_raw = MoUtility::get_current_page_parameter_value( 'redirect_to', '' );
			$redirect_to  = wp_validate_redirect( $redirect_raw, MoUtility::current_page_url() );
			$this->send_challenge( $username, null, null, $phone_number, VerificationType::PHONE, $password, $redirect_to, false, $this->form_session_var );
		}


		/**
		 * This functions is used to  start the otp verification process via email.
		 *
		 * @param array $username - the user's username.
		 * @param array $email - email to send otp to.
		 * @param array $password - password of the user.
		 * @throws ReflectionException .
		 */
		private function mo_start_email_verification( $username, $email, $password ) {
			MoUtility::initialize_transaction( $this->form_session_var2 );
			$redirect_raw = MoUtility::get_current_page_parameter_value( 'redirect_to', '' );
			$redirect_to  = wp_validate_redirect( $redirect_raw, MoUtility::current_page_url() );
			$this->send_challenge( $username, $email, null, null, VerificationType::EMAIL, $password, $redirect_to, false, $this->form_session_var );
		}


		/**
		 * This function is used to send the OTP to the user's phone number.
		 *
		 * @param array $post_data - $_POST.
		 */
		private function mo_handle_wp_login_ajax_send_otp( $post_data ) {
			$user_phone = $post_data['user_phone'];
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( $this->restrict_duplicates() && ! MoUtility::is_blank( $this->mo_get_user_from_phone_number( $user_phone ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_EXISTS ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} elseif ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$this->send_challenge( 'ajax_phone', '', null, $user_phone, VerificationType::PHONE, null, $post_data, null, $this->form_session_var );
			} else {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}


		/**
		 * This function is used to process the OTP entered by the user. Check
		 * if the phone number being sent is the same one OTP was sent to .
		 *
		 * @param array $post_data - $_POST.
		 */
		private function mo_handle_wp_login_ajax_form_validate_action( $post_data ) {
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				return;
			}

			$phone = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( strcmp( $phone, MoUtility::process_phone_number( $post_data['user_phone'] ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->validate_challenge( $this->get_verification_type() );
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
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
				wp_send_json( MoUtility::create_json( MoMessages::showMessage( MoMessages::INVALID_OTP ), MoConstants::ERROR_JSON_TYPE ) );
			}

			if ( SessionUtils::is_otp_initialized( $this->form_session_var2 ) ) {
				miniorange_site_otp_validation_form(
					$user_login,
					$user_email,
					$phone_number,
					MoMessages::showMessage( MoMessages::INVALID_OTP ),
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
			if ( ( ! isset( $_POST['mopopup_wpnonce'] ) || ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mopopup_wpnonce'] ) ), 'mo_popup_options' ) ) ) ) {
				return;
			}
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
				wp_send_json( MoUtility::create_json( '', MoConstants::SUCCESS_JSON_TYPE ) );
			}

			if ( SessionUtils::is_otp_initialized( $this->form_session_var2 ) ) {
				$username = MoUtility::is_blank( $user_login ) ? MoUtility::sanitize_check( 'log', $_POST ) : $user_login;
				$username = MoUtility::is_blank( $username ) ? MoUtility::sanitize_check( 'username', $_POST ) : $username;
				$this->login_wp_user( $username, $extra_data, $phone_number );
			}
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session(
				array(
					'mo_wp_login_enrollment_ownership_ok',
					'mo_wp_login_pending_phone_enrollment_uid',
					$this->tx_session_id,
					$this->form_session_var,
					$this->form_session_var2,
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
			if ( $this->is_form_enabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Login intent when "OTP + username/password fallback" mode is enabled (from posted hidden field).
		 * Prevents browser-autofilled passwords from skipping OTP when the user chose "Login with OTP".
		 *
		 * @return string 'otp'|'password'|''
		 */
		private function mo_get_wp_login_intent() {
			$intent_raw = filter_input( INPUT_POST, 'mo_wp_login_intent', FILTER_UNSAFE_RAW );
			if ( ! is_string( $intent_raw ) || '' === $intent_raw ) {
				return '';
			}
			$intent = sanitize_text_field( wp_unslash( $intent_raw ) );
			return in_array( $intent, array( 'otp', 'password' ), true ) ? $intent : '';
		}

		/**
		 * Checks if user has initiated login with OTP.
		 *
		 * @param array  $user_roles  to check the user roles.
		 * @param string $password password entered by the user.
		 * @return bool TRUE or FALSE
		 */
		private function mo_is_login_with_otp( $user_roles = array(), $password = null ) {
			// OTP-only layout: explicit password-login mode (link from OTP popup) must verify password.
			if ( $this->skip_password_check && ! $this->skip_pass_fallback ) {
				$intent = $this->mo_get_wp_login_intent();
				if ( 'password' === $intent ) {
					return false;
				}
			}

			if ( $this->skip_password_check && $this->skip_pass_fallback ) {
				$intent = $this->mo_get_wp_login_intent();
				if ( 'otp' === $intent ) {
					return true;
				}
				if ( 'password' === $intent ) {
					return false;
				}
			}

			if ( $this->skip_password_check && $this->skip_pass_fallback && isset( $password ) && ! empty( $password ) ) {
				return false;
			} elseif ( $this->skip_password_check && $this->skip_pass_fallback && ( ! isset( $password ) || empty( $password ) ) ) {
				return true;
			} elseif ( $this->skip_password_check && ! $this->skip_pass_fallback && 'password' !== $this->mo_get_wp_login_intent() && isset( $password ) && ! empty( $password ) ) {
				return true;
			} elseif ( $this->skip_password_check && ! $this->skip_pass_fallback && 'password' !== $this->mo_get_wp_login_intent() && ( ! isset( $password ) || empty( $password ) ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Check if the user needs to be validated via OTP. Makes sure to check if admin has
		 * allowed fallback. If so check if password is entered by the user. If password is entered
		 * then do not initiate OTP
		 *
		 * @param string $password password entered by the user.
		 * @param object $user     roles of the user trying to log in.
		 * @return bool
		 */
		private function skip_otp_process( $password, $user ) {
			$user_meta = get_userdata( $user->data->ID );
			return $this->skip_password_check && $this->skip_pass_fallback && isset( $password ) && ! $this->mo_is_login_with_otp( $user_meta->roles, $password );
		}



		/**
		 * Checks to see if delay OTP has been enabled and if user's last verified DTTM is
		 * greater or equal to the time interval that has been set.
		 *
		 * @param int $user_id    user id of the user.
		 * @return bool TRUE or FALSE
		 */
		private function mo_delay_otp_process( $user_id ) {
			if ( $this->delay_otp && $this->delay_otp_interval < 0 ) {
				return true;
			}
			$last_verified_dttm = get_user_meta( $user_id, $this->time_stamp_meta_key, true );
			if ( MoUtility::is_blank( $last_verified_dttm ) ) {
				return false;
			}
			$time_diff = time() - $last_verified_dttm;
			return $this->delay_otp && $time_diff < ( $this->delay_otp_interval * 60 );
		}

		/**
		 * Handles saving all the WordPress Login Form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option(), 'wp_login_enable' ) ) {
				return;
			}

			$this->is_form_enabled             = $this->sanitize_form_post( 'wp_login_enable' );
			$this->save_phone_numbers          = $this->sanitize_form_post( 'wp_login_register_phone' );
			$this->by_pass_admin               = $this->sanitize_form_post( 'wp_login_bypass_admin' );
			$this->phone_key                   = $this->sanitize_form_post( 'wp_login_phone_field_key' );
			$this->allow_login_through_phone   = $this->sanitize_form_post( 'wp_login_allow_phone_login' );
			$this->restrict_duplicates         = $this->sanitize_form_post( 'wp_login_restrict_duplicates' );
			$this->otp_type                    = $this->sanitize_form_post( 'wp_login_enable_type' );
			$this->skip_password_check         = $this->sanitize_form_post( 'wp_login_skip_password' );
			$this->user_label                  = $this->sanitize_form_post( 'wp_username_label_text' );
			$this->skip_pass_fallback          = $this->sanitize_form_post( 'wp_login_skip_password_fallback' );
			$this->delay_otp                   = $this->sanitize_form_post( 'wp_login_delay_otp' );
			$this->delay_otp_interval          = $this->sanitize_form_post( 'wp_login_delay_otp_interval' );
			$this->redirect_after_login        = $this->sanitize_form_post( 'wp_login_redirection_enable' );
			$this->login_with_otp_button_text  = $this->sanitize_form_post( 'wp_login_with_otp_button_text' );
			$this->login_with_pass_button_text = $this->sanitize_form_post( 'wp_login_with_pass_button_text' );
			$this->login_with_pass_button_css  = $this->sanitize_form_post( 'wp_login_with_pass_button_css' );
			$page_id                           = $this->sanitize_form_post( 'mo_login_page_id', '' );
			$this->redirect_to_page            = $page_id ? absint( $page_id ) : 0;

			update_mo_option( 'wp_login_enable_type', $this->otp_type );
			update_mo_option( 'wp_login_enable', $this->is_form_enabled );
			update_mo_option( 'wp_login_register_phone', $this->save_phone_numbers );
			update_mo_option( 'wp_login_bypass_admin', $this->by_pass_admin );
			update_mo_option( 'wp_login_key', $this->phone_key );
			update_mo_option( 'wp_login_allow_phone_login', $this->allow_login_through_phone );
			update_mo_option( 'wp_login_phone_only', false );
			update_mo_option( 'wp_login_restrict_duplicates', $this->restrict_duplicates );
			update_mo_option( 'wp_login_skip_password', $this->skip_password_check && $this->is_form_enabled );
			update_mo_option( 'wp_login_skip_password_fallback', $this->skip_pass_fallback );
			update_mo_option( 'wp_username_label_text', $this->user_label );
			update_mo_option( 'wp_login_delay_otp', $this->delay_otp && $this->is_form_enabled );
			update_mo_option( 'wp_login_delay_otp_interval', $this->delay_otp_interval );
			update_mo_option( 'wp_login_redirection_enable', $this->redirect_after_login );
			update_mo_option( 'login_custom_redirect', $this->redirect_to_page );
			update_mo_option( 'wp_login_with_pass_button_text', $this->login_with_pass_button_text );
			update_mo_option( 'wp_login_with_pass_button_css', $this->login_with_pass_button_css );
			update_mo_option( 'wp_login_with_otp_button_text', $this->login_with_otp_button_text );
		}



		/*
		|--------------------------------------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------------------------------------
		*/
		/**
		 * Checks if admin has set the option to save the phone number in the database for each user.
		 *
		 * @return string
		 */
		public function mo_save_phone_numbers() {
			return $this->save_phone_numbers; }

		/**
		 * Checks if admin has set the option to bypass two factor for logged in users.
		 *
		 * @return string
		 */
		public function mo_by_pass_check_for_admins() {
			return $this->by_pass_admin; }

		/**
		 * Checks if admin has set the option to allow phone number login
		 *
		 * @return String
		 */
		public function mo_allow_login_through_phone() {
			return $this->allow_login_through_phone; }

		/**
		 * Checks if admin has set the option to allow login through username+otp
		 *
		 * @return bool|String
		 */
		public function mo_get_skip_password_check() {
			return $this->skip_password_check; }

		/**
		 * Gets the User Label Text to be shown on the Default Login Form
		 *
		 * @return string
		 */
		public function mo_get_user_label() {
			return $this->user_label; }

		/**
		 * Checks if admin has set the option to allow users to use username + password as well as username + otp
		 *
		 * @return bool
		 */
		public function mo_get_skip_password_check_fallback() {
			return $this->skip_pass_fallback; }

		/**
		 * Getter for $delay_otp
		 *
		 * @return bool
		 */
		public function mo_is_delay_otp() {
			return $this->delay_otp; }

		/**
		 * Getter for $delay_otp_interval
		 *
		 * @return int
		 */
		public function mo_get_delay_otp_interval() {
			return $this->delay_otp_interval; }

		/**
		 * Checks if admin has set the option to redirect users after loggin in
		 *
		 * @return bool
		 */
		public function mo_select_redirection_after_login() {
			return $this->redirect_after_login; }

		/**
		 * Getter for $redirect_to_page
		 *
		 * @return string
		 */
		public function mo_redirect_to_page() {
			return $this->redirect_to_page; }

		/**
		 * Getter for $login_with_pass_button_text
		 *
		 * @return string
		 */
		public function mo_get_login_with_pass_button_text() {
			return $this->login_with_pass_button_text; }

		/**
		 * Getter for $login_with_pass_button_css
		 *
		 * @return string
		 */
		public function mo_get_login_with_pass_button_css() {
			return $this->login_with_pass_button_css; }

		/**
		 * Getter for $login_with_otp_button_text
		 *
		 * @return string
		 */
		public function mo_get_login_with_otp_button_text() {
			return $this->login_with_otp_button_text; }

		/**
		 * Retrieves email and phone data from the submitted form.
		 *
		 * @return array {
		 *     @type string $email email address.
		 *     @type string $phone phone number.
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
			$phone = isset( $data['phone_number_mo'] ) ? $data['phone_number_mo'] : '';
			return array(
				'email' => '',
				'phone' => $phone,
			);
		}
	}
}
