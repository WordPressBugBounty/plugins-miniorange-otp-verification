<?php
/**Load adminstrator changes for MoUtility
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

use OTP\Objects\NotificationSettings;
use OTP\Objects\TabDetails;
use OTP\Objects\Tabs;
use OTP\Objects\VerificationType;
use \ReflectionClass;
use ReflectionException;
use \stdClass;
use OTP\LicenseLibrary\Mo_License_Service;
use OTP\Helper\MoConstants;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is the main Utility class of the plugin.
 * Lists down all the necessary common utility
 * functions being used in the plugin.
 */
if ( ! class_exists( 'MoUtility' ) ) {
	/**
	 * MoUtility class
	 */
	class MoUtility {


		/**Checking Script tags
		 *
		 * @param string $template checking script tag.
		 * @return string
		 */
		public static function check_for_script_tags( $template ) {
			return preg_match( '<script>', $template, $match );

		}

		/**Sanitizing array
		 *
		 * @param array $data data array to be sanitized.
		 * @return string
		 */
		public static function mo_sanitize_array( $data ) {
			$sanitized_data = array();
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$sanitized_data[ $key ] = self::mo_sanitize_array( $value );
				} else {
					$sanitized_data[ $key ] = sanitize_text_field( $value );
				}
			}
			return $sanitized_data;
		}

		/**MoInternal Function
		 */
		public static function mo_allow_html_array() {
			$allowed_tags = array(
				'a'          => array(
					'style'   => array(),
					'onclick' => array(),
					'class'   => array(),
					'href'    => array(),
					'rel'     => array(),
					'title'   => array(),
					'hidden'  => array(),
					'target'  => array(),
				),
				'b'          => array(
					'style' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'blockquote' => array(
					'cite' => array(),
				),
				'code'       => array(),
				'del'        => array(
					'datetime' => array(),
					'title'    => array(),
				),
				'div'        => array(
					'name'   => array(),
					'dir'    => array(),
					'id'     => array(),
					'class'  => array(),
					'title'  => array(),
					'style'  => array(),
					'hidden' => array(),
				),
				'script'     => array(),
				'style'      => array(),
				'dl'         => array(),
				'dt'         => array(),
				'em'         => array(),
				'h1'         => array(),
				'h2'         => array(),
				'h3'         => array(),
				'h4'         => array(),
				'h5'         => array(),
				'h6'         => array(),
				'hr'         => array(),
				'i'          => array(),
				'textarea'   => array(
					'id'          => array(),
					'class'       => array(),
					'name'        => array(),
					'row'         => array(),
					'style'       => array(),
					'placeholder' => array(),
					'readonly'    => array(),
				),
				'img'        => array(
					'alt'    => array(),
					'class'  => array(),
					'height' => array(),
					'style'  => array(),
					'src'    => array(),
					'width'  => array(),
					'href'   => array(),
					'hidden' => array(),
				),
				'link'       => array(
					'rel'    => array(),
					'type'   => array(),
					'href'   => array(),
					'hidden' => array(),
				),
				'li'         => array(
					'class'  => array(),
					'hidden' => array(),
				),
				'ol'         => array(
					'class' => array(),
				),
				'p'          => array(
					'class'  => array(),
					'hidden' => array(),
				),
				'q'          => array(
					'cite'  => array(),
					'title' => array(),
				),
				'span'       => array(
					'id'     => array(),
					'value'  => array(),
					'class'  => array(),
					'title'  => array(),
					'style'  => array(),
					'hidden' => array(),
				),
				'strike'     => array(),
				'strong'     => array(),
				'u'          => array(),
				'ul'         => array(
					'class' => array(),
					'style' => array(),
				),
				'form'       => array(
					'name'   => array(),
					'method' => array(),
					'id'     => array(),
					'style'  => array(),
					'hidden' => array(),
				),
				'table'      => array(
					'class' => array(),
					'style' => array(),
				),
				'tbody'      => array(),
				'button'     => array(),
				'tr'         => array(),
				'td'         => array(
					'class' => array(),
					'style' => array(),
				),
				'input'      => array(
					'type'          => array(),
					'id'            => array(),
					'name'          => array(),
					'value'         => array(),
					'class'         => array(),
					'size '         => array(),
					'tabindex'      => array(),
					'hidden'        => array(),
					'style'         => array(),
					'placeholder'   => array(),
					'disabled'      => array(),
					'data-next'     => array(),
					'data-previous' => array(),
					'maxlength'     => array(),
				),
				'br'         => array(),
				'title'      => array(
					'title' => true,
				),
			);
			return $allowed_tags;
		}

		/**MoInternal Function
		 */
		public static function mo_allow_svg_array() {
			$allowed_tags = array(
				'svg'            => array(
					'class'   => true,
					'width'   => true,
					'height'  => true,
					'viewbox' => true,
					'fill'    => true,
				),
				'circle'         => array(
					'id'           => true,
					'cx'           => true,
					'cy'           => true,
					'cz'           => true,
					'r'            => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'g'              => array(
					'fill' => true,
					'id'   => true,
				),
				'path'           => array(
					'd'              => true,
					'fill'           => true,
					'id'             => true,
					'fill-rule'      => true,
					'clip-rule'      => true,
					'stroke'         => true,
					'stroke-width'   => true,
					'stroke-linecap' => true,
				),
				'rect'           => array(
					'width'  => true,
					'height' => true,
					'rx'     => true,
					'fill'   => true,
				),
				'defs'           => array(),
				'lineargradient' => array(
					'id'            => true,
					'x1'            => true,
					'x2'            => true,
					'y1'            => true,
					'y2'            => true,
					'gradientunits' => true,
				),
				'stop'           => array(
					'stop-color' => true,
					'offset'     => true,
				),
			);
			return $allowed_tags;
		}

		/** Process the phone number and get_hidden_phone.
		 *
		 * @param string $phone - the phone number to processed.
		 *
		 * @return string
		 */
		public static function get_hidden_phone( $phone ) {
			return 'xxxxxxx' . substr( $phone, strlen( $phone ) - 3 );
		}


		/**
		 * Process the value being passed and checks if it is empty or null
		 *
		 * @param string $value - the value to be checked.
		 *
		 * @return bool
		 */
		public static function is_blank( $value ) {
			return ! isset( $value ) || empty( $value );
		}

		/**
		 * Process the plugin name is being passed and checks if it plugin is active or not
		 *
		 * @param string $plugin - the plugin name to be checked.
		 *
		 * @return bool
		 */
		public static function is_plugin_installed( $plugin ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return is_plugin_active( $plugin );
		}


		/**
		 * Creates and returns the JSON response.
		 *
		 * @param string $message - the message.
		 * @param string $type - the type of result ( success or error ).
		 * @return array
		 */
		public static function create_json( $message, $type ) {
			return array(
				'message' => $message,
				'result'  => $type,
			);
		}
		/**
		 * Check for selected country addon
		 *
		 * @param mixed $phone .
		 * @return bool
		 */
		public static function check_for_selected_country_addon( $phone ) {
			$countriesavail = CountryList::get_countrycode_list();
			$countriesavail = apply_filters( 'selected_countries', $countriesavail );

			foreach ( $countriesavail as $key => $value ) {
				if ( 'All Countries' !== $value['name'] ) {
					if ( strpos( $phone, $value['countryCode'] ) !== false ) {
						return false;
					}
				}
			}
			return true;
		}

		/** This function checks if cURL is installed on the server. */
		public static function mo_is_curl_installed() {
			return in_array( 'curl', get_loaded_extensions(), true );
		}


		/** The function returns the current page URL. */
		public static function current_page_url() {
			$page_url = 'http';

			if ( ( isset( $_SERVER['HTTPS'] ) ) && ( sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) === 'on' ) ) { //phpcs:ignore -- false positive.
				$page_url .= 's';
			}

			$page_url .= '://';

			$server_port = isset( $_SERVER['SERVER_PORT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) : ''; //phpcs:ignore -- false positive.
			$server_uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; //phpcs:ignore -- false positive.
			$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : ''; //phpcs:ignore -- false positive.

			if ( '80' !== $server_port ) {
				$page_url .= $server_name . ':' . $server_port . $server_uri;

			} else {
				$page_url .= $server_name . $server_uri;
			}

			if ( function_exists( 'apply_filters' ) ) {
				apply_filters( 'mo_curl_page_url', $page_url );
			}

			return $page_url;
		}


		/**
		 * The function retrieves the domain part of the email
		 *
		 * @param string $email - the email whose domain has to be validated.
		 *
		 * @return bool|string
		 */
		public static function get_domain( $email ) {
			$domain_name = substr( strrchr( $email, '@' ), 1 );
			return $domain_name;
		}


		/**
		 * This function validates the phone number format. Makes sure that country code
		 * is appended to the phone number. Return True or false.
		 *
		 * @param string $phone - the phone number to be validated.
		 *
		 * @return false|int
		 */
		public static function validate_phone_number( $phone ) {
			return preg_match( MoConstants::PATTERN_PHONE, self::process_phone_number( $phone ), $matches );
		}


		/**
		 * This function validates the phone number format and checks if it has country code appended.
		 * Return True or false.
		 *
		 * @param string $phone - the phone number to be checked.
		 *
		 * @return bool
		 */
		public static function is_country_code_appended( $phone ) {
			return preg_match( MoConstants::PATTERN_COUNTRY_CODE, $phone, $matches ) ? true : false;
		}

		/**
		 * Process the phone number. Check if country code is appended to the phone number. If
		 * country code is not appended then add the default country code if set any by the
		 * admin.
		 *
		 * @param string $phone - the phone number to be processed.
		 *
		 * @return mixed
		 */
		public static function process_phone_number( $phone ) {
			if ( ! $phone ) {
				return;
			}
			$phone                = preg_replace( MoConstants::PATTERN_SPACES_HYPEN, '', ltrim( trim( $phone ), '0' ) );
			$default_country_code = CountryList::get_default_countrycode();
			$phone                = ! isset( $default_country_code ) || self::is_country_code_appended( $phone ) ? $phone : $default_country_code . $phone;
			return apply_filters( 'mo_process_phone', $phone );
		}


		/**
		 * Checks if user has completed his registration in miniOrange.
		 */
		public static function micr() {
			$email        = get_mo_option( 'admin_email' );
			$customer_key = get_mo_option( 'admin_customer_key' );
			if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
				return 0;
			} else {
				return 1;
			}
		}

		/**
		 * Checks the class for license library and returns bool by checking if the license is expired.
		 */
		public static function mllc() {
			$is_free_plugin = strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0;
			return ( class_exists( MoConstants::LICENCE_LIBRARY, false ) || ( ! $is_free_plugin ) ) ? Mo_License_Service::is_license_expired() : array( 'STATUS' => false );
		}
		/**
		 * Function generates a random alphanumeric value and returns it.
		 */
		public static function rand() {
			$length        = wp_rand( 0, 15 );
			$characters    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$random_string = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$random_string .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
			}
			return $random_string;
		}


		/**
		 * Checks if user has upgraded to one of the plans.
		 */
		public static function micv() {
			$email        = get_mo_option( 'admin_email' );
			$customer_key = get_mo_option( 'admin_customer_key' );
			$check_ln     = get_mo_option( 'check_ln' );
			if ( ! $email || ! $customer_key || ! is_numeric( trim( $customer_key ) ) ) {
				return 0;
			} else {
				return $check_ln ? $check_ln : 0;
			}
		}

		/**
		 * This function checks the license of the customer. Updates the license plan,
		 * sms and email remaining values in the database if user has upgraded.
		 *
		 * @param string $show_message - show message or not.
		 * @param string $customer_key - customerKey of the admin.
		 * @param string $api_key - apiKey of the admin.
		 */
		public static function handle_mo_check_ln( $show_message, $customer_key, $api_key ) {
			$msg  = MoMessages::FREE_PLAN_MSG;
			$plan = array();

			$gateway = GatewayFunctions::instance();
			$content = json_decode( MocURLCall::check_customer_ln( $customer_key, $api_key, $gateway->get_application_name(), 'PREMIUM' ), true );
			if ( isset( $content['status'] ) && strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {

				$email_remaining = isset( $content['emailRemaining'] ) ? $content['emailRemaining'] : 0;
				$sms_remaining   = isset( $content['smsRemaining'] ) ? $content['smsRemaining'] : 0;
				$license_plan    = isset( $content['licensePlan'] ) ? $content['licensePlan'] : '';

				if ( self::sanitize_check( 'licensePlan', $content ) ) {
					if ( strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0 || strcmp( MOV_TYPE, 'EnterpriseGatewayWithAddons' ) === 0 ) {
						$msg  = MoMessages::REMAINING_TRANSACTION_MSG;
						$plan = array(
							'plan'  => $license_plan,
							'sms'   => $sms_remaining,
							'email' => $email_remaining,
						);

					} else {
						$msg  = MoMessages::UPGRADE_MSG;
						$plan = array( 'plan' => $license_plan );
					}
					update_mo_option( 'check_ln', base64_encode( $license_plan ) );//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 is needed.
				}
				update_mo_option( 'customer_license_plan', $license_plan );
				update_mo_option( 'email_transactions_remaining', $email_remaining );
				update_mo_option( 'phone_transactions_remaining', $sms_remaining );
			} else {
				$content         = json_decode( MocURLCall::check_customer_ln( $customer_key, $api_key, 'wp_email_verification_intranet', 'PREMIUM' ), true );
				$email_remaining = isset( $content['emailRemaining'] ) ? $content['emailRemaining'] : 0;
				$sms_remaining   = isset( $content['smsRemaining'] ) ? $content['smsRemaining'] : 0;
				$license_plan    = isset( $content['licensePlan'] ) ? $content['licensePlan'] : '';
				update_mo_option( 'customer_license_plan', $license_plan );
				update_mo_option( 'email_transactions_remaining', $email_remaining );
				update_mo_option( 'phone_transactions_remaining', $sms_remaining );

				if ( self::sanitize_check( 'licensePlan', $content ) ) {
					$msg = MoMessages::INSTALL_PREMIUM_PLUGIN;
				}
			}

			if ( isset( $content['status'] ) && strcasecmp( $content['status'], 'FAILED' ) === 0 ) {
				$content         = json_decode( MocURLCall::check_customer_ln( $customer_key, $api_key, '' ), true );
				$email_remaining = isset( $content['emailRemaining'] ) ? $content['emailRemaining'] : 0;
				$sms_remaining   = isset( $content['smsRemaining'] ) ? $content['smsRemaining'] : 0;
				$license_plan    = isset( $content['licenseType'] ) ? $content['licenseType'] : '';
				update_mo_option( 'customer_license_plan', $license_plan );
				update_mo_option( 'email_transactions_remaining', $email_remaining );
				update_mo_option( 'phone_transactions_remaining', $sms_remaining );
			}
			if ( isset( $content['licenseExpiry'] ) && strcasecmp( $content['status'], 'SUCCESS' ) === 0 ) {
				if ( class_exists( MoConstants::LICENCE_LIBRARY, false ) ) {
					Mo_License_Service::update_license_expiry( $content['licenseExpiry'] );
				}
			}

			if ( $show_message ) {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( $msg, $plan ), 'SUCCESS' );
			}
		}


		/**
		 * Initialize the form session indicating that the OTP Verification for the
		 * form has started.
		 *
		 * @param string $form - form for which session is being initialized / session constant name.
		 */
		public static function initialize_transaction( $form ) {
			$reflect = new ReflectionClass( FormSessionVars::class );
			foreach ( $reflect->getConstants() as $key => $value ) {
				MoPHPSessions::unset_session( $value );
			}
			SessionUtils::initialize_form( $form );
		}


		/**
		 * Returns the invalid OTP message. This function checks if admin has set an
		 * invalid otp message in the settings. If so then that is returned instead of the default.
		 */
		public static function get_invalid_otp_method() {
			return get_mo_option( 'invalid_message', 'mo_otp_' ) ? mo_( get_mo_option( 'invalid_message', 'mo_otp_' ) )
			: MoMessages::showMessage( MoMessages::INVALID_OTP );
		}


		/**
		 * Returns TRUE or FALSE depending on if the POLYLANG plugin is active.
		 * This is used to check if the translation should use the polylang
		 * function or the default local translation.
		 *
		 * @return boolean
		 */
		public static function is_polylang_installed() {
			return function_exists( 'pll__' ) && function_exists( 'pll_register_string' );
		}

		/**
		 * Take an array of string having the keyword to replace
		 * and the keyword to be replaced. This is used to modify
		 * the SMS templates that the user might have saved in the
		 * settings or the default ones by the plugin.
		 *
		 * @param array  $replace the array containing search and replace keywords.
		 * @param string $string entire string to be modified.
		 *
		 * @return mixed
		 */
		public static function replace_string( array $replace, $string ) {
			foreach ( $replace as $key => $value ) {
				$string = str_replace( '{' . $key . '}', $value, $string );
			}

			return $string;
		}

		/**
		 * Returns a stdClass Object with status Success as a
		 * temporary result when TEST_MODE is on
		 *
		 * @return stdClass
		 */
		private static function test_result() {
			$temp         = new stdClass();
			$temp->status = MO_FAIL_MODE ? 'ERROR' : 'SUCCESS';
			return $temp;
		}


		/**
		 * Send the notification to the number provided and
		 * process the response to check if the message was sent
		 * successfully or not. Return TRUE or FALSE based on the
		 * API call response.
		 *
		 * @param string $number the number to be sent.
		 * @param string $msg the message to be sent.
		 *
		 * @return bool
		 */
		public static function send_phone_notif( $number, $msg ) {

			$api_call_result = function( $number, $msg ) {
				return json_decode( MocURLCall::send_notif( new NotificationSettings( $number, $msg ) ) );
			};

			$mle = self::mllc();
			if ( $mle['STATUS'] ) {
				return false;
			}
			$number       = self::process_phone_number( $number );
			$msg          = self::replace_string( array( 'phone' => str_replace( '+', '', '%2B' . $number ) ), $msg );
			$content      = MO_TEST_MODE ? self::test_result() : $api_call_result( $number, $msg );
			$notif_status = strcasecmp( $content->status, 'SUCCESS' ) === 0 ? 'SMS_NOTIF_SENT' : 'SMS_NOTIF_FAILED';
			$tx_id        = isset( $content->txId ) ? $content->txId : '';  //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- API response from IDP returns txId.
			apply_filters( 'mo_start_reporting', $tx_id, $number, $number, 'NOTIFICATION', $msg, $notif_status );
			return strcasecmp( $content->status, 'SUCCESS' ) === 0 ? true : false;
		}

		/**
		 * Send the notification to the number provided and
		 * process the response to check if the message was sent
		 * successfully or not. Return TRUE or FALSE based on the
		 * API call response.
		 *
		 * @param string $number the number to be sent.
		 * @param string $template_name the template name.
		 * @param string $sms_tags the tags used in sms template.
		 *
		 * @return bool
		 */
		public static function send_whatsapp_notif( $number, $template_name, $sms_tags ) {
			$api_call_result = function( $number, $data ) {
				return apply_filters( 'mo_wp_send_otp_token', 'WHATSAPP_NOTIFICATION', null, $number, $data );
			};

			$data = array(
				'template_name' => $template_name,
				'sms_tags'      => $sms_tags,
			);
			$number       = self::process_phone_number( $number );
			$content      = MO_TEST_MODE ? self::test_result() : $api_call_result( $number, $data );
			$notif_status = strcasecmp( $content->status, 'SUCCESS' ) === 0 ? 'SMS_NOTIF_SENT' : 'SMS_NOTIF_FAILED';
			$tx_id        = isset( $content->txId ) ? $content->txId : '';  //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- API response from IDP returns txId.
			apply_filters( 'mo_start_reporting', $tx_id, $number, $number, 'NOTIFICATION', $template_name, $notif_status );
			return strcasecmp( $content->status, 'SUCCESS' ) === 0 ? true : false;
		}


		/**
		 * Send the notification to the email provided and
		 * process the response to check if the message was sent
		 * successfully or not. Return TRUE or FALSE based on the
		 * API call response.
		 *
		 * @param string $from_email The From Email.
		 * @param string $from_name  The From Name.
		 * @param string $to_email   The email to send message to.
		 * @param string $subject   The subject of the email.
		 * @param string $message   The message to be sent.
		 *
		 * @return bool
		 */
		public static function send_email_notif( $from_email, $from_name, $to_email, $subject, $message ) {
			$api_call_result = function( $from_email, $from_name, $to_email, $subject, $message ) {
				$notification_settings = new NotificationSettings( $from_email, $from_name, $to_email, $subject, $message );
				return json_decode( MocURLCall::send_notif( $notification_settings ) );
			};
			$content         = MO_TEST_MODE ? self::test_result() : $api_call_result( $from_email, $from_name, $to_email, $subject, $message );
			$notif_status    = strcasecmp( $content->status, 'SUCCESS' ) === 0 ? 'EMAIL_NOTIF_SENT' : 'EMAIL_NOTIF_FAILED';
			$tx_id           = isset( $content->txId ) ? $content->txId : ''; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- API response from IDP returns txId.
			apply_filters( 'mo_start_reporting', $tx_id, $to_email, $to_email, 'NOTIFICATION', '', $notif_status );
			return strcasecmp( $content->status, 'SUCCESS' ) === 0 ? true : false;
		}
		/**
		 * Check if there is an existing value in the array/buffer and return the value
		 * that exists against that key otherwise return false.
		 * <p></p>
		 * The function also makes sure to sanitize the values being fetched.
		 * <p></p>
		 * If the buffer to fetch the value from is not an array then return buffer as it is.
		 *
		 * @param string       $key    the key to check against.
		 * @param   string|array $buffer the post/get or array.
		 * @return string|bool|array
		 */
		public static function sanitize_check( $key, $buffer ) {
			if ( ! is_array( $buffer ) ) {
				return $buffer;
			}
			$value = ! array_key_exists( $key, $buffer ) || self::is_blank( $buffer[ $key ] ) ? false : $buffer[ $key ];
			return is_array( $value ) ? $value : sanitize_text_field( $value );
		}

		/**
		 * Checks if user has upgraded to the on-prem plugin
		 */
		public static function mclv() {
			$gateway = GatewayFunctions::instance();
			return $gateway->mclv();
		}


		/**Checks if the current plugin is Custom Gateway Plugin
		 */
		public static function is_gateway_config() {
			$gateway = GatewayFunctions::instance();
			return $gateway->is_gateway_config();
		}

		/**
		 * Checks if the current plugin is MiniOrangeGateway Plugin
		 *
		 * @return bool
		 */
		public static function is_mg() {
			$gateway = GatewayFunctions::instance();
			return $gateway->is_mg();
		}


		/**
		 * This function checks if all conditions to save the form settings
		 * are true. This checks if the user saving the form settings is an admin,
		 * has registered with miniorange and the the form post has an option value
		 * mo_customer_validation_settings
		 *
		 * @param string $key_val the key to check against.
		 *
		 * @return bool
		 */
		public static function are_form_options_being_saved( $key_val ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'mo_admin_actions' ) ) { //phpcs:ignore -- false positive.
				return;
			}
			return current_user_can( 'manage_options' )
			&& self::mclv()
			&& isset( $_POST['option'] ) //phpcs:ignore -- false positive.
			&& sanitize_text_field( wp_unslash( $_POST['option'] ) ) === $key_val;
		}

				/**
				 * Update SMS Email transaction in DataBase
				 *
				 * @param string $response      Resposne form Gateway.
				 * @param string $type Message type type email or phone.
				 */
		public static function mo_update_sms_email_transations( $response, $type ) {
			$content = json_decode( $response );
			if ( strcasecmp( $content->status, 'SUCCESS' ) === 0 ) {
				$option_type   = ( VerificationType::PHONE === $type ) ? 'phone_transactions_remaining' : 'email_transactions_remaining';
				$remaining_txn = get_mo_option( $option_type );
				if ( $remaining_txn > 0 ) {
					update_mo_option( $option_type, $remaining_txn - 1 );
				}
			}
		}

		/**
		 * Checks if the customer is registered or not and shows a message on the page
		 * to the user so that they can register or login themselves to use the plugin.
		 */
		public static function is_addon_activated() {
			if ( self::micr() && self::mclv() ) {
				return;
			}
			$tab_details      = TabDetails::instance();
			$server_uri       = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; //phpcs:ignore -- false positive.
			$registration_url = add_query_arg(
				array( 'page' => $tab_details->tab_details[ Tabs::ACCOUNT ]->menu_slug ),
				remove_query_arg( 'addon', $server_uri )
			);
			echo '<div style="display:block;margin-top:10px;color:red;background-color:rgba(251, 232, 0, 0.15);
								padding:5px;border:solid 1px rgba(255, 0, 9, 0.36);">
			 		<a href="' . esc_url( $registration_url ) . '">' . esc_html( mo_( 'Validate your purchase' ) ) . '</a>
			 				' . esc_html( mo_( ' to enable the Add On' ) ) . '</div>';
		}

		/**
		 * Check if the phone number is empty and return error.
		 *
		 * @param string $phone_number phone number of the user.
		 */
		public static function check_if_phone_exist( $phone_number ) {
			if ( empty( $phone_number ) ) {
				wp_send_json( self::create_json( MoMessages::showMessage( MoMessages::PHONE_NOT_FOUND ), MoConstants::ERROR_JSON_TYPE ) );
			}
		}


		/**
		 * Checks the version of the plugin active with the mentioned name.
		 *
		 * @param string  $plugin_name     -   Plugin Name.
		 * @param integer $sequence       -   index of the version digit to get.
		 * @return integer  Version number.
		 */
		public static function get_active_plugin_version( $plugin_name, $sequence = 0 ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$all_plugins   = get_plugins();
			$active_plugin = get_option( 'active_plugins' );
			foreach ( $all_plugins as $key => $value ) {
				if ( strcasecmp( $value['Name'], $plugin_name ) === 0 ) {
					if ( in_array( $key, $active_plugin, true ) ) {
						return (int) $value['Version'][ $sequence ];
					}
				}
			}
			return null;
		}
	}
}
