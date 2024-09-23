<?php
/**
 * Comman Handler .
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\CountryList;
use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoConstants;
use OTP\Helper\MocURLCall;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;
use OTP\Objects\BaseActionHandler;
use OTP\Objects\TabDetails;
use OTP\Objects\Tabs;
use OTP\Traits\Instance;


/**
 * This class handles all the Admin related actions of the user related to the
 * OTP Verification Plugin.
 */
if ( ! class_exists( 'MoActionHandlerHandler' ) ) {
	/**
	 * MoActionHandlerHandler class
	 */
	class MoActionHandlerHandler extends BaseActionHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			parent::__construct();
			$this->nonce = 'mo_admin_actions';
			add_action( 'admin_init', array( $this, 'mo_handle_admin_actions' ), 1 );
			add_action( 'admin_init', array( $this, 'moScheduleTransactionSync' ), 1 );
			add_action( 'admin_init', array( $this, 'checkIfPopupTemplateAreSet' ), 1 );
			add_filter( 'dashboard_glance_items', array( $this, 'otp_transactions_glance_counter' ), 10, 1 );
			add_action( 'admin_post_miniorange_get_form_details', array( $this, 'showFormHTMLData' ) );
			add_action( 'admin_post_miniorange_get_gateway_config', array( $this, 'showGatewayConfig' ) );
			add_action( 'admin_notices', array( $this, 'showNotice' ) );
			add_action( 'wp_ajax_mo_dismiss_notice', array( $this, 'dismiss_notice' ) );
			add_action( 'wp_ajax_mo_dismiss_sms_notice', array( $this, 'dismiss_sms_notice' ) );
			add_action( 'wp_ajax_mo_modal_action', array( $this, 'mo_transaction_modal_action' ) );
			add_action( 'wp_ajax_miniorange_get_message_value', array( $this, 'get_message_value' ) );
			add_action( 'wp_ajax_mo_dismiss_alert', array( $this, 'dismiss_alert' ) );
		}


		/**
		 * This function shows the Enterprise plan notificaton on the admin site only at once.
		 * Once you click on the close notice it will not displayed again.
		 * After deactivation of plugin again the notification will get display.
		 **/
		public function showNotice() {
			$license_page_url = admin_url() . 'admin.php?page=mootppricing';
			$addon_page_url   = admin_url() . 'admin.php?page=addon';
			$query_string     = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : ''; //phpcs:ignore -- false positive.
			$current_url      = admin_url() . 'admin.php?' . $query_string;
			$is_notice_closed = get_mo_option( 'mo_hide_notice' );
			$is_alert_closed  = get_mo_option( 'mo_hide_alert' );
			if ( 'mo_hide_alert' !== $is_alert_closed ) {
				echo '<div style="display:flex;" class="notice notice-error is-dismissible" id="close_alert">
						<p class="mo-lic-admin-notice-text">
							<b>ALERT:</b> We have released some changes to our miniOrange gateway guidelines. According to the new guidelines, you cannot add any special character without spaces around it.
						</p>
					</div>';
			}
			if ( 'mo_hide_notice' !== $is_notice_closed ) {
				if ( ( ! strcmp( MOV_TYPE, 'EnterpriseGatewayWithAddons' ) !== 0 ) && ( $current_url !== $license_page_url ) ) {
					echo '<div class="mo_notice updated notice is-dismissible" >
        <p style ="font-size:14px;"><img src="' . esc_url( MOV_FEATURES_GRAPHIC ) . '" class="show_mo_icon_form" style="width: 3%;margin-bottom: -1%;">&ensp;<b>We support OTP Verification on 50+ forms, PasswordLess Login, WooCommerce SMS Notifications for Admins, Vendors & Customers, Password Reset via OTP and many more.<br><br>AWS SNS, Twilio Gateway & more gateways supported! Want to know more? Check it out here : <a href=' . esc_url( $license_page_url ) . '>Plan Details</a>.</b></p>
         </div>';
				}
			}

		}

        /**
		 * This function we used to update the value on click of hide admin alert.
		 * This is the check for notification on click of close notification.
		 */
		public function dismiss_alert() {
			if ( current_user_can( 'manage_options' ) ) {
				update_mo_option( 'mo_hide_alert', 'mo_hide_alert' );
			}
		}

		/**
		 * This function we used to update the value on click of hide admin notice.
		 * This is the check for notification on click of close notification.
		 */
		public function dismiss_notice() {
			if ( current_user_can( 'manage_options' ) ) {
				update_mo_option( 'mo_hide_notice', 'mo_hide_notice' );
			}
		}

		/**
		 * This function we used to update the value on click of hide admin notice.
		 * This is the check for notification on click of close notification.
		 */
		public function dismiss_sms_notice() {
			if ( current_user_can( 'manage_options' ) ) {
				update_mo_option( 'mo_hide_sms_notice', 'mo_hide_sms_notice' );
			}
		}

		/**
		 * This function hooks into the admin_init WordPress hook. This function
		 * checks the form being posted and routes the data to the correct function
		 * for processing. The 'option' value in the form post is checked to make
		 * the diversion.
		 */
		public function mo_handle_admin_actions() {
			if ( ! isset( $_POST['option'] ) ) { //phpcs:ignore -- false positive.
				return;
			}
			switch ( $_POST['option'] ) { //phpcs:ignore -- false positive.
				case 'mo_customer_validation_settings':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_save_settings( MoUtility::mo_sanitize_array( $_POST ), MoUtility::mo_sanitize_array( $_GET ) );
					break;
				case 'mo_customer_validation_messages':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_handle_custom_messages_form_submit( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'mo_customer_validation_popup_change':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
						$this->mo_popup_change( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'mo_validation_contact_us_query_option':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_validation_support_query( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'mo_otp_extra_settings':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_save_extra_settings( $_POST ); //phpcs:ignore -- sanitized within the function.
					break;
				case 'mo_general_settings':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_save_general_settings( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'mo_otp_feedback_option':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_validation_feedback_query( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'check_mo_ln':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_check_l();
					break;
				case 'mo_check_transactions':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'mo_check_transactions_form', '_nonce' ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_check_transactions();
					break;
				case 'mo_customer_validation_gateway_configuration':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_configure_gateway( MoUtility::mo_sanitize_array( $_POST ) );
					break;
				case 'mo_customer_customization_form':
					if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
						wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
					}
					$this->mo_configure_custom_form( MoUtility::mo_sanitize_array( $_POST ) );
					break;
			}
		}


		/**
		 * This function is used to Configure the custom form settings.
		 *
		 * @param array $post .
		 * @return void
		 */
		private function mo_configure_custom_form( $post ) {

			$this->is_valid_request();

			update_mo_option( 'cf_submit_id', MoUtility::sanitize_check( 'cf_submit_id', $post ), 'mo_otp_' );
			update_mo_option( 'cf_field_id', MoUtility::sanitize_check( 'cf_field_id', $post ), 'mo_otp_' );
			update_mo_option( 'cf_enable_type', MoUtility::sanitize_check( 'cf_enable_type', $post ), 'mo_otp_' );
			update_mo_option( 'cf_button_text', MoUtility::sanitize_check( 'cf_button_text', $post ), 'mo_otp_' );

		}

		/**
		 * This function is used to Configure the custom form settings.
		 */
		public function get_message_value() {
			if ( ! check_ajax_referer( 'addmsgnonce', 'security', false ) ) {
				return;
			}
			$msg_key   = isset( $_POST['msg_key'] ) ? sanitize_text_field( wp_unslash( $_POST['msg_key'] ) ) : ''; //phpcs:ignore -- false positive.
			$msg_array = MoMessages::get_original_message_list();
			foreach ( $msg_array as $key => $value ) {
				if ( $key === $msg_key ) {
					wp_send_json( MoUtility::create_json( $value, MoConstants::SUCCESS_JSON_TYPE ) );
				}
			}
		}

		/**
		 * This function is used to Configure the custom form settings.
		 *
		 * @param array $post data submitted.
		 */
		public function mo_popup_change( $post ) {
			if ( isset( $post['select_popup_option'] ) ) {
				$selected_temp = isset( $post ['select_popup_option'] ) ? sanitize_text_field( wp_unslash( $post['select_popup_option'] ) ) : null;
				update_mo_option( 'selected_popup', $selected_temp );
			}
		}

		/**
		 * This function is used to process and save the custom messages .
		 * set by the admin. These messages are user facing messages.
		 *
		 * @param array $post - The post data containing all the messaging information to be processed .
		 */
		public function mo_handle_custom_messages_form_submit( $post ) {

			$imp_msg = array(
				'OTP_SENT_EMAIL',
				'OTP_SENT_PHONE',
				'ERROR_OTP_EMAIL',
				'ERROR_OTP_PHONE',
				'ERROR_PHONE_FORMAT',
				'ERROR_EMAIL_FORMAT',
				'ERROR_EMAIL_BLOCKED',
				'ERROR_PHONE_BLOCKED',
				'INVALID_OTP',
			);
			update_mo_option( 'success_email_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_OTP_SENT_EMAIL', $post ) ), 'mo_otp_' );
			update_mo_option( 'success_phone_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_OTP_SENT_PHONE', $post ) ), 'mo_otp_' );
			update_mo_option( 'error_phone_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_OTP_PHONE', $post ) ), 'mo_otp_' );
			update_mo_option( 'error_email_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_OTP_EMAIL', $post ) ), 'mo_otp_' );
			update_mo_option( 'invalid_phone_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_PHONE_FORMAT', $post ) ), 'mo_otp_' );
			update_mo_option( 'invalid_email_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_EMAIL_FORMAT', $post ) ), 'mo_otp_' );
			update_mo_option( 'invalid_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_INVALID_OTP', $post ) ), 'mo_otp_' );
			update_mo_option( 'blocked_email_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_EMAIL_BLOCKED', $post ) ), 'mo_otp_' );
			update_mo_option( 'blocked_phone_message', stripslashes( MoUtility::sanitize_check( 'new_msg_list_ERROR_PHONE_BLOCKED', $post ) ), 'mo_otp_' );
			$msg_array = MoMessages::get_original_message_list();
			foreach ( $msg_array as $key => $value ) {
				if ( ! isset( $imp_msg[ $key ] ) ) {
						update_mo_option( $key, stripslashes( MoUtility::sanitize_check( 'new_msg_list_' . $key, $post ) ), 'mo_otp_' );
				}
			}

			do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::MSG_TEMPLATE_SAVED ), 'SUCCESS' );
		}

		/**
		 * All form related data to be saved are saved in the form's
		 * handle_form_options function. This function checks if there's
		 * a javascript error and show the appropriate message.
		 *
		 * @param array $post_data   the post data containing all settings data admin saved.
		 * @param array $get_data   the get data.
		 */
		private function mo_save_settings( $post_data, $get_data ) {
			$tab_details = TabDetails::instance();

			$form_settings_tab = $tab_details->tab_details[ Tabs::FORMS ];
			if ( MoUtility::sanitize_check( 'page', $get_data ) !== $form_settings_tab->menu_slug
			&& sanitize_text_field( $post_data['error_message'] ) ) {
				do_action(
					'mo_registration_show_message',
					MoMessages::showMessage( sanitize_text_field( $post_data['error_message'] ) ),
					'ERROR'
				);
			}
		}

		/**
		 * This function sets the extra OTP related settings in the
		 * plugin.
		 *
		 * @param array $posted   the post data containing all settings data admin saved.
		 */
		private function mo_save_extra_settings( $posted ) {
			update_mo_option( 'show_remaining_trans', MoUtility::sanitize_check( 'mo_show_remaining_trans', $posted ) );
			update_mo_option( 'otp_length', MoUtility::sanitize_check( 'mo_otp_length', $posted ) );
			update_mo_option( 'otp_validity', MoUtility::sanitize_check( 'mo_otp_validity', $posted ) );
			update_mo_option( 'generate_alphanumeric_otp', MoUtility::sanitize_check( 'mo_generate_alphanumeric_otp', $posted ) );
			update_mo_option( 'masterotp_validity', MoUtility::sanitize_check( 'mo_masterotp_validity', $posted ) );
			update_mo_option( 'autofill_otp_enabled', MoUtility::sanitize_check( 'autofill_otp_enabled', $posted ) );
			update_mo_option( 'masterotp_admin', MoUtility::sanitize_check( 'mo_masterotp_admin', $posted ) );
			update_mo_option( 'masterotp_user', MoUtility::sanitize_check( 'mo_masterotp_user', $posted ) );
			update_mo_option( 'masterotp_admins', MoUtility::sanitize_check( 'mo_masterotp_admins', $posted ) );
			update_mo_option( 'masterotp_specific_user', MoUtility::sanitize_check( 'mo_masterotp_specific_user', $posted ) );
			update_mo_option( 'masterotp_specific_user_details', MoUtility::sanitize_check( 'masterotp_specific_user_details', $posted ) );
			$this->mo_configure_sms_template( $posted );

			do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::EXTRA_SETTINGS_SAVED ), 'SUCCESS' );
		}

		/**
		 * This function sets the general settings in the
		 * plugin.
		 *
		 * @param array $posted   the post data containing all settings data admin saved.
		 */
		private function mo_save_general_settings( $posted ) {
			delete_site_option( 'default_country_code' );
			$default_country = isset( $posted['default_country_code'] ) ? sanitize_text_field( $posted['default_country_code'] ) : '';

			update_mo_option( 'default_country', ! empty( $default_country ) ? maybe_serialize( CountryList::$countries[ $default_country ] ) : '' );
			update_mo_option( 'blocked_domains', MoUtility::sanitize_check( 'mo_otp_blocked_email_domains', $posted ) );
			update_mo_option( 'blocked_phone_numbers', MoUtility::sanitize_check( 'mo_otp_blocked_phone_numbers', $posted ) );
			update_mo_option( 'show_remaining_trans', MoUtility::sanitize_check( 'mo_show_remaining_trans', $posted ) );
			update_mo_option( 'show_dropdown_on_form', MoUtility::sanitize_check( 'show_dropdown_on_form', $posted ) );
			update_mo_option( 'globally_banned_phone', MoUtility::sanitize_check( 'mo_globally_banned_phone', $posted ) );

			do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::EXTRA_SETTINGS_SAVED ), 'SUCCESS' );
		}

		/**
		 * This function processes the support form data before sending it to the server.
		 *
		 * @param array $post_data .
		 */
		private function mo_validation_support_query( $post_data ) {
			$email      = MoUtility::sanitize_check( 'mo_query_email', $post_data );
			$form_link  = MoUtility::sanitize_check( 'mo_query_form_link', $post_data );
			$query      = MoUtility::sanitize_check( 'query', $post_data );
			$query_type = MoUtility::sanitize_check( 'query_type', $post_data );

			if ( ! $email || ! $query || ! $form_link ) {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::SUPPORT_FORM_VALUES ), 'ERROR' );
				return;
			}

			$submitted = MocURLCall::submit_contact_us( $email, null, $query, $form_link, $query_type );

			if ( json_last_error() === JSON_ERROR_NONE && $submitted ) {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::SUPPORT_FORM_SENT ), 'SUCCESS' );
				return;
			}

			do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::SUPPORT_FORM_ERROR ), 'ERROR' );
		}

		/**
		 * This function hooks into the dashboard_glance_items filter to show remaining transactions
		 * on the dashboard.
		 */
		public function otp_transactions_glance_counter() {
			if ( ! MoUtility::micr() || ! MoUtility::is_mg() ) {
				return;
			}
			$email = get_mo_option( 'email_transactions_remaining' );
			$phone = get_mo_option( 'phone_transactions_remaining' );
			echo "<li class='mo-trans-count'><a href='" . esc_url( admin_url() ) . "admin.php?page=mosettings'>"
				. wp_kses(
					MoMessages::showMessage(
						MoMessages::TRANS_LEFT_MSG,
						array(
							'email' => $email,
							'phone' => $phone,
						)
					),
					array(
						'b' => array(),
						'i' => array(),
					)
				) . '</a></li>';
		}
		/**
		 * Updates the value for low transaction alert
		 *
		 * @return void
		 */
		public function mo_transaction_modal_action() {
			if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( $this->nonce, 'security' ) ) {
				wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			$data        = MoUtility::mo_sanitize_array( $_POST );
			$array       = get_mo_option( 'mo_transaction_notice' );
			$transaction = $data['shown_remaining'];

			if ( false !== $transaction ) {
				unset( $array[ $transaction ] );
			}

			update_mo_option( 'mo_transaction_notice', $array );
			wp_send_json( MoUtility::create_json( $transaction, MoConstants::SUCCESS_JSON_TYPE ) );

		}


		/**
		 * This function checks if the popup templates have been set in the
		 * database. If not then set the templates up and save them in the
		 * database.
		 */
		public function checkIfPopupTemplateAreSet() {
			$email_templates = maybe_unserialize( get_mo_option( 'custom_popups' ) );
			if ( empty( $email_templates ) ) {
				$templates = apply_filters( 'mo_template_defaults', array() );
				update_mo_option( 'custom_popups', maybe_serialize( $templates ) );
				update_mo_option( 'selected_popup', 'Default' );
			}
		}

		/**
		 * Show Form Data in the Admin Dashboard. Calls the controller of the form
		 * in question to directly get HTML content of the form. This is sent back
		 * in a JSON format which can be used to show data to the admin in the
		 * dashboard.
		 *
		 * @deprecated Deprecated as of version 3.2.80
		 */
		public function showFormHTMLData() {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			$data      = MoUtility::mo_sanitize_array( $_POST );
			$form_name = sanitize_text_field( $data['form_name'] );

			$controller = MOV_DIR . 'controllers/';
			$disabled   = ! MoUtility::micr() ? 'disabled' : '';
			$page_list  = admin_url() . 'edit.php?post_type=page';
			ob_start();
			include $controller . 'forms/' . $form_name . '.php';
			$string = ob_get_clean();
			wp_send_json( MoUtility::create_json( $string, MoConstants::SUCCESS_JSON_TYPE ) );
		}

		/**
		 * Show the gateway configuration fields as per the gateway name.
		 * return a json format view of the page.
		 */
		public function showGatewayConfig() {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			$data                = MoUtility::mo_sanitize_array( $_POST );
			$gateway_type        = sanitize_text_field( wp_unslash( $data['gateway_type'] ) );
			$gateway_class       = 'OTP\Helper\Gateway\\' . $gateway_type;
			$disabled            = ! MoUtility::micr() ? 'disabled' : '';
			$gateway_url         = get_mo_option( 'custom_sms_gateway' )
										? get_mo_option( 'custom_sms_gateway' )
										: '';
			$gateway_config_view = $gateway_class::instance()->get_gateway_config_view( $disabled, $gateway_url );
			wp_send_json( MoUtility::create_json( $gateway_config_view, MoConstants::SUCCESS_JSON_TYPE ) );
		}

		/**
		 * This function hooks into the WordPress init hook to
		 * start the daily sync schedule. This function starts
		 * a daily schedule to sync the email and sms transactions
		 * from the server.
		 *
		 * @note - this might say hourlySync but it's actually a daily sync
		 */
		public function moScheduleTransactionSync() {
			if ( ! wp_next_scheduled( 'hourly_sync' ) && MoUtility::micr() ) {
				wp_schedule_event( time(), 'daily', 'hourly_sync' );
			}
		}


		/**
		 * This function provides the feedback reasons to the users
		 * on the deactivation of the plugin.
		 */
		public function mo_feedback_reasons() {
			$deactivationreasons = array(
				'not_the_feture_i_wanted' => 'Features I wanted are missing',
				'otp_not_received'        => 'OTP/SMS  is not receiving',
				'unable_to_setup_plugin'  => 'Unable to setup plugin, Lack of documentation',
				'temporary_deactivation'  => 'Temporarily deactivation to debug an issue',
				'cost_is_too_high'        => 'Cost is too high',
				'upgraded_to_prem_plan'   => 'Upgraded to the premium plugin',
			);

			return $deactivationreasons;
		}

		/**
		 * This function returns the list of form enabled during deactivation
		 */
		public function enabled_form_list() {
			global $wpdb;
			$enabled_form_list = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM `{$wpdb->prefix}options` WHERE option_value = 1 AND option_name LIKE %s", array( 'mo_%enable' ) ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			$enabled_forms     = '';
			foreach ( $enabled_form_list as $form_name ) {
				$curr_form_name  = str_replace( '_enable', '', $form_name->option_name );
				$final_form_name = str_replace( 'mo_customer_validation_', '', $curr_form_name );
				$enabled_forms  .= $final_form_name . ' , ';
			}
			return $enabled_forms;
		}

		/**
		 * Function to fetch the HTML body of the feedback template.
		 *
		 * @return string
		 */
		private function get_feedback_html() {
			$template =
			'<html><head><title></title></head><body> <div> First Name :{{FIRST_NAME}}<br/><br/> Last Name :{{LAST_NAME}}<br/><br/> Server Name :{{SERVER}}<br/><br/> Email :{{EMAIL}}<br/><br/>Plugin Type : {{PLUGIN_TYPE}}<br/><br/> {{TYPE}}: [{{PLUGIN}} - {{VERSION}}] : <br/><br/><strong><em>Feedback : </em></strong>{{FEEDBACK}}<br/><br/>Enabled Forms : {{ENABLED_FORMS}}</div></body></html>';
			return $template;
		}
		/**
		 * Process and send the feedback
		 *
		 * @param array $posted $_POST.
		 */
		private function mo_validation_feedback_query( $posted ) {

			$submit_type = sanitize_text_field( $posted['miniorange_feedback_submit'] );

			if ( 'Skip & Deactivate' === $submit_type ) {
				deactivate_plugins( array( MOV_PLUGIN_NAME ) );
				delete_mo_option( 'mo_hide_notice' );
				return;
			}

			$deactivating_plugin = strcasecmp( sanitize_text_field( $posted['plugin_deactivated'] ), 'true' ) === 0;
			$type                = ! $deactivating_plugin ? mo_( '[ Plugin Feedback ] : ' ) : mo_( '[ Plugin Deactivated ]' );

			$views               = array();
			$deactivationreasons = $this->mo_feedback_reasons();
			if ( isset( $posted['miniorange_feedback_submit'] ) ) {
				if ( ! empty( $posted['reason'] ) ) {
					foreach ( MoUtility::mo_sanitize_array( $posted['reason'] ) as $value ) {
						$views[] = $deactivationreasons[ $value ];
					}
				}
			}
			$feedback = implode( ' , ', $views ) . ' , ' . sanitize_text_field( $posted['query_feedback'] );

			$feedback_template = $this->get_feedback_html();

			$current_user         = wp_get_current_user();
			$customer_type        = MoUtility::micv() ? 'Premium' : 'Free';
			$email                = get_mo_option( 'admin_email' );
			$activation_date      = get_mo_option( 'plugin_activation_date' );
			$activation_days      = round( ( strtotime( gmdate( 'Y-m-d h:i:sa' ) ) - strtotime( $activation_date ) ) / ( 60 * 60 * 24 ) );
			$activation_date_html = '<br><br>Days since Activated: ' . $activation_days;
			$server_name          = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : ''; //phpcs:ignore -- false positive.
			$feedback_template    = str_replace( '{{FIRST_NAME}}', $current_user->first_name, $feedback_template );
			$feedback_template    = str_replace( '{{LAST_NAME}}', $current_user->last_name, $feedback_template );
			$feedback_template    = str_replace( '{{PLUGIN_TYPE}}', MOV_TYPE . ':' . $customer_type . $activation_date_html, $feedback_template );
			$feedback_template    = str_replace( '{{SERVER}}', $server_name, $feedback_template );
			$feedback_template    = str_replace( '{{EMAIL}}', $email, $feedback_template );
			$feedback_template    = str_replace( '{{PLUGIN}}', MoConstants::AREA_OF_INTEREST, $feedback_template );
			$feedback_template    = str_replace( '{{VERSION}}', MOV_VERSION, $feedback_template );

			$feedback_template = str_replace( '{{TYPE}}', $type, $feedback_template );
			$feedback_template = str_replace( '{{FEEDBACK}}', $feedback, $feedback_template );
			$feedback_template = str_replace( '{{ENABLED_FORMS}}', $this->enabled_form_list(), $feedback_template );

			$notif = MoUtility::send_email_notif(
				$email,
				'Xecurify',
				MoConstants::FEEDBACK_EMAIL,
				'WordPress OTP Verification Plugin Feedback',
				$feedback_template
			);

			if ( $notif ) {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::FEEDBACK_SENT ), 'SUCCESS' );
			} else {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::FEEDBACK_ERROR ), 'ERROR' );
			}

			if ( $deactivating_plugin ) {
				deactivate_plugins( array( MOV_PLUGIN_NAME ) );
			}
			delete_mo_option( 'mo_hide_notice' );
		}

		/**
		 * Checks the number of transactions available in user's account.
		 * We can change the isValidRequest() by adding a nonce param to make it generic.
		 */
		public static function mo_check_transactions() {
			MoUtility::handle_mo_check_ln(
				false,
				get_mo_option( 'admin_customer_key' ),
				get_mo_option( 'admin_api_key' )
			);
		}


		/**
		 * Check the license of the user and update the transaction count in WordPress
		 * so that it can be shown to the users on the At a Glance section of WordPress.
		 * This endpoint is called from the licensing tab or the account page in the
		 * WordPress Plugin.
		 */
		private function mo_check_l() {

			MoUtility::handle_mo_check_ln(
				true,
				get_mo_option( 'admin_customer_key' ),
				get_mo_option( 'admin_api_key' )
			);
		}

		/**
		 * Check when users changes the SMS template.
		 *
		 * @param array $posted .
		 * @return void
		 */
		private function mo_configure_sms_template( $posted ) {
			$gateway = GatewayFunctions::instance();
			$gateway->mo_configure_sms_template( $posted );
			$gateway->mo_configure_email_template( $posted );
		}

		/**
		 * Check when users changes the SMS template.
		 *
		 * @param array $posted .
		 * @return void
		 */
		private function mo_configure_gateway( $posted ) {
			if ( isset( $posted['mo_customer_validation_custom_sms_gateway'] ) && empty( sanitize_text_field( $posted['mo_customer_validation_custom_sms_gateway'] ) ) ) {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::SMS_TEMPLATE_ERROR ), 'ERROR' );

			} else {
				do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::SMS_TEMPLATE_SAVED ), 'SUCCESS' );
			}

			$gateway = GatewayFunctions::instance();
			$gateway->mo_configure_gateway( $posted );
		}
	}
}
