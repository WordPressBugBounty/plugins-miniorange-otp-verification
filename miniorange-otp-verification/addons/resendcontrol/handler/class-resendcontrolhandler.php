<?php
/**
 * Limit OTP Handler.
 *
 * @package miniorange-otp-verification/addons
 */

namespace ROC\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use ROC\Traits\Instance;
use OTP\Helper\MoConstants;
use OTP\Helper\MoPHPSessions;
use OTP\Helper\MoUtility;
use OTP\Helper\MoMessages;

/**
 * This class lists out all the messages that can be used across the AddOn.
 * Created a Base Class to handle all messages.
 */
if ( ! class_exists( 'ResendControlHandler' ) ) {
	/**
	 * ResendControlHandler class
	 */
	class ResendControlHandler {

		use Instance;


		/**
		 * The user's login name
		 *
		 * @var string $user_login The user's login name.
		 */
		private $user_login;

		/**
		 * The user's email address
		 *
		 * @var string $user_email The user's email address.
		 */
		private $user_email;

		/**
		 * The user's phone number
		 *
		 * @var string $user_phone The user's phone number.
		 */
		private $user_phone;

		/**
		 * The user's IP address
		 *
		 * @var string $user_ip The user's IP address.
		 */
		private $user_ip;

		/**
		 * If the user is blocked
		 *
		 * @var bool $is_user_blocked_mo Indicates if the user is blocked.
		 */
		private $is_user_blocked_mo;

		/**
		 * The user's ID.
		 *
		 * @var int $user_id The user's ID.
		 */
		private $user_id;

		/**
		 * The type of OTP
		 *
		 * @var string $otp_type The type of OTP (One-Time Password).
		 */
		private $otp_type;

		/**
		 * Flag indicating whether to show error messages
		 *
		 * @var bool $show_error Flag indicating whether to show error messages.
		 */
		private $show_error;

		/**
		 * Plan name of the addon
		 *
		 * @var bool $app_name Plan name of the addon.
		 */
		protected $app_name;

		/**
		 * Flag indicating whether to save options
		 *
		 * @var bool $option Flag indicating whether to save options.
		 */
		public $option;

		/**
		 * Nonce variable
		 *
		 * @var bool $nonce Nonce variable.
		 */
		public $nonce;

		/**
		 * The time saved by the admin to show timer after resend OTP
		 *
		 * @var bool $otp_control_timer_time The time saved by the admin to show timer after resend OTP.
		 */
		protected $otp_control_timer_time;

		/**
		 * The time saved by the admin to block a user
		 *
		 * @var bool $otp_control_block_time The time saved by the admin to block a user.
		 */
		protected $otp_control_block_time;


		/**
		 * Intializes the values
		 */
		public function __construct() {
			$this->app_name = 'otp_control';
			$this->option   = 'mo_handle_limit_otp_actions';
			$this->nonce    = 'mo_admin_actions';
			add_action( 'admin_init', array( $this, 'mo_update_values' ) );
			if ( get_mo_rc_option( 'otp_timer_enable' ) === false && get_mo_rc_option( 'otp_timer' ) === false ) {
				update_mo_rc_option( 'otp_timer_enable', 1 );
				update_mo_rc_option( 'otp_timer', 1 );
			}
			if ( get_mo_rc_option( 'otp_control_enable' ) || get_mo_rc_option( 'otp_timer_enable' ) ) {
				$this->otp_control_timer_time = get_mo_rc_option( 'otp_timer' ) * 60;
				$this->otp_control_block_time = get_mo_rc_option( 'otp_control_block_time' ) * 60;
				add_filter( 'limit_exceeded', array( $this, 'show_error' ), 1, 1 );
				add_filter( 'mo_add_script', array( $this, 'add_timer_otp' ), 1, 1 );
				add_action( 'mo_generate_or_resend_otp', array( $this, 'mo_handle_otp_action' ), 1, 5 );

				add_action( 'wp_ajax_nopriv_check_blocked', array( $this, 'mo_is_user_blocked' ) );
				add_action( 'wp_ajax_check_blocked', array( $this, 'mo_is_user_blocked' ) );

				add_action( 'wp_ajax_nopriv_unblock_user', array( $this, 'mo_unblock_user' ) );
				add_action( 'wp_ajax_unblock_user', array( $this, 'mo_unblock_user' ) );
			}
			if ( get_mo_rc_option( 'otp_timer_enable' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'mo_enqueue_otp_timer_script' ) );
				add_action( 'mo_include_js', array( $this, 'mo_include_js' ) );
			}
		}

		/**
		 * Function to include the javascript file on the pop-up.
		 *
		 * @return void
		 */
		public function mo_include_js() {

			wp_register_script( 'moOtpTimerScript', MO_ROC_URL . 'includes/js/moOtpTimerScript.min.js', array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'moOtpTimerScript',
				'moOtpTimerScript',
				array(
					'siteURL'                  => wp_ajax_url(),
					'action'                   => 'mo_control_otp_block',
					'otpControlTimerTime'      => $this->otp_control_timer_time,
					'otpControlBlockTime'      => $this->otp_control_block_time,
					'isUserBlocked'            => $this->is_user_blocked_mo,
					'limit_otp_sent_message'   => MoMessages::showMessage( MoMessages::LIMIT_OTP_SENT ),
					'user_blocked_message'     => MoMessages::showMessage( MoMessages::USER_IS_BLOCKED_AJAX ),
					'error_otp_verify_message' => MoMessages::showMessage( MoMessages::ERROR_OTP_VERIFY ),
				)
			);
			wp_print_scripts( 'moOtpTimerScript' );
		}

		/**
		 * Checks if the user is blocked and sends a JSON response.
		 *
		 * This function calculates the remaining block time for a user
		 * based on the stored session variable and a cooldown duration.
		 * If the user is still blocked, it returns the remaining time.
		 * Otherwise, it updates the block time and returns an unblocked status.
		 *
		 * @return void Sends a JSON response with the blocked status and remaining time.
		 */
		public function mo_is_user_blocked() {

			$block_time        = (int) MoPHPSessions::get_session_var( 'MO_OTP_BLOCKED_TIME' );
			$current_time      = time();
			$cooldown_duration = get_mo_option( 'otp_timer', 'mo_rc_sms_' ) * 60;
			$remaining_time    = $block_time ? max( 0, $cooldown_duration - ( $current_time - $block_time ) ) : 0;
			if ( $remaining_time > 0 ) {
				wp_send_json(
					array(
						'blocked'        => true,
						'remaining_time' => $remaining_time,
					)
				);
			} else {
				MoPHPSessions::add_session_var( 'MO_OTP_BLOCKED_TIME', $current_time );
				wp_send_json( array( 'blocked' => false ) );
			}
			wp_die();
		}

		/**
		 * Unblocks a user if the block duration has expired and sends a JSON response.
		 *
		 * This function checks whether the user's block time has expired. If expired,
		 * it removes the block time session variable and sends a response indicating
		 * the user is unblocked. If the block time remains, it sends a response with
		 * the remaining block time.
		 *
		 * @return void Sends a JSON response with the blocked/unblocked status and remaining time if blocked.
		 */
		public function mo_unblock_user() {
			$block_time        = (int) MoPHPSessions::get_session_var( 'MO_OTP_BLOCKED_TIME' );
			$current_time      = time();
			$cooldown_duration = get_mo_option( 'otp_timer', 'mo_rc_sms_' ) * 60;
			$remaining_time    = $block_time ? max( 0, $cooldown_duration - ( $current_time - $block_time ) ) : 0;

			if ( 0 === $remaining_time ) {
				MoPHPSessions::unset_session( 'MO_OTP_BLOCKED_TIME' );
				wp_send_json( array( 'unblocked' => true ) );
				wp_die();
			} else {
				wp_send_json(
					array(
						'blocked'        => true,
						'remaining_time' => $remaining_time,
					)
				);
			}
		}

		/**
		 * Enqueues and localizes the OTP timer script.
		 *
		 * This function checks if the OTP timer is enabled. If enabled, it registers
		 * the `moOtpTimerScript` JavaScript file and localizes it with necessary data
		 * for managing OTP blocking and timer functionalities.
		 *
		 * @return void Returns early if the OTP timer is disabled, otherwise enqueues the script.
		 */
		public function mo_enqueue_otp_timer_script() {
			if ( ! get_mo_rc_option( 'otp_timer_enable' ) ) {
				return;
			}
			wp_register_script( 'moOtpTimerScript', MO_ROC_URL . 'includes/js/moOtpTimerScript.min.js', array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'moOtpTimerScript',
				'moOtpTimerScript',
				array(
					'siteURL'                  => wp_ajax_url(),
					'action'                   => 'mo_control_otp_block',
					'otpControlTimerTime'      => $this->otp_control_timer_time,
					'otpControlBlockTime'      => $this->otp_control_block_time,
					'isUserBlocked'            => $this->is_user_blocked_mo,
					'limit_otp_sent_message'   => MoMessages::showMessage( MoMessages::LIMIT_OTP_SENT ),
					'user_blocked_message'     => MoMessages::showMessage( MoMessages::USER_IS_BLOCKED_AJAX ),
					'error_otp_verify_message' => MoMessages::showMessage( MoMessages::ERROR_OTP_VERIFY ),
				)
			);
			wp_enqueue_script( 'moOtpTimerScript' );
		}
		/**
		 * Handles the OTP action for user login, registration, or password reset.
		 * This function checks if OTP control is enabled and performs the necessary actions
		 * based on the OTP control settings and user status.
		 *
		 * @param string $user_login The user's login username.
		 * @param string $user_email The user's email address.
		 * @param string $user_phone The user's phone number.
		 * @param string $otp_type The type of OTP action (login, registration, or password reset).
		 * @param bool   $from_both Flag indicating if the OTP action is triggered from both login and registration.
		 *
		 * @return void
		 */
		public function mo_handle_otp_action( $user_login, $user_email, $user_phone, $otp_type, $from_both ) {
			if ( get_mo_rc_option( 'otp_control_enable' ) ) {
				$mo_otp_utilities = new utilities();
				$mo_otp_db        = new MoAddonDb();

				$user_ip = $mo_otp_utilities->get_client_ipaddress();
				$user_id = $mo_otp_db->check_if_user_exists( $user_login, $user_email, $user_phone, $user_ip, $otp_type );
				/**Initialize variable to be used in handle_post_challenge*/
				$this->user_ip                = $user_ip;
				$this->user_login             = $user_login;
				$this->user_email             = $user_email;
				$this->user_phone             = $user_phone;
				$this->user_id                = $user_id;
				$this->otp_type               = $otp_type;
				$is_ajax_form                 = apply_filters( 'is_ajax_form', false );
				$this->otp_control_block_time = get_mo_rc_option( 'otp_control_block_time' );
				$current_timestamp            = idate( 'U' );
				if ( $user_id ) {
					$is_blocked               = $mo_otp_utilities->is_blocked( $user_id );
					$this->is_user_blocked_mo = $is_blocked;

					/**Update attempts*/
					$this->update_user_attempt( $user_id, $is_blocked, $mo_otp_utilities );
					/**Handle blocked user*/
					$this->handle_blocked_user( $user_id, $is_blocked, $mo_otp_utilities, $current_timestamp, $this->otp_control_block_time, $is_ajax_form, $otp_type, $from_both );
				} else {
					$is_blocked = 0;
					$mo_otp_utilities->mo_insert_user( $user_login, $user_email, $user_phone, $user_ip );
					$user_id = $mo_otp_db->check_if_user_exists( $user_login, $user_email, $user_phone, $user_ip, $otp_type );
					$mo_otp_utilities->mo_block_unblock_user( $user_id, $is_blocked, $current_timestamp );
					$this->update_user_attempt( $user_id, $is_blocked, $mo_otp_utilities );
					$this->handle_blocked_user( $user_id, $is_blocked, $mo_otp_utilities, $current_timestamp, $this->otp_control_block_time, $is_ajax_form, $otp_type, $from_both );

				}
			}

		}

		/**
		 * Checks for the attempts user have made and updates in database
		 *
		 * @param mixed $user_id .
		 * @param mixed $is_blocked .
		 * @param mixed $mo_otp_utilities .
		 * @return void
		 */
		public function update_user_attempt( $user_id, $is_blocked, $mo_otp_utilities ) {
			if ( $user_id && ! $is_blocked ) {
				$attempts = $mo_otp_utilities->check_attempts( $user_id );
				++$attempts;
				$check = $mo_otp_utilities->mo_update_attempts( $user_id, $attempts );
			}
		}

		/**
		 * Handles blocked and unblocked users
		 *
		 * @param mixed $user_id .
		 * @param mixed $is_blocked .
		 * @param mixed $mo_otp_utilities .
		 * @param mixed $current_timestamp .
		 * @param mixed $otp_control_block_time .
		 * @param mixed $is_ajax_form .
		 * @param mixed $otp_type .
		 * @param mixed $from_both .
		 * @return void
		 */
		public function handle_blocked_user( $user_id, $is_blocked, $mo_otp_utilities, $current_timestamp, $otp_control_block_time, $is_ajax_form, $otp_type, $from_both ) {
			if ( $is_blocked ) {
				$case_time = $mo_otp_utilities->mo_check_time_and_update_user( $user_id, $current_timestamp, $otp_control_block_time );
				if ( 'renew' === $case_time ) {
					$mo_otp_utilities->mo_block_unblock_user( $user_id, 0, $current_timestamp );
					$this->is_user_blocked_mo = $mo_otp_utilities->is_blocked( $user_id );
					$this->update_user_attempt( $user_id, $this->is_user_blocked_mo, $mo_otp_utilities );
					return;
				} else {
					$mo_otp_utilities->mo_block_unblock_user( $user_id, 1, $current_timestamp );
					if ( $is_ajax_form || 'ajax_phone' === $this->user_login ) {
						wp_send_json( MoUtility::create_json( $this->mo_otp_control_limit_script( '', 'ajax_form' ), MoConstants::ERROR_JSON_TYPE ) );
					} else {
						miniorange_site_otp_validation_form( null, null, null, $this->mo_otp_control_limit_script( '', '' ), $otp_type, $from_both );
					}
				}
				$this->show_error = true;
			} else {
				$otp_control_limit = get_mo_rc_option( 'otp_control_limit' );
				$attempts          = $mo_otp_utilities->check_attempts( $user_id );
				if ( $attempts < $otp_control_limit ) {
					return;
				} else {
					$is_blocked = 1;
					$mo_otp_utilities->mo_block_unblock_user( $user_id, $is_blocked, $current_timestamp );
				}
			}
		}
		/**
		 * Shows error message based on OTP control settings.
		 * This function checks if OTP control is enabled and if the user is blocked,
		 * then it returns a JSON error message. Otherwise, it returns the original JSON.
		 *
		 * @param string $json The original JSON data.
		 *
		 * @return string The JSON data with error message if applicable.
		 */
		public function show_error( $json ) {
			if ( ! get_mo_rc_option( 'otp_control_enable' ) ) {
				return $json;
			}
			if ( $this->is_user_blocked_mo && $this->show_error ) {
				$ajax_block = wp_json_encode(
					array(
						'authType' => 'LIMITEXCEEDED',
						'status'   => 'LIMITEXCEEDED',
						'message'  => $this->mo_otp_control_limit_script(
							'',
							''
						),
					)
				);
				return $ajax_block;
			}
			return $json;
		}

		/**
		 * Add OTP control timer script
		 *
		 * @param string $message message to be shown if user is blocked.
		 * @param string $form_type checks if the form is On page or pop-up.
		 * @return string
		 */
		public function mo_otp_control_limit_script( $message, $form_type ) {

			if ( $this->is_user_blocked_mo && get_mo_rc_option( 'otp_control_enable' ) ) {
				$mo_otp_db                    = new MoAddonDb();
				$user_block_time              = $mo_otp_db->mo_check_time_and_update_user( $this->user_id );
				$current_timestamp            = idate( 'U' );
				$this->otp_control_block_time = get_mo_rc_option( 'otp_control_block_time' ) * 60;
				$required_time                = $user_block_time + $this->otp_control_block_time;

				$otp_control_block_time_left = $required_time - $current_timestamp;
				$message                     = MoMessages::showMessage( MoMessages::USER_IS_BLOCKED, array( 'remaining_time' => gmdate( 'i:s', $otp_control_block_time_left ) ) );

				if ( 'ajax_phone' === $this->user_login ) {
					$message .= $this->mo_get_resend_timer_script( 'ajax_form', '', $otp_control_block_time_left );
					wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
				} else {
					if ( 'ajax_form' === $form_type ) {
						$message .= $this->mo_get_timer_script( $form_type, $message );
					} else {
						$timer_script = $this->mo_get_timer_script( $form_type, $message );
						if ( is_string( $timer_script ) && ! empty( $timer_script ) ) {
							echo wp_kses_post( $timer_script );
						}
					}
				}
			}
			return $message;
		}

		/**
		 * Generates and outputs an inline OTP timer script.
		 *
		 * This function dynamically constructs a JavaScript script to handle OTP timer functionality.
		 * It is specifically used to enforce OTP request limits and display a countdown timer
		 * on various forms based on selectors.
		 *
		 * @param string $form_type The type of form to determine the behavior ('ajax_form' or other forms).
		 * @param string $message   The inline script that includes dynamic timer functionality and button control.
		 *
		 * @return string|void Returns the script as a string if the form type is 'ajax_form', otherwise outputs it.
		 *
		 * @throws void No exceptions are thrown by this function.
		 *
		 * @note Inline script usage is necessary for dynamic requirements. Proper care has been taken to
		 *       construct the script safely, and escaping is not required for this specific output.
		 */
		public static function mo_get_timer_script( $form_type, $message ) {
			$data = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Nonce is already verified before.
			if ( isset( $data['option'] ) && 'miniorange-validate-otp-form' === $data['option'] ) {
				echo $message;// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputting dynamic inline script that is safely constructed and does not require escaping.
				return;
			}
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Inline script is required due to specific dynamic requirements.
			$message = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
			<script>
			$mo=jQuery;
			setTimeout(function() {
				const mopopUpButtonSelectors = [
					\'input[id*="mo_wc_send_otp"]\',
					\'input[id*="miniorange_otp_token_submit"]\',
					\'input[id*="mo_send_otp_"]\',
					\'input[id*="mo_otp_token_submit"]\',
					\'input[id*="mo_tutor_lms_login_send_otp"]\',
					\'input[id*="mo_um_send_otp_pass"]\',
					\'input[id*="mo_um_accup_button"]\',
					\'input[id*="mo_tutor_lms_send_otp"]\',
					\'input[id*="mo_tutor_lms_student_send_otp"]\',
					\'input[id*="mo_um_getotp_button"]\',
					\'input[id*="send_otp"]\',
					\'a[id*="miniorange_otp_token_submit"]\',
					\'button[id*="miniorange_otp_token_submit"]\',
					\'button[id*="mo_send_otp_"]\',
					\'button[id*="miniorange_otp_token_submit_wc_block_checkout"]\',
				];
			
			var otp_control_block_time_string = jQuery("#mo-time-remain").attr("value");
			var timeParts = otp_control_block_time_string.split(":");
			var minutes = parseInt(timeParts[0], 10);
			var seconds = parseInt(timeParts[1], 10);

			var otp_control_block_time = (minutes * 60) + seconds;

			mopopUpButtonSelectors.forEach(function(selector) {
				if ($mo(selector).length > 0) {
					$mo(selector).hide();
				}
			});

			if($mo(".mo_send_otp_button-container").length>0) $mo(".mo_send_otp_button-container").hide();
			var timeLeft = otp_control_block_time;
			if($mo("#miniorange_wc_popup_send_otp_token").length>0)
			{
				var timerspan="<span>You have exceeded the limit to send OTP. Please wait for <span id=\'wc-timer-left\'></span></span>";
				$mo("#miniorange_wc_popup_send_otp_token").after(timerspan);
				mo_messageSelector = "div[id*=\'mo_message\']";
				jQuery($mo(mo_messageSelector)).empty().append(timerspan);
				$mo("#miniorange_wc_popup_send_otp_token").hide();
			}
			var displayVar = document.querySelector("#mo-time-remain");
			var displayVarWc = document.querySelector("#wc-timer-left");
			startTimerVar(timeLeft, displayVar);
			if($mo("#wc-timer-left").length>0)
			{
			 	startTimerVar(timeLeft, displayVarWc);
				var form_goback = document.getElementById("validation_goBack_form");
				if (form_goback && $mo(form_goback).is(":visible")) {
					form_goback.submit();
				}
			}
			$mo(".mo_customer_validation-login-container").hide();

			function startTimerVar(durationVar, displayVar) {
				var timerVar = durationVar, minutesVar, secondsVar;
				var timerFunction =    setInterval(function () {
					minutesVar = parseInt(timerVar / 60, 10);
					secondsVar = parseInt(timerVar % 60, 10);
					minutesVar = minutesVar < 10 ? "0" + minutesVar : minutesVar;
					secondsVar = secondsVar < 10 ? "0" + secondsVar : secondsVar;
					displayVar.textContent = minutesVar + ":" + secondsVar;
					if(timerVar==1 || timerVar <2){
						if($mo("#miniorange_wc_popup_send_otp_token").length>0)
						{
							$mo(".woocommerce-NoticeGroup-checkout").empty();
						}
						$mo("#popup_wc_mo").hide();
						$mo("#miniorange_wc_popup_send_otp_token").show();
						$mo("#miniorange_otp_token_submit").show();
						$mo("#wc-timer-left").parent().remove();
						var form_goback = document.getElementById("validation_goBack_form");
						if (form_goback && $mo(form_goback).is(":visible")) {
							form_goback.submit();
						}

						clearInterval(timerFunction);
						mo_messageSelector = "div[id*=\'mo_message\']";
						jQuery($mo(mo_messageSelector)).empty().hide();
						jQuery(".mo_message_box").empty().hide();
						jQuery($mo(mo_messageSelector)).css({
							"border-top": "",
							"background-color": ""
						});
						mopopUpButtonSelectors.forEach(function(selector) {
							$mo(selector).show();
						});
					}
					if (--timerVar < 0) {
						timerVar = durationVar;
					}
					}, 1000);
				}
			}, 100);
			</script>';
			if ( 'ajax_form' === $form_type ) {
				return $message;
			} else {
				echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputting dynamic inline script that is safely constructed and does not require escaping.
				return;
			}
		}

		/**
		 * Generates and outputs an inline resend OTP timer script.
		 *
		 * This function dynamically constructs a JavaScript script to handle OTP resend timer functionality.
		 * It hides resend buttons during the cooldown period and displays a countdown timer until the timer expires.
		 *
		 * @param string $form_type  The type of form to determine the behavior ('ajax_form' or other forms).
		 * @param string $message    The inline script that includes dynamic timer functionality and button control.
		 * @param int    $time_remain The time remaining for the resend OTP cooldown, in seconds.
		 *
		 * @return string|void Returns the script as a string if the form type is 'ajax_form', otherwise outputs it.
		 *
		 * @throws void No exceptions are thrown by this function.
		 *
		 * @note Inline script usage is necessary due to dynamic requirements. Proper care has been taken to construct
		 *       the script safely, and escaping is not required for this specific output.
		 */
		public static function mo_get_resend_timer_script( $form_type, $message, $time_remain ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Inline script is required due to specific dynamic requirements.
			$message = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
			<script>
			$mo=jQuery;

				var moSelectors = [
					\'input[id*="mo_wc_send_otp"]\',
					\'input[id*="miniorange_otp_token_submit"]\',
					\'input[id*="mo_send_otp_"]\',
					\'input[id*="mo_otp_token_submit"]\',
					\'input[id*="mo_tutor_lms_login_send_otp"]\',
					\'input[id*="mo_um_send_otp_pass"]\',
					\'input[id*="mo_um_accup_button"]\',
					\'input[id*="mo_tutor_lms_send_otp"]\',
					\'input[id*="mo_tutor_lms_student_send_otp"]\',
					\'input[id*="mo_um_getotp_button"]\',
					\'input[id*="send_otp"]\',
					\'a[id*="miniorange_otp_token_submit"]\',
					\'button[id*="miniorange_otp_token_submit"]\',
					\'button[id*="mo_send_otp_"]\',
					\'input[id*="mo_um_accup_button"]\',
				];

				moSelectors.forEach(function(selector) {
					if ($mo(selector).length > 0) {
						$mo(selector).hide();
					}
				});

				if (window.sentTimerFunction) {
					clearInterval(window.sentTimerFunction);
				}
		
				var timeLeft = ' . $time_remain . ';
				if($mo("#miniorange_wc_popup_send_otp_token").length>0)
				{
					var timerspan="<span>You have exceeded the limit to send OTP. Please wait for <span id=\'wc-timer-left\'></span></span>";
					$mo("#miniorange_wc_popup_send_otp_token").after(timerspan);
					$mo("#miniorange_wc_popup_send_otp_token").hide();
				}
				var displayVar = document.querySelector("#mo-time-remain");
				var blockMessageSelector = "div[id*=\'mo_message\']";
				jQuery($mo(blockMessageSelector)).css({ "background-color": " rgb(255, 239, 239)" });
				startTimerVar(timeLeft, displayVar);
				if($mo("#wc-timer-left").length>0)
				{
					var displayVarWc = document.querySelector("#wc-timer-left");
					startTimerVar(timeLeft, displayVarWc);
				}

				function startTimerVar(durationVar, displayVar) {
					var timerVar = durationVar, minutesVar, secondsVar;
					var blockTimerFunction =    setInterval(function () {
						minutesVar = parseInt(timerVar / 60, 10);
						secondsVar = parseInt(timerVar % 60, 10);
						minutesVar = minutesVar < 10 ? "0" + minutesVar : minutesVar;
						secondsVar = secondsVar < 10 ? "0" + secondsVar : secondsVar;
						displayVar.textContent = minutesVar + ":" + secondsVar;
						if(timerVar==1 || timerVar <2){
							$mo("#popup_wc_mo").hide();
							$mo("#miniorange_wc_popup_send_otp_token").show();
							$mo("#wc-timer-left").parent().remove();
							var form_goback = document.getElementById("validation_goBack_form");
							if (form_goback && $mo(form_goback).is(":visible")) {
								form_goback.submit();
							}

							clearInterval(blockTimerFunction);
							jQuery($mo(blockMessageSelector)).empty().hide();
							jQuery(".mo_message_box").empty().hide();
							jQuery($mo(blockMessageSelector)).css({
								"border-top": "",
								"background-color": ""
							});
							moSelectors.forEach(function(selector) {
								$mo(selector).show();
							});
							if($mo("#miniorange_wc_popup_send_otp_token").length>0){
      							$mo(".woocommerce-NoticeGroup").remove();
							}
						}
						if (--timerVar < 0) {
							timerVar = durationVar;
						}
					}, 1000);
				}

			</script>';
			if ( 'ajax_form' === $form_type ) {
				return $message;
			} else {
				echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputting dynamic inline script that is safely constructed and does not require escaping.
			}
		}

		/**
		 * Add Resend button timer script
		 *
		 * @param string $message message to be shown if user is blocked.
		 * @return string
		 */
		public function add_timer_otp( $message ) {
			$data   = MoUtility::mo_sanitize_array( $_POST );// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Nonce is already verified before.
			$action = isset( $data['action'] ) ? $data['action'] : '';

			if ( ( isset( $data['option'] ) && 'miniorange-validate-otp-form' === $data['option'] ) || 'mo_preview_popup' === $action ) {
				return $message;
			}

			if ( ( get_mo_option( 'wc_checkout_popup' ) || get_mo_option( 'wc_new_checkout_enable' ) ) && is_page( 'checkout' ) ) {
				return $message;
			}

			$this->mo_check_and_unblock_user();

			if ( get_mo_rc_option( 'otp_timer_enable' ) ) {
				$blocked_time   = MoPHPSessions::get_session_var( 'mo_blocked_time' );
				$block_duration = $this->otp_control_timer_time;

				if ( $blocked_time ) {
					$elapsed_time = time() - (int) $blocked_time;
					if ( $elapsed_time < $block_duration ) {
						$remaining_time = $block_duration - $elapsed_time;
						$this->mo_get_timer_script( 'pop-up', '' );
						$message      = MoMessages::showMessage( MoMessages::USER_IS_BLOCKED, array( 'remaining_time' => gmdate( 'i:s', $remaining_time ) ) );
						$is_ajax_form = apply_filters( 'is_ajax_form', false );
						if ( $is_ajax_form ) {
							wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR ) );
						} else {
							miniorange_site_otp_validation_form( null, null, null, $message, null, null );
						}
						return $message;
					}
				}
				MoPHPSessions::add_session_var( 'mo_blocked_time', time() );
				$this->mo_add_resend_button_timer( $data );
			}

			return $message;
		}

		/**
		 * Checks if the user has been blocked for the specified duration and unblocks them if the duration has passed.
		 *
		 * This function compares the current time with the user's blocked time, and if the blocking duration has passed,
		 * it removes the block by unsetting the session variable.
		 *
		 * @return void
		 *
		 * @note The function uses the session variable `mo_blocked_time` to track the user's blocked status and
		 *       compares it with the configured OTP control timer time.
		 */
		private function mo_check_and_unblock_user() {
			$blocked_time   = MoPHPSessions::get_session_var( 'mo_blocked_time' );
			$block_duration = $this->otp_control_timer_time;
			if ( $blocked_time && ( time() - $blocked_time ) >= $block_duration ) {
				MoPHPSessions::unset_session( 'mo_blocked_time' );
			}
		}

		/**
		 * Add Resend button timer script
		 *
		 * @param array $data - Data passed by the user.
		 * @return void
		 */
		public function mo_add_resend_button_timer( $data ) {
			if ( isset( $data['option'] ) && 'miniorange-validate-otp-form' === $data['option'] ) {
				return;
			}
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Inline script is required due to specific dynamic requirements.
			echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
			<script>
				$mo=jQuery;
				setTimeout(function() {
					moresendButtonSelector = $mo("a[onClick=\'mo_otp_verification_resend()\']");
					hideSelector = "a[onClick=\'mo_otp_verification_resend()\']";
					timerHtml = "<p id = \'otpTimer\' hidden style=\'float:right;margin-top:5px\'></p>";
					$mo(timerHtml).insertAfter(moresendButtonSelector);    
					moresendButtonSelector.length > 0 ?  buttonTimer(hideSelector) : "" ;

					function buttonTimer(hideSelector){
						timeLeftUnblock = ' . esc_html( $this->otp_control_timer_time ) . ';
						display = document.querySelector("#otpTimer");
						$mo(display).show();
						$mo(hideSelector).hide();
						startTimer(timeLeftUnblock, display,hideSelector);
					}

					function startTimer(duration, display,hideSelector) {
						var timer = duration, minutes, seconds;
						var timerFunction =  setInterval(function () {
							minutes = parseInt(timer / 60, 10);
							seconds = parseInt(timer % 60, 10);
							minutes = minutes < 10 ? "0" + minutes : minutes;
							seconds = seconds < 10 ? "0" + seconds : seconds;
							display.textContent = minutes + ":" + seconds + " minutes.";
							if(timer<1){
								$mo(display).hide();
								$mo(hideSelector).show();
								clearInterval(timerFunction);
							}
							if (--timer < 0) {
								timer = duration;
							}
						}, 1000);
					}            
				}, 100);

			</script>';
		}
		/**
		 * Updates the values based on the data received from the POST request.
		 * This function is responsible for updating the OTP control settings and OTP timer settings.
		 *
		 * @return void
		 */
		public function mo_update_values() {
			if ( ! wp_verify_nonce( MoUtility::sanitize_check( 'otp_control_field', $_POST ), 'otp_control' ) ) {
				return;
			}
			$data = $_POST;
			if ( isset( $data['option'] ) && 'mo_otp_control_settings' === $data['option'] ) {
				if ( ! ctype_digit( $data['otp_timer'] ) || ! ctype_digit( $data['otp_control_block_time'] ) || ! ctype_digit( $data['otp_control_limit'] ) ) {
					do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::ENTER_VALID_INT ), 'ERROR' );
					return;
				}
				if ( isset( $data['mo_customer_validation_otp_control'] ) && $data['mo_customer_validation_otp_control'] ) {
					if ( ( isset( $data['mo_customer_validation_otp_timer'] ) && $data['mo_customer_validation_otp_timer']  ) && isset( $data['otp_timer'] ) && $data['otp_timer'] >= $data['otp_control_block_time'] ) {
						do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::ENTER_VALID_BLOCK_TIME ), 'ERROR' );
						return;
					}
					if ( isset( $data['otp_control_limit'] ) && isset( $data['otp_control_block_time'] ) ) {
						update_mo_rc_option( 'otp_control_enable', 1 );
						update_mo_rc_option( 'otp_control_limit', $data['otp_control_limit'] );
						update_mo_rc_option( 'otp_control_block_time', $data['otp_control_block_time'] );
						do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::EXTRA_SETTINGS_SAVED ), 'SUCCESS' );
					} else {
						do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::ENTER_VALID_INT ), 'ERROR' );
						return;
					}
				} else {
					update_mo_rc_option( 'otp_control_enable', 0 );
				}

				if ( isset( $data['mo_customer_validation_otp_timer'] ) && $data['mo_customer_validation_otp_timer'] ) {
					if ( isset( $data['otp_timer'] ) && $data['otp_timer'] > 0 ) {
						update_mo_rc_option( 'otp_timer_enable', 1 );
						update_mo_rc_option( 'otp_timer', $data['otp_timer'] );
						do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::EXTRA_SETTINGS_SAVED ), 'SUCCESS' );

					} else {
						do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::ENTER_VALID_INT ), 'ERROR' );
					}
				} else {
					update_mo_rc_option( 'otp_timer_enable', 0 );
				}
			}

		}
	}
}
