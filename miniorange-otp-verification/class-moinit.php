<?php
/**Load Main File MoInit
 *
 * @package miniorange-otp-verification
 */

namespace OTP;

use OTP\Handler\EmailVerificationLogic;
use OTP\Handler\FormActionHandler;
use OTP\Handler\MoActionHandlerHandler;
use OTP\Handler\MoRegistrationHandler;
use OTP\Handler\PhoneVerificationLogic;
use OTP\Helper\CountryList;
use OTP\Helper\GatewayFunctions;
use OTP\Helper\MenuItems;
use OTP\Helper\MoConstants;
use OTP\Helper\MoDisplayMessages;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;
use OTP\Helper\MOVisualTour;
use OTP\Helper\PolyLangStrings;
use OTP\Helper\Templates\DefaultPopup;
use OTP\Helper\Templates\ErrorPopup;
use OTP\Helper\Templates\ExternalPopup;
use OTP\Helper\Templates\UserChoicePopup;
use OTP\Objects\PluginPageDetails;
use OTP\Objects\TabDetails;
use OTP\Objects\Tabs;
use OTP\Traits\Instance;
use OTP\Helper\MoAddonListContent;
use OTP\Handler\CustomForm;
use OTP\Helper\MocURLCall;
use OTP\Objects\BaseMessages;
use OTP\Helper\MoVersionUpdate;
use OTP\Helper\MoAlphaNumeric;
use OTP\Helper\MoSMSBackupGateway;
use OTP\Helper\MoGloballyBannedPhone;
use OTP\Helper\MoWhatsApp;
use OTP\Helper\MoMasterCode;
use OTP\Helper\PopupTemplateChange;
use OTP\Helper\MoReporting;
use OTP\Helper\TransactionCost;
use OTP\Helper\MoAutofill;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoInit' ) ) {
	/**
	 * Final class that runs base functionalities of the plugin.
	 * It initializes some of the common helper and handler for the plugin
	 * classes.
	 */
	final class MoInit {

		use Instance;
		/** Constructor */
		private function __construct() {
			$this->initialize_hooks();
			$this->initialize_globals();
			$this->initialize_helpers();
			$this->initialize_handlers();
			$this->register_polylang_strings();
			$this->register_addons();
		}

		/**
		 * Initialize all the main hooks needed for the plugin
		 */
		private function initialize_hooks() {
			add_action( 'plugins_loaded', array( $this, 'otp_load_textdomain' ) );
			add_action( 'admin_menu', array( $this, 'miniorange_customer_validation_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_registration_plugin_settings_style' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'mo_registration_plugin_settings_script' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'mo_registration_plugin_frontend_scripts' ), 99 );
			add_action( 'login_enqueue_scripts', array( $this, 'mo_registration_plugin_frontend_scripts' ), 99 );
			add_action( 'mo_registration_show_message', array( $this, 'mo_show_otp_message' ), 1, 2 );
			add_action( 'hourly_sync', array( $this, 'hourly_sync' ) );
			add_action( 'admin_footer', array( $this, 'feedback_request' ) );
			add_filter( 'wp_mail_from_name', array( $this, 'custom_wp_mail_from_name' ) );
			add_filter( 'plugin_row_meta', array( $this, 'mo_meta_links' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_jquery_on_forms' ) );

			add_action( 'plugin_action_links_' . MOV_PLUGIN_NAME, array( $this, 'plugin_action_links' ), 10, 1 );

		}

		/**
		 * Function to check if jQuery library is included, if not present then insert it.
		 * This was added to avoid conflicts with other scripts in WordPress all the while
		 * making sure our plugin is working as intended.
		 */
		public function load_jquery_on_forms() {
			if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery' );
			}
		}

		/**
		 * Initialize all the helper classes
		 */
		private function initialize_helpers() {
			MoMessages::instance();
			MoAddonListContent::instance();
			PolyLangStrings::instance();
			MOVisualTour::instance();
			TransactionCost::instance();
			if ( file_exists( MOV_DIR . 'helper/class-moversionupdate.php' ) ) {
				MoVersionUpdate::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-moalphanumeric.php' ) ) {
				MoAlphaNumeric::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-mosmsbackupgateway.php' ) ) {
				MoSMSBackupGateway::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-mogloballybannedphone.php' ) ) {
				MoGloballyBannedPhone::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-mowhatsapp.php' ) ) {
				MoWhatsApp::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-momastercode.php' ) ) {
				MoMasterCode::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-moreporting.php' ) ) {
				MoReporting::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-popuptemplatechange.php' ) ) {
				PopupTemplateChange::instance();
			}
			if ( file_exists( MOV_DIR . 'helper/class-moautofill.php' ) ) {
				MoAutofill::instance();
			}
		}
		/**
		 * Initialize all the Template Handlers
		 */
		private function initialize_handlers() {
			FormActionHandler::instance();
			MoActionHandlerHandler::instance();
			DefaultPopup::instance();
			ErrorPopup::instance();
			ExternalPopup::instance();
			UserChoicePopup::instance();
			MoRegistrationHandler::instance();
			CustomForm::instance();
		}

		/**
		 * Initialize all the global variables.
		 */
		private function initialize_globals() {
			global $phone_logic,$email_logic;
			$phone_logic = PhoneVerificationLogic::instance();
			$email_logic = EmailVerificationLogic::instance();
		}

		/**
		 * This function hooks into the admin_menu WordPress hook to generate
		 * WordPress menu items. You define all the options and links you want
		 * to show to the admin in the WordPress sidebar.
		 */
		public function miniorange_customer_validation_menu() {
			MenuItems::instance();
		}


		/**
		 * The main callback function for each of the menu links. This function
		 * is called when user visits any one of the menu URLs.
		 */
		public function mo_customer_validation_options() {
			include MOV_DIR . 'controllers/main-controller.php';
		}


		/**
		 * This function is called to append our CSS file
		 * in the backend and frontend. Uses the admin_enqueue_scripts
		 * and enqueue_scripts WordPress hook.
		 */
		public function mo_registration_plugin_settings_style() {
			wp_enqueue_style( 'mo_customer_validation_admin_settings_style', MOV_CSS_URL, array(), MOV_VERSION );
			wp_enqueue_style( 'mo_customer_validation_form_main_css', MOV_FORM_CSS, array(), MOV_VERSION );
			wp_enqueue_style( 'mo_customer_validation_inttelinput_style', MO_INTTELINPUT_CSS, array(), MOV_VERSION );
			wp_enqueue_style( 'mo_main_style', MOV_MAIN_CSS, array(), MOV_VERSION );
		}


		/**
		 * This function is called to append our CSS file
		 * in the backend and frontend. Uses the admin_enqueue_scripts
		 * and enqueue_scripts WordPress hook.
		 */
		public function mo_registration_plugin_settings_script() {
			$country_val = array();
			wp_enqueue_script( 'mo_customer_validation_admin_settings_script', MOV_JS_URL, array( 'jquery' ), MOV_VERSION, false );
			wp_enqueue_script( 'mo_customer_validation_form_validation_script', VALIDATION_JS_URL, array( 'jquery' ), MOV_VERSION, false );
			wp_register_script( 'mo_customer_validation_inttelinput_script', MO_INTTELINPUT_JS, array( 'jquery' ), MOV_VERSION, false );
			$countriesavail = CountryList::get_countrycode_list();
			$countriesavail = apply_filters( 'selected_countries', $countriesavail );
			foreach ( $countriesavail as $key => $value ) {
				array_push( $country_val, $value );
			}
			wp_localize_script(
				'mo_customer_validation_inttelinput_script',
				'moselecteddropdown',
				array(
					'selecteddropdown' => $country_val,
				)
			);
			wp_enqueue_script( 'mo_customer_validation_inttelinput_script' );
		}


		/**
		 * This function is called to append certain javascripts
		 * to the frontend. Mostly used for the appending a country
		 * code dropdown to the phone number field.
		 */
		public function mo_registration_plugin_frontend_scripts() {
			wp_enqueue_style( 'mo_customer_validation_form_main_css', MOV_FORM_CSS, array(), MOV_VERSION );
			$country_val = array();
			if ( ! get_mo_option( 'show_dropdown_on_form' ) ) {
				return;
			}
			$selector = apply_filters( 'mo_phone_dropdown_selector', array() );
			if ( MoUtility::is_blank( $selector ) ) {
				return;
			}
			$selector       = array_unique( $selector );
			$countriesavail = CountryList::get_countrycode_list();
			$countriesavail = apply_filters( 'selected_countries', $countriesavail );
			foreach ( $countriesavail as $key => $value ) {
				array_push( $country_val, $value );
			}
			$default_country = CountryList::get_default_country_iso_code();
			$get_ip_country  = apply_filters( 'mo_get_default_country', $default_country );
			wp_register_script( 'mo_customer_validation_inttelinput_script', MO_INTTELINPUT_JS, array( 'jquery' ), MOV_VERSION, false );
			wp_localize_script(
				'mo_customer_validation_inttelinput_script',
				'moselecteddropdown',
				array(
					'selecteddropdown' => $country_val,

				)
			);
			wp_enqueue_script( 'mo_customer_validation_inttelinput_script' );

			wp_enqueue_style( 'mo_customer_validation_inttelinput_style', MO_INTTELINPUT_CSS, array(), MOV_VERSION );
			wp_register_script( 'mo_customer_validation_dropdown_script', MO_DROPDOWN_JS, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'mo_customer_validation_dropdown_script',
				'modropdownvars',
				array(
					'selector'       => wp_json_encode( $selector ),
					'defaultCountry' => $get_ip_country,
					'onlyCountries'  => CountryList::get_only_country_list(),
				)
			);
			wp_enqueue_script( 'mo_customer_validation_dropdown_script' );
		}


		/**
		 * This function runs when mo_registration_show_message hook
		 * is initiated. The hook runs to show a plugin generated
		 * message to the user in the admin dashboard.
		 *
		 * @param string $content refers to the message content.
		 * @param string $type refers to the type of message.
		 */
		public function mo_show_otp_message( $content, $type ) {
			new MoDisplayMessages( $content, $type );
		}



		/**
		 * Function tells where to look for translations.
		 * <b>PLEASE NOTE:</b> Dont be clever and try to replace the Text domain 'miniorange-otp-verification'
		 * with a constant value. Its kept as string for a reason. Its so that other automated
		 * tools can read it and use it for automatic translation.
		 */
		public function otp_load_textdomain() {
			load_plugin_textdomain( 'miniorange-otp-verification', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			do_action( 'mo_otp_verification_add_on_lang_files' );
		}


		/**
		 * Function loads the polylang string. This is used to declare all the strings that will show
		 * up in the PolyLang plugin list. A user can use the strings defined in the function to
		 * declare his own translation and support multiligual texts.
		 */
		private function register_polylang_strings() {
			if ( ! MoUtility::is_polylang_installed() ) {
				return;
			}
			foreach ( maybe_unserialize( MO_POLY_STRINGS ) as $key => $value ) {
				pll_register_string( $key, $value, 'miniorange-otp-verification' );
			}
		}


		/**
		 * Function initializes all the AddOns associated with the plugin.
		 *
		 * We can use reflection and automate the instantiation process
		 * but that will be a little costly and would affect performance
		 * hence decided not to.
		 */
		private function register_addons() {

			$gateway = GatewayFunctions::instance();
			$gateway->register_addons();
		}

		/**
		 * Function hooks into the admin_footer hook to append the feedback form in the
		 * footer section of the page.
		 */
		public function feedback_request() {
			include MOV_DIR . 'controllers/feedback.php';
		}


		/**
		 * Function hooks into the plugin_row_meta link to add custom
		 * links to the plugin's page.
		 *
		 * @param object $meta_fields .
		 * @param object $file .
		 * @return array
		 */
		public function mo_meta_links( $meta_fields, $file ) {
			if ( MOV_PLUGIN_NAME === $file ) {
				$meta_fields[] = "<span class='dashicons dashicons-sticky'></span>
            <a href='" . MoConstants::FAQ_URL . "' target='_blank'>" . mo_( 'FAQs' ) . '</a>';
			}
			return $meta_fields;
		}


		/**
		 * Add action links to the plugin list page for easy navigation
		 * after plugin activation.
		 *
		 * @param string $links .
		 * @return array
		 */
		public function plugin_action_links( $links ) {

			$tab_details = TabDetails::instance();

			$form_settings_tab = $tab_details->tab_details[ Tabs::FORMS ];
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active( MOV_PLUGIN_NAME ) ) {
				$links = array_merge(
					array(
						'<a href="' . esc_url( admin_url( 'admin.php?page=' . $form_settings_tab->menu_slug ) ) . '">' .
							mo_( 'Settings' )
						. '</a>',
					),
					$links
				);
			}
			return $links;
		}

		/**
		 * Daily sync to do a license check and update the email and
		 * SMS Transaction.
		 *
		 * @note - this might say hourlySync but it's actually a daily sync
		 */
		public function hourly_sync() {
			$gateway = GatewayFunctions::instance();
			$gateway->hourly_sync();
		}

		/**
		 * Change the from name going out in the email
		 * via WP_MAIL of WordPress.
		 *
		 * @param  String $original_email_from    The Original From Email Address passed by the hook.
		 * @return String From Email Address for the email going out
		 */
		public function custom_wp_mail_from_name( $original_email_from ) {

			$gateway = GatewayFunctions::instance();
			return $gateway->custom_wp_mail_from_name( $original_email_from );
		}
	}
}
