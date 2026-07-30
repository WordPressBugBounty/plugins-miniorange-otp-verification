<?php
/**
 * Gateway configuration abilities — SMS/Email gateway, WhatsApp, and OTP templates.
 *
 * Registers:
 *   mo-otp/get-gateway-config
 *   mo-otp/configure-gateway
 *   mo-otp/get-whatsapp-settings
 *   mo-otp/update-whatsapp-settings
 *   mo-otp/get-email-templates
 *   mo-otp/update-email-template
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\GatewayFunctions;

/**
 * Registers gateway configuration abilities.
 */
class MoGatewayConfigAbilities {

	/**
	 * Registers all gateway configuration abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_gateway_config();
		static::register_configure_gateway();
		static::register_get_whatsapp_settings();
		static::register_update_whatsapp_settings();
		static::register_get_email_templates();
		static::register_update_email_template();
	}

	/**
	 * Registers the 'mo-otp/get-gateway-config' ability.
	 *
	 * This ability reads the current SMS/Email gateway configuration —
	 * the gateway type (miniOrange or custom URL), the custom SMS URL
	 * if one is set, and whether the gateway is fully configured and ready
	 * to send OTPs.
	 *
	 * @return void
	 */
	public static function register_get_gateway_config() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-gateway-config',
			array(
				'label'               => 'Get Gateway Config',
				'description'         => 'Retrieve the current SMS/Email gateway configuration: whether the gateway is configured, the gateway type, customer account ID, and token status.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'configured'     => array( 'type' => 'boolean', 'description' => 'Whether the gateway is fully configured.' ),
						'gateway_type'   => array( 'type' => array( 'string', 'null' ), 'description' => 'Gateway type (e.g. MoGateway, custom_sms).' ),
						'customer_id'    => array( 'type' => array( 'string', 'null' ), 'description' => 'miniOrange customer ID (partial, for verification only).' ),
						'has_token'      => array( 'type' => 'boolean', 'description' => 'Whether a customer API token is stored.' ),
						'has_api_key'    => array( 'type' => 'boolean', 'description' => 'Whether a customer API key is stored.' ),
						'custom_sms_url' => array( 'type' => array( 'string', 'null' ), 'description' => 'Custom SMS gateway URL (if using a custom SMS gateway).' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$customer_id  = (string) get_mo_option( 'admin_customer_key' );
					$token        = (string) get_mo_option( 'customer_token' );
					$api_key      = (string) get_mo_option( 'customer_api_key' );
					$gateway_type = (string) get_mo_option( 'custome_gateway_type' );
					$custom_url   = (string) get_mo_option( 'custom_sms_gateway' );

					return array(
						'configured'     => (bool) GatewayFunctions::instance()->is_gateway_config(),
						'gateway_type'   => $gateway_type ? $gateway_type : null,
						'customer_id'    => $customer_id ? substr( $customer_id, 0, 4 ) . '****' : null,
						'has_token'      => ! empty( $token ),
						'has_api_key'    => ! empty( $api_key ),
						'custom_sms_url' => $custom_url ? $custom_url : null,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/configure-gateway' ability.
	 *
	 * This ability updates the SMS gateway settings — the gateway type
	 * (miniOrange or a custom HTTP URL) and the custom SMS URL if you
	 * are using your own SMS provider. The URL must contain the ##phone##
	 * placeholder so the plugin knows where to insert the phone number.
	 *
	 * @return void
	 */
	public static function register_configure_gateway() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/configure-gateway',
			array(
				'label'               => 'Configure Gateway',
				'description'         => 'Update the SMS/Email gateway type and custom SMS gateway URL.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'gateway_type'   => array(
							'type'        => 'string',
							'description' => 'Gateway type to use. Common values: MoGateway (miniOrange shared gateway), custom_sms (custom HTTP SMS gateway), smtp (WordPress SMTP for email).',
						),
						'custom_sms_url' => array(
							'type'        => 'string',
							'description' => 'URL for the custom SMS gateway (required when gateway_type is custom_sms). Must contain ##phone## and ##message## placeholders.',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return MoAbilitiesConstants::premium_required_response();
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/get-whatsapp-settings' ability.
	 *
	 * This ability reads the current WhatsApp OTP configuration — whether
	 * WhatsApp is enabled, the WhatsApp provider type, and the access
	 * token if WhatsApp Business API is in use.
	 *
	 * @return void
	 */
	public static function register_get_whatsapp_settings() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-whatsapp-settings',
			array(
				'label'               => 'Get WhatsApp Settings',
				'description'         => 'Retrieve the current WhatsApp OTP settings: enabled status, gateway type, and whether an access token is configured.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'enabled'          => array( 'type' => 'boolean', 'description' => 'Whether WhatsApp OTP is enabled.' ),
						'whatsapp_type'    => array( 'type' => array( 'string', 'null' ), 'description' => 'Gateway type: bussiness_whatsapp or other.' ),
						'has_access_token' => array( 'type' => 'boolean', 'description' => 'Whether a WhatsApp Business access token is stored.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return MoAbilitiesConstants::premium_required_response();
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/update-whatsapp-settings' ability.
	 *
	 * This ability updates the WhatsApp OTP configuration — enabling or
	 * disabling WhatsApp, setting the provider type, and storing the
	 * WhatsApp Business API access token. Only the fields you provide
	 * are updated; others stay unchanged.
	 *
	 * @return void
	 */
	public static function register_update_whatsapp_settings() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/update-whatsapp-settings',
			array(
				'label'               => 'Update WhatsApp Settings',
				'description'         => 'Enable or disable WhatsApp OTP and configure the WhatsApp gateway type and access token.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'enabled'       => array( 'type' => 'boolean', 'description' => 'Enable or disable WhatsApp OTP.' ),
						'whatsapp_type' => array(
							'type'        => 'string',
							'enum'        => array( 'bussiness_whatsapp', 'other' ),
							'description' => 'WhatsApp gateway type.',
						),
						'access_token'  => array( 'type' => 'string', 'description' => 'WhatsApp Business API access token (required for bussiness_whatsapp type).' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return MoAbilitiesConstants::premium_required_response();
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/get-email-templates' ability.
	 *
	 * This ability reads the current SMS template, email template, and
	 * email subject used when sending OTPs. Templates must contain the
	 * ##otp## placeholder so the plugin can insert the generated code.
	 *
	 * @return void
	 */
	public static function register_get_email_templates() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-email-templates',
			array(
				'label'               => 'Get OTP Templates',
				'description'         => 'Retrieve the current SMS OTP message template, Email OTP message template, and Email subject line.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'sms_template'   => array( 'type' => array( 'string', 'null' ), 'description' => 'SMS OTP message template. Use ##otp## as the OTP placeholder.' ),
						'email_template' => array( 'type' => array( 'string', 'null' ), 'description' => 'Email OTP message body template. Use ##otp## as the OTP placeholder.' ),
						'email_subject'  => array( 'type' => array( 'string', 'null' ), 'description' => 'Subject line for the OTP email.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return MoAbilitiesConstants::premium_required_response();
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/update-email-template' ability.
	 *
	 * This ability saves a custom SMS template, email body template, or
	 * email subject for OTP messages. Each template must include the
	 * ##otp## placeholder. Only the fields you provide are updated;
	 * others stay unchanged.
	 *
	 * @return void
	 */
	public static function register_update_email_template() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/update-email-template',
			array(
				'label'               => 'Update OTP Templates',
				'description'         => 'Update the SMS OTP template, Email OTP template, or Email subject. Use ##otp## as the OTP placeholder in templates.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'sms_template'   => array( 'type' => 'string', 'description' => 'New SMS OTP message template. Must contain ##otp##.' ),
						'email_template' => array( 'type' => 'string', 'description' => 'New Email OTP body template. Must contain ##otp##.' ),
						'email_subject'  => array( 'type' => 'string', 'description' => 'New subject line for the OTP email.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return MoAbilitiesConstants::premium_required_response();
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}
}
