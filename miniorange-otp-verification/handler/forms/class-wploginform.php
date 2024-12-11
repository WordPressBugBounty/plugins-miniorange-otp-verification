<?php
/**
 * Load admin view for WordPress / WooCommerce / Ultimate Member Login Form.
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
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use \WP_Error;
use \WP_User;

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
			$this->form_name                   = mo_( 'WordPress / WooCommerce / Ultimate Member Login Form' );
			$this->is_form_enabled             = get_mo_option( 'wp_login_enable' );
			$this->user_label                  = get_mo_option( 'wp_username_label_text' );
			$this->user_label                  = $this->user_label ? mo_( $this->user_label ) : mo_( 'Username, E-mail or Phone No.' );
			$this->skip_password_check         = get_mo_option( 'wp_login_skip_password' );
			$this->allow_login_through_phone   = get_mo_option( 'wp_login_allow_phone_login' );
			$this->skip_pass_fallback          = get_mo_option( 'wp_login_skip_password_fallback' );
			$this->delay_otp                   = get_mo_option( 'wp_login_delay_otp' );
			$this->delay_otp_interval          = get_mo_option( 'wp_login_delay_otp_interval' );
			$this->login_with_otp_button_text  = get_mo_option( 'wp_login_with_otp_button_text' ) ? get_mo_option( 'wp_login_with_otp_button_text' ) : mo_( 'Login With OTP' );
			$this->login_with_pass_button_text = get_mo_option( 'wp_login_with_pass_button_text' ) ? get_mo_option( 'wp_login_with_pass_button_text' ) : mo_( 'Login With Password' );
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
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 */
		public function handle_form() {
			$this->otp_type             = get_mo_option( 'wp_login_enable_type' );
			$this->phone_key            = get_mo_option( 'wp_login_key' );
			$this->save_phone_numbers   = get_mo_option( 'wp_login_register_phone' );
			$this->by_pass_admin        = get_mo_option( 'wp_login_bypass_admin' );
			$this->restrict_duplicates  = get_mo_option( 'wp_login_restrict_duplicates' );
			$this->redirect_after_login = get_mo_option( 'wp_login_redirection_enable' );
			$this->redirect_to_page     = get_mo_option( 'login_custom_redirect' );

			add_filter( 'authenticate', array( $this, 'mo_handle_mo_wp_login' ), 99, 3 );

			add_action( 'wp_ajax_mo-admin-check', array( $this, 'isAdmin' ) );
			add_action( 'wp_ajax_nopriv_mo-admin-check', array( $this, 'isAdmin' ) );

			if ( class_exists( 'UM' ) ) {
				add_filter( 'wp_authenticate_user', array( $this, 'mo_get_and_return_user' ), 99, 2 );
				add_filter( 'um_custom_authenticate_error_codes', array( $this, 'mo_get_um_form_errors' ), 99, 1 );
			}

			$this->routeData();
		}

		/**
		 * Function check if the user loggin in is admin.
		 */
		public function isAdmin() {

			if ( ! check_ajax_referer( $this->nonce, $this->nonce_key ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::UNKNOWN_ERROR ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			$username  = MoUtility::sanitize_check( 'username', $post_data );
			$user      = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			if ( ! $user ) {
				if ( $this->allow_login_through_phone && MoUtility::validate_phone_number( $username ) ) {
					$username = MoUtility::process_phone_number( $username );
					$user     = $this->getUserFromPhoneNumber( $username );
				}
			}

			$const = $user ? ( in_array( 'administrator', $user->roles, true ) ? MoConstants::SUCCESS_JSON_TYPE : MoConstants::ERROR_JSON_TYPE ) : MoConstants::ERROR_JSON_TYPE;

			wp_send_json(
				MoUtility::create_json(
					MoMessages::showMessage( MoMessages::PHONE_EXISTS ),
					$const
				)
			);

		}

		/**
		 * Function to handle login errors on UM invalid form
		 *
		 * @param Array $errors - Errors.
		 */
		public function mo_get_um_form_errors( $errors ) {

			$data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook

			$form_id  = MoUtility::sanitize_check( 'form_id', $data );
			$username = MoUtility::sanitize_check( 'username-' . $form_id, $data );
			$password = MoUtility::sanitize_check( 'user_password-' . $form_id, $data );
			$user     = $this->getUser( $username, $data, $password );

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
		private function routeData() {

			if ( ! array_key_exists( 'mo_external_popup_option', $_REQUEST ) ) { // phpcs:ignore -- false positive.
				return;
			}
			if ( ! check_ajax_referer( 'mo_popup_options', 'mopopup_wpnonce', false ) ) {
				wp_send_json( MoUtility::create_json( 'Not a valid request !', MoConstants::ERROR_JSON_TYPE ) );
			}

			$post_data = MoUtility::mo_sanitize_array( $_POST );

			switch ( trim( sanitize_text_field( wp_unslash( $_REQUEST['mo_external_popup_option'] ) ) ) ) { // phpcs:ignore -- false positive.
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
			wp_register_script( 'mologin', MOV_URL . 'includes/js/loginform.min.js', array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'mologin',
				'movarlogin',
				array(
					'userLabel'           => ( $this->allow_login_through_phone && $this->get_verification_type() === VerificationType::PHONE ) ? $this->user_label : null,
					'skipPwdCheck'        => $this->skip_password_check,
					'skipPwdFallback'     => $this->skip_pass_fallback,
					'loginOTPButtonText'  => $this->login_with_otp_button_text,
					'loginPassButtonText' => $this->login_with_pass_button_text,
					'loginPassButtonCSS'  => $this->login_with_pass_button_css,
					'isAdminAction'       => 'mo-admin-check',
					'nonce'               => wp_create_nonce( $this->nonce ),
					'byPassAdmin'         => $this->by_pass_admin,
					'siteURL'             => wp_ajax_url(),
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

			$post_data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			if ( is_object( $username ) ) {
				return $username;
			}
			$user = $this->getUser( $username, $post_data, $password );
			if ( is_wp_error( $user ) ) {
				return $user;
			}
			UM()->login()->auth_id = $user->data->ID; //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default Ultimamte member form function
			UM()->form()->errors   = null; //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default Ultimamte member form function
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
		private function byPassLogin( $user, $skip_otp_process ) {
			$user_meta = get_userdata( $user->data->ID );
			$user_role = $user_meta->roles;
			return ( in_array( 'administrator', $user_role, true ) && $this->by_pass_admin ) || $skip_otp_process || $this->delayOTPProcess( $user->data->ID );
		}

		/**
		 * This function is called after the OTP is verified to
		 * login the user into WordPress.
		 *
		 * @param array $post_data - $_POST.
		 */
		private function mo_handle_wp_login_create_user_action( $post_data ) {
			/**
			 * Anonymous function that returns the user for the email or
			 * username that the user has submitted on the login screen
			 *
			 * @param $post_data
			 * @return bool|WP_User
			 */
			$get_user_from_post = function( $post_data ) {
				$username = MoUtility::sanitize_check( 'log', $post_data );
				if ( ! $username ) {
					$array    = array_filter(
						$post_data,
						function( $key ) {
							return strpos( $key, 'username' ) === 0;
						},
						ARRAY_FILTER_USE_KEY
					);
					$username = ! empty( $array ) ? array_shift( $array ) : $username;
				}
				return is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			};

			if ( ! SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				return;
			}

			$user = $get_user_from_post( $post_data );
			update_user_meta( $user->data->ID, $this->phone_key, $this->check_phone_length( $post_data['mo_phone_number'] ) );
			$this->login_wp_user( $user->data->user_login );
		}

		/**
		 * The function is called to login the user
		 *
		 * @param array $user_log - the username of the user logging in.
		 * @param array $extra_data - Extra dada stored in the session during sending the OTP.
		 */
		private function login_wp_user( $user_log, $extra_data = null ) {
			$user = is_email( $user_log ) ? get_user_by( 'email', $user_log ) : get_user_by( 'login', $user_log );
			$user = $user ? $user : ( $this->allowLoginThroughPhone() && MoUtility::validate_phone_number( $user_log ) ? $this->getUserFromPhoneNumber( MoUtility::process_phone_number( $user_log ) ) : '' );

			if( $user ){
				wp_set_auth_cookie( $user->data->ID, true );
				if ( $this->delay_otp && $this->delay_otp_interval > 0 ) {
					update_user_meta( $user->data->ID, $this->time_stamp_meta_key, time() );
				}
				$this->unset_otp_session_variables();
				do_action( 'wp_login', $user->user_login, $user );
			}
			
			if ( 'redirect_to_the_page' === $this->redirect_after_login ) {
				wp_safe_redirect(
					get_permalink(
						get_posts(
							array(
								'title'     => $this->redirect_to_page,
								'post_type' => 'page',
							)
						)[0]->ID
					)
				);
				exit;
			} else {
				$redirect = MoUtility::is_blank( $extra_data ) ? site_url() : $extra_data;
				wp_safe_redirect( $redirect );
				exit;

			}
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

			$post_data = MoUtility::mo_sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			$req_data  = MoUtility::mo_sanitize_array( $_REQUEST ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need for nonce verification as the function is called on third party plugin hook.

			if ( ! MoUtility::is_blank( $username ) ) {
				$user = $this->getUser( $username, $post_data, $password );
				if ( is_wp_error( $user ) ) {
					return $user;
				}

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
				
				$skip_otp_process = $this->skip_otp_process( $password, $post_data, $user );
				if ( $this->byPassLogin( $user, $skip_otp_process ) ) {
					return $user;
				}

				apply_filters( 'mo_master_otp_send_user', $user );
				$this->startOTPVerificationProcess( $user, $username, $password, $req_data );
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
		 * @param array   $req_data $_REQUEST.
		 * @throws ReflectionException .
		 */
		private function startOTPVerificationProcess( $user, $username, $password, $req_data ) {
			$otp_type = $this->get_verification_type();
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $otp_type )
			|| SessionUtils::is_status_match( $this->form_session_var2, self::VALIDATED, $otp_type ) ) {
				return;
			}

			if ( VerificationType::PHONE === $otp_type ) {
				$phone_number = get_user_meta( $user->data->ID, $this->phone_key, true );
				$phone_number = $this->check_phone_length( $phone_number );
				$this->askPhoneAndStartVerification( $user, $this->phone_key, $username, $phone_number );
				$this->fetchPhoneAndStartVerification( $username, $password, $phone_number, $req_data );
			} elseif ( VerificationType::EMAIL === $otp_type ) {
				$email = $user->data->user_email;
				$this->startEmailVerification( $username, $email );
			}
		}

		/**
		 * This functions checks if user has enabled phone number as a valid username and fetches the user
		 * associated with the phone number. Checks if the skip Password is enabled with feedback to handle
		 * OTP login and normal login.
		 *
		 * @param string $username           the user's username.
		 * @param array  $post_data          $_POST.
		 * @param string $password           the users's password.
		 * @return WP_Error|WP_User
		 */
		private function getUser( $username, $post_data, $password = null ) {
			$user = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
			if ( ! $user && $this->allow_login_through_phone && MoUtility::validate_phone_number( $username ) ) {
				$username = MoUtility::process_phone_number( $username );
				$user     = $this->getUserFromPhoneNumber( $username );
			}
			if ( $user && ! $this->isLoginWithOTP( $post_data, $user->roles ) ) {
				$user = wp_authenticate_username_password( null, $user->data->user_login, $password );
			}
			return $user ? $user : new WP_Error( 'INVALID_USERNAME', mo_( ' <b>ERROR:</b> Invalid UserName. ' ) );
		}


		/**
		 * This functions fetches the user associated with a phone number
		 *
		 * @param string $username  the user's username.
		 * @return bool|WP_User
		 */
		private function getUserFromPhoneNumber( $username ) {
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
		 * This functions is used to ask users the phone number and start the otp verification
		 * process.
		 *
		 * @param object $user the WordPress user data object containing all the user information.
		 * @param string $key the phone user_meta key which stores the user's phone number.
		 * @param string $username the user's username.
		 * @param string $phone_number the phone number entered by the user.
		 * @throws ReflectionException .
		 */
		private function askPhoneAndStartVerification( $user, $key, $username, $phone_number ) {
			if ( ! MoUtility::is_blank( $phone_number ) ) {
				return;
			}

			if ( ! $this->savePhoneNumbers() ) {
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
					)
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
		 * @param array $req_data - $_REQUEST.
		 * @throws ReflectionException .
		 */
		private function fetchPhoneAndStartVerification( $username, $password, $phone_number, $req_data ) {
			MoUtility::initialize_transaction( $this->form_session_var2 );
			$redirect_to = isset( $req_data['redirect_to'] ) ? sanitize_text_field( $req_data['redirect_to'] ) : MoUtility::current_page_url();
			$this->send_challenge( $username, null, null, $phone_number, VerificationType::PHONE, $password, $redirect_to, false );
		}


		/**
		 * This functions is used to  start the otp verification process via email.
		 *
		 * @param array $username - the user's username.
		 * @param array $email - email to send otp to.
		 * @throws ReflectionException .
		 */
		private function startEmailVerification( $username, $email ) {
			MoUtility::initialize_transaction( $this->form_session_var2 );
			$this->send_challenge( $username, $email, null, null, VerificationType::EMAIL );
		}


		/**
		 * This function is used to send the OTP to the user's phone number.
		 *
		 * @param array $post_data - $_POST.
		 */
		private function mo_handle_wp_login_ajax_send_otp( $post_data ) {
			if ( $this->restrict_duplicates()
			&& ! MoUtility::is_blank( $this->getUserFromPhoneNumber( MoUtility::process_phone_number( sanitize_text_field( $post_data['user_phone'] ) ) ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_EXISTS ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} elseif ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$this->send_challenge( 'ajax_phone', '', null, trim( sanitize_text_field( $post_data['user_phone'] ) ), VerificationType::PHONE, null, $post_data );
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
			if ( strcmp( $phone, $this->check_phone_length( $post_data['user_phone'] ) ) ) {
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
				wp_send_json( MoUtility::create_json( MoUtility::get_invalid_otp_method(), MoConstants::ERROR_JSON_TYPE ) );
			}

			if ( SessionUtils::is_otp_initialized( $this->form_session_var2 ) ) {
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
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
				wp_send_json( MoUtility::create_json( '', MoConstants::SUCCESS_JSON_TYPE ) );
			}

			if ( SessionUtils::is_otp_initialized( $this->form_session_var2 ) ) {
				$username = MoUtility::is_blank( $user_login ) ? MoUtility::sanitize_check( 'log', $post_data ) : $user_login;
				$username = MoUtility::is_blank( $username ) ? MoUtility::sanitize_check( 'username', $post_data ) : $username;
				$this->login_wp_user( $username, $extra_data );
			}
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var, $this->form_session_var2 ) );
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
		 * Checks if user has initiated login with OTP.
		 *
		 * @param array  $post_data - $_POST.
		 * @param string $user_roles to check the user roles.
		 * @return TRUE or FALSE
		 */
		private function isLoginWithOTP( $post_data, $user_roles = array() ) {
			if ( in_array( 'administrator', $user_roles, true ) && $this->by_pass_admin ) {
				return false;
			}
			return MoUtility::sanitize_check( 'wp-submit', $post_data ) === $this->login_with_otp_button_text || MoUtility::sanitize_check( 'login', $post_data ) === $this->login_with_otp_button_text || MoUtility::sanitize_check( 'logintype', $post_data ) === $this->login_with_otp_button_text;        }

		/**
		 * Check if the user needs to be validated via OTP. Makes sure to check if admin has
		 * allowed fallback. If so check if password is entered by the user. If password is entered
		 * then do not initiate OTP
		 *
		 * @param string $password  password entered by the user.
		 * @param array  $post_data - $_POST.
		 * @param object $user - roles of the user trying to log in.
		 * @return bool
		 */
		private function skip_otp_process( $password, $post_data, $user ) {
			$user_meta = get_userdata( $user->data->ID );
			return $this->skip_password_check && $this->skip_pass_fallback && isset( $password ) && ! $this->isLoginWithOTP( $post_data, $user_meta->roles );        }


		/**
		 * Function to check the length of the phone number
		 *
		 * @param array $phone - check the phone length.
		 */
		private function check_phone_length( $phone ) {
			$phone_check = MoUtility::process_phone_number( $phone );
			return strlen( $phone_check ) >= 5 ? $phone_check : '';

		}

		/**
		 * Checks to see if delay OTP has been enabled and if user's last verified DTTM is
		 * greater or equal to the time interval that has been set.
		 *
		 * @param int $user_id    user id of the user.
		 * @return bool TRUE or FALSE
		 */
		private function delayOTPProcess( $user_id ) {
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
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			$data = MoUtility::mo_sanitize_array( $_POST );

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
			$this->redirect_to_page            = isset( $data['mo_login_page_id'] ) ? get_the_title( $data['mo_login_page_id'] ) : 'My Account';

			update_mo_option( 'wp_login_enable_type', $this->otp_type );
			update_mo_option( 'wp_login_enable', $this->is_form_enabled );
			update_mo_option( 'wp_login_register_phone', $this->save_phone_numbers );
			update_mo_option( 'wp_login_bypass_admin', $this->by_pass_admin );
			update_mo_option( 'wp_login_key', $this->phone_key );
			update_mo_option( 'wp_login_allow_phone_login', $this->allow_login_through_phone );
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
		public function savePhoneNumbers() {
			return $this->save_phone_numbers; }

		/**
		 * Checks if admin has set the option to bypass two factor for logged in users.
		 *
		 * @return string
		 */
		public function byPassCheckForAdmins() {
			return $this->by_pass_admin; }

		/**
		 * Checks if admin has set the option to allow phone number login
		 *
		 * @return String
		 */
		public function allowLoginThroughPhone() {
			return $this->allow_login_through_phone; }

		/**
		 * Checks if admin has set the option to allow login through username+otp
		 *
		 * @return bool|String
		 */
		public function getSkipPasswordCheck() {
			return $this->skip_password_check; }

		/**
		 * Gets the User Label Text to be shown on the Default Login Form
		 *
		 * @return string
		 */
		public function getUserLabel() {
			return mo_( $this->user_label ); }

		/**
		 * Checks if admin has set the option to allow users to use username + password as well as username + otp
		 *
		 * @return bool
		 */
		public function getSkipPasswordCheckFallback() {
			return $this->skip_pass_fallback; }

		/**
		 * Getter for $delay_otp
		 *
		 * @return bool
		 */
		public function isDelayOtp() {
			return $this->delay_otp; }

		/**
		 * Getter for $delay_otp_interval
		 *
		 * @return int
		 */
		public function getDelayOtpInterval() {
			return $this->delay_otp_interval; }

		/**
		 * Checks if admin has set the option to redirect users after loggin in
		 *
		 * @return bool
		 */
		public function select_redirection_after_login() {
			return $this->redirect_after_login; }

		/**
		 * Getter for $redirect_to_page
		 *
		 * @return string
		 */
		public function redirectToPage() {
			return $this->redirect_to_page; }

		/**
		 * Getter for $login_with_pass_button_text
		 *
		 * @return string
		 */
		public function get_login_with_pass_button_text() {
			return $this->login_with_pass_button_text; }

		/**
		 * Getter for $login_with_pass_button_css
		 *
		 * @return string
		 */
		public function get_login_with_pass_button_css() {
			return $this->login_with_pass_button_css; }

		/**
		 * Getter for $login_with_otp_button_text
		 *
		 * @return string
		 */
		public function get_login_with_otp_button_text() {
			return $this->login_with_otp_button_text; }
	}
}
