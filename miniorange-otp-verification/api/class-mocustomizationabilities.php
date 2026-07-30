<?php
/**
 * Customization abilities — popup templates and OTP message overrides.
 *
 * Registers:
 *   mo-otp/get-popup-templates
 *   mo-otp/update-popup-template
 *   mo-otp/get-custom-messages
 *   mo-otp/update-custom-messages
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers customization abilities for popup templates and OTP messages.
 */
class MoCustomizationAbilities {

	const POPUP_KEYS = array( 'Default', 'Error', 'UserChoice', 'External' );

	const MESSAGE_KEYS = array(
		'OTP_SENT_PHONE',
		'OTP_SENT_EMAIL',
		'ERROR_OTP_PHONE',
		'ERROR_OTP_EMAIL',
		'INVALID_OTP',
		'ERROR_PHONE_BLOCKED',
		'ERROR_EMAIL_BLOCKED',
		'ERROR_PHONE_FORMAT',
		'ERROR_EMAIL_FORMAT',
		'ENTER_PHONE',
		'ENTER_EMAIL',
		'ENTER_PHONE_CODE',
		'ENTER_EMAIL_CODE',
		'PLEASE_VALIDATE',
		'REQUIRED_OTP',
		'PHONE_EXISTS',
		'EMAIL_EXISTS',
	);

