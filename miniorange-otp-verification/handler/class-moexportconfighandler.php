<?php
/**
 * Handles Export of plugin settings.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

use OTP\Helper\MoMessages;
use OTP\Objects\BaseActionHandler;
use OTP\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoExportConfigHandler' ) ) {
	/**
	 * Handles export (JSON file download) of all plugin settings, with
	 * per-section grouping and sensitive-field filtering.
	 */
	class MoExportConfigHandler extends BaseActionHandler {

		use Instance;

		/** Prefix shared by all core plugin options. */
		const CORE_PREFIX = 'mo_customer_validation_';

		/**
		 * Option-name prefixes that may be exported.
		 *
		 * @var string[]
		 */
		private $allowed_prefixes = array(
			'mo_customer_validation_',
			'mo_sc_code_',
			'mo_otp_',
			'mo_wc_pr_',
			'mo_rc_sms_',
			'mo_um_pr_',
		);

		/**
		 * Notification options fetched by exact name (no shared prefix).
		 *
		 * @var string[]
		 */
		private $notification_keys = array(
			'mo_wc_sms_notification_settings',
			'mo_wc_sms_notification_settings_option',
			'mo_um_sms_notification_settings',
			'mo_um_sms_notification_settings_option',
		);

		/**
		 * The only user-configurable fields on a notification object.
		 * Internal display fields (tool_tip_header, tool_tip_body, page, title, etc.)
		 * are hardcoded by each notification class and must not be exported.
		 *
		 * @var string[]
		 */
		private $notification_user_fields = array(
			'is_enabled',
			'sms_body',
			'recipient',
			'template_name',
			'sms_tags',
		);

		/**
		 * Option names (or prefixes) always excluded from export.
		 * Exact key names are matched as-is.
		 * Entries ending with '_' are treated as prefix matches (dynamic keys with unpredictable suffixes).
		 *
		 * @var string[]
		 */
		private $excluded_keys = array(
			'mo_customer_validation_custom_popups',
			'mo_customer_validation_reporting_table_migration_completed',
			'mo_sc_code_allcountrywithcountrycode',
			'mo_customer_validation_check_ln',
			'mo_customer_validation_site_email_ckl',
			'mo_customer_validation_mo_hide_notice',
			'mo_customer_validation_mo_hide_sms_notice',
			'mo_customer_validation_mo_transaction_notice',
			'mo_customer_validation_masterotp_for_specific_user_phone',
			'mo_customer_validation_mo_otp_security_logs',
			'mo_customer_validation_mo_selected_country_modal_dismissed_ts',
			'mo_customer_validation_mo_transaction_logs_modal_dismissed_ts',
			'mo_customer_validation_mo_spam_preventer_modal_dismissed_ts',
			'mo_customer_validation_mo_report_logs_modal_dismissed_ts',
			'mo_otp_led',
			'mo_otp_ln_check_t',
			'mo_otp_license_expiry_date',
			'mo_customer_validation_tourTaken_',
			'mo_customer_validation_mo_osp_spam_data_',
		);

		/**
		 * Substrings that identify a sensitive option name.
		 * Use the most specific fragment possible — avoid short substrings
		 * that would match unrelated option names (e.g. 'sid' also matches 'inside').
		 *
		 * @var string[]
		 */
		private $sensitive_fragments = array(
			'api_key',
			'token',
			'secret',
			'password',
			'auth_key',
			'auth_token',
			'twilio_sid',
			'aws_key',
			'aws_secret',
			'basic_auth',
		);

		/**
		 * Short keys for the "otp_verification" top-level section.
		 *
		 * @var string[]
		 */
		private $otp_verification_keys = array(
			'admin_email',
			'admin_phone',
			'admin_customer_key',
			'admin_api_key',
			'customer_token',
			'customer_license_plan',
			'company_name',
			'first_name',
			'last_name',
			'registration_status',
			'verify_customer',
			'new_registration',
			'email_verification_lk',
			'plugin_activation_date',
			'transactionId',
			'phone_transactions_remaining',
			'email_transactions_remaining',
			'whatsapp_transactions_remaining',
		);

		/**
		 * Short-key lists per settings sub-section.
		 *
		 * @var array<string, string[]>
		 */
		private $settings_groups = array(

			'general_settings' => array(
				'default_country',
				'default_country_code',
				'show_dropdown_on_form',
				'blocked_domains',
				'blocked_phone_numbers',
				'show_remaining_trans',
				'enable_debug_logs',
				'is_mo_report_enabled',
				'default_user_role',
				'globally_banned_phone',
				'globally_banned_phone_message',
			),

			'otp_settings'     => array(
				'otp_length',
				'otp_validity',
				'otp_timer',
				'otp_timer_enable',
				'generate_alphanumeric_otp',
				'masterotp_validity',
				'masterotp_admin',
				'masterotp_user',
				'masterotp_admins',
				'masterotp_specific_user',
				'masterotp_specific_user_details',
				'autofill_otp_enabled',
			),

			'gateway_settings' => array(
				'custom_sms_gateway',
				'custome_gateway_type',
				'custome_gateway_method',
				'smtp_enable_type',
				'custom_gateway_post_body',
				'mo_basic_auth_gateway_details',
				'mo_basic_auth_body_method',
				'mo_basic_auth_gateway_post_body',
				'mo_basic_auth_gateway_post_headers',
				'mo_basic_auth_gateway_post_raw_body',
				'mo_custom_gateway_post_headers',
				'mo_custom_gateway_post_raw_body',
				'mo_twilio_gateway_post_body',
				'mo_twilio_sms_gateway_url',
				'twilio_sid',
				'twilio_token',
				'twilio_from_phone',
				'aws_app_id',
				'aws_key',
				'aws_secret',
				'aws_region',
				'aws_sender_id',
				'mo_whatsapp_enable',
				'mo_whatsapp_type',
				'mo_whatsapp_access_token',
				'mo_whatsapp_phone_number_id',
				'mo_whatsapp_template_name',
				'mo_whatsapp_template_language',
				'mo_whatsapp_otp_enable',
				'mo_whatsapp_notification_enable',
				'mo_whatsapp_email_id',
				'mo_whatsapp_password',
			),

			'message_settings' => array(
				'custom_sms_msg',
				'custom_email_msg',
				'custom_email_subject',
				'custom_email_from_name',
			),

			'otp_messages'     => array(
				'mo_otp_',
				array(
					'success_email_message',
					'success_phone_message',
					'error_email_message',
					'error_phone_message',
					'invalid_email_message',
					'invalid_phone_message',
					'invalid_message',
					'blocked_email_message',
					'blocked_phone_message',
				),
			),

			'custom_form'      => array(
				'mo_otp_',
				array(
					'cf_submit_id',
					'cf_field_id',
					'cf_enable_type',
					'cf_button_text',
				),
			),

			'popup_settings'   => array(
				'selected_popup',
			),
		);

		/**
		 * Addon definitions: addon_group_name → [ prefix, short_key[] ].
		 *
		 * @var array<string, array{0: string, 1: string[]}>
		 */
		private $addon_groups = array(

			'selected_country_code'      => array(
				'mo_sc_code_',
				array(
					'select_country_type',
					'selected_country_list',
					'block_selected_country_list',
				),
			),

			'woocommerce_password_reset' => array(
				'mo_wc_pr_',
				array(
					'pass_enable',
					'enabled_type',
					'passphone_key',
					'only_phone_reset',
					'pass_button_text',
				),
			),

			'rate_limit_sms'             => array(
				'mo_rc_sms_',
				array(
					'otp_control_enable',
					'otp_control_limit',
					'otp_control_block_time',
					'otp_timer',
					'otp_timer_enable',
				),
			),

			'otp_spam_protection'        => array(
				'mo_customer_validation_',
				array( 'mo_osp_settings' ),
			),

			'um_password_reset'          => array(
				'mo_um_pr_',
				array(
					'pass_enable',
					'enabled_type',
					'pass_button_text',
					'passphone_key',
					'only_phone_reset',
				),
			),
		);

		/**
		 * Maps a short-key prefix (core prefix already stripped) to a form-type
		 * label used inside the "forms" JSON section.
		 * More-specific prefixes MUST appear before less-specific ones.
		 *
		 * @var array<string, string>
		 */
		private $form_prefix_map = array(
			'wp_login_'          => 'wordpress_login',
			'wp_default_'        => 'wordpress_default_registration',
			'wp_reg_'            => 'wordpress_default_registration',
			'wp_member_reg_'     => 'wp_member',
			'wp_user_manager_'   => 'wp_user_manager',
			'wpcomment_'         => 'wordpress_comments',
			'wc_checkout_'       => 'woocommerce_checkout',
			'wc_billing_'        => 'woocommerce_billing',
			'wc_profile_'        => 'woocommerce_profile',
			'wc_social_'         => 'woocommerce_social_login',
			'wcreg_'             => 'woocommerce_registration',
			'wc_'                => 'woocommerce_registration',
			'wpforms_'           => 'wpforms',
			'wpform_'            => 'wpforms',
			'gf_'                => 'gravity_forms',
			'cf7_'               => 'contact_form_7',
			'forminator_'        => 'forminator',
			'fluentform_'        => 'fluent_forms',
			'everest_'           => 'everest_forms',
			'caldera_'           => 'caldera_forms',
			'ninja_form_'        => 'ninja_forms',
			'nja_'               => 'ninja_forms',
			'emember_'           => 'emember',
			'mrp_single_'        => 'memberpress_single_checkout',
			'mrp_'               => 'memberpress',
			'mpr_'               => 'memberpress',
			'pmpro_'             => 'paid_memberships_pro',
			'um_profile_'        => 'ultimate_member_profile',
			'um_'                => 'ultimate_member_registration',
			'upme_'              => 'user_profile_made_easy',
			'userpro_'           => 'userpro',
			'uultra_'            => 'user_ultra',
			'ultipro_'           => 'ultimate_pro_registration',
			'pb_'                => 'profile_builder',
			'tutor_lms_student_' => 'tutor_lms_student',
			'tutor_lms_'         => 'tutor_lms',
			'bbp_'               => 'buddypress',
			'frm_'               => 'formidable_forms',
			'formmaker_'         => 'form_maker',
			'formcraft_'         => 'formcraft_basic',
			'fcpremium_'         => 'formcraft_premium',
			'visual_form_'       => 'visual_form_builder',
			'dokan_'             => 'dokan',
			'dokanreg_'          => 'dokan',
			'houzez_'            => 'houzez_theme',
			'docdirect_'         => 'docdirect_theme',
			'reales_'            => 'reales_wp_theme',
			'classify_'          => 'classify_registration',
			'armember_'          => 'armember',
			'simplr_'            => 'simplr_registration',
			'pie_'               => 'pie_register',
			'crf_'               => 'registration_magic',
		);

		/** Registers the admin-post export action hook. */
		protected function __construct() {
			parent::__construct();
			$this->nonce = 'mo_export_config_nonce';
			add_action( 'admin_post_mo_customer_validation_export_settings', array( $this, 'handle_export' ) );
		}

		/**
		 * Returns true if the value should be omitted from the export.
		 * Only drops values that represent "never configured": null, false,
		 * empty string, and empty array. String '0' and integer 0 are kept
		 * because they represent an explicit "off/disabled" choice by the admin.
		 *
		 * @param mixed $value Option value.
		 * @return bool
		 */
		private function is_empty_value( $value ) {
			return null === $value
				|| false === $value
				|| '' === $value
				|| ( is_array( $value ) && empty( $value ) );
		}

		/**
		 * Returns true if any sensitive fragment appears in $option_name.
		 *
		 * @param string $option_name Full option name.
		 * @return bool
		 */
		private function is_sensitive_key( $option_name ) {
			foreach ( $this->sensitive_fragments as $fragment ) {
				if ( false !== strpos( $option_name, $fragment ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Returns the form-type label for a given short key, or null if not a form key.
		 *
		 * @param string $short_key Short key with core prefix already stripped.
		 * @return string|null
		 */
		private function get_form_type( $short_key ) {
			foreach ( $this->form_prefix_map as $prefix => $form_type ) {
				if ( 0 === strpos( $short_key, $prefix ) ) {
					return $form_type;
				}
			}
			return null;
		}

		/**
		 * Tries to add a key to a target array, applying empty and sensitive-field rules.
		 *
		 * @param string              $full_key          Full wp_options key.
		 * @param mixed               $value             Already-unserialized value.
		 * @param string              $json_key          Key to use in the JSON output.
		 * @param array<string,mixed> &$target           Array to write into.
		 * @param bool                $include_sensitive Whether to include sensitive keys.
		 * @return bool True if the entry was added.
		 */
		private function try_add( $full_key, $value, $json_key, array &$target, $include_sensitive ) {
			if ( $this->is_empty_value( $value ) ) {
				return false;
			}
			if ( ! $include_sensitive && $this->is_sensitive_key( $full_key ) ) {
				return false;
			}
			$target[ $json_key ] = $value;
			return true;
		}

		/**
		 * Queries all exportable options from the database as a flat
		 * [ full_option_name => unserialized_value ] map.
		 * Handles both single-site (wp_options) and multisite (wp_sitemeta).
		 *
		 * @global \wpdb $wpdb
		 * @return array<string,mixed>
		 */
		private function fetch_all_options() {
			global $wpdb;

			$conditions = array();

			if ( is_multisite() ) {
				$table   = $wpdb->sitemeta;
				$site_id = (int) $wpdb->siteid;

				foreach ( $this->allowed_prefixes as $prefix ) {
					$conditions[] = $wpdb->prepare( 'meta_key LIKE %s', $wpdb->esc_like( $prefix ) . '%' );
				}
				foreach ( $this->notification_keys as $key ) {
					$conditions[] = $wpdb->prepare( 'meta_key = %s', $key );
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$sql = "SELECT meta_key AS option_name, meta_value AS option_value FROM {$table} WHERE site_id = {$site_id} AND (" . implode( ' OR ', $conditions ) . ')';
			} else {
				$table = $wpdb->options;

				foreach ( $this->allowed_prefixes as $prefix ) {
					$conditions[] = $wpdb->prepare( 'option_name LIKE %s', $wpdb->esc_like( $prefix ) . '%' );
				}
				foreach ( $this->notification_keys as $key ) {
					$conditions[] = $wpdb->prepare( 'option_name = %s', $key );
				}

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$sql = "SELECT option_name, option_value FROM {$table} WHERE " . implode( ' OR ', $conditions );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $sql, ARRAY_A );

			$map = array();
			if ( is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$map[ $row['option_name'] ] = maybe_unserialize( $row['option_value'] );
				}
			}
			return $map;
		}

		/**
		 * Builds the full hierarchical export array from a flat DB map.
		 * Sections are assembled in the order:
		 *   otp_verification → forms → settings → notifications → addons → other_settings
		 * Empty sections are omitted.
		 *
		 * @param array<string,mixed> $flat              Full-key → value map from DB.
		 * @param bool                $include_sensitive Whether to include sensitive credentials.
		 * @return array<string,mixed>
		 */
		private function build_export_structure( array $flat, $include_sensitive ) {
			$used = array();

			$otp_v = array();
			foreach ( $this->otp_verification_keys as $short ) {
				$full = self::CORE_PREFIX . $short;
				if ( isset( $flat[ $full ] ) ) {
					$this->try_add( $full, $flat[ $full ], $short, $otp_v, $include_sensitive );
					$used[ $full ] = true;
				}
			}

			$settings = array();
			foreach ( $this->settings_groups as $sub_name => $group_def ) {
				if ( \is_array( $group_def ) && 2 === \count( $group_def ) && \is_string( $group_def[0] ) && \is_array( $group_def[1] ) ) {
					$prefix     = $group_def[0];
					$short_keys = $group_def[1];
				} else {
					$prefix     = self::CORE_PREFIX;
					$short_keys = $group_def;
				}
				$sub = array();
				foreach ( $short_keys as $short ) {
					$full = $prefix . $short;
					if ( isset( $flat[ $full ] ) ) {
						$this->try_add( $full, $flat[ $full ], $short, $sub, $include_sensitive );
						$used[ $full ] = true;
					}
				}
				if ( ! empty( $sub ) ) {
					$settings[ $sub_name ] = $sub;
				}
			}

			$notifs = array();
			foreach ( $this->notification_keys as $full ) {
				if ( ! isset( $flat[ $full ] ) || ! \is_object( $flat[ $full ] ) ) {
					continue;
				}
				$data = array();
				foreach ( (array) $flat[ $full ] as $slug => $sub ) {
					if ( ! \is_object( $sub ) ) {
						continue;
					}
					$sub_data = array();
					foreach ( $this->notification_user_fields as $field ) {
						if ( isset( $sub->{ $field } ) ) {
							$sub_data[ $field ] = $sub->{ $field };
						}
					}
					if ( ! empty( $sub_data ) ) {
						$data[ $slug ] = $sub_data;
					}
				}
				if ( ! empty( $data ) ) {
					$notifs[ $full ] = $data;
					$used[ $full ]   = true;
				}
			}

			$addons = array();
			foreach ( $this->addon_groups as $addon_name => $addon_group_def ) {
				$prefix     = $addon_group_def[0];
				$short_keys = $addon_group_def[1];
				$addon_data = array();
				foreach ( $short_keys as $short ) {
					$full = $prefix . $short;
					if ( isset( $flat[ $full ] ) ) {
						$this->try_add( $full, $flat[ $full ], $short, $addon_data, $include_sensitive );
						$used[ $full ] = true;
					}
				}
				if ( ! empty( $addon_data ) ) {
					$addons[ $addon_name ] = $addon_data;
				}
			}

			/*
			 * Single pass over remaining keys.
			 * Non-core addon prefixes → addons catch-all (for options not in the explicit lists).
			 * Core-prefix keys → forms (matched by form_prefix_map) or other_settings.
			 * The core prefix is excluded from the addon catch-all because it is shared by
			 * every section; only its explicitly listed short keys are handled above.
			 */
			$forms = array();
			$other = array();

			foreach ( $flat as $full => $value ) {
				$is_excluded = false;
				foreach ( $this->excluded_keys as $excl ) {
					if ( '_' === substr( $excl, -1 ) ) {
						if ( 0 === strpos( $full, $excl ) ) {
							$is_excluded = true;
							break;
						}
					} elseif ( $full === $excl ) {
						$is_excluded = true;
						break;
					}
				}
				if ( isset( $used[ $full ] ) || $is_excluded ) {
					continue;
				}

				$addon_matched = false;
				foreach ( $this->addon_groups as $addon_name => $addon_group_def ) {
					$prefix = $addon_group_def[0];
					if ( self::CORE_PREFIX === $prefix || 0 !== strpos( $full, $prefix ) ) {
						continue;
					}
					if ( ! isset( $addons[ $addon_name ] ) ) {
						$addons[ $addon_name ] = array();
					}
					$short = substr( $full, strlen( $prefix ) );
					$this->try_add( $full, $value, $short, $addons[ $addon_name ], $include_sensitive );
					$addon_matched = true;
					break;
				}
				if ( $addon_matched ) {
					continue;
				}

				if ( 0 !== strpos( $full, self::CORE_PREFIX ) ) {
					$this->try_add( $full, $value, $full, $other, $include_sensitive );
					continue;
				}
				$short     = substr( $full, strlen( self::CORE_PREFIX ) );
				$form_type = $this->get_form_type( $short );
				if ( $form_type ) {
					if ( ! $this->is_empty_value( $value ) && ( $include_sensitive || ! $this->is_sensitive_key( $full ) ) ) {
						$forms[ $form_type ][ $short ] = $value;
					}
				} else {
					$this->try_add( $full, $value, $full, $other, $include_sensitive );
				}
			}

			$result = array();
			if ( ! empty( $otp_v ) ) {
				$result['otp_verification'] = $otp_v;
			}
			if ( ! empty( $forms ) ) {
				$result['forms'] = $forms;
			}
			if ( ! empty( $settings ) ) {
				$result['settings'] = $settings;
			}
			if ( ! empty( $notifs ) ) {
				$result['notifications'] = $notifs;
			}
			if ( ! empty( $addons ) ) {
				$result['addons'] = $addons;
			}
			if ( ! empty( $other ) ) {
				$result['other_settings'] = $other;
			}

			return $result;
		}

		/**
		 * Handles the export: builds the hierarchical JSON and streams it as a download.
		 *
		 * @return void
		 */
		public function handle_export() {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_html( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}

			$include_sensitive = isset( $_POST['mo_export_sensitive_credentials'] ) && '1' === $_POST['mo_export_sensitive_credentials']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$flat    = $this->fetch_all_options();
			$grouped = $this->build_export_structure( $flat, $include_sensitive );

			$json = wp_json_encode( $grouped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

			if ( false === $json ) {
				wp_die( esc_html__( 'Failed to encode settings as JSON.', 'miniorange-otp-verification' ) );
			}

			$filename    = 'mo-otp-config-' . gmdate( 'Y-m-d' ) . '.json';
			$byte_length = function_exists( 'mb_strlen' ) ? mb_strlen( $json, '8bit' ) : strlen( $json );

			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . $byte_length );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $json;
			exit;
		}
	}
}
