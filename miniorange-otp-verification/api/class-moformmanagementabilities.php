<?php
/**
 * Form management abilities — form registry helpers and CRUD abilities.
 *
 * Registers:
 *   mo-otp/list-forms
 *   mo-otp/get-form-details
 *   mo-otp/update-form-settings
 *   mo-otp/enable-form
 *   mo-otp/disable-form
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\PremiumFeatureList;
use OTP\Helper\MoUtility;

/**
 * Registers form management abilities and provides the form registry.
 */
class MoFormManagementAbilities {

	/**
	 * Returns the premium form definition for a given form key, or null
	 * if the key does not belong to a premium (paid-plan) form.
	 *
	 * @param string $form_key The form key to look up (e.g. 'DIVI_FORM').
	 * @return array|null Array with 'name' and 'plan_name', or null.
	 */
	public static function get_premium_form( $form_key ) {
		$premium = PremiumFeatureList::instance()->get_premium_forms();
		return $premium[ $form_key ] ?? null;
	}

	/**
	 * Registers all form management abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_list_forms();
		static::register_get_form_details();
		static::register_update_form_settings();
		static::register_enable_form();
		static::register_disable_form();
	}

	/**
	 * Returns a static map of all supported form handlers and their option keys.
	 *
	 * Registry entry fields:
	 *   name               Human-readable form name.
	 *   enable_option      Option key that enables/disables the form.
	 *   otp_type_option    Option key that stores the OTP type tag.
	 *   type_phone_tag     Value stored when PHONE is selected.
	 *   type_email_tag     Value stored when EMAIL is selected.
	 *   type_both_tag      Value stored when BOTH is selected (null = unsupported).
	 *   phone_key_option   Option key for the phone field name (null = not applicable).
	 *   email_key_option   Option key for the email field name (null = not applicable).
	 *   button_text_option Option key for the Send-OTP button label (null = not applicable).
	 *   use_raw_option     true = use get_option/update_option; false = get_mo_option/update_mo_option.
	 *   required_plugin    Plugin file path that must be active (null = WordPress core).
	 *   extra_options      Map of input_key => [ option_key, type, description ] for form-specific settings.
	 */
	public static function get_form_registry() {
		return array(
			'WP_DEFAULT_LOGIN'          => array(
				'name'               => 'WordPress / WooCommerce / UM Login Form',
				'enable_option'      => 'wp_login_enable',
				'otp_type_option'    => 'wp_login_enable_type',
				'type_phone_tag'     => 'mo_wp_login_phone_enable',
				'type_email_tag'     => 'mo_wp_login_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'wp_login_with_otp_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => null,
				'extra_options'      => array(),
			),
			'WP_DEFAULT'                => array(
				'name'               => 'WordPress Default / TML Registration Form',
				'enable_option'      => 'wp_default_enable',
				'otp_type_option'    => 'wp_default_enable_type',
				'type_phone_tag'     => 'mo_wp_default_phone_enable',
				'type_email_tag'     => 'mo_wp_default_email_enable',
				'type_both_tag'      => 'mo_wp_default_both_enable',
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => null,
				'extra_options'      => array(
					'auto_activate'       => array(
						'option_key'  => 'wp_reg_auto_activate',
						'type'        => 'boolean',
						'description' => 'Automatically activate new accounts after OTP verification (skip email activation).',
					),
					'restrict_duplicates' => array(
						'option_key'  => 'wp_reg_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts from sharing the same phone number.',
					),
				),
			),
			'WC_REG_FORM'               => array(
				'name'               => 'WooCommerce Registration Form',
				'enable_option'      => 'wc_default_enable',
				'otp_type_option'    => 'wc_enable_type',
				'type_phone_tag'     => 'mo_wc_phone_enable',
				'type_email_tag'     => 'mo_wc_email_enable',
				'type_both_tag'      => 'mo_wc_both_enable',
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'wc_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'woocommerce/woocommerce.php',
				'extra_options'      => array(
					'restrict_duplicates'         => array(
						'option_key'  => 'wc_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
					'ajax_mode'                   => array(
						'option_key'  => 'wc_is_ajax_form',
						'type'        => 'boolean',
						'description' => 'Enable AJAX form mode (no page reload on submit).',
					),
					'redirect_after_registration' => array(
						'option_key'  => 'wcreg_redirect_after_registration',
						'type'        => 'boolean',
						'description' => 'Redirect user to a specific page after successful registration.',
					),
					'redirect_page_id'            => array(
						'option_key'  => 'wc_redirect',
						'type'        => 'integer',
						'description' => 'Page ID to redirect to after registration (used when redirect_after_registration is true).',
					),
				),
			),
			'WC_CHECKOUT_FORM'          => array(
				'name'               => 'WooCommerce Checkout Form',
				'enable_option'      => 'wc_checkout_enable',
				'otp_type_option'    => 'wc_checkout_type',
				'type_phone_tag'     => 'mo_wc_phone_enable',
				'type_email_tag'     => 'mo_wc_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'wc_checkout_button_link_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'woocommerce/woocommerce.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'wc_checkout_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
					'popup_mode'          => array(
						'option_key'  => 'wc_checkout_popup',
						'type'        => 'boolean',
						'description' => 'Show OTP form as a popup instead of inline.',
					),
					'guest_only'          => array(
						'option_key'  => 'wc_checkout_guest',
						'type'        => 'boolean',
						'description' => 'Only require OTP for guest (non-logged-in) users.',
					),
					'disable_auto_login'  => array(
						'option_key'  => 'wc_checkout_disable_auto_login',
						'type'        => 'boolean',
						'description' => 'Disable automatic login after OTP verification at checkout.',
					),
					'selective_payment'   => array(
						'option_key'  => 'wc_checkout_selective_payment',
						'type'        => 'boolean',
						'description' => 'Enable OTP verification only for selected payment methods.',
					),
					'show_button'         => array(
						'option_key'  => 'mo_customer_validation_wc_checkout_button',
						'type'        => 'boolean',
						'description' => 'Show the OTP verification button on the checkout page.',
					),
				),
			),
			'WC_BILLING_FORM'           => array(
				'name'               => 'WooCommerce Billing Address Form',
				'enable_option'      => 'wc_billing_enable',
				'otp_type_option'    => 'wc_billing_type_enabled',
				'type_phone_tag'     => 'mo_wcb_phone_enable',
				'type_email_tag'     => 'mo_wcb_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'wc_billing_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'woocommerce/woocommerce.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'wc_billing_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
				),
			),
			'WC_AC_FORM'                => array(
				'name'               => 'WooCommerce Account Details Form',
				'enable_option'      => 'wc_profile_enable',
				'otp_type_option'    => 'wc_profile_enable_type',
				'type_phone_tag'     => 'mo_wc_profile_phone_enable',
				'type_email_tag'     => 'mo_wc_profile_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => 'wc_profile_phone_key',
				'email_key_option'   => null,
				'button_text_option' => 'wc_profile_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'woocommerce/woocommerce.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'wc_profile_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
				),
			),
			'CF7_FORM'                  => array(
				'name'               => 'Contact Form 7',
				'enable_option'      => 'cf7_contact_enable',
				'otp_type_option'    => 'cf7_contact_type',
				'type_phone_tag'     => 'mo_cf7_contact_phone_enable',
				'type_email_tag'     => 'mo_cf7_contact_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => 'cf7_email_key',
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'contact-form-7/wp-contact-form-7.php',
				'extra_options'      => array(),
			),
			'GRAVITY_FORM'              => array(
				'name'               => 'Gravity Form',
				'enable_option'      => 'gf_contact_enable',
				'otp_type_option'    => 'gf_contact_type',
				'type_phone_tag'     => 'mo_gf_contact_phone_enable',
				'type_email_tag'     => 'mo_gf_contact_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'gf_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'gravityforms/gravityforms.php',
				'extra_options'      => array(
					'button_css'    => array(
						'option_key'  => 'gf_button_css',
						'type'        => 'string',
						'description' => 'Inline CSS for the Send OTP button.',
					),
					'show_dropdown' => array(
						'option_key'  => 'show_dropdown_on_form',
						'type'        => 'boolean',
						'description' => 'Show a country/phone dropdown on the form.',
					),
				),
			),
			'NINJA_FORM_AJAX'           => array(
				'name'               => 'Ninja Forms',
				'enable_option'      => 'nja_enable',
				'otp_type_option'    => 'ninja_form_enable_type',
				'type_phone_tag'     => 'mo_ninja_form_phone_enable',
				'type_email_tag'     => 'mo_ninja_form_email_enable',
				'type_both_tag'      => 'mo_ninja_form_both_enable',
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'nja_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'ninja-forms/ninja-forms.php',
				'extra_options'      => array(),
			),
			'FORMINATOR'                => array(
				'name'               => 'Forminator Forms',
				'enable_option'      => 'forminator_enable',
				'otp_type_option'    => 'forminator_enable_type',
				'type_phone_tag'     => 'mo_forminator_phone_enable',
				'type_email_tag'     => 'mo_forminator_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'forminator_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'forminator/forminator.php',
				'extra_options'      => array(),
			),
			'FLUENTFORM'                => array(
				'name'               => 'Fluent Form',
				'enable_option'      => 'fluentform_enable',
				'otp_type_option'    => 'fluentform_enable_type',
				'type_phone_tag'     => 'mo_fluentform_phone_enable',
				'type_email_tag'     => 'mo_fluentform_email_enable',
				'type_both_tag'      => 'mo_fluentform_both_enable',
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'fluentform/fluentform.php',
				'extra_options'      => array(),
			),
			'WPFORMS'                   => array(
				'name'               => 'WPForms',
				'enable_option'      => 'wpform_enable',
				'otp_type_option'    => 'wpform_enable_type',
				'type_phone_tag'     => 'mo_wpform_phone_enable',
				'type_email_tag'     => 'mo_wpform_email_enable',
				'type_both_tag'      => 'mo_wpform_both_enable',
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'wpforms-lite/wpforms.php',
				'extra_options'      => array(
					'send_otp_button_text' => array(
						'option_key'  => 'wpforms_sendotp_button_text',
						'type'        => 'string',
						'description' => 'Label on the Send OTP button.',
					),
					'verify_button_text'   => array(
						'option_key'  => 'wpforms_verify_button_text',
						'type'        => 'string',
						'description' => 'Label on the Verify OTP button.',
					),
					'enter_otp_field_text' => array(
						'option_key'  => 'wpforms_enterotp_field_text',
						'type'        => 'string',
						'description' => 'Placeholder text for the Enter OTP input field.',
					),
				),
			),
			'FORMIDABLE_FORM'           => array(
				'name'               => 'Formidable Forms',
				'enable_option'      => 'frm_form_enable',
				'otp_type_option'    => 'frm_form_enable_type',
				'type_phone_tag'     => 'mo_frm_form_phone_enable',
				'type_email_tag'     => 'mo_frm_form_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'frm_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'formidable/formidable.php',
				'extra_options'      => array(),
			),
			'EVEREST_CONTACT'           => array(
				'name'               => 'Everest Contact Form',
				'enable_option'      => 'everest_contact_enable',
				'otp_type_option'    => 'everest_contact_enable_type',
				'type_phone_tag'     => 'mo_everest_contact_phone_enable',
				'type_email_tag'     => 'mo_everest_contact_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => 'everest_contact_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'everest-forms/everest-forms.php',
				'extra_options'      => array(),
			),
			'BP_DEFAULT_FORM'           => array(
				'name'               => 'BuddyPress / BuddyBoss Registration Form',
				'enable_option'      => 'bbp_default_enable',
				'otp_type_option'    => 'bbp_enable_type',
				'type_phone_tag'     => 'mo_bbp_phone_enable',
				'type_email_tag'     => 'mo_bbp_email_enable',
				'type_both_tag'      => 'mo_bbp_both_enabled',
				'phone_key_option'   => 'bbp_phone_key',
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'buddypress/bp-loader.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'bbp_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
					'disable_activation'  => array(
						'option_key'  => 'bbp_disable_activation',
						'type'        => 'boolean',
						'description' => 'Disable BuddyPress activation email after successful OTP verification.',
					),
				),
			),
			'MEMBERPRESS'               => array(
				'name'               => 'MemberPress Registration Form',
				'enable_option'      => 'mrp_default_enable',
				'otp_type_option'    => 'mrp_enable_type',
				'type_phone_tag'     => 'mo_mrp_phone_enable',
				'type_email_tag'     => 'mo_mrp_email_enable',
				'type_both_tag'      => 'mo_mrp_both_enable',
				'phone_key_option'   => 'mrp_phone_key',
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'memberpress/memberpress.php',
				'extra_options'      => array(
					'anonymous_only' => array(
						'option_key'  => 'mpr_anon_only',
						'type'        => 'boolean',
						'description' => 'Require OTP only for anonymous (non-logged-in) users.',
					),
				),
			),
			'MEMBERPRESSSINGLECHECKOUT' => array(
				'name'               => 'MemberPress Single Checkout Form',
				'enable_option'      => 'mrp_single_default_enable',
				'otp_type_option'    => 'mrp_single_enable_type',
				'type_phone_tag'     => 'mo_mrp_single_phone_enable',
				'type_email_tag'     => 'mo_mrp_single_email_enable',
				'type_both_tag'      => 'mo_mrp_single_both_enable',
				'phone_key_option'   => 'mrp_single_phone_key',
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'memberpress/memberpress.php',
				'extra_options'      => array(
					'anonymous_only' => array(
						'option_key'  => 'mrp_single_anon_only',
						'type'        => 'boolean',
						'description' => 'Require OTP only for anonymous (non-logged-in) users.',
					),
				),
			),
			'ULTIMATE_FORM'             => array(
				'name'               => 'Ultimate Member Registration Form',
				'enable_option'      => 'um_default_enable',
				'otp_type_option'    => 'um_enable_type',
				'type_phone_tag'     => 'mo_um_phone_enable',
				'type_email_tag'     => 'mo_um_email_enable',
				'type_both_tag'      => 'mo_um_both_enable',
				'phone_key_option'   => 'um_phone_key',
				'email_key_option'   => null,
				'button_text_option' => 'um_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'ultimate-member/ultimate-member.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'um_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
					'ajax_mode'           => array(
						'option_key'  => 'um_is_ajax_form',
						'type'        => 'boolean',
						'description' => 'Enable AJAX form mode (no page reload on submit).',
					),
				),
			),
			'ULTIMATE_PROFILE_FORM'     => array(
				'name'               => 'Ultimate Member Profile/Account Form',
				'enable_option'      => 'um_profile_enable',
				'otp_type_option'    => 'um_profile_enable_type',
				'type_phone_tag'     => 'mo_um_profile_phone_enable',
				'type_email_tag'     => 'mo_um_profile_email_enable',
				'type_both_tag'      => 'mo_um_profile_both_enable',
				'phone_key_option'   => 'um_profile_phone_key',
				'email_key_option'   => null,
				'button_text_option' => 'um_profile_button_text',
				'use_raw_option'     => false,
				'required_plugin'    => 'ultimate-member/ultimate-member.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'um_profile_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
				),
			),
			'UM_PASS_RESET'             => array(
				'name'               => 'Ultimate Member Password Reset Form',
				'enable_option'      => 'mo_um_pr_pass_enable',
				'otp_type_option'    => 'mo_um_pr_enabled_type',
				'type_phone_tag'     => 'mo_um_phone_enable',
				'type_email_tag'     => 'mo_um_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => 'mo_um_pr_passphone_key',
				'email_key_option'   => null,
				'button_text_option' => 'mo_um_pr_pass_button_text',
				'use_raw_option'     => true,
				'required_plugin'    => 'ultimate-member/ultimate-member.php',
				'extra_options'      => array(
					'phone_only_reset' => array(
						'option_key'  => 'mo_um_pr_only_phone_reset',
						'type'        => 'boolean',
						'description' => 'Allow password reset only via phone OTP (disables email-based reset).',
					),
				),
			),
			'PM_PRO_FORM'               => array(
				'name'               => 'Paid Membership Pro Registration Form',
				'enable_option'      => 'pmpro_enable',
				'otp_type_option'    => 'pmpro_otp_type',
				'type_phone_tag'     => 'pmpro_phone_enable',
				'type_email_tag'     => 'pmpro_email_enable',
				'type_both_tag'      => null,
				'phone_key_option'   => null,
				'email_key_option'   => null,
				'button_text_option' => null,
				'use_raw_option'     => false,
				'required_plugin'    => 'paid-memberships-pro/paid-memberships-pro.php',
				'extra_options'      => array(
					'restrict_duplicates' => array(
						'option_key'  => 'pmpro_restrict_duplicates',
						'type'        => 'boolean',
						'description' => 'Prevent multiple accounts sharing the same phone number.',
					),
				),
			),
		);
	}

	/**
	 * Reads one option value for a form using the correct storage method.
	 *
	 * Some forms store their options with get_option() (raw WordPress),
	 * others use get_mo_option() (plugin-prefixed). The config's
	 * 'use_raw_option' flag decides which one to call.
	 *
	 * @param array  $config     The form's registry entry.
	 * @param string $option_key The registry field name whose option to read (e.g. 'enable_option').
	 * @return mixed The stored option value, or null if the key does not exist in the config.
	 */
	public static function get_form_option_value( array $config, $option_key ) {
		$opt = $config[ $option_key ] ?? null;
		if ( null === $opt ) {
			return null;
		}
		return ! empty( $config['use_raw_option'] ) ? get_option( $opt ) : get_mo_option( $opt );
	}

	/**
	 * Saves one option value for a form using the correct storage method.
	 *
	 * Mirrors get_form_option_value() but writes the value. Uses
	 * update_option() or update_mo_option() based on 'use_raw_option'.
	 *
	 * @param array  $config     The form's registry entry.
	 * @param string $option_key The registry field name whose option to write (e.g. 'enable_option').
	 * @param mixed  $value      The value to store.
	 * @return void
	 */
	public static function update_form_option_value( array $config, $option_key, $value ) {
		$opt = $config[ $option_key ] ?? null;
		if ( null === $opt ) {
			return;
		}
		if ( ! empty( $config['use_raw_option'] ) ) {
			update_option( $opt, $value );
		} else {
			update_mo_option( $opt, $value );
		}
	}

	/**
	 * Reads one extra (form-specific) option and casts it to the correct type.
	 *
	 * Extra options are the form-specific settings stored in the registry's
	 * 'extra_options' map. This method reads the raw stored value and casts
	 * it to boolean, integer, or string based on the definition's 'type' field.
	 *
	 * @param array $config  The form's registry entry.
	 * @param array $opt_def One entry from the form's 'extra_options' map.
	 * @return mixed The cast value, or null if nothing is stored yet.
	 */
	public static function read_extra_option( array $config, array $opt_def ) {
		$key = $opt_def['option_key'];
		$raw = ! empty( $config['use_raw_option'] ) ? get_option( $key ) : get_mo_option( $key );
		if ( 'boolean' === $opt_def['type'] ) {
			return (bool) $raw;
		}
		if ( 'integer' === $opt_def['type'] ) {
			return ( '' !== $raw && null !== $raw && false !== $raw ) ? absint( $raw ) : null;
		}
		return ( '' !== $raw && null !== $raw && false !== $raw ) ? (string) $raw : null;
	}

	/**
	 * Saves one extra (form-specific) option, sanitizing it first.
	 *
	 * Sanitizes the value based on the definition's 'type' field before
	 * storing it. Booleans are stored as '1' or '', integers use absint(),
	 * and strings go through sanitize_text_field().
	 *
	 * @param array $config  The form's registry entry.
	 * @param array $opt_def One entry from the form's 'extra_options' map.
	 * @param mixed $value   The raw input value to sanitize and store.
	 * @return void
	 */
	public static function write_extra_option( array $config, array $opt_def, $value ) {
		$key = $opt_def['option_key'];
		if ( 'boolean' === $opt_def['type'] ) {
			$stored = $value ? '1' : '';
		} elseif ( 'integer' === $opt_def['type'] ) {
			$stored = absint( $value );
		} else {
			$stored = sanitize_text_field( (string) $value );
		}
		if ( ! empty( $config['use_raw_option'] ) ) {
			update_option( $key, $stored );
		} else {
			update_mo_option( $key, $stored );
		}
	}

	/**
	 * Converts a raw stored OTP type tag to a human-readable label.
	 *
	 * Each form stores its active OTP channel as an internal tag string
	 * (e.g. 'mo_wp_default_phone_enable'). This method maps that tag back
	 * to 'PHONE', 'EMAIL', or 'BOTH' for use in API responses.
	 *
	 * @param string $raw    The raw stored tag value from the database.
	 * @param array  $config The form's registry entry containing the tag constants.
	 * @return string|null 'PHONE', 'EMAIL', 'BOTH', or null if the tag is unrecognized.
	 */
	public static function resolve_otp_type_label( $raw, array $config ) {
		if ( empty( $raw ) ) {
			return null;
		}
		if ( $raw === $config['type_phone_tag'] ) {
			return 'PHONE';
		}
		if ( $raw === $config['type_email_tag'] ) {
			return 'EMAIL';
		}
		if ( ! empty( $config['type_both_tag'] ) && $raw === $config['type_both_tag'] ) {
			return 'BOTH';
		}
		return null;
	}

	/**
	 * Builds a short status summary for one form integration.
	 *
	 * Reads the form's current enabled state, OTP type, and required field
	 * values, then returns a summary array with a status of 'disabled',
	 * 'configured', or 'incomplete' and a list of any missing required fields.
	 *
	 * @param string $form_key The registry key for the form (e.g. 'WC_REG_FORM').
	 * @param array  $config   The form's registry entry.
	 * @return array Summary with keys: form_key, name, enabled, otp_type, status, missing_fields.
	 */
	public static function build_form_summary( $form_key, array $config ) {
		$enabled      = (bool) static::get_form_option_value( $config, 'enable_option' );
		$raw_otp_type = (string) static::get_form_option_value( $config, 'otp_type_option' );
		$otp_type     = static::resolve_otp_type_label( $raw_otp_type, $config );

		$missing = array();
		if ( $enabled ) {
			if ( null === $otp_type ) {
				$missing[] = 'otp_type';
			}
			if (
				! empty( $config['phone_key_option'] ) &&
				in_array( $otp_type, array( 'PHONE', 'BOTH' ), true ) &&
				empty( static::get_form_option_value( $config, 'phone_key_option' ) )
			) {
				$missing[] = 'phone_key';
			}
			if (
				! empty( $config['email_key_option'] ) &&
				in_array( $otp_type, array( 'EMAIL', 'BOTH' ), true ) &&
				empty( static::get_form_option_value( $config, 'email_key_option' ) )
			) {
				$missing[] = 'email_key';
			}
		}

		$status = 'disabled';
		if ( $enabled ) {
			$status = empty( $missing ) ? 'configured' : 'incomplete';
		}

		return array(
			'form_key'       => $form_key,
			'name'           => $config['name'],
			'enabled'        => $enabled,
			'otp_type'       => $otp_type,
			'status'         => $status,
			'missing_fields' => $missing,
		);
	}

	/**
	 * Registers the 'mo-otp/list-forms' ability.
	 *
	 * This ability returns all supported form integrations in one call —
	 * each with its name, enabled state, OTP type, and whether it is fully
	 * configured or has missing required fields. Use it as a starting point
	 * before calling get-form-details or update-form-settings.
	 *
	 * @return void
	 */
	public static function register_list_forms() {
		wp_register_ability(
			'mo-otp/list-forms',
			array(
				'label'               => 'List All Forms',
				'description'         => 'List all supported form integrations with enabled status, OTP type, and whether each is fully configured.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'total'      => array( 'type' => 'integer' ),
						'enabled'    => array( 'type' => 'integer' ),
						'configured' => array( 'type' => 'integer' ),
						'incomplete' => array( 'type' => 'integer' ),
						'forms'      => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$registry = MoFormManagementAbilities::get_form_registry();
					$forms    = array();
					foreach ( $registry as $form_key => $config ) {
						$forms[] = MoFormManagementAbilities::build_form_summary( $form_key, $config );
					}
					$enabled_count    = count( array_filter( $forms, function ( $f ) { return $f['enabled']; } ) );
					$configured_count = count( array_filter( $forms, function ( $f ) { return 'configured' === $f['status']; } ) );
					$incomplete_count = count( array_filter( $forms, function ( $f ) { return 'incomplete' === $f['status']; } ) );
					return array(
						'total'      => count( $forms ),
						'enabled'    => $enabled_count,
						'configured' => $configured_count,
						'incomplete' => $incomplete_count,
						'forms'      => $forms,
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
	 * Registers the 'mo-otp/get-form-details' ability.
	 *
	 * This ability returns the full configuration for one form — all
	 * current setting values, which OTP types it supports, which fields
	 * are editable, and which required fields are still missing. Use it
	 * before calling update-form-settings to see what can be changed.
	 *
	 * @return void
	 */
	public static function register_get_form_details() {
		wp_register_ability(
			'mo-otp/get-form-details',
			array(
				'label'               => 'Get Form Details',
				'description'         => 'Get the full configuration for one form: all current settings, editable fields, and which required fields are missing.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'form_key' ),
					'properties'           => array(
						'form_key' => array(
							'type'        => 'string',
							'description' => 'The form key (e.g. WP_DEFAULT). Use list-forms to get all keys.',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'form_key'            => array( 'type' => 'string' ),
						'name'                => array( 'type' => 'string' ),
						'enabled'             => array( 'type' => 'boolean' ),
						'otp_type'            => array( 'type' => array( 'string', 'null' ) ),
						'phone_key'           => array( 'type' => array( 'string', 'null' ) ),
						'email_key'           => array( 'type' => array( 'string', 'null' ) ),
						'button_text'         => array( 'type' => array( 'string', 'null' ) ),
						'settings'            => array( 'type' => 'object', 'description' => 'All form-specific settings keyed by their input field name.' ),
						'status'              => array( 'type' => 'string' ),
						'missing_fields'      => array( 'type' => 'array' ),
						'editable_fields'     => array( 'type' => 'array' ),
						'supported_otp_types' => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$form_key = sanitize_text_field( $input['form_key'] ?? '' );
					$registry = MoFormManagementAbilities::get_form_registry();
					if ( ! isset( $registry[ $form_key ] ) ) {
						$premium = MoFormManagementAbilities::get_premium_form( $form_key );
						if ( $premium ) {
							return array(
								'success'   => false,
								'message'   => $premium['name'] . ' is a premium form and requires the ' . $premium['plan_name'] . '. Please upgrade your plan to use this form.',
								'premium'   => true,
								'plan_name' => $premium['plan_name'],
							);
						}
						return array(
							'success' => false,
							'message' => 'Unknown form_key: ' . $form_key . '. Call list-forms to see all valid keys.',
						);
					}
					$config  = $registry[ $form_key ];
					$summary = MoFormManagementAbilities::build_form_summary( $form_key, $config );

					$phone_key   = ! empty( $config['phone_key_option'] ) ? (string) MoFormManagementAbilities::get_form_option_value( $config, 'phone_key_option' ) : null;
					$email_key   = ! empty( $config['email_key_option'] ) ? (string) MoFormManagementAbilities::get_form_option_value( $config, 'email_key_option' ) : null;
					$button_text = ! empty( $config['button_text_option'] ) ? (string) MoFormManagementAbilities::get_form_option_value( $config, 'button_text_option' ) : null;

					$supported_types = array( 'PHONE', 'EMAIL' );
					if ( ! empty( $config['type_both_tag'] ) ) {
						$supported_types[] = 'BOTH';
					}

					$settings = array();
					foreach ( $config['extra_options'] as $input_key => $opt_def ) {
						$settings[ $input_key ] = MoFormManagementAbilities::read_extra_option( $config, $opt_def );
					}

					$editable_fields = array(
						array(
							'field'         => 'enabled',
							'type'          => 'boolean',
							'label'         => 'Enable OTP Verification',
							'current_value' => $summary['enabled'] ? 'true' : 'false',
							'description'   => 'Whether OTP verification is active for this form.',
							'required'      => false,
						),
						array(
							'field'         => 'otp_type',
							'type'          => 'string',
							'label'         => 'OTP Verification Type',
							'current_value' => $summary['otp_type'] ?? 'not set',
							'description'   => 'Verification channel — one of: ' . implode( ', ', $supported_types ) . '.',
							'required'      => true,
						),
					);

					if ( ! empty( $config['phone_key_option'] ) ) {
						$editable_fields[] = array(
							'field'         => 'phone_key',
							'type'          => 'string',
							'label'         => 'Phone Field Name',
							'current_value' => ! empty( $phone_key ) ? $phone_key : 'not set',
							'description'   => 'HTML field name / ID of the phone input in this form.',
							'required'      => in_array( $summary['otp_type'], array( 'PHONE', 'BOTH' ), true ),
						);
					}

					if ( ! empty( $config['email_key_option'] ) ) {
						$editable_fields[] = array(
							'field'         => 'email_key',
							'type'          => 'string',
							'label'         => 'Email Field Name',
							'current_value' => ! empty( $email_key ) ? $email_key : 'not set',
							'description'   => 'HTML field name / ID of the email input in this form.',
							'required'      => in_array( $summary['otp_type'], array( 'EMAIL', 'BOTH' ), true ),
						);
					}

					if ( ! empty( $config['button_text_option'] ) ) {
						$editable_fields[] = array(
							'field'         => 'button_text',
							'type'          => 'string',
							'label'         => 'Send OTP Button Text',
							'current_value' => ! empty( $button_text ) ? $button_text : '(default)',
							'description'   => 'Label shown on the button that sends the OTP.',
							'required'      => false,
						);
					}

					foreach ( $config['extra_options'] as $input_key => $opt_def ) {
						$cur = $settings[ $input_key ];
						if ( 'boolean' === $opt_def['type'] ) {
							$cur_str = $cur ? 'true' : 'false';
						} elseif ( null === $cur ) {
							$cur_str = 'not set';
						} else {
							$cur_str = (string) $cur;
						}
						$editable_fields[] = array(
							'field'         => $input_key,
							'type'          => $opt_def['type'],
							'label'         => ucwords( str_replace( '_', ' ', $input_key ) ),
							'current_value' => $cur_str,
							'description'   => $opt_def['description'],
							'required'      => false,
						);
					}

					return array(
						'form_key'            => $form_key,
						'name'                => $config['name'],
						'enabled'             => $summary['enabled'],
						'otp_type'            => $summary['otp_type'],
						'phone_key'           => $phone_key,
						'email_key'           => $email_key,
						'button_text'         => ! empty( $button_text ) ? $button_text : null,
						'settings'            => $settings,
						'status'              => $summary['status'],
						'missing_fields'      => $summary['missing_fields'],
						'editable_fields'     => $editable_fields,
						'supported_otp_types' => $supported_types,
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
	 * Registers the 'mo-otp/update-form-settings' ability.
	 *
	 * This ability updates one or more settings for a specific form
	 * integration — such as enabling OTP, setting the verification channel,
	 * providing field names, or toggling form-specific options. Only the
	 * fields you pass are changed; everything else stays the same.
	 *
	 * @return void
	 */
	public static function register_update_form_settings() {
		wp_register_ability(
			'mo-otp/update-form-settings',
			array(
				'label'               => 'Update Form Settings',
				'description'         => 'Update one or more settings for a form. Use get-form-details to see which fields each form supports.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'form_key' ),
					'additionalProperties' => false,
					'properties'           => array(
						'form_key'                    => array( 'type' => 'string', 'description' => 'The form key to update. Use list-forms to get all valid keys.' ),
						'enabled'                     => array( 'type' => 'boolean', 'description' => 'true to enable OTP on this form, false to disable.' ),
						'otp_type'                    => array( 'type' => 'string', 'enum' => array( 'PHONE', 'EMAIL', 'BOTH' ), 'description' => 'Verification channel. Not all forms support BOTH.' ),
						'phone_key'                   => array( 'type' => 'string', 'description' => 'HTML field name / ID of the phone input.' ),
						'email_key'                   => array( 'type' => 'string', 'description' => 'HTML field name / ID of the email input.' ),
						'button_text'                 => array( 'type' => 'string', 'description' => 'Label on the Send OTP button.' ),
						'auto_activate'               => array( 'type' => 'boolean', 'description' => '[WP_DEFAULT] Auto-activate accounts after OTP verification.' ),
						'restrict_duplicates'         => array( 'type' => 'boolean', 'description' => '[Many forms] Prevent multiple accounts sharing the same phone number.' ),
						'ajax_mode'                   => array( 'type' => 'boolean', 'description' => '[WC_REG_FORM, ULTIMATE_FORM] Enable AJAX form mode.' ),
						'redirect_after_registration' => array( 'type' => 'boolean', 'description' => '[WC_REG_FORM] Redirect user after successful registration.' ),
						'redirect_page_id'            => array( 'type' => 'integer', 'description' => '[WC_REG_FORM] Page ID to redirect to after registration.' ),
						'popup_mode'                  => array( 'type' => 'boolean', 'description' => '[WC_CHECKOUT_FORM] Show OTP form as a popup instead of inline.' ),
						'guest_only'                  => array( 'type' => 'boolean', 'description' => '[WC_CHECKOUT_FORM] Only require OTP for guest users.' ),
						'disable_auto_login'          => array( 'type' => 'boolean', 'description' => '[WC_CHECKOUT_FORM] Disable automatic login after checkout OTP.' ),
						'selective_payment'           => array( 'type' => 'boolean', 'description' => '[WC_CHECKOUT_FORM] Enable OTP only for selected payment methods.' ),
						'show_button'                 => array( 'type' => 'boolean', 'description' => '[WC_CHECKOUT_FORM] Show the OTP verification button on checkout.' ),
						'button_css'                  => array( 'type' => 'string', 'description' => '[GRAVITY_FORM] Inline CSS for the Send OTP button.' ),
						'show_dropdown'               => array( 'type' => 'boolean', 'description' => '[GRAVITY_FORM] Show country/phone dropdown on the form.' ),
						'anonymous_only'              => array( 'type' => 'boolean', 'description' => '[MEMBERPRESS, MEMBERPRESSSINGLECHECKOUT] Require OTP only for non-logged-in users.' ),
						'disable_activation'          => array( 'type' => 'boolean', 'description' => '[BP_DEFAULT_FORM] Disable BuddyPress activation email after OTP.' ),
						'phone_only_reset'            => array( 'type' => 'boolean', 'description' => '[UM_PASS_RESET] Allow password reset only via phone OTP.' ),
						'send_otp_button_text'        => array( 'type' => 'string', 'description' => '[WPFORMS] Label on the Send OTP button.' ),
						'verify_button_text'          => array( 'type' => 'string', 'description' => '[WPFORMS] Label on the Verify OTP button.' ),
						'enter_otp_field_text'        => array( 'type' => 'string', 'description' => '[WPFORMS] Placeholder text for the Enter OTP field.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'form'    => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$form_key = sanitize_text_field( $input['form_key'] ?? '' );
					$registry = MoFormManagementAbilities::get_form_registry();
					if ( ! isset( $registry[ $form_key ] ) ) {
						$premium = MoFormManagementAbilities::get_premium_form( $form_key );
						if ( $premium ) {
							return array( 'success' => false, 'message' => $premium['name'] . ' is a premium form and requires the ' . $premium['plan_name'] . '. Please upgrade your plan to use this form.', 'premium' => true, 'plan_name' => $premium['plan_name'] );
						}
						return array( 'success' => false, 'message' => 'Unknown form_key: ' . $form_key . '. Call list-forms to see all valid keys.' );
					}
					$config  = $registry[ $form_key ];
					$updated = array();

					if ( isset( $input['enabled'] ) ) {
						if ( $input['enabled'] && ! empty( $config['required_plugin'] ) && ! MoUtility::is_plugin_installed( $config['required_plugin'] ) ) {
							return array( 'success' => false, 'message' => 'Please install the ' . $config['name'] . ' plugin.' );
						}
						MoFormManagementAbilities::update_form_option_value( $config, 'enable_option', $input['enabled'] ? '1' : '' );
						$updated[] = 'enabled → ' . ( $input['enabled'] ? 'true' : 'false' );
					}

					if ( isset( $input['otp_type'] ) ) {
						$req_type = strtoupper( sanitize_text_field( $input['otp_type'] ) );
						$tag      = null;
						if ( 'PHONE' === $req_type ) {
							$tag = $config['type_phone_tag'];
						} elseif ( 'EMAIL' === $req_type ) {
							$tag = $config['type_email_tag'];
						} elseif ( 'BOTH' === $req_type ) {
							if ( empty( $config['type_both_tag'] ) ) {
								return array( 'success' => false, 'message' => 'This form does not support BOTH verification type.' );
							}
							$tag = $config['type_both_tag'];
						}
						if ( $tag ) {
							MoFormManagementAbilities::update_form_option_value( $config, 'otp_type_option', $tag );
							$updated[] = 'otp_type → ' . $req_type;
						}
					}

					if ( isset( $input['phone_key'] ) && ! empty( $config['phone_key_option'] ) ) {
						MoFormManagementAbilities::update_form_option_value( $config, 'phone_key_option', sanitize_text_field( $input['phone_key'] ) );
						$updated[] = 'phone_key → ' . $input['phone_key'];
					}

					if ( isset( $input['email_key'] ) && ! empty( $config['email_key_option'] ) ) {
						MoFormManagementAbilities::update_form_option_value( $config, 'email_key_option', sanitize_text_field( $input['email_key'] ) );
						$updated[] = 'email_key → ' . $input['email_key'];
					}

					if ( isset( $input['button_text'] ) && ! empty( $config['button_text_option'] ) ) {
						MoFormManagementAbilities::update_form_option_value( $config, 'button_text_option', sanitize_text_field( $input['button_text'] ) );
						$updated[] = 'button_text → ' . $input['button_text'];
					}

					foreach ( $config['extra_options'] as $input_key => $opt_def ) {
						if ( ! isset( $input[ $input_key ] ) ) {
							continue;
						}
						MoFormManagementAbilities::write_extra_option( $config, $opt_def, $input[ $input_key ] );
						$updated[] = $input_key . ' → ' . ( 'boolean' === $opt_def['type'] ? ( $input[ $input_key ] ? 'true' : 'false' ) : $input[ $input_key ] );
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid fields provided.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' setting(s) updated successfully.',
						'updated' => $updated,
						'form'    => MoFormManagementAbilities::build_form_summary( $form_key, $registry[ $form_key ] ),
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

	/**
	 * Registers the 'mo-otp/enable-form' ability.
	 *
	 * This ability turns OTP verification on for a specific form. If the
	 * form requires a plugin that is not installed, the call fails with
	 * a clear error message telling the user what to install first.
	 *
	 * @return void
	 */
	public static function register_enable_form() {
		wp_register_ability(
			'mo-otp/enable-form',
			array(
				'label'               => 'Enable Form',
				'description'         => 'Enable OTP verification for a specific form integration.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'form_key' ),
					'properties'           => array(
						'form_key' => array(
							'type'        => 'string',
							'description' => 'The form key to enable. Use list-forms to see all keys.',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'form'    => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$form_key = sanitize_text_field( $input['form_key'] ?? '' );
					$registry = MoFormManagementAbilities::get_form_registry();
					if ( ! isset( $registry[ $form_key ] ) ) {
						$premium = MoFormManagementAbilities::get_premium_form( $form_key );
						if ( $premium ) {
							return array(
								'success'   => false,
								'message'   => $premium['name'] . ' is a premium form and requires the ' . $premium['plan_name'] . '. Please upgrade your plan to use this form.',
								'premium'   => true,
								'plan_name' => $premium['plan_name'],
							);
						}
						return array( 'success' => false, 'message' => 'Unknown form_key: ' . $form_key . '. Call list-forms to see all valid keys.' );
					}
					$config = $registry[ $form_key ];
					if ( ! empty( $config['required_plugin'] ) && ! MoUtility::is_plugin_installed( $config['required_plugin'] ) ) {
						return array( 'success' => false, 'message' => 'Please install the ' . $config['name'] . ' plugin.' );
					}
					MoFormManagementAbilities::update_form_option_value( $config, 'enable_option', '1' );
					return array(
						'success' => true,
						'message' => $config['name'] . ' has been enabled.',
						'form'    => MoFormManagementAbilities::build_form_summary( $form_key, $registry[ $form_key ] ),
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
	 * Registers the 'mo-otp/disable-form' ability.
	 *
	 * This ability turns OTP verification off for a specific form without
	 * deleting any of its other settings. The form can be re-enabled at
	 * any time using the enable-form ability.
	 *
	 * @return void
	 */
	public static function register_disable_form() {
		wp_register_ability(
			'mo-otp/disable-form',
			array(
				'label'               => 'Disable Form',
				'description'         => 'Disable OTP verification for a specific form integration.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'form_key' ),
					'properties'           => array(
						'form_key' => array(
							'type'        => 'string',
							'description' => 'The form key to disable. Use list-forms to see all keys.',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'form'    => array( 'type' => 'object' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$form_key = sanitize_text_field( $input['form_key'] ?? '' );
					$registry = MoFormManagementAbilities::get_form_registry();
					if ( ! isset( $registry[ $form_key ] ) ) {
						$premium = MoFormManagementAbilities::get_premium_form( $form_key );
						if ( $premium ) {
							return array(
								'success'   => false,
								'message'   => $premium['name'] . ' is a premium form and requires the ' . $premium['plan_name'] . '. Please upgrade your plan to use this form.',
								'premium'   => true,
								'plan_name' => $premium['plan_name'],
							);
						}
						return array( 'success' => false, 'message' => 'Unknown form_key: ' . $form_key . '. Call list-forms to see all valid keys.' );
					}
					$config = $registry[ $form_key ];
					MoFormManagementAbilities::update_form_option_value( $config, 'enable_option', '' );
					return array(
						'success' => true,
						'message' => $config['name'] . ' has been disabled.',
						'form'    => MoFormManagementAbilities::build_form_summary( $form_key, $registry[ $form_key ] ),
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
}