	/**
	 * Registers all customization abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_popup_templates();
		static::register_update_popup_template();
		static::register_get_custom_messages();
		static::register_update_custom_messages();
	}

	/**
	 * Registers the 'mo-otp/get-popup-templates' ability.
	 *
	 * This ability reads the stored HTML for all four OTP popup templates
	 * (Default, Error, UserChoice, External) and returns them. If a template
	 * has not been customized yet, its value is returned as null, meaning
	 * the plugin's built-in default is currently active.
	 *
	 * @return void
	 */
	public static function register_get_popup_templates() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-popup-templates',
			array(
				'label'               => 'Get Popup Templates',
				'description'         => 'Retrieve the HTML for all OTP popup templates (Default, Error, UserChoice, External). Returns the stored override HTML or null if the default template is in use.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'templates' => array(
							'type'        => 'object',
							'description' => 'Map of template key to HTML override (null = default is active).',
						),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$stored    = maybe_unserialize( get_mo_option( 'custom_popups' ) );
					$templates = array();
					foreach ( MoCustomizationAbilities::POPUP_KEYS as $key ) {
						$templates[ $key ] = ( is_array( $stored ) && isset( $stored[ $key ] ) ) ? $stored[ $key ] : null;
					}
					return array( 'templates' => $templates );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/update-popup-template' ability.
	 *
	 * This ability saves a custom HTML override for one of the four popup
	 * templates. Passing null as the html value removes the custom override
	 * and restores the plugin's built-in template. Script tags are stripped
	 * from the HTML before saving to prevent stored XSS attacks.
	 *
	 * @return void
	 */
	public static function register_update_popup_template() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/update-popup-template',
			array(
				'label'               => 'Update Popup Template',
				'description'         => 'Update the HTML for a specific OTP popup template. Pass html as null to reset that template back to the plugin default.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'template_key' ),
					'additionalProperties' => false,
					'properties'           => array(
						'template_key' => array(
							'type'        => 'string',
							'enum'        => array( 'Default', 'Error', 'UserChoice', 'External' ),
							'description' => 'The popup template to update.',
						),
						'html'         => array(
							'type'        => array( 'string', 'null' ),
							'description' => 'New HTML for the template. Pass null to reset to the plugin default.',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array( 'type' => 'boolean' ),
						'message'      => array( 'type' => 'string' ),
						'template_key' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$key = sanitize_text_field( $input['template_key'] ?? '' );
					if ( ! in_array( $key, MoCustomizationAbilities::POPUP_KEYS, true ) ) {
						return array(
							'success' => false,
							'message' => 'Invalid template_key. Must be one of: ' . implode( ', ', MoCustomizationAbilities::POPUP_KEYS ) . '.',
						);
					}

					$stored = maybe_unserialize( get_mo_option( 'custom_popups' ) );
					if ( ! is_array( $stored ) ) {
						$stored = array();
					}

					$html = $input['html'] ?? null;
					if ( null === $html ) {
						unset( $stored[ $key ] );
						$msg = $key . ' template reset to plugin default.';
					} else {
						// Prevents stored XSS via custom popup HTML.
						$stored[ $key ] = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', (string) $html );
						$msg            = $key . ' template updated successfully.';
					}

					update_mo_option( 'custom_popups', $stored );
					return array( 'success' => true, 'message' => $msg, 'template_key' => $key );
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
	 * Registers the 'mo-otp/get-custom-messages' ability.
	 *
	 * This ability reads all OTP message overrides (for example "OTP sent
	 * to your phone" or "Invalid OTP entered") and returns them. Any key
	 * that has not been overridden is returned as null, meaning the plugin's
	 * default text is currently shown to users.
	 *
	 * @return void
	 */
	public static function register_get_custom_messages() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-custom-messages',
			array(
				'label'               => 'Get Custom Messages',
				'description'         => 'Retrieve all custom OTP message overrides. Returns the overridden text for each key, or null if the plugin default is active.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'messages' => array(
							'type'        => 'object',
							'description' => 'Map of message key to current override text (null = plugin default in use).',
						),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$messages = array();
					foreach ( MoCustomizationAbilities::MESSAGE_KEYS as $key ) {
						$override         = get_mo_option( sanitize_key( $key ), 'mo_otp_' );
						$messages[ $key ] = $override ? (string) $override : null;
					}
					return array( 'messages' => $messages );
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
	 * Registers the 'mo-otp/update-custom-messages' ability.
	 *
	 * This ability updates one or more OTP message strings that are shown
	 * to the end user during the verification flow (for example, success
	 * messages, error messages, and field placeholders). Passing null for
	 * a key resets that message back to the plugin default.
	 *
	 * @return void
	 */
	public static function register_update_custom_messages() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/update-custom-messages',
			array(
				'label'               => 'Update Custom Messages',
				'description'         => 'Update one or more OTP message overrides shown to users. Pass a key with null to reset it to the plugin default.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'messages' ),
					'additionalProperties' => false,
					'properties'           => array(
						'messages' => array(
							'type'                 => 'object',
							'description'          => 'Map of message_key => new_text (or null to reset). Valid keys: OTP_SENT_PHONE, OTP_SENT_EMAIL, ERROR_OTP_PHONE, ERROR_OTP_EMAIL, INVALID_OTP, ERROR_PHONE_BLOCKED, ERROR_EMAIL_BLOCKED, ERROR_PHONE_FORMAT, ERROR_EMAIL_FORMAT, ENTER_PHONE, ENTER_EMAIL, ENTER_PHONE_CODE, ENTER_EMAIL_CODE, PLEASE_VALIDATE, REQUIRED_OTP, PHONE_EXISTS, EMAIL_EXISTS.',
							'additionalProperties' => array( 'type' => array( 'string', 'null' ) ),
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
				'execute_callback'    => function ( $input ) {
					$incoming = $input['messages'] ?? array();
					if ( ! is_array( $incoming ) || empty( $incoming ) ) {
						return array( 'success' => false, 'message' => 'Provide a non-empty messages map.' );
					}

					$updated = array();
					foreach ( $incoming as $raw_key => $value ) {
						$key = strtoupper( sanitize_text_field( (string) $raw_key ) );
						if ( ! in_array( $key, MoCustomizationAbilities::MESSAGE_KEYS, true ) ) {
							continue;
						}
						if ( null === $value ) {
							delete_mo_option( sanitize_key( $key ), 'mo_otp_' );
							$updated[] = $key . ' → reset to default';
						} else {
							update_mo_option( sanitize_key( $key ), MoAbilitiesConstants::sanitize_textarea( (string) $value ), 'mo_otp_' );
							$updated[] = $key . ' → updated';
						}
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid message keys provided.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' message(s) updated.',
						'updated' => $updated,
					);
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
}
