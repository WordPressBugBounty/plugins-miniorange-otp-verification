<?php
/**Load adminstrator changes for ErrorPopup
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
 * This is the Error Popup class. This class handles all the
 * functionality related to Error popup functionality of the plugin. It extends the Template
 * and implements the MoITemplate class to implement some much needed functions.
 */
if ( ! class_exists( 'ErrorPopup' ) ) {
	/**
	 * ErrorPopup class
	 */
	class ErrorPopup extends Template implements MoITemplate {

		use Instance;
		/**
		 * Constructor to declare variables of the class on initialization
		 **/
		protected function __construct() {
			$this->key                = 'ERROR';
			$this->template_editor_id = 'customEmailMsgEditor4';
			$this->required_tags      = array_diff( $this->required_tags, array( '{{FORM_ID}}', '{{REQUIRED_FIELDS}}' ) );
			parent::__construct();
		}

		/**
		 * Function to fetch the HTML body of the error pop-up template.
		 *
		 * @return string
		 */
		private function get_error_pop_up_html() {
			$pop_up_template =
			'<html><head><title></title><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" type="text/css" href="{{MO_CSS_URL}}">{{JQUERY}}</head><body><div class="mo-modal-backdrop"><div class="mo_customer_validation-modal mo-new-ui-modal" tabindex="-1" role="dialog" id="mo_site_otp_form"><div class="mo_customer_validation-modal-backdrop"></div><div class="mo_customer_validation-modal-dialog mo_customer_validation-modal-md"><div class="login mo_customer_validation-modal-content mo-new-ui-content"><div class="mo_customer_validation-modal-header mo-new-ui-header"><div class="mo-popup-header">{{HEADER}}</div><a href="#" onclick={{GO_BACK_ACTION_CALL}}><span class="mo-icon-button close mo-close-button-x">{{GO_BACK}}</span></a></div><div class="mo_customer_validation-modal-body center"><div>{{MESSAGE}}</div><br></div></div></div></div></div>{{REQUIRED_FORMS_SCRIPTS}}</body></html>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet --already enqued file.
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
		 * @note: The html content has been minified for public release
		 * @return array
		 */
		public function get_defaults( $templates ) {
			if ( ! is_array( $templates ) ) {
				$templates = array();
			}
			$pop_up_templates_request = $this->get_error_pop_up_html();

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
			$from_both        = $from_both ? 'true' : 'false';
			$required_scripts = $this->getRequiredFormsSkeleton( $otp_type, $from_both );

			$template = str_replace( '{{JQUERY}}', $this->jquery_url, $template );
			$template = str_replace( '{{GO_BACK_ACTION_CALL}}', 'mo_validation_goback();', $template );
			$template = str_replace( '{{MO_CSS_URL}}', MOV_CSS_URL, $template );
			$template = str_replace( '{{REQUIRED_FORMS_SCRIPTS}}', $required_scripts, $template );
			$template = str_replace( '{{HEADER}}', mo_( 'Validate OTP (One Time Passcode)' ), $template );
			$template = str_replace( '{{GO_BACK}}', mo_( 'X' ), $template );
			$template = str_replace( '{{MESSAGE}}', mo_( $message ), $template );
			return $template;
		}

		/**
		 * This function is used to replace the {{REQUIRED_FORMS_SCRIPTS}} in the template
		 * with the appropriate scripts and forms. These forms and scripts are required
		 * for the popup to work.
		 *
		 * @param string $otp_type the otp type invoked.
		 * @param string $from_both does user have the option to choose b/w email and sms verification.
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
		 *
		 * @return string
		 */
		private function getRequiredScripts() {
			$scripts  = '<style>.mo_customer_validation-modal{display:block!important}</style>';
			$scripts .= '<script>function mo_validation_goback(){document.getElementById("validation_goBack_form").submit()}</script>';
			return $scripts;
		}
	}
}
