<?php
/**
 * Load admin view for WooCommerce Checkout Form form.
 *
 * @package miniorange-otp-verification/handler
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
use OTP\Objects\BaseMessages;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WC_Checkout;
use WP_Error;

/**
 * This is the WooCommerce CheckOut form class. This class handles all the
 * functionality related to WooCommerce CheckOut form. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 *
 * @todo scripts needs to be better managed
 * @todo disable autologin after checkout needs to be better managed
 */
if ( ! class_exists( 'WooCommerceCheckOutForm' ) ) {
	/**
	 * WooCommerceCheckOutForm class
	 */
	class WooCommerceCheckOutForm extends FormHandler implements IFormHandler {

		use Instance;

		/**
		 * Should OTP Verification be applied only
		 * for guest users
		 *
		 * @var bool
		 */
		private $guest_check_out_only;

		/**
		 * Should OTP Verification be applied only
		 * for guest users
		 *
		 * @var bool
		 */
		private $show_button;

		/**
		 * Should show a popUp for verifying the OTP
		 * sent instead of fields on the page
		 *
		 * @var boolean
		 */
		private $popup_enabled;

		/**
		 * The array of all the available payment
		 * options
		 *
		 * @var array
		 */
		private $payment_methods;

		/**
		 * Option to enable/disable OTP Verification based
		 * on payment type
		 *
		 * @var bool
		 */
		private $selective_payment;

		/**
		 * Option to enable/disable Auto Login After checkout
		 *
		 * @var bool
		 */
		private $disable_auto_login;

		/**
		 * The array of all the category on which otp is enabled
		 *
		 * @var array
		 */
		private $mo_special_category_list;

		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::WC_CHECKOUT;
			$this->type_phone_tag          = 'mo_wc_phone_enable';
			$this->type_email_tag          = 'mo_wc_email_enable';
			$this->phone_form_id           = 'input[name=billing_phone]';
			$this->form_key                = 'WC_CHECKOUT_FORM';
			$this->form_name               = mo_( 'WooCommerce Checkout Form - Classic Form' );
			$this->is_form_enabled         = get_mo_option( 'wc_checkout_enable' );
			$this->button_text             = get_mo_option( 'wc_checkout_button_link_text' );
			$this->button_text             = ! MoUtility::is_blank( $this->button_text ) ? $this->button_text
							: ( ! $this->popup_enabled ? mo_( 'Verify Your Purchase' ) : mo_( 'Place Order' ) );
			$this->form_documents          = MoFormDocs::WC_CHECKOUT_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException .
		 */
		public function handle_form() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return;
			}
			// check for WooCommerce selected category addon.
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) ) {
				add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'webroom_check_if_product_category_in_cart' ) );
			}

			$this->disable_auto_login   = get_mo_option( 'wc_checkout_disable_auto_login' );
			$this->payment_methods      = maybe_unserialize( get_mo_option( 'wc_checkout_payment_type' ) );
			$this->payment_methods      = $this->payment_methods ? $this->payment_methods : WC()->payment_gateways->payment_gateways(); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
			$this->popup_enabled        = get_mo_option( 'wc_checkout_popup' );
			$this->guest_check_out_only = get_mo_option( 'wc_checkout_guest' );
			$this->show_button          = get_mo_option( 'wc_checkout_button' );
			$this->otp_type             = get_mo_option( 'wc_checkout_type' );
			$this->selective_payment    = get_mo_option( 'wc_checkout_selective_payment' );
			$this->restrict_duplicates  = get_mo_option( 'wc_checkout_restrict_duplicates' );

			if ( $this->popup_enabled ) {
				add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_custom_popup' ), 99 );
				add_action( 'woocommerce_review_order_after_submit', array( $this, 'add_custom_button' ), 1, 1 );
			} else {
				add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'my_custom_checkout_field' ), 99 );
			}

			if ( $this->disable_auto_login ) {
				add_action( 'woocommerce_thankyou', array( $this, 'disable_auto_login_after_checkout' ), 1, 1 );
			}

			add_filter( 'woocommerce_checkout_posted_data', array( $this, 'billing_phone_process' ), 99, 1 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_on_page' ) );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'my_custom_checkout_field_process' ), 99, 2 );
			$this->routeData();
		}

		/**
		 * This function adds true in the session if the otp is enabled on
		 * the category of the product.
		 */
		public function webroom_check_if_product_category_in_cart() {
			MoPHPSessions::add_session_var( 'specialproductexist', 'false' );
			/**
				* Contains the list of all the product categories enabled.
				*
				*  @var array $mo_special_category_list */
			$this->mo_special_category_list = get_option( 'mo_wcsc_sms_wc_selected_category' );
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$category_id      = $cart_item['product_id'];
				$product_category = wp_get_post_terms( $category_id, 'product_cat' );
				$product_category = json_decode( wp_json_encode( $product_category ), true );
				foreach ( $product_category as $category ) {
					if ( in_array( $category['name'], $this->mo_special_category_list, true ) ) {
						MoPHPSessions::add_session_var( 'specialproductexist', 'true' );
						break;
					}
				}
			}
		}

		/**
		 * This function hooks too WooCommerce hook to edit posted Phone Number
		 * before saving in database. It saves phone number in general format.
		 *
		 * @param  array $data  contains posted data for order.
		 * @return array
		 */
		public function billing_phone_process( $data ) {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return $data;
			}
			$data['billing_phone'] = MoUtility::process_phone_number( $data['billing_phone'] );
			return $data;
		}

		/**
		 * This function hooks into WooCommerce Thankyou hook to Logout users
		 * that are autoLogged in after checkout.
		 *
		 * @param  string $order Order number.
		 * @todo Figure out a better way to handle this.
		 */
		public function disable_auto_login_after_checkout( $order ) {
			MoPHPSessions::add_session_var( 'specialproductexist', 'false' );

			if ( is_user_logged_in() ) {
				wp_logout();
				$mo_logout_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; //phpcs:ignore -- false positive.
				wp_safe_redirect( $mo_logout_request_uri );
				exit();
			}
		}

		/**
		 * This function checks what kind of OTP Verification needs to be done
		 * and starts the otp verification process with appropriate parameters.
		 *
		 * @throws ReflectionException .
		 */
		private function routeData() {
			if ( ! array_key_exists( 'option', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for checking the option name, doesn't require nonce verification.
				return;
			}
			if ( strcasecmp( trim( sanitize_text_field( wp_unslash( $_GET['option'] ) ) ), 'miniorange-woocommerce-checkout' ) === 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for checking the option name, doesn't require nonce verification.
				$this->handle_woocommerce_checkout_form( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
			}
		}


		/**
		 * This functions checks if Verification was started and handles
		 * what needs to be done if verification process was not started
		 * and user is trying to submit the form.
		 *
		 * @param array $get_data $_GET data.
		 * @throws ReflectionException .
		 */
		private function handle_woocommerce_checkout_form( $get_data ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$this->checkPhoneValidity( $get_data );
				$this->send_challenge(
					'test',
					sanitize_email( $get_data['user_email'] ),
					null,
					sanitize_text_field( trim( $get_data['user_phone'] ) ),
					VerificationType::PHONE
				);
			} else {
				$this->send_challenge(
					'test',
					sanitize_email( $get_data['user_email'] ),
					null,
					null,
					VerificationType::EMAIL
				);
			}
		}


		/**
		 * Checks if the phone is valid and not a duplicate
		 * if admin has enabled the restrictDuplicate option
		 *
		 * @param array $get_data    $_GET data.
		 */
		private function checkPhoneValidity( $get_data ) {
			if ( $this->isPhoneNumberAlreadyInUse( sanitize_text_field( $get_data['user_phone'] ) ) && $this->restrict_duplicates ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( MoMessages::PHONE_EXISTS ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
				exit;
			}
		}


		/**
		 * Checks if the Phone number is already associated with any other account.
		 *
		 * @param string $phone Phone number in the checkout form.
		 * @return boolean
		 */
		private function isPhoneNumberAlreadyInUse( $phone ) {
			global $wpdb;
			$phone            = MoUtility::process_phone_number( $phone );
			$key              = 'billing_phone';
			$current_user_i_d = strval( wp_get_current_user()->ID );
			$results          = $wpdb->get_row( $wpdb->prepare( "SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE `meta_key` = %s AND `meta_value` =  %s", array( $key, $phone ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, Direct database call without caching detected -- DB Direct Query is necessary here.
			return MoUtility::is_blank( $results ) ? false : $results->user_id !== $current_user_i_d;
		}


		/**
		 * This function checks if verification codes was entered in the form
		 * by the user and handles what needs to be done if verification code
		 * was not entered by the user.
		 */
		private function checkIfVerificationNotStarted() {

			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				wc_add_notice( MoMessages::showMessage( MoMessages::ENTER_VERIFY_CODE ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				return true;
			}
			return false;
		}


		/**
		 * Checks if the verification code was not entered by the user.
		 * If no verification code was entered then throw an error to the user.
		 */
		private function checkIfVerificationCodeNotEntered() {
			if ( array_key_exists( 'order_verify', $_POST ) && ! MoUtility::is_blank( sanitize_text_field( wp_unslash( $_POST['order_verify'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook
				return false;
			}

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				wc_add_notice( MoMessages::showMessage( MoMessages::ENTER_PHONE_CODE ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
			} else {
				wc_add_notice( MoMessages::showMessage( MoMessages::ENTER_EMAIL_CODE ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
			}
			return true;
		}


		/**
		 * Adds the popup HTML and scripts on the checkout page for OTP Verification.
		 *
		 * @param string $order_id order id passed by the hook.
		 * @todo Make the script here more readable.
		 */
		public function add_custom_button( $order_id ) {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return;
			}
			if ( $this->guest_check_out_only && is_user_logged_in() ) {
				return;
			}
			$this->show_validation_button_or_text();
			$this->common_button_or_link_enable_disable_script();
			echo ',$mo("#miniorange_otp_token_submit").click(function(o){
                    var requiredFields = areAllMandotryFieldsFilled(),
                    e=$mo("input[name=billing_email]").val(),
                    m=$mo("#billing_phone").val(),
                    a=$mo("div.woocommerce");
                    if(requiredFields=="")
                    {
                        a.addClass("processing").block({message:null,overlayCSS:{background:"#fff",opacity:.6}});
                        $mo.ajax({
                            url:"' . esc_url( site_url() ) . '/?option=miniorange-woocommerce-checkout",type:"POST",
                            data:{user_email:e,user_phone:m},crossDomain:!0,dataType:"json",
                            success:function(o){
                                "success"==o.result?(
                                    $mo(".blockUI").hide(),$mo("#mo_message").empty(),
                                    $mo("#mo_message").append(o.message).show(),
                                    $mo("#mo_message").addClass("woocommerce-message").removeClass("woocommerce-error"),
                                    //$mo("#myModal .modal-content").append(popupTemplate),
                                    $mo("#myModal").show(),$mo("#mo_validate_field").show()):($mo(".blockUI").hide(),$mo("#mo_message").empty(),
                                    $mo("#mo_message").append(o.message),$mo("#mo_message").addClass("woocommerce-error"),
                                    $mo("#mo_validate_field").hide(),$mo("#myModal").show()
                                )
                            },
                            error:function(o,e,m){}
                        });
                    }else{
                        $mo(".woocommerce-NoticeGroup-checkout").empty();
                        $mo("form.woocommerce-checkout").prepend(requiredFields);
                        $mo("html, body").animate({scrollTop: $mo(".woocommerce-error").offset().top}, 2000);
                    }
                    o.preventDefault()});
                    $mo("#miniorange_otp_validate_submit").click(function(o){$mo("#myModal").hide(),$mo(\'form[name="checkout"]\').submit()}),
                    $mo(".close").click(function(){$mo(".modal").hide();});});';

			echo 'function areAllMandotryFieldsFilled(){
                var err = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">`+
                                `<ul class="woocommerce-error" role="alert">{{errors}}</ul>`+
                         `</div>`;
                var errors = "";
                $mo(".validate-required").each(function(){
                    var id = $mo(this).attr("id");
                    if(id!=undefined){
                        var n = id.replace("_field","");
                        if(n!="") {
                            var val = $mo("#"+n).val();
                            if((val=="" || val=="select") && checkOptionalMandatoryFields(n) ) {
                                $mo("#"+n).addClass("woocommerce-invalid woocommerce-invalid-required-field");
                                errors  += 	`<li><strong>`+
                                                $mo("#"+n+"_field").children("label").text().replace("*","")+
                                                "</strong> is a required field."+
                                            `</li>`;
                            }
                        }
                    }
                });
                return errors != "" ? err.replace("{{errors}}",errors) : 0;
            }
            function checkOptionalMandatoryFields(n){
                var optional = { "shipping": { "fields": [ "shipping_first_name","shipping_last_name","shipping_postcode","shipping_address_1","shipping_address_2","shipping_city","shipping_state"],"checkbox": "ship-to-different-address-checkbox"},"account": {"fields": ["account_password","account_username"],"checkbox": "createaccount"}};
                if(n.indexOf("shipping") != -1){
                   return check_fields(n,optional["shipping"]);
                }else if(n.indexOf("account") != -1){
                   return check_fields(n,optional["account"]);
                }
                return true;
            }
            function check_fields(n,data){
                if($mo.inArray(n,data["fields"]) == -1 || ($mo.inArray(n,data["fields"]) > -1 &&
                        $mo("#"+data[\'checkbox\']+":checked").length > 0)) {
                    return true;
                }
                return false;
            }</script>';
		}


		/**
		 * Adds a validation button of text along with the scripts and an
		 * extra verification field where users can enter OTP on the checkout page.
		 *
		 * @todo Make the script here more readable
		 */
		public function add_custom_popup() {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return;
			}
			if ( $this->guest_check_out_only && is_user_logged_in() ) {
				return;
			}
			echo '<style>@media only screen and (max-width: 800px) {.modal-content {width: 90% !important;}.modal-header .close{margin-left: 80% !important;}}.modal{display:none;position:fixed;z-index:1;padding-top:100px;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.4);}.modal-content{position:relative;background-color:#fefefe;margin:auto;padding:0;border:1px solid #888;width:40%;box-shadow:04px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);-webkit-animation-name:animatetop;-webkit-animation-duration:0.4s;animation-name:animatetop;animation-duration:0.4s}@-webkit-keyframes animatetop{from{top:-300px;opacity:0}to{top:0;opacity:1}}@keyframes animatetop{from{top:-300px;opacity:0}to{top:0;opacity:1}}.close{color:white;font-weight:bold;}.close:hover,.close:focus{color:#000;text-decoration:none;cursor:pointer;}.modal-header{background-color:#5cb85c;color:white;}.modal-footer{background-color:#5cb85c;color:white;</style>';
			echo '<script>
                var e = `<div id="myModal" class="modal"><div class="modal-content"><div class="modal-header"> <i><span style="margin-left:90%;" class="close" id="close"> close </span></i> </div><div class="modal-body"><div id="mo_message"></div><div id="mo_validate_field" style="margin:1em"><input type="text" name="order_verify" autofocus="true" placeholder="" id="mo_otp_token" required="true" style="color: #000;font-family: Helvetica,sans-serif;padding: 7px;text-shadow: 1px 1px 0 #fff;width: 100%;border-radius: 2px;" class="mo_customer_validation-textbox" autofocus="true"/><input type="button" name="miniorange_otp_validate_submit"  style="margin-top:1%;width:100%" id="miniorange_otp_validate_submit" class="miniorange_otp_token_submit"  value="' . esc_attr( mo_( 'Validate OTP' ) ) . '" /></div></div></div></div>`;
                jQuery(`form[name="checkout"]`).append(e);
             </script>';
		}


		/**
		 * Show validation button or text on the checkout form based on the settings
		 * done by the Admin.
		 *
		 * @param object $checkout checkout check the value.
		 */
		public function my_custom_checkout_field( $checkout ) {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return;
			}
			if ( $this->guest_check_out_only && is_user_logged_in() ) {
				return;
			}
			echo '<div id="mo_validation_wrapper">';
			$this->show_validation_button_or_text();

			echo '<div id="mo_message" style="display:none;"></div>';

			woocommerce_form_field( // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				'order_verify',
				array(
					'type'        => 'text',
					'class'       => array( 'form-row-wide' ),
					'label'       => mo_( 'Verify Code' ),
					'required'    => true,
					'placeholder' => mo_( 'Enter Verification Code' ),
				),
				$checkout->get_value( 'order_verify' )
			);
			$this->place_after_validating_field();
			$this->common_button_or_link_enable_disable_script();
			echo ',$mo(".woocommerce-error").length>0&&$mo("html, body").animate({scrollTop:$mo("div.woocommerce").offset().top-50},1e3),$mo("#miniorange_otp_token_submit").click(function(o){var e=$mo("input[name=billing_email]").val(),n=$mo("#billing_phone").val(),a=$mo("div.woocommerce");a.addClass("processing").block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),$mo.ajax({url:"' . esc_url( site_url() ) . '/?option=miniorange-woocommerce-checkout",type:"POST",data:{user_email:e, user_phone:n},crossDomain:!0,dataType:"json",success:function(o){ if(o.result=="success"){$mo(".blockUI").hide(),$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").addClass("woocommerce-message").removeClass("woocommerce-error"),$mo("#mo_message").show()}else{$mo(".blockUI").hide(),$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").addClass("woocommerce-error"),$mo("#mo_message").show();} ;},error:function(o,e,n){}}),o.preventDefault()});});</script></div>';
		}


		/**
		 * Show Text link on the checkout form which user can click to start the
		 * OTP Verification process.
		 */
		private function show_validation_button_or_text() {
			if ( ! $this->show_button ) {
				$this->showTextLinkOnPage();
			}
			if ( $this->show_button ) {
				$this->mo_showButtonOnPage();
			}
		}

		/**
		 * Show Button on the checkout form which user can click to start the
		 * OTP Verification process.
		 */
		private function showTextLinkOnPage() {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				echo '<div style = "margin-bottom: 15px;" title="' . esc_attr( mo_( 'Please Enter a Phone Number to enable this link' ) ) . '">
                        <a  href="#" style="text-align:center;color:grey;pointer-events:initial;display:none;" 
                            id="miniorange_otp_token_submit" 
                            class="" >' . esc_html( mo_( $this->button_text ) ) . '
                        </a>
                   </div>';
			} else {
				echo '<div style = "margin-bottom: 15px;" title="' . esc_attr( mo_( 'Please Enter an Email Address to enable this link' ) ) . '">
                        <a  href="#" 
                            style="text-align:center;color:grey;pointer-events:initial;display:none;" 
                            id="miniorange_otp_token_submit" 
                            class="" >' . esc_html( mo_( $this->button_text ) ) . '
                        </a>
                   </div>';
			}
		}

		/**
		 * Show Button on the checkout form which user can click to start the
		 * OTP Verification process.
		 */
		private function mo_showButtonOnPage() {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				echo '<input type="button" class="button alt" style="'
				. ( $this->popup_enabled ? 'float: right;line-height: 1;margin-right: 2em;padding: 1em 2em; display:none;' : 'display:none;width:100%;margin-bottom: 15px;' )
				. '" id="miniorange_otp_token_submit" title="'
				. esc_attr( mo_( 'Please Enter a Phone Number to enable this.' ) ) . '" value="';
			} else {
				echo '<input type="button" class="button alt" style="'
				. ( $this->popup_enabled ? 'float: right;line-height: 1;margin-right: 2em;padding: 1em 2em; display:none;' : 'display:none;width:100%;margin-bottom: 15px;' )
				. '" id="miniorange_otp_token_submit" title="'
				. esc_attr( mo_( 'Please Enter an Email Address to enable this.' ) ) . '" value="';
			}
			echo esc_attr( mo_( $this->button_text ) ) . '"></input>';
		}


		/**
		 * Adds the necessary scripts on the checkout form for link based
		 * OTP Verification.
		 *
		 * @todo Make the script here more readable
		 */
		private function common_button_or_link_enable_disable_script() {
			echo '<script>jQuery(document).ready(function() { $mo = jQuery,';
			echo '$mo(".woocommerce-message").length>0&&($mo("#mo_message").addClass("woocommerce-message"),$mo("#mo_message").show())';
		}

		/**
		 * Add verification field after the the phone or email field.
		 */
		private function place_after_validating_field() {

			echo '<script>jQuery(document).ready(function(){
                    setTimeout(function(){
                        jQuery("#mo_validation_wrapper").insertAfter("#billing_' . ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phone' : 'email' ) . '_field");
                    },200);
        });</script>';
		}

		/**
		 * Process the checkout form being submitted. Validate if
		 * OTP has been sent and the form has been submitted with an OTP.
		 *
		 * @param array    $data   The data submitted by the form.
		 * @param WP_Error $errors Validation errors.
		 */
		public function my_custom_checkout_field_process( $data, $errors ) {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return;
			}
			if ( ! MoUtility::is_blank( $errors->get_error_messages() ) ) {
				return;
			}
			if ( $this->guest_check_out_only && is_user_logged_in() ) {
				return;
			}
			if ( ! $this->isPaymentVerificationNeeded() ) {
				return;
			}
			if ( $this->checkIfVerificationNotStarted() ) {
				return;
			}
			if ( $this->checkIfVerificationCodeNotEntered() ) {
				return;
			}
			$this->handle_otp_token_submitted();
		}


		/**
		 * Validate if the phone number or email otp was sent to and
		 * the phone number and email in the final submission are the
		 * same. If not then throw an error.
		 */
		private function handle_otp_token_submitted() {
			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$error = $this->process_phone_number();
			} else {
				$error = $this->processEmail();
			}
			if ( ! $error ) {
				$this->validate_challenge( $this->get_verification_type(), 'order_verify' );
			}
		}

		/**
		 * Checks if OTP Verification is enabled for the current.
		 */
		private function isPaymentVerificationNeeded() {
			$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			return $this->selective_payment ? array_key_exists( $payment_method, $this->payment_methods ) : true;
		}


		/**
		 * Check to see if email address OTP was sent to and the phone number
		 * submitted in the final form submission are the same.
		 *
		 * @return bool
		 */
		private function process_phone_number() {

			$phone = isset( $_POST['billing_phone'] ) ? MoUtility::process_phone_number( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			if ( strcasecmp( MoPHPSessions::get_session_var( 'phone_number_mo' ), $phone ) !== 0 ) {
				wc_add_notice( MoMessages::showMessage( MoMessages::PHONE_MISMATCH ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				return true;
			}
			return false;
		}


		/**
		 * Check to see if email address OTP was sent to and the phone number
		 * submitted in the final form submission are the same.
		 *
		 * @return bool
		 */
		private function processEmail() {
			$billing_email = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			if ( strcasecmp( MoPHPSessions::get_session_var( 'user_email' ), $billing_email ) !== 0 ) {
				wc_add_notice( MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ), MoConstants::ERROR_JSON_TYPE ); // phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
				return true;
			}
			return false;
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

			wc_add_notice( MoUtility::get_invalid_otp_method(), MoConstants::ERROR_JSON_TYPE ); //phpcs:ignore intelephense.diagnostics.undefinedFunctions -- Default function of WooCommerce.
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

			$this->unset_otp_session_variables();
			MoPHPSessions::unset_session( 'specialproductexist' );

		}


		/**
		 * This function is used to enqueue script on the frontend to facilitate
		 * OTP Verification for the FormCraft form. This function
		 * also localizes certain values required by the script.
		 */
		public function enqueue_script_on_page() {
			if ( file_exists( MOV_DIR . 'addons/wcselectedcategory' ) && MoPHPSessions::get_session_var( 'specialproductexist' ) !== 'true' ) {
				return;
			}
			$script_url = MOV_URL . 'includes/js/wccheckout.min.js?version=' . MOV_VERSION;
			wp_register_script( 'wccheckout', $script_url, array( 'jquery' ), MOV_VERSION, true );
			wp_localize_script(
				'wccheckout',
				'mowccheckout',
				array(
					'paymentMethods'          => $this->payment_methods,
					'selectivePaymentEnabled' => $this->selective_payment,
					'popupEnabled'            => $this->popup_enabled,
					'isLoggedIn'              => $this->guest_check_out_only && is_user_logged_in(),
					'otpType'                 => strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'phone' : 'email',
				)
			);
			wp_enqueue_script( 'wccheckout' );
		}


		/**
		 * Unset all the session variables so that a new form submission starts
		 * a fresh process of OTP verification.
		 */
		public function unset_otp_session_variables() {
			SessionUtils::unset_session( array( $this->tx_session_id, $this->form_session_var ) );
		}

		/**
		 * This function is called by the filter mo_phone_dropdown_selector
		 * to return the Jquery selector of the phone field. The function will
		 * push the formID to the selector array if OTP Verification for the
		 * form has been enabled.
		 *
		 * @param  array $selector the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && ( $this->otp_type === $this->type_phone_tag ) ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the woocommerce checkout form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) || ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->admin_nonce ) ) {
				return;
			}
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return;
			}

			$data            = MoUtility::mo_sanitize_array( $_POST );
			$payment_methods = array();
			if ( array_key_exists( 'wc_payment', $data ) ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
				foreach ( ( $data['wc_payment'] ) as $selected ) { //phpcs:ignore -- $data is an array but considered as a string (false positive).
					$payment_methods[ $selected ] = $selected;
				}
			}

			$this->is_form_enabled      = $this->sanitize_form_post( 'wc_checkout_enable' );
			$this->otp_type             = $this->sanitize_form_post( 'wc_checkout_type' );
			$this->guest_check_out_only = $this->sanitize_form_post( 'wc_checkout_guest' );
			$this->show_button          = $this->sanitize_form_post( 'wc_checkout_button' );
			$this->popup_enabled        = $this->sanitize_form_post( 'wc_checkout_popup' );
			$this->selective_payment    = $this->sanitize_form_post( 'wc_checkout_selective_payment' );
			$this->button_text          = $this->sanitize_form_post( 'wc_checkout_button_link_text' );
			$this->payment_methods      = $payment_methods;
			$this->disable_auto_login   = $this->sanitize_form_post( 'wc_checkout_disable_auto_login' );
			$this->restrict_duplicates  = $this->sanitize_form_post( 'wc_checkout_restrict_duplicates' );

			if ( $this->basic_validation_check( BaseMessages::WC_CHECKOUT_CHOOSE ) ) {
				update_mo_option( 'wc_checkout_restrict_duplicates', $this->restrict_duplicates );
				update_mo_option( 'wc_checkout_disable_auto_login', $this->disable_auto_login );
				update_mo_option( 'wc_checkout_enable', $this->is_form_enabled );
				update_mo_option( 'wc_checkout_type', $this->otp_type );
				update_mo_option( 'wc_checkout_guest', $this->guest_check_out_only );
				update_mo_option( 'wc_checkout_button', $this->show_button );
				update_mo_option( 'wc_checkout_popup', $this->popup_enabled );
				update_mo_option( 'wc_checkout_selective_payment', $this->selective_payment );
				update_mo_option( 'wc_checkout_button_link_text', $this->button_text );
				update_mo_option( 'wc_checkout_payment_type', maybe_serialize( $payment_methods ) );
			}
		}

		/*
		|--------------------------------------------------------------------------------------------------------
		| Getters
		|--------------------------------------------------------------------------------------------------------
		*/

		/**
		 * Getter for guest checkout option
		 *
		 * @return bool
		 */
		public function isGuestCheckoutOnlyEnabled() {
			return $this->guest_check_out_only; }

		/**
		 * Getter for showing button instead of text for checkout form
		 *
		 * @return bool
		 */
		public function showButtonInstead() {
			return $this->show_button; }

		/**
		 * Getter for is popup enabled for otp verification during wc checkout
		 *
		 * @return bool
		 */
		public function isPopUpEnabled() {
			return $this->popup_enabled; }

		/**
		 * Getter for payment methods for which OTP Verification has been enabled
		 *
		 * @return array
		 */
		public function getPaymentMethods() {
			return $this->payment_methods; }

		/**
		 * Getter for is selective payment enabled (based on payment methods)
		 *
		 * @return bool
		 */
		public function isSelectivePaymentEnabled() {
			return $this->selective_payment; }

		/**
		 * Getter for disable_auto_login
		 *
		 * @return bool
		 */
		public function isAutoLoginDisabled() {
			return $this->disable_auto_login; }
	}
}
