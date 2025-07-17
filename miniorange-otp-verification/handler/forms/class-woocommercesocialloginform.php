<?php
/**
 * Load admin view for User WooCommerce Social Login form.
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
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Traits\Instance;
use ReflectionException;
use WC_Emails;
use WC_Social_Login_Provider_Profile;

/**
 * This is the WooCommerce Social Login class. This class handles all the
 * functionality related to WooCommerce Social Login. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WooCommerceSocialLoginForm' ) ) {
	/**
	 * WooCommerceSocialLoginForm class
	 */
	class WooCommerceSocialLoginForm extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Returns list of oauth providers.
		 *
		 * @var array
		 * */

		private $oauth_providers = array(
			'facebook',
			'twitter',
			'google',
			'amazon',
			'linkedIn',
			'paypal',
			'instagram',
			'disqus',
			'yahoo',
			'vk',
		);
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = true;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::WC_SOCIAL_LOGIN;
			$this->otp_type                = 'phone';
			$this->phone_form_id           = '#mo_phone_number';
			$this->form_key                = 'WC_SOCIAL_LOGIN';
			$this->form_name               = mo_( 'WooCommerce Social Login ( SMS Verification Only )' );
			$this->is_form_enabled         = get_mo_option( 'wc_social_login_enable' );
			$this->form_documents          = MoFormDocs::WC_SOCIAL_LOGIN;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->includeRequiredFiles();
			foreach ( $this->oauth_providers as $provider ) {
				add_filter( 'wc_social_login_' . $provider . '_profile', array( $this, 'mo_wc_social_login_profile' ), 99, 2 );
				add_filter( 'wc_social_login_' . $provider . '_new_user_data', array( $this, 'mo_wc_social_login' ), 99, 2 );
			}
			$this->routeData();
		}

		/**
		 * Provides a route from Request option.
		 *
		 * @return void
		 */
		public function routeData() {
			if ( ! array_key_exists( 'mo_external_popup_option', $_REQUEST ) ) { //phpcs:ignore -- false positive.
				return;
			}
			if ( ! check_ajax_referer( 'mo_popup_options', 'mopopup_wpnonce', false ) ) {
				wp_send_json( MoUtility::create_json( 'Not a valid request !', MoConstants::ERROR_JSON_TYPE ) );
			}
			$data = MoUtility::mo_sanitize_array( $_POST );
			switch ( trim( sanitize_text_field( wp_unslash( $_REQUEST['mo_external_popup_option'] ) ) ) ) { //phpcs:ignore -- false positive.
				case 'miniorange-ajax-otp-generate':
					$this->mo_handle_wc_ajax_send_otp( $data );
					break;
				case 'miniorange-ajax-otp-validate':
					$this->processOTPEntered( sanitize_text_field( wp_unslash( $_REQUEST ) ) ); //phpcs:ignore -- false positive.
					break;
				case 'mo_ajax_form_validate':
					$this->mo_handle_wc_create_user_action( $data );
					break;
			}
		}


		/**
		 * This function is used to include required WooCommerce Social Login
		 * files here so that some of their functionality can be levereged
		 * in our code.
		 */
		public function includeRequiredFiles() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active( 'woocommerce-social-login/woocommerce-social-login.php' ) ) {
				require_once plugin_dir_path( MOV_DIR ) . 'woocommerce-social-login/src/class-wc-social-login-provider-profile.php';
			}
		}


		/**
		 * This function hooks into the WooCommerce Social Provider profile hook
		 * so that the Provider details can be stored in session to be used later on
		 * after user has verified his phone number.
		 *
		 * @param string $profile - Profile of the service provider which has sent the response.
		 * @param string $provider_id - Profile of the service provider which has sent the response.
		 * @return mixed.
		 * @throws ReflectionException .
		 */
		public function mo_wc_social_login_profile( $profile, $provider_id ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			MoPHPSessions::add_session_var( 'wc_provider', $profile );
			$_SESSION['wc_provider_id'] = maybe_serialize( $provider_id );
			return $profile;
		}

		/**
		 * This function hooks into the WooCommerce Social Provider new user data
		 * hook so that OTP Verification process can be started. The hook is called
		 * by the WooCommerce Social Login plugin after it receives and processes
		 * the OAuth or OpenId response from the provider.
		 *
		 * @param array  $usermeta - The userdata coming from the provider.
		 * @param string $profile   - The profile of the service provider which has sent the response.
		 */
		public function mo_wc_social_login( $usermeta, $profile ) {
			$this->send_challenge(
				null,
				$usermeta['user_email'],
				null,
				null,
				'external',
				null,
				array(
					'data'    => $usermeta,
					'message' => MoMessages::showMessage( MoMessages::PHONE_VALIDATION_MSG ),
					'form'    => 'WC_SOCIAL',
					'curl'    => MoUtility::current_page_url(),
				)
			);
		}


		/**
		 * This function is called after the final OTP has been verified by the user so that
		 * a user can be created in WordPress. Checks to make sure that the session variable
		 * has been set to validated before calling the create new user function.
		 *
		 * @param array $post_data the data being sent by the verification form.
		 */
		public function mo_handle_wc_create_user_action( $post_data ) {

			if ( ! $this->checkIfVerificationNotStarted()
			&& SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->create_new_wc_social_customer( $post_data );
			}
		}


		/**
		 * This content of this function is based on the WooCommerce social login plugin to
		 * implement it's functionality here. It reads the response and creates the user in
		 * WordPress.
		 *
		 * @param array $user_data the postdata containing details of the user.
		 */
		public function create_new_wc_social_customer( $user_data ) {
			require_once plugin_dir_path( MOV_DIR ) . 'woocommerce/includes/class-wc-emails.php';
			WC_Emails::init_transactional_emails();

			$auth        = MoPHPSessions::get_session_var( 'wc_provider' );
			$provider_id = maybe_unserialize( sanitize_text_field( $_SESSION['wc_provider_id'] ) );
			$this->unset_otp_session_variables();
			$profile   = new WC_Social_Login_Provider_Profile( $provider_id, $auth );
			$phone     = $user_data['mo_phone_number'];
			$user_data = array(
				'role'       => 'customer',
				'user_login' => $profile->has_email() ? sanitize_email( $profile->get_email() ) : $profile->get_nickname(),
				'user_email' => $profile->get_email(),
				'user_pass'  => wp_generate_password(),
				'first_name' => $profile->get_first_name(),
				'last_name'  => $profile->get_last_name(),
			);

			if ( empty( $user_data['user_login'] ) ) {
				$user_data['user_login'] = $user_data['first_name'] . $user_data['last_name'];
			}

			$append     = 1;
			$o_username = $user_data['user_login'];

			while ( username_exists( $user_data['user_login'] ) ) {
				$user_data['user_login'] = $o_username . $append;
				$append ++;
			}

			$customer_id = wp_insert_user( $user_data );

			update_user_meta( $customer_id, 'billing_phone', MoUtility::process_phone_number( $phone ) );
			update_user_meta( $customer_id, 'telephone', MoUtility::process_phone_number( $phone ) );

			do_action( 'woocommerce_created_customer', $customer_id, $user_data, false );

			$user = get_user_by( 'id', $customer_id );

			$profile->update_customer_profile( $user->ID, $user );
			$message = apply_filters( 'wc_social_login_set_auth_cookie', '', $user );

			if ( ! $message ) {
				wc_set_customer_auth_cookie( $user->ID ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				update_user_meta( $user->ID, '_wc_social_login_' . $profile->get_provider_id() . '_login_timestamp', current_time( 'Y/m/d H:i:s' ) );
				update_user_meta( $user->ID, '_wc_social_login_' . $profile->get_provider_id() . '_login_timestamp_gmt', time() );
				do_action( 'wc_social_login_user_authenticated', $user->ID, $profile->get_provider_id() );
			} else {
				wc_add_notice( $message, 'notice' ); //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
			}

			if ( is_wp_error( $customer_id ) ) {
				$this->redirect( 'error', 0, $customer_id->get_error_code() );
			} else {
				$this->redirect( null, $customer_id );
			}
		}


		/**
		 * This function like the one above it is based from the WooCommerce Social login
		 * plugin to implement the redirect functionality of the plugin.
		 *
		 * @param array $type        - the type of redirect.
		 * @param array $user_id      - WordPress id of the user that was created.
		 * @param array $error_code   - WooCommerce Social Login error code.
		 */
		public function redirect( $type = null, $user_id = 0, $error_code = 'wc-social-login-error' ) {
			$user = get_user_by( 'id', $user_id );

			if ( MoUtility::is_blank( $user->user_email ) ) {
				$return_url = add_query_arg( 'wc-social-login-missing-email', 'true', wc_customer_edit_account_url() ); //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
			} else {
				$url_remains = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null; //phpcs:ignore -- false positive.
				$return_url  = get_transient( 'wcsl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null . $url_remains ) ); //phpcs:ignore -- false positive.
				$return_url  = $return_url ? esc_url( urldecode( $return_url ) ) : wc_get_page_permalink( 'myaccount' ); //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				delete_transient( 'wcsl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) ); //phpcs:ignore -- false positive.
			}

			if ( 'error' === $type ) {
				$return_url = add_query_arg( $error_code, 'true', $return_url );
			}

			wp_safe_redirect( esc_url_raw( $return_url ) );
			exit;
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

			wp_send_json(
				MoUtility::create_json(
					MoUtility::get_invalid_otp_method(),
					MoConstants::ERROR_JSON_TYPE
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
			wp_send_json( MoUtility::create_json( MoConstants::SUCCESS, MoConstants::SUCCESS_JSON_TYPE ) );
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
		}


		/**
		 * This function is called to generate the OTP based on the phone number provided
		 * or coming in the response from the Oauth provider.
		 *
		 * @param array $data - Data coming in the ajax call. Usually has the phone number provided.
		 */
		public function mo_handle_wc_ajax_send_otp( $data ) {

			if ( ! $this->checkIfVerificationNotStarted() ) {
				$this->send_challenge( 'ajax_phone', '', null, trim( $data['user_phone'] ), $this->otp_type, null, $data );
			}
		}


		/**
		 * This function is called to validate the OTP entered by the user. This is
		 * an ajax call and needs to send a json response indicating if the validation was
		 * successful or not.
		 *
		 * @param array $data - the data coming in the ajax call. Mostly has the otp entered.
		 */
		public function processOTPEntered( $data ) {

			if ( $this->checkIfVerificationNotStarted() ) {
				return;
			}

			if ( $this->process_phone_number( $data ) ) {
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
		 * Check to see if phone number OTP was sent to and the phone number
		 * submitted in the final form submission are the same.
		 *
		 * @param array $data .
		 * @return bool
		 */
		private function process_phone_number( $data ) {
			$phone = MoPHPSessions::get_session_var( 'phone_number_mo' );
			return strcmp( $phone, MoUtility::process_phone_number( $data['user_phone'] ) ) !== 0;
		}


		/**
		 * This functions checks if Verification was started or not.
		 */
		private function checkIfVerificationNotStarted() {

			return ! SessionUtils::is_otp_initialized( $this->form_session_var );
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
		 * Handles saving all the woocommerce Social form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			$this->is_form_enabled = $this->sanitize_form_post( 'wc_social_login_enable' );
			update_mo_option( 'wc_social_login_enable', $this->is_form_enabled );
		}
	}
}
