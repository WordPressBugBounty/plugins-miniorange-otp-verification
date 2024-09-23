<?php
/**Load adminstrator changes for ExternalPopup
 *
 * @package miniorange-otp-verification/helper/templates
 */

namespace OTP\Helper\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Objects\MoITemplate;
use OTP\Objects\Template;
use OTP\Traits\Instance;

/**
 * This is the External Popup class. This class handles all the
 * functionality related to External popup functionality of the plugin. It extends the Template
 * and implements the MoITemplate class to implement some much needed functions.
 */
if ( ! class_exists( 'ExternalPopup' ) ) {
	/**
	 * ExternalPopup class
	 */
	class ExternalPopup extends Template implements MoITemplate {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->key                = 'EXTERNAL';
			$this->template_editor_id = 'customEmailMsgEditor3';
			$this->required_tags      = array_merge(
				$this->required_tags,
				array(
					'{{PHONE_FIELD_NAME}}',
					'{{SEND_OTP_BTN_ID}}',
					'{{VERIFICATION_FIELD_NAME}}',
					'{{VALIDATE_BTN_ID}}',
					'{{SEND_OTP_BTN_ID}}',
					'{{VERIFY_CODE_BOX}}',
				)
			);
			parent::__construct();
		}

		/**
		 * Function to fetch the HTML body of the external pop-up template.
		 *
		 * @return string
		 */
		private function get_external_pop_up_html() {
			$pop_up_template =
			'<html><head><title></title><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" type="text/css" href="{{MO_CSS_URL}}">{{JQUERY}}</head><body><div class="mo-modal-backdrop"><div class="mo_customer_validation-modal mo-new-ui-modal" tabindex="-1" role="dialog" id="mo_site_otp_form"><div class="mo_customer_validation-modal-backdrop"></div><div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md"><div class="login mo_customer_validation-modal-content mo-new-ui-content"><div class="mo_customer_validation-modal-header mo-new-ui-header"><div class="mo-popup-header">{{HEADER}}</div><a href="#" onclick={{GO_BACK_ACTION_CALL}}><span class="mo-icon-button close mo-close-button-x">{{GO_BACK}}</span></a></div><div class="mo_customer_validation-modal-body center"><div>{{MESSAGE}}</div><br><div class="mo_customer_validation-login-container"><form id="{{FORM_ID}}" name="f" method="post" action="">{{REQUIRED_FIELDS}} <input type="text" name="{{PHONE_FIELD_NAME}}" autofocus placeholder="" id="{{PHONE_FIELD_NAME}}" required class="mo_customer_validation-textbox mo-new-ui-validation-textbox" autofocus pattern="^[\+]\d{1,4}\d{7,12}$|^[\+]\d{1,4}[\s]\d{7,12}$" title="{{PHONE_NUMBER_TITLE}}"><div id="{{OTP_MESSAGE_BOX}}" hidden style="background-color:#f7f6f7;padding:1em 2em 1em 1.5em;color:#000"></div><br><div id="{{VERIFY_CODE_BOX}}" hidden>Verify Code: <input type="text" name="{{VERIFICATION_FIELD_NAME}}" autofocus placeholder="" id="{{VERIFICATION_FIELD_NAME}}" required class="mo_customer_validation-textbox mo-new-ui-validation-textbox"></div><div class="mo-flex-space-between"><input type="button" hidden id="{{VALIDATE_BTN_ID}}" name="otp_token_submit" class="miniorange_otp_token_submit mo-new-ui-submit" value="{{VALIDATE_BUTTON_TEXT}}"> <input type="button" id="{{SEND_OTP_BTN_ID}}" class="miniorange_otp_token_submit " value="{{SEND_OTP_TEXT}}"></div> {{EXTRA_POST_DATA}}</form></div></div></div></div></div></div>{{REQUIRED_FORMS_SCRIPTS}}</body></html>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet --already enqued file.
			return $pop_up_template;
		}

		/**
		 * This function initializes the default HTML of the PopUp Template
		 * to be used by the plugin. This function is called only during
		 * plugin activation or when user resets the templates. In Both
		 * cases the plugin initializes the template to the default value
		 * that the plugin ships with.
		 *
		 * @param array $templates - the template string to be parsed.
		 *
		 * @note: The html content has been minified Check helper/templates/templates.html
		 * @return array
		 */
		public function get_defaults( $templates ) {
			if ( ! is_array( $templates ) ) {
				$templates = array();
			}

			$pop_up_templates_request = $this->get_external_pop_up_html();

			if ( is_wp_error( $pop_up_templates_request ) ) {
				return $templates;
			}
			$templates[ $this->get_template_key() ] = $pop_up_templates_request;
			return $templates;
		}
		/**
		 * This function is used to parse the template and replace the
		 * tags with the appropriate content. Some of the contents are
		 * not shown if the admin/user is just previewing the pop-up.
		 *
		 * @param string $template the HTML Template.
		 * @param string $message the message to be show in the popup.
		 * @param string $otp_type the otp type invoked.
		 * @param string $from_both does user have the option to choose b/w email and sms verification.
		 * @return mixed|string
		 */
		public function parse( $template, $message, $otp_type, $from_both ) {
			$required_scripts   = $this->getRequiredScripts();
			$extra_post_data    = $this->preview ? '' : extra_post_data();
			$extra_form_fields  = '<input type="hidden" name="mo_external_popup_option" value="mo_ajax_form_validate" />';
			$extra_form_fields .= '<input type="hidden" id="mopopup_wpnonce" name="mopopup_wpnonce" value="' . wp_create_nonce( $this->nonce ) . '"/>';

			$template  = str_replace( '{{JQUERY}}', $this->jquery_url, $template );
			$template  = str_replace( '{{FORM_ID}}', 'mo_validate_form', $template );
			$template  = str_replace( '{{GO_BACK_ACTION_CALL}}', 'mo_validation_goback();', $template );
			$template  = str_replace( '{{MO_CSS_URL}}', MOV_CSS_URL, $template );
			$template  = str_replace( '{{OTP_MESSAGE_BOX}}', 'mo_message', $template );
			$template  = str_replace( '{{REQUIRED_FORMS_SCRIPTS}}', $required_scripts, $template );
			$template  = str_replace( '{{HEADER}}', mo_( 'Validate OTP (One Time Passcode)' ), $template );
			$template  = str_replace( '{{GO_BACK}}', mo_( 'X' ), $template );
			$template  = str_replace( '{{MESSAGE}}', mo_( $message ), $template );
			$template  = str_replace( '{{REQUIRED_FIELDS}}', $extra_form_fields, $template );
			$template  = str_replace( '{{PHONE_FIELD_NAME}}', 'mo_phone_number', $template );
			$template  = str_replace( '{{OTP_FIELD_TITLE}}', mo_( 'Enter Code' ), $template );
			$template  = str_replace( '{{VERIFY_CODE_BOX}}', 'mo_validate_otp', $template );
			$template  = str_replace( '{{VERIFICATION_FIELD_NAME}}', 'mo_otp_token', $template );
			$template  = str_replace( '{{VALIDATE_BTN_ID}}', 'validate_otp', $template );
			$template  = str_replace( '{{VALIDATE_BUTTON_TEXT}}', mo_( 'Validate' ), $template );
			$template  = str_replace( '{{SEND_OTP_TEXT}}', mo_( 'Send OTP' ), $template );
			$template  = str_replace( '{{SEND_OTP_BTN_ID}}', 'send_otp', $template );
			$template  = str_replace( '{{EXTRA_POST_DATA}}', $extra_post_data, $template );
			$template .= $this->getExtraFormFields();

			return $template;
		}

		/**
		 * Returns necessary form elements for the template.
		 * Includes mostly hidden forms/fields.
		 *
		 * @return string
		 */
		private function getExtraFormFields() {
			$ffields = '<form name="f" method="post" action="" id="validation_goBack_form">
                        <input id="validation_goBack" name="option" value="validation_goBack" type="hidden"/>
                    </form>';
			return $ffields;
		}

		/**
		 * This function is used to replace the {{SCRIPTS}} in the template
		 * with the appropriate scripts. These scripts are required
		 * for the popup to work. Scripts are not added if the form is in
		 * preview mode.
		 */
		private function getRequiredScripts() {
			$scripts = '<style>.mo_customer_validation-modal{display:block!important}</style>';
			if ( ! $this->preview ) {
				$scripts .=
				'<script>function mo_validation_goback(){
               document.getElementById("validation_goBack_form").submit()};' .
					'jQuery(document).ready(function(){' .
						'$mo=jQuery,' .
						'$mo("#send_otp").click(function(o){' .
							'var e=$mo("input[name=mo_phone_number]").val();' .
							'var n = $mo("input[name=mopopup_wpnonce]").val();' .
							'$mo("#mo_message").empty(),' .
							'$mo("#mo_message").append("' . $this->img . '"),' .
							'$mo("#mo_message").show(),' .
							'$mo.ajax({' .
								'url:"' . site_url() . '/?mo_external_popup_option=miniorange-ajax-otp-generate",' .
								'type:"POST",' .
								'data:{user_phone:e,mopopup_wpnonce:n},' .
								'crossDomain:!0,' .
								'dataType:"json",
                                success:function(o){' .
									'"success"==o.result?(' .
										'$mo("#mo_message").empty(),' .
										'$mo("#mo_message").append(o.message),' .
										'$mo("#mo_message").css("background-color","#8eed8e"),' .
										'$mo("#validate_otp").show(),' .
										'$mo("#send_otp").val("' . mo_( 'Resend OTP' ) . '"),' .
										'$mo("#mo_validate_otp").show(),' .
										'$mo("input[name=mo_validate_otp]").focus()' .
									'):(' .
										'$mo("#mo_message").empty(),' .
										'$mo("#mo_message").append(o.message),' .
										'$mo("#mo_message").css("background-color","#eda58e"),' .
										'$mo("input[name=mo_phone_number]").focus()' .
									')' .
								'},' .
								'error:function(o,e,m){}' .
							'})' .
						'}),' .
						'$mo("#validate_otp").click(function(o){' .
							'var e=$mo("input[name=mo_otp_token]").val(),' .
							'm=$mo("input[name=mo_phone_number]").val();' .
							'n=$mo("input[name=mopopup_wpnonce]").val();' .
							'$mo("#mo_message").empty(),' .
							'$mo("#mo_message").append("' . $this->img . '"),' .
							'$mo("#mo_message").show(),' .
							'$mo.ajax({' .
								'url:"' . site_url() . '/?mo_external_popup_option=miniorange-ajax-otp-validate",' .
								'type:"POST",' .
								'data:{mo_otp_token:e,user_phone:m,mopopup_wpnonce:n},' .
								'crossDomain:!0,' .
								'dataType:"json",' .
								'success:function(o){' .
									'"success"==o.result?(' .
										'$mo("#mo_message").empty(),' .
										'$mo("#mo_validate_form").submit()' .
									'):(' .
										'$mo("#mo_message").empty(),' .
										'$mo("#mo_message").append(o.message),' .
										'$mo("#mo_message").css("background-color","#eda58e"),' .
										'$mo("input[name=validate_otp]").focus()' .
									')' .
								'},' .
								'error:function(o,e,m){}' .
							'})' .
						'})' .
					'});' .
				'</script>';
			} else {
				$scripts .= '<script>' .
							'$mo=jQuery,' .
							'$mo("#mo_validate_form").submit(function(e){' .
								'e.preventDefault();' .
							'});' .
						'</script>';
			}
			return $scripts;
		}
	}
}
