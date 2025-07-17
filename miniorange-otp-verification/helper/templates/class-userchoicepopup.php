<?php
/**Load adminstrator changes for UserChoicePopup
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
 * This is the UserChoice Popup class. This class handles all the
 * functionality related to UserChoice popup functionality of the plugin. It extends the Template
 * and implements the MoITemplate class to implement some much needed functions.
 */
if ( ! class_exists( 'UserChoicePopup' ) ) {
	/**
	 * UserChoicePopup class
	 */
	class UserChoicePopup extends Template implements MoITemplate {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->key                = 'USERCHOICE';
			$this->template_editor_id = 'customEmailMsgEditor2';
			parent::__construct();
		}

		/**
		 * Function to fetch the HTML body of the user-choice pop-up template.
		 *
		 * @return string
		 */
		private function get_user_choice_pop_up_html() {
			$pop_up_template =
			'<head><title></title><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" type="text/css" href="{{MO_CSS_URL}}">{{JQUERY}}</head><body><div class="mo-modal-backdrop"><div class="mo_customer_validation-modal mo-new-ui-modal" tabindex="-1" role="dialog" id="mo_site_otp_form"><div class="mo_customer_validation-modal-backdrop"></div><div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md"><div class="login mo_customer_validation-modal-content mo-new-ui-content"><div class="mo_customer_validation-modal-header mo-new-ui-header"><div class="mo-popup-header">{{HEADER}}</div><a href="#" onclick={{GO_BACK_ACTION_CALL}}><span class="mo-icon-button close mo-close-button-x">{{GO_BACK}}</span></a></div><div class="mo_customer_validation-modal-body center"><div>{{MESSAGE}}</div><br><div class="mo_customer_validation-login-container"><form id="{{FORM_ID}}" name="f" method="post" action=""><div class="mo-flex-space-around"><button class="mo-svg-button" id="mo_user_email_verification"><svg width="50" height="50" viewBox="0 0 24 24" fill="none"><path d="M22 7V17C22 19.2091 20.2091 21 18 21H6C3.79086 21 2 19.2091 2 17V7M22 7C22 4.79086 20.2091 3 18 3H6C3.79086 3 2 4.79086 2 7M22 7L14.5166 10.386C14.3184 10.4757 14.1299 10.5854 13.9397 10.6909C12.7341 11.3598 11.2659 11.3598 10.0603 10.6909C9.87009 10.5854 9.68159 10.4757 9.48336 10.386L2 7" stroke="#6D6D6D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg><div style="padding-top: 6%">Email Verification</div></button><button class="mo-svg-button"><svg width="50" height="50" viewBox="0 0 24 24" fill="none"><rect x="5" y="2" width="14" height="20" rx="3" stroke="#6D6D6D" stroke-width="1.5" stroke-linejoin="round"/><path d="M11 19.5H12.5M6 17H12H18" stroke="#6D6D6D" stroke-width="1.5" stroke-linecap="round"/></svg><div style="padding-top: 6%">Phone Verification</div></button></div><input type="hidden" name="mo_customer_validation_otp_choice" id="otpChoice" value="">{{REQUIRED_FIELDS}}</form></div></div></div></div></div></div>{{REQUIRED_FORMS_SCRIPTS}}</body></html>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet --already enqued file.
			return $pop_up_template;
		}

		/**
		 * This function initializes the default HTML of the PopUp Template
		 * to be used by the plugin. This function is called only during
		 * plugin activation or when user resets the templates. In Both
		 * cases the plugin initializes the template to the default value
		 * that the plugin ships with.
		 *
		 * @param string $templates - the template string to be parsed.
		 *
		 * @note: The html content has been minified Check helper/templates/templates.html
		 * @return array
		 */
		public function get_defaults( $templates ) {
			if ( ! is_array( $templates ) ) {
				$templates = array();
			}
			$pop_up_templates_request = $this->get_user_choice_pop_up_html();

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
			$required_scripts   = $this->getRequiredFormsSkeleton( $otp_type, $from_both );
			$extra_post_data    = $this->preview ? '' : extra_post_data();
			$extra_form_fields  = '{{EXTRA_POST_DATA}}<input type="hidden" name="option" value="miniorange-validate-otp-choice-form" />';
			$extra_form_fields .= '<input type="hidden" id="mopopup_wpnonce" name="mopopup_wpnonce" value="' . wp_create_nonce( $this->nonce ) . '"/>';

			$template = str_replace( '{{JQUERY}}', $this->jquery_url, $template );
			$template = str_replace( '{{FORM_ID}}', 'mo_validate_form', $template );
			$template = str_replace( '{{GO_BACK_ACTION_CALL}}', 'mo_validation_goback();', $template );
			$template = str_replace( '{{MO_CSS_URL}}', MOV_CSS_URL, $template );
			$template = str_replace( '{{REQUIRED_FORMS_SCRIPTS}}', $required_scripts, $template );
			$template = str_replace( '{{HEADER}}', mo_( 'Validate OTP (One Time Passcode)' ), $template );
			$template = str_replace( '{{GO_BACK}}', mo_( 'X' ), $template );
			$template = str_replace( '{{MESSAGE}}', mo_( $message ), $template );
			$template = str_replace( '{{BUTTON_TEXT}}', mo_( 'Send OTP' ), $template );
			$template = str_replace( '{{REQUIRED_FIELDS}}', $extra_form_fields, $template );
			$template = str_replace( '{{EXTRA_POST_DATA}}', $extra_post_data, $template );
			return $template;
		}

		/**
		 * This function is used to replace the {{REQUIRED_FORMS_SCRIPTS}} in the template
		 * with the appropriate scripts and forms. These forms and scripts are required
		 * for the popup to work.
		 *
		 * @param string $otp_type - the otp type invoked.
		 * @param string $from_both - does user have the option to choose b/w email and sms verification.
		 * @return mixed|string
		 */
		private function getRequiredFormsSkeleton( $otp_type, $from_both ) {
			$required_fields = '<form name="f" method="post" action="" id="validation_goBack_form">
				<input id="validation_goBack" name="option" value="validation_goBack" type="hidden"/>
			</form>{{SCRIPTS}}';
			$required_fields = str_replace( '{{SCRIPTS}}', $this->getRequiredScripts(), $required_fields );
			return $required_fields;
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
				$scripts .= '<script>
								function mo_validation_goback(){
									document.getElementById("validation_goBack_form").submit();
								}
								document.getElementById("mo_user_email_verification").addEventListener("click", function() {
									document.getElementById("otpChoice").value = "user_email_verification";
								});
							</script>';
			} else {
				$scripts .= '<script>$mo=jQuery;$mo("#mo_validate_form").submit(function(e){e.preventDefault();});</script>';
			}
			return $scripts;
		}
	}
}
