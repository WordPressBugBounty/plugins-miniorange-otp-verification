<?php
/**
 * Handler Functions for Social Login Form
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

/**
 * This is the miniOrange Social Login Form class. This class handles all the
 * functionality related to miniOrange Social Login Form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'SocialLoginIntegration' ) ) {
	/**
	 * SocialLoginIntegration class
	 */
	class SocialLoginIntegration extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::SOCIAL_LOGIN;
			$this->phone_key               = 'phone';
			$this->phone_form_id           = '#phone_number_mo';
			$this->form_key                = 'SOCIAL_LOGIN';
			$this->type_phone_tag          = 'mo_social_login_phone_enable';
			$this->type_email_tag          = 'mo_wp_default_email_enable';
			$this->type_both_tag           = 'mo_wp_default_both_enable';
			$this->form_name               = mo_( 'miniOrange Social Login' );
			$this->is_form_enabled         = get_mo_option( 'mo_social_login_enable' );
			$this->form_documents          = MoFormDocs::SOCIAL_LOGIN;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException Adds exception.
		 */
		public function handle_form() {
			$this->otp_type = $this->is_form_enabled ? $this->type_phone_tag : '';
			add_action( 'mo_before_insert_user', array( $this, 'social_login_verification' ), 1, 2 );
			MoPHPSessions::check_session();
			if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
				$this->unset_otp_session_variables();
				$social_login_nonce = wp_create_nonce( 'social_login_nonce' );
				if ( ! wp_verify_nonce( $social_login_nonce, 'social_login_nonce' ) === 1 ) {
					return;
				}

				$mo_phone_number = isset( $_POST['mo_phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['mo_phone_number'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
				$random_password = wp_generate_password( 10, false );
				$userdetails     = MoPHPSessions::get_session_var( 'userdetails' );
				$cust_reg_val    = MoPHPSessions::get_session_var( 'cust_reg_val' );
				$userdata        = array(
					'user_login'   => $userdetails['user_login'],
					'user_email'   => $userdetails['user_email'],
					'user_pass'    => $random_password,
					'display_name' => $userdetails['display_name'],
					'first_name'   => $userdetails['first_name'],
					'last_name'    => $userdetails['last_name'],
					'user_url'     => $userdetails['user_url'],
					'phone'        => $mo_phone_number,
				);
				if ( get_option( 'mo_openid_restricted_domains' ) === 'mo_openid_restrict_domain' ) {
					$this->restricted_domain( $userdata['user_email'] );
				} else {
					$this->allowed_domain( $userdata['user_email'] );
				}

				$_SESSION['registered_user'] = '1';

				if ( get_option( 'mo_openid_enable_registration_on_page' ) === '1' ) {
					$user_id = $this->mo_openid_check_registration_block( $userdata );
				} else {
					$user_id = wp_insert_user( $userdata );
				}

				if ( '' !== $cust_reg_val ) {
					$this->update_custom_data( $user_id, $cust_reg_val );
				}

				if ( get_option( 'mo_openid_user_moderation' ) === 1 ) {
					add_user_meta( $user_id, 'activation_state', '1' );
				}

				if ( isset( $_COOKIE['mo_openid_signup_url'] ) ) { //phpcs:ignore -- false positive.
					add_user_meta( $user_id, 'registered_url', sanitize_text_field( wp_unslash( $_COOKIE['mo_openid_signup_url'] ) ) ); //phpcs:ignore -- false positive.
				}

				$user = get_user_by( 'email', $userdata['user_email'] );
				if ( $user_id && ! is_wp_error( $user_id ) && get_option( 'mo_openid_email_activation' ) === 1 ) {
					$this->mo_send_activation_mail( $user, $user_id );
					$this->mo_openid_insert_query( $userdetails['social_app_name'], $userdetails['user_email'], $user_id, $userdetails['social_user_id'], $userdetails['user_picture'] );
					exit;
				}

				if ( is_wp_error( $user_id ) ) {
					print_r( $user_id ); //phpcs:ignore
					wp_die( 'Error Code 5: ' . esc_attr( get_option( 'mo_registration_error_message' ) ) );
				}

				update_option( 'mo_openid_user_count', get_option( 'mo_openid_user_count' ) + 1 );

				$session_values = array(
					'username'        => sanitize_text_field( $userdetails['user_login'] ),
					'user_email'      => sanitize_email( $userdetails['user_email'] ),
					'user_full_name'  => sanitize_text_field( $userdetails['display_name'] ),
					'first_name'      => sanitize_text_field( $userdetails['first_name'] ),
					'last_name'       => sanitize_text_field( $userdetails['last_name'] ),
					'user_url'        => sanitize_text_field( $userdetails['user_url'] ),
					'user_picture'    => sanitize_text_field( $userdetails['user_picture'] ),
					'social_app_name' => sanitize_text_field( $userdetails['social_app_name'] ),
					'social_user_id'  => sanitize_text_field( $userdetails['social_user_id'] ),
				);

				$this->mo_openid_start_session_login( $session_values );
				$user = get_user_by( 'id', $user_id );
				update_user_meta( $user_id, 'verified_number', $mo_phone_number );
						do_action( 'mo_user_register', $user_id, $userdetails['user_profile_url'] );
				$this->mo_openid_paid_membership_pro_integration( $user_id );
				$this->mo_openid_link_account( $user->user_login, $user );
				global $wpdb;
				$db_prefix       = $wpdb->prefix;
				$linked_email_id = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM `{$wpdb->prefix}mo_openid_linked_user` where `linked_social_app` = %s AND `identifier` = %s', array( $userdetails['social_app_name'], $userdetails['social_user_id'] ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
				$this->mo_openid_login_user( $linked_email_id, $user_id, $user, $userdetails['user_picture'], 0 );
			}

			$this->routeData();
		}
		/**
		 * Function to check if the email domain is restricted.
		 *
		 * @param string $user_email - user email entered by user.
		 * @return void
		 */
		private function restricted_domain( $user_email ) {
			$allowed           = false;
			$restricted_domain = get_option( 'mo_openid_restricted_domains_name' );

			if ( empty( $restricted_domain ) || empty( $user_email ) ) {
				return;
			}
			$email = explode( ';', $restricted_domain );
			foreach ( $email as $value ) {
				$data        = explode( '@', $user_email );
				$user_domain = isset( $data[1] ) ? $data[1] : '';

				if ( $value === $user_domain ) {
					$allowed = true;
					break;
				}
			}
			if ( $allowed ) {
				wp_die( 'Permission denied. You are not allowed to register. Please contact the administrator. Click <a href="' . esc_url( get_site_url() ) . '">here</a> to go back to the website.' );
			}
		}

		/**
		 * Function to check if the email domain is allowed.
		 *
		 * @param string $user_email - user email entered by user.
		 * @return void
		 */
		private function allowed_domain( $user_email ) {
			$allowed           = false;
			$restricted_domain = get_option( 'mo_openid_allowed_domains_name' );

			if ( empty( $restricted_domain ) || empty( $user_email ) ) {
				return;
			}
			$email = explode( ';', $restricted_domain );

			foreach ( $email as $value ) {
				$data        = explode( '@', $user_email );
				$user_domain = isset( $data[1] ) ? $data[1] : '';

				if ( $value === $user_domain ) {
					$allowed = true;
					break;
				}
			}

			if ( ! $allowed ) {
				wp_die( 'Permission denied. You are not allowed to register. Please contact the administrator. Click <a href="' . esc_url( get_site_url() ) . '">here</a> to go back to the website.' );
			}
		}

		/**
		 * Function to check Registration block
		 *
		 * @param array $userdata - gives user information to register a user.
		 */
		private function mo_openid_check_registration_block( $userdata ) {
			$registration_urls = explode( ';', get_option( 'mo_openid_registration_page_urls' ) );
			foreach ( $registration_urls as $val ) {
				if ( isset( $_COOKIE['mo_openid_signup_url'] ) ) { //phpcs:ignore -- false positive.
					if ( strpos( sanitize_text_field( wp_unslash( $_COOKIE['mo_openid_signup_url'] ) ), $val ) !== false ) { //phpcs:ignore -- false positive.
						$user_id = wp_insert_user( $userdata );
						return $user_id;
					}
				}
			}
			wp_safe_redirect( get_option( 'mo_openid_block_registration_redirect_url' ) );
			exit;
		}

		/**
		 * Function to start user login session
		 *
		 * @param array $session_values - user details to save in session.
		 * @return void
		 */
		private function mo_openid_start_session_login( $session_values ) {
			mo_openid_start_session();   //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default Social login function.
			$_SESSION['mo_login']        = true;
			$_SESSION['username']        = isset( $session_values['username'] ) ? $session_values['username'] : '';
			$_SESSION['user_email']      = isset( $session_values['user_email'] ) ? $session_values['user_email'] : '';
			$_SESSION['user_full_name']  = isset( $session_values['user_full_name'] ) ? $session_values['user_full_name'] : '';
			$_SESSION['first_name']      = isset( $session_values['first_name'] ) ? $session_values['first_name'] : '';
			$_SESSION['last_name']       = isset( $session_values['last_name'] ) ? $session_values['last_name'] : '';
			$_SESSION['user_url']        = isset( $session_values['user_url'] ) ? $session_values['user_url'] : '';
			$_SESSION['user_picture']    = isset( $session_values['user_picture'] ) ? $session_values['user_picture'] : '';
			$_SESSION['social_app_name'] = isset( $session_values['social_app_name'] ) ? $session_values['social_app_name'] : '';
			$_SESSION['social_user_id']  = isset( $session_values['social_user_id'] ) ? $session_values['social_user_id'] : '';
		}

		/**
		 * Function for Paid Membership Pro Plugin Integration
		 *
		 * @param int $user_id - user id of the current user.
		 * @return void
		 */
		private function mo_openid_paid_membership_pro_integration( $user_id ) {
			global $wpdb;
			if ( get_option( 'mo_openid_paid_memb_default' ) === 1 ) {
				global $wpdb;
				$db_prefix = $wpdb->prefix;
				$id        = $wpdb->get_var( 'SELECT COUNT(*) FROM wp_pmpro_memberships_users ' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
				++$id;
				$membership_id = get_option( 'mo_openid_paid_memb_default_opt' );
				$c_time        = gmdate( 'Y-m-d H:i:s' );
				$s             = $wpdb->query( $wpdb->prepare( 'insert into `{$wpdb->prefix}memberships_users` values ($id, $user_id, $membership_id, 0, 0.00, 0.00, 0, "", 0, 0.00, 0, "active", %s, "0000-00-00 00:00:00", %s', array( $c_time, $c_time ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
				if ( false === $s ) {
					$wpdb->show_errors();
					$wpdb->print_error();
					wp_die( 'Error in insert Query' );
					exit;
				}
			}
			if ( get_option( 'mo_openid_paid_memb_choose' ) === 1 ) {
				update_user_meta( $user_id, 'chosen_membership', 0 );
			}
		}

		/**
		 * Undocumented function
		 *
		 * @param string        $username - username of current user.
		 * @param WP_User|false $user - user details of current user.
		 * @return void
		 */
		private function mo_openid_link_account( $username, $user ) {

			if ( $user ) {
				$userid = $user->ID;
			}
			if ( function_exists( 'mo_openid_start_session' ) ) {
				mo_openid_start_session();  //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function Social Login plugin.
			}

			$user_email            = isset( $_SESSION['user_email'] ) ? sanitize_text_field( $_SESSION['user_email'] ) : '';
			$social_app_identifier = isset( $_SESSION['social_user_id'] ) ? sanitize_text_field( $_SESSION['social_user_id'] ) : '';
			$social_app_name       = isset( $_SESSION['social_app_name'] ) ? sanitize_text_field( $_SESSION['social_app_name'] ) : '';
			if ( isset( $userid ) && empty( $social_app_identifier ) && empty( $social_app_name ) ) {
				return;
			} elseif ( ! isset( $userid ) ) {
				return;
			}
			global $wpdb;
			$db_prefix       = $wpdb->prefix;
			$linked_email_id = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM `{$wpdb->prefix}mo_openid_linked_user` where `linked_email` = %s AND `linked_social_app` = %s', array( $user_email, $social_app_name ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			if ( ! isset( $linked_email_id ) ) {
				$this->mo_openid_insert_query( $social_app_name, $user_email, $userid, $social_app_identifier );
			}
		}

		/**
		 * Login user using openid
		 *
		 * @param string        $linked_email_id - linked email id of user.
		 * @param int           $user_id - user id of the current user.
		 * @param WP_User|false $user - user details of current user.
		 * @param string        $user_picture - picture of the current user.
		 * @param int           $user_mod_msg - default 0.
		 * @return void
		 */
		private function mo_openid_login_user( $linked_email_id, $user_id, $user, $user_picture, $user_mod_msg ) {
			if ( get_option( 'moopenid_social_login_avatar' ) && isset( $user_picture ) ) {
				update_user_meta( $user_id, 'moopenid_user_avatar', $user_picture );
			}
			if ( get_option( 'mo_openid_email_activation' ) === 1 ) {
				mo_verify_activated_user( $user, $user->ID );  //phpcs:ignore: intelephense.diagnostics.undefinedFunctions -- Default function Social login plugin.
				exit;
			}
			if ( get_option( 'mo_openid_user_moderation' ) === 1 ) {
				$x = get_user_meta( $linked_email_id, 'activation_state' );
				if ( '1' !== $x[0] ) {
					$this->mo_openid_paid_membership_pro_integration( $user_id );
					do_action( 'wp_login', $user->user_login, $user );
				} else {
					$this->mo_openid_paid_membership_pro_integration( $user_id );
					$this->mo_openid_link_account( $user->user_login, $user );
					?>
				<script>
					var pop_up = '<?php echo esc_js( get_option( 'mo_openid_popup_window' ) ); ?>';
					if (pop_up== '0') {
						alert("Successfully registered! You will get notification after activation of your account.");
						window.location = "<?php echo esc_attr( get_option( 'siteurl' ) ); ?>";
					} else {
						alert("Successfully registered! You will get notification after activation of your account.");
						window.close();
					}
				</script>
					<?php
					exit();
				}
			} else {
				$this->mo_openid_paid_membership_pro_integration( $user_id );
			}
			do_action( 'wp_login', $user->user_login, $user );
			wp_set_auth_cookie( $user_id, true );
			$redirect_url = mo_openid_get_redirect_url();  //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default Social login function.
			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Function to run query for inserting user data
		 *
		 * @param string $social_app_name - Name of the Social Login application from which user has logged in.
		 * @param string $user_email - current user email address.
		 * @param int    $userid - current user userID.
		 * @param string $social_app_identifier - Identifier of the Social Login application.
		 */
		private function mo_openid_insert_query( $social_app_name, $user_email, $userid, $social_app_identifier ) {

			if ( ! empty( $social_app_name ) && ! empty( $user_email ) && ! empty( $userid ) && ! empty( $social_app_identifier ) ) {

				date_default_timezone_set( 'Asia/Kolkata' ); //phpcs:ignore -- Function sets the default timezone used by all date/time functions.
				$date = gmdate( 'Y-m-d H:i:s' );

				global $wpdb;
				$db_prefix  = $wpdb->prefix;
				$table_name = $db_prefix . 'mo_openid_linked_user';

				$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
					$table_name,
					array(
						'linked_social_app' => $social_app_name,
						'linked_email'      => $user_email,
						'user_id'           => $userid,
						'identifier'        => $social_app_identifier,
						'timestamp'         => $date,
					),
					array(
						'%s',
						'%s',
						'%d',
						'%s',
						'%s',
					)
				);
				if ( false === $result ) {

					wp_die( 'Error in insert query' );
				}
			}
		}

		/**
		 * Function to Send Email for user Activation
		 *
		 * @param WP_User|false $user - user details of current user.
		 * @param int           $user_id - user id of the current user.
		 * @return void
		 */
		private function mo_send_activation_mail( $user, $user_id ) {
			update_user_meta( $user_id, 'mo_user_status', '0' );
			$redirect_url = wp_login_url();
			$to           = $user->user_email;
			$websitename  = get_option( 'siteurl' );

			$act_code        = base64_encode( $user_id . time() ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 is needed.
			$subject         = 'Please Verify your account';
			$user_id_encoded = base64_encode( $user_id ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 is needed.

			$replace = "<html><body>
            <a href= $redirect_url?uid=$user_id_encoded&act_code=$act_code>VERIFY YOUR ACCOUNT </a><br><br><br>
             </body></html>";

			$msg = get_option( 'mo_openid_activation_email_message' );
			$msg = str_replace( '##activation_link##', $replace, $msg );
			$msg = str_replace( '##website_name##', $websitename, $msg );

			$headers = 'Content-Type: text/html';
			wp_mail( $to, $subject, $msg, $headers );
			update_user_meta( $user_id, 'activation_code', $act_code );
			?>
		<script>
			var pop_up = '<?php echo esc_js( get_option( 'mo_openid_popup_window' ) ); ?>';
			var redirect_home =  '<?php echo esc_url_raw( get_option( 'mo_openid_activation_page_urls' ) ); ?>';
			if (pop_up=='0') {
				window.location = redirect_home;
			}else {
				window.close();
			}
		</script>
			<?php
				do_action( 'mo_user_register', $user_id, $user->user_profile_url );
			do_action( 'miniorange_collect_attributes_for_authenticated_user', $user, mo_openid_get_redirect_url() ); //phpcs:ignore  Default Social login function.

		}

		/**
		 * Function to update customer data
		 *
		 * @param int    $user_id - user id of the current user.
		 * @param object $cust_reg_val - customer registration value.
		 * @return void
		 */
		private function update_custom_data( $user_id, $cust_reg_val ) {
			foreach ( $cust_reg_val as $x ) {
				foreach ( $x as $field => $res ) {
					update_user_meta( $user_id, $field, $res );
				}
			}
		}

		/**
		 * * @throws ReflectionException
		 */
		private function routeData() {
			if ( ! array_key_exists( 'mo_external_popup_option', $_REQUEST ) ) { //phpcs:ignore -- false positive.
				return;
			}

			switch ( trim( sanitize_text_field( wp_unslash( $_REQUEST['mo_external_popup_option'] ) ) ) ) { //phpcs:ignore -- false positive.
				case 'miniorange-ajax-otp-generate':
					if ( ! check_ajax_referer( 'mo_popup_options', 'mopopup_wpnonce', false ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoMessages::showMessage( MoMessages::INVALID_OP ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
						exit;
					}
					$data = MoUtility::mo_sanitize_array( $_POST );
					$this->handle_social_login_ajax_send_otp( $data );
					break;
				case 'miniorange-ajax-otp-validate':
					if ( ! check_ajax_referer( 'mo_popup_options', 'mopopup_wpnonce', false ) ) {
						wp_send_json(
							MoUtility::create_json(
								MoMessages::showMessage( MoMessages::INVALID_OP ),
								MoConstants::ERROR_JSON_TYPE
							)
						);
						exit;
					}
					$data = MoUtility::mo_sanitize_array( $_POST );
					$this->handle_social_login_ajax_form_validate_action( $data );
					break;
			}
		}

		/**
		 * Function to send OTP after social login
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function handle_social_login_ajax_send_otp( $data ) {
			MoPHPSessions::check_session();
			MoUtility::initialize_transaction( $this->form_session_var );
			if ( SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				$this->send_challenge( 'ajax_phone', '', null, trim( sanitize_text_field( $data['user_phone'] ) ), VerificationType::PHONE, sanitize_text_field( $data['user_pass'] ), $data );
			}
		}

		/**
		 * Function to validate OTP
		 *
		 * @param array $data - this is the get / post data from the ajax call containing email or phone.
		 */
		private function handle_social_login_ajax_form_validate_action( $data ) {
			MoPHPSessions::check_session();
			$phone = MoPHPSessions::get_session_var( 'phone_number_mo' );
			if ( strcmp( $phone, MoUtility::process_phone_number( sanitize_text_field( $data['user_phone'] ) ) ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_MISMATCH ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			} else {
				$this->validate_challenge( $this->get_verification_type(), null, sanitize_text_field( $data['mo_otp_token'] ) );
				if ( SessionUtils::is_status_match( $this->form_session_var, self::VALIDATED, $this->get_verification_type() ) ) {
					wp_send_json(
						MoUtility::create_json(
							MoConstants::SUCCESS_JSON_TYPE,
							MoConstants::SUCCESS_JSON_TYPE
						)
					);
				} else {
					wp_send_json(
						MoUtility::create_json(
							MoMessages::showMessage( MoMessages::INVALID_OTP ),
							MoConstants::ERROR_JSON_TYPE
						)
					);
				}
			}
		}

		/**
		 * This is a utility function specific to this class which checks if
		 * SMS Verification has been enabled by the admin for Registration Magic Registration
		 * form.
		 */
		private function isPhoneVerificationEnabled() {
			$otp_type = $this->get_verification_type();
			return VerificationType::PHONE === $otp_type || VerificationType::BOTH === $otp_type;
		}

		/**
		 * Function for Social Login Verification and hooks to mo_before_insert_user
		 *
		 * @param array  $userdetails - user details of the current user.
		 * @param string $cust_reg_val - customer registration value.
		 * @return void
		 */
		public function social_login_verification( $userdetails, $cust_reg_val ) {
			MoUtility::initialize_transaction( $this->form_session_var );
			MoPHPSessions::add_session_var( 'cust_reg_val', $cust_reg_val );
			MoPHPSessions::add_session_var( 'userdetails', $userdetails );
			$this->send_challenge(
				null,
				null,
				null,
				null,
				'external',
				$userdetails['user_pass'],
				array(
					'data'    => $userdetails['user_pass'],
					'message' => MoMessages::showMessage( MoMessages::REGISTER_PHONE_LOGIN ),
					'form'    => $this->phone_key,
					'curl'    => MoUtility::current_page_url(),
				)
			);

		}

		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );

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
			MoPHPSessions::check_session();
			SessionUtils::add_status( $this->form_session_var, self::VALIDATED, $otp_type );
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
			MoPHPSessions::check_session();
			SessionUtils::add_status( $this->form_session_var, self::VERIFICATION_FAILED, $otp_type );
			wp_send_json( MoUtility::create_json( MoUtility::get_invalid_otp_method(), MoConstants::ERROR_JSON_TYPE ) );
		}


		/**
		 * This function is called by the filter mo_phone_dropdown_selector
		 * to return the Jquery selector of the phone field. The function will
		 * push the form_id to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {
			MoPHPSessions::check_session();
			if ( $this->is_form_enabled() && $this->isPhoneVerificationEnabled() ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;

		}

		/**
		 * Handles saving all the Social Login form related options by the admin.
		 */
		public function handle_form_options() {

			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'mo_social_login_enable' );

			update_mo_option( 'mo_social_login_enable', $this->is_form_enabled );
		}
	}
}
?>
