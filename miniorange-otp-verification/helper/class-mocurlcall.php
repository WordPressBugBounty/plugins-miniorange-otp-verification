<?php
/**Load adminstrator changes for MocURLCall
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

use OTP\Objects\NotificationSettings;
use OTP\Objects\VerificationType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class denotes all the cURL related functions to make API calls
 * to the miniOrange server. You can read about cURL here : {@link https://curl.haxx.se/}
 * and read how it is implemented in PHP here: {@link http://php.net/manual/en/book.curl.php}
 *
 * CURL is required by the plugin for OTP Verification to work. Without
 * cURL the plugin is as good as useless.
 */
if ( ! class_exists( 'MocURLCall' ) ) {
	/**
	 * MocURLCall class
	 */
	class MocURLCall {


		/** MoInternal Function
		 *
		 * @param string $email email address.
		 * @param string $company comapany name.
		 * @param string $password password of user.
		 * @param string $phone phone number of user.
		 * @param string $first_name first name of user.
		 * @param string $last_name last name of user.
		 */
		public static function create_customer( $email, $company, $password, $phone = '', $first_name = '', $last_name = '' ) {
			$url          = MoConstants::HOSTNAME . '/moas/rest/customer/add';
			$customer_key = MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'companyName'    => $company,
				'areaOfInterest' => MoConstants::AREA_OF_INTEREST,
				'firstname'      => $first_name,
				'lastname'       => $last_name,
				'email'          => $email,
				'phone'          => $phone,
				'password'       => $password,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/** MoInternal Function
		 *
		 * @param string $email email address.
		 * @param string $password password of user.
		 */
		public static function get_customer_key( $email, $password ) {
			$url          = MoConstants::HOSTNAME . '/moas/rest/customer/key';
			$customer_key = MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'email'    => $email,
				'password' => $password,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/** MoInternal Function
		 *
		 * @param string $email email address.
		 */
		public static function check_customer( $email ) {
			$url          = MoConstants::HOSTNAME . '/moas/rest/customer/check-if-exists';
			$customer_key = MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'email' => $email,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}


		/** MoInternal Function
		 *
		 * @param string $auth_type authentication type wither SMS or Email.
		 * @param string $email email address.
		 * @param string $phone phone number of user.
		 */
		public static function mo_send_otp_token( $auth_type, $email = '', $phone = '' ) {
			$email        = 'SMS' === $auth_type ? null : $email;
			$url          = MoConstants::HOSTNAME . '/moas/api/auth/challenge';
			$customer_key = ! MoUtility::is_blank( get_mo_option( 'admin_customer_key' ) )
						? get_mo_option( 'admin_customer_key' ) : MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = ! MoUtility::is_blank( get_mo_option( 'admin_api_key' ) )
						? get_mo_option( 'admin_api_key' ) : MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'customerKey'     => $customer_key,
				'email'           => $email,
				'phone'           => $phone,
				'authType'        => $auth_type,
				'transactionName' => MoConstants::AREA_OF_INTEREST,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			$verify_type  = null === $email ? VerificationType::PHONE : VerificationType::EMAIL;
			MoUtility::mo_update_sms_email_transations( $response, $verify_type );
			return $response;
		}

		/** Validate_otp_token Function
		 *
		 * @param string $transaction_id transaction id.
		 * @param string $otp_token otp token.
		 */
		public static function validate_otp_token( $transaction_id, $otp_token ) {
			$url          = MoConstants::HOSTNAME . '/moas/api/auth/validate';
			$customer_key = ! MoUtility::is_blank( get_mo_option( 'admin_customer_key' ) )
						? get_mo_option( 'admin_customer_key' ) : MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = ! MoUtility::is_blank( get_mo_option( 'admin_api_key' ) )
						? get_mo_option( 'admin_api_key' ) : MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'txId'  => $transaction_id,
				'token' => $otp_token,
			);
			$json         = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/** MoInternal Function
		 *
		 * @param string $q_email email address.
		 * @param string $q_phone phone number of user.
		 * @param string $query query of user.
		 * @param string $form_link form link of user.
		 * @param string $query_type type of query.
		 */
		public static function submit_contact_us( $q_email, $q_phone, $query, $form_link, $query_type = null ) {
			$current_user = wp_get_current_user();
			$mo_user      = get_mo_option( 'admin_email' );
			$url          = MoConstants::HOSTNAME . '/moas/rest/customer/contact-us';
			$query        = '[' . MoConstants::AREA_OF_INTEREST . ' (' . MoConstants::PLUGIN_TYPE . ') ](' . $mo_user . '): ' . $query_type . ' - ' . $query . ' - Form link :' . $form_link;
			$customer_key = ! MoUtility::is_blank( get_mo_option( 'admin_customer_key' ) )
							? get_mo_option( 'admin_customer_key' ) : MoConstants::DEFAULT_CUSTOMER_KEY;
			$api_key      = ! MoUtility::is_blank( get_mo_option( 'admin_api_key' ) )
							? get_mo_option( 'admin_api_key' ) : MoConstants::DEFAULT_API_KEY;
			$fields       = array(
				'firstName' => $current_user->user_firstname,
				'lastName'  => $current_user->user_lastname,
				'company'   => isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : null,
				'email'     => $q_email,
				'ccEmail'   => MoConstants::FEEDBACK_EMAIL,
				'phone'     => $q_phone,
				'query'     => $query,
			);
			$field_string = wp_json_encode( $fields );
			$auth_header  = self::create_auth_header( $customer_key, $api_key );
			$response     = self::call_api( $url, $field_string, $auth_header );
			return true;
		}

		/** MoInternal Function
		 *
		 * @param string $email email address.
		 */
		public static function forgot_password( $email ) {
			$url          = MoConstants::HOSTNAME . '/moas/rest/customer/password-reset';
			$customer_key = get_mo_option( 'admin_customer_key' );
			$api_key      = get_mo_option( 'admin_api_key' );

			$fields = array(
				'email' => $email,
			);

			$json        = wp_json_encode( $fields );
			$auth_header = self::create_auth_header( $customer_key, $api_key );
			$response    = self::call_api( $url, $json, $auth_header );
			return $response;
		}


		/** MoInternal Function
		 *
		 * @param string $customer_key internal use.
		 * @param string $api_key api key of user.
		 * @param string $app_name plugin name of user.
		 * @param string $license_type .
		 */
		public static function check_customer_ln( $customer_key, $api_key, $app_name, $license_type = 'DEMO' ) {

			$url    = MoConstants::HOSTNAME . '/moas/rest/customer/license';
			$fields = array(
				'customerId'      => $customer_key,
				'applicationName' => $app_name,
				'licenseType'     => $license_type,
			);

			$json        = wp_json_encode( $fields );
			$auth_header = self::create_auth_header( $customer_key, $api_key );
			$response    = self::call_api( $url, $json, $auth_header );
			return $response;
		}

		/** MoInternal Function
		 *
		 * @param string $customer_key internal use.
		 * @param string $api_key api key of user.
		 */
		public static function create_auth_header( $customer_key, $api_key ) {
			$current_timestamp_in_millis = self::get_timestamp();
			if ( MoUtility::is_blank( $current_timestamp_in_millis ) ) {
				$current_timestamp_in_millis = round( microtime( true ) * 1000 );
				$current_timestamp_in_millis = number_format( $current_timestamp_in_millis, 0, '', '' );
			}
			$string_to_hash = $customer_key . $current_timestamp_in_millis . $api_key;
			$auth_header    = hash( 'sha512', $string_to_hash );

			$header = array(
				'Content-Type'  => 'application/json',
				'Customer-Key'  => $customer_key,
				'Timestamp'     => $current_timestamp_in_millis,
				'Authorization' => $auth_header,
			);
			return $header;
		}

		/** MoInternal Function
		 */
		public static function get_timestamp() {
			$url = MoConstants::HOSTNAME . '/moas/rest/mobile/get-timestamp';
			return self::call_api( $url, null, null );
		}


		/**
		 *  Uses WordPress HTTP API to make cURL calls to miniOrange server
		 *  <br/>Arguments that you can pass
		 * <ol>
		 *  <li>'timeout'     => 5,</li>
		 *  <li>'redirection' => 5,</li>
		 *  <li>'httpversion' => '1.0',</li>
		 *  <li>'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),</li>
		 *  <li>'blocking'    => true,</li>
		 *  <li>'headers'     => array(),</li>
		 *  <li>'cookies'     => array(),</li>
		 *  <li>'body'        => null,</li>
		 *  <li>'compress'    => false,</li>
		 *  <li>'decompress'  => true,</li>
		 *  <li>'sslverify'   => true,</li>
		 *  <li>'stream'      => false,</li>
		 *  <li>'filename'    => null</li>
		 * </ol>
		 *
		 * @param string $url URL to post to.
		 * @param string $json_string json encoded post data.
		 * @param array  $headers headers to be passed in the call.
		 * @param string $method GET or POST or PUT HTTP Method.
		 * @return string
		 */
		public static function call_api( $url, $json_string, $headers = array( 'Content-Type' => 'application/json' ), $method = 'POST' ) {
			$args     = array(
				'method'      => $method,
				'body'        => $json_string,
				'timeout'     => '10000',
				'redirection' => '10',
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $headers,
				'sslverify'   => MOV_SSL_VERIFY,
			);
			$response = wp_remote_post( $url, $args );
			if ( is_wp_error( $response ) ) {
				wp_die( wp_kses( "Something went wrong: <br/> {$response->get_error_message()}", array( 'br' => array() ) ) );
			}
			return wp_remote_retrieve_body( $response );
		}

		/**
		 * Send Notification Email to either customer or user
		 *
		 * @param NotificationSettings $settings settings object.
		 * @return string
		 */
		public static function send_notif( NotificationSettings $settings ) {
			$gateway = GatewayFunctions::instance();
			return $gateway->mo_send_notif( $settings );
		}
	}
}
