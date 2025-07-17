<?php
/**
 * Load admin view for WordPressCommentsForm.
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
use OTP\Helper\MoUtility;
use OTP\Helper\SessionUtils;
use OTP\Objects\FormHandler;
use OTP\Objects\IFormHandler;
use OTP\Objects\VerificationType;
use OTP\Traits\Instance;
use ReflectionException;
use WP_Comment;

/**
 * This is the WordPress Comment form class. This class handles all the
 * functionality related to WordPress Comment. It extends the FormHandler
 * and implements the IFormHandler class to implement some much needed functions.
 */
if ( ! class_exists( 'WordPressComments' ) ) {
	/**
	 * WordPressComments class
	 */
	class WordPressComments extends FormHandler implements IFormHandler {

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->is_login_or_social_form = false;
			$this->is_ajax_form            = true;
			$this->form_session_var        = FormSessionVars::WPCOMMENT;
			$this->phone_form_id           = 'input[name=phone]';
			$this->form_key                = 'WPCOMMENT';
			$this->type_phone_tag          = 'mo_wpcomment_phone_enable';
			$this->type_email_tag          = 'mo_wpcomment_email_enable';
			$this->form_name               = mo_( 'WordPress Comment Form' );
			$this->is_form_enabled         = get_mo_option( 'wpcomment_enable' );
			$this->form_documents          = MoFormDocs::WP_COMMENT_LINK;
			parent::__construct();
		}

		/**
		 * Function checks if form has been enabled by the admin and initializes
		 * all the class variables. This function also defines all the hooks to
		 * hook into to make OTP Verification possible.
		 *
		 * @throws ReflectionException //.
		 */
		public function handle_form() {
			$this->otp_type      = get_mo_option( 'wpcomment_enable_type' );
			$this->by_pass_login = get_mo_option( 'wpcomment_enable_for_loggedin_users' );

			if ( ! $this->by_pass_login ) {
				add_action( 'comment_form_logged_in_after', array( $this, 'add_scripts_and_additional_fields' ), 1 );
				add_action( 'comment_form_after_fields', array( $this, 'add_scripts_and_additional_fields' ), 1 );
			} else {
				add_filter( 'comment_form_default_fields', array( $this, 'add_custom_fields' ), 99, 1 );
			}
			add_filter( 'preprocess_comment', array( $this, 'verify_comment_meta_data' ), 1, 1 );
			add_action( 'comment_post', array( $this, 'save_comment_meta_data' ), 1, 1 );
			add_action( 'add_meta_boxes_comment', array( $this, 'extend_comment_add_meta_box' ), 1, 1 );
			add_action( 'edit_comment', array( $this, 'extend_comment_edit_metafields' ), 1, 1 );

			$wp_comments_nonce = wp_create_nonce( 'wp_comments_nonce' );
			if ( ! wp_verify_nonce( $wp_comments_nonce, 'wp_comments_nonce' ) === 1 ) {
				return;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			$get_data  = MoUtility::mo_sanitize_array( $_GET );

			$this->routeData( $post_data, $get_data );
		}


		/**
		 * Checks whether option parameter is present in $_GET and start the OTP Verification process.
		 *
		 * @param array $post_data - $_POST.
		 * @param array $get_data - $_GET.
		 * @throws ReflectionException //.
		 */
		private function routeData( $post_data, $get_data ) {
			if ( ! array_key_exists( 'option', $get_data ) ) {
				return;
			}

			switch ( trim( sanitize_text_field( $get_data['option'] ) ) ) {
				case 'mo-comments-verify':
					$this->startOTPVerificationProcess( $post_data );
					break;
			}
		}


		/**
		 * This function is called to process and start the otp verification process.
		 * It's called when user clicks on the Send OTP button on the comment form. Data is
		 * passed using an AJAX call.
		 *
		 * @param array $get_data - the data being passed in the ajax call.
		 * @throws ReflectionException // .
		 */
		private function startOTPVerificationProcess( $get_data ) {

			MoUtility::initialize_transaction( $this->form_session_var );
			$user_email = is_email( $get_data['user_email'] ) ? sanitize_email( $get_data['user_email'] ) : sanitize_text_field( $get_data['user_email'] );

			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 && MoUtility::sanitize_check( 'user_email', $get_data ) ) {
				SessionUtils::add_email_verified( $this->form_session_var, $user_email );
				$this->send_challenge( '', $user_email, null, '', VerificationType::EMAIL );
			} elseif ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 && MoUtility::sanitize_check( 'user_phone', $get_data ) ) {
				SessionUtils::add_phone_verified( $this->form_session_var, trim( sanitize_text_field( $get_data['user_phone'] ) ) );
				$this->send_challenge( '', '', null, trim( sanitize_text_field( $get_data['user_phone'] ) ), VerificationType::PHONE );
			} else {
				$message = strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0
						? MoMessages::showMessage( MoMessages::ENTER_PHONE ) : MoMessages::showMessage( MoMessages::ENTER_EMAIL );
				wp_send_json( MoUtility::create_json( $message, MoConstants::ERROR_JSON_TYPE ) );
			}
		}


		/**
		 * This function hooks into the edit_comment hook and is used to update any
		 * existing meta value for a specific comment. update_comment_meta or delete_comment_meta
		 * functions are used to update or delete the existing metadata related to a comment field.
		 * In this case we are updating or deleting the phone number based on users/admin's update.
		 *
		 * @param string $comment_id - the id of the comment.
		 */
		public function extend_comment_edit_metafields( $comment_id ) {
			$wp_comments_nonce = wp_create_nonce( 'wp_comments_nonce' );
			if ( ! wp_verify_nonce( $wp_comments_nonce, 'wp_comments_nonce' ) ) {
				return;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			if ( ! isset( $post_data['extend_comment_update'] )
			|| ! wp_verify_nonce( sanitize_text_field( $post_data['extend_comment_update'] ), 'extend_comment_update' ) ) {
				return;
			}

			if ( ( isset( $post_data['phone'] ) ) && ( sanitize_text_field( $post_data['phone'] ) !== '' ) ) {
				$phone = sanitize_text_field( $post_data['phone'] );
				$phone = wp_filter_nohtml_kses( $phone );
				update_comment_meta( $comment_id, 'phone', $phone );
			} else {
				delete_comment_meta( $comment_id, 'phone' );
			}
		}


		/**
		 * This hooks into the add_meta_boxes_comment action to add a meta box
		 * to the comment edit page. add_meta_boxes_comment hook is fired when
		 * comment specific meta boxes are added. add_meta_box adds meta box
		 * to one or more screens
		 */
		public function extend_comment_add_meta_box() {
			add_meta_box(
				'title',
				mo_( 'Extra Fields' ),
				array( $this, 'extend_comment_meta_box' ),
				'comment',
				'normal',
				'high'
			);
		}


		/**
		 * This is the call back function of our meta_box that was added. This function
		 * is used to add the Phone field and the value stored in the database for that
		 * comment to be shown on the meta_box that was added.
		 *
		 * @param WP_Comment $comment - the coment object related to the specific comment being edited.
		 */
		private function extend_comment_meta_box( $comment ) {
			$phone = get_comment_meta( $comment->comment_ID, 'phone', true );
			wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false );

			echo '<table class="form-table editcomment">
                <tbody>
                <tr>
                    <td class="first"><label for="phone">' . esc_html( mo_( 'Phone' ) ) . ':</label></td>
                    <td><input type="text" name="phone" size="30" value="' . esc_attr( $phone ) . '" id="phone"></td>
                </tr>
                </tbody>
            </table>';
		}


		/**
		 * This function hooks into the preprocess_comment filter which can be used to
		 * validate or verify the comment posted before it is saved in the database.
		 * It's being used here to validate the OTP entered by the user.
		 *
		 * @param  array $commentdata - the comment posted by the user.
		 * @return array
		 */
		public function verify_comment_meta_data( $commentdata ) {
			if ( isset( $_POST['_wp_unfiltered_html_comment'] ) && isset( $_POST['comment_post_ID'] ) ) { // phpcs:ignore -- false positive.
				if ( ! wp_verify_nonce( sanitize_key( $_POST['_wp_unfiltered_html_comment'] ), 'unfiltered-html-comment_' . sanitize_text_field( wp_unslash( $_POST['comment_post_ID'] ) ) ) ) { // phpcs:ignore -- false positive.
					return;
				}
			}
			$post_data    = MoUtility::mo_sanitize_array( $_POST );
			$otp_ver_type = $this->get_verification_type();
			if ( $this->by_pass_login && is_user_logged_in() ) {
				return $commentdata;
			}
			if ( ! isset( $post_data['phone'] ) && strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				wp_die( '<strong>Error:</strong> ' . esc_attr( MoMessages::showMessage( MoMessages::WPCOMMNENT_PHONE_ENTER ) ), 'Comment Submission Failure', array( 'back_link' => true ) );
			}
			if ( ! isset( $post_data['verifyotp'] ) ) {
				wp_die( '<strong>Error:</strong> ' . esc_attr( MoMessages::showMessage( MoMessages::WPCOMMNENT_VERIFY_ENTER ) ), 'Comment Submission Failure', array( 'back_link' => true ) );
			}
			if ( ! SessionUtils::is_otp_initialized( $this->form_session_var ) ) {
				wp_die( '<strong>Error:</strong> ' . esc_attr( MoMessages::showMessage( MoMessages::PLEASE_VALIDATE ) ), 'Comment Submission Failure', array( 'back_link' => true ) );
			}
			if ( VerificationType::EMAIL === $otp_ver_type
				&& ! SessionUtils::is_email_verified_match( $this->form_session_var, sanitize_email( $post_data['email'] ) ) ) {
				wp_die( '<strong>Error:</strong> ' . esc_attr( MoMessages::showMessage( MoMessages::EMAIL_MISMATCH ) ), 'Comment Submission Failure', array( 'back_link' => true ) );
			}
			if ( VerificationType::PHONE === $otp_ver_type
				&& ! SessionUtils::is_phone_verified_match( $this->form_session_var, sanitize_text_field( $post_data['phone'] ) ) ) {
				wp_die( '<strong>Error:</strong> ' . esc_attr( MoMessages::showMessage( MoMessages::PHONE_MISMATCH ) ), 'Comment Submission Failure', array( 'back_link' => true ) );
			}
			$this->validate_challenge( $otp_ver_type, null, sanitize_text_field( $post_data['verifyotp'] ) );
			return $commentdata;
		}


		/**
		 * This function hooks into the comment_form_logged_in_after and comment_form_after_fields
		 * hook to add the necessary fields on the comment page or OTP Verification. This is similar
		 * to the add_custom_fields function but here we are echoing the fields rather than adding
		 * to a field variable.
		 */
		public function add_scripts_and_additional_fields() {
			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				echo wp_kses( $this->getFieldHTML( 'email' ), MoUtility::mo_allow_html_array() );
			}

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				echo wp_kses( $this->getFieldHTML( 'phone' ), MoUtility::mo_allow_html_array() );
			}

			echo wp_kses( $this->getFieldHTML( 'verifyotp' ), MoUtility::mo_allow_html_array() );
		}


		/**
		 * This function is used to add extra fields and html content to the comments section.
		 *
		 * @param string[] $fields the array of fields and their respective HTML content.
		 * @return string[]
		 */
		public function add_custom_fields( $fields ) {

			if ( strcasecmp( $this->otp_type, $this->type_email_tag ) === 0 ) {
				$fields['email'] = $this->getFieldHTML( 'email' );
			}

			if ( strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ) {
				$fields['phone'] = $this->getFieldHTML( 'phone' );
			}

			$fields['verifyotp'] = $this->getFieldHTML( 'verifyotp' );
			return $fields;
		}


		/**
		 * This function return the field HTML to be added
		 *
		 * @param string $field_name  - field name i.e phone, email or verification code.
		 * @return string  - HTML code for the field
		 */
		private function getFieldHTML( $field_name ) {
			$field_html = array(
				'email'     => (
					! is_user_logged_in() && ! $this->by_pass_login ? '' :
					'<p class="comment-form-email">'
						. '<label for="email">' . mo_( 'Email *' ) . '</label>'
						. '<input id="email" name="email" type="text" size="30"  tabindex="4" />'
					. '</p>'
				)
				. $this->get_otp_html_content( 'email' ),

				'phone'     => '<p class="comment-form-email">'
								. '<label for="phone">' . mo_( 'Phone *' ) . '</label>'
								. '<input id="phone" name="phone" type="text" size="30"  tabindex="4" />'
							. '</p>'
							. $this->get_otp_html_content( 'phone' ),

				'verifyotp' => '<p class="comment-form-email">' .
									'<label for="verifyotp">' . mo_( 'Verification Code' ) . '</label>' .
									'<input id="verifyotp" name="verifyotp" type="text" size="30"  tabindex="4" />'
								. '</p><br>',
			);

			return $field_html[ $field_name ];
		}


		/**
		 * This function is used to add the verification button, message div and the scripts
		 * required to make OTP Verification possible for the comment section.
		 *
		 * @param  [type] $id - phone / email.
		 * @return string
		 */
		private function get_otp_html_content( $id ) {
			$img = "<div class= 'moloader'></div>";

			$html  = '<div style="margin-bottom:3%"><input type="button" class="button alt" style="width:100%" id="miniorange_otp_token_submit" ';
			$html .= strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'title="Please Enter a phone number to enable this." '
																	: 'title="Please Enter a email number to enable this." ';
			$html .= strcasecmp( $this->otp_type, $this->type_phone_tag ) === 0 ? 'value="Click here to verify your Phone">'
																	: 'value="Click here to verify your Email">';
			$html .= '<div id="mo_message" hidden="" style="background-color: #f7f6f7;padding: 1em 2em 1em 3.5em;"></div></div>';

			$html .= '<script>jQuery(document).ready(function(){$mo=jQuery;$mo("#miniorange_otp_token_submit").click(function(o){';
			$html .= 'var e=$mo("input[name=' . $id . ']").val();$mo("#mo_message").empty(),$mo("#mo_message").append("' . $img . '"),';
			$html .= '$mo("#mo_message").show(),$mo.ajax({url:"' . site_url() . '/?option=mo-comments-verify",type:"POST",';
			$html .= 'data:{user_phone:e,user_email:e},crossDomain:!0,dataType:"json",success:function(o){ if(o.result==="success"){';
			$html .= '$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),$mo("#mo_message").css("border-top","3px solid green"),';
			$html .= '$mo("input[name=email_verify]").focus()}else{$mo("#mo_message").empty(),$mo("#mo_message").append(o.message),';
			$html .= '$mo("#mo_message").css("border-top","3px solid red"),$mo("input[name=phone_verify]").focus()} ;},';
			$html .= 'error:function(o,e,n){}})});});</script>';
			return $html;
		}


		/**
		 * This function hooks into the comment_post hook. It is used to save
		 * the phone number fields in the database for the comment posted by the
		 * user.
		 *
		 * @param  [type] $comment_id - id of the comment posted and saved in the database.
		 */
		public function save_comment_meta_data( $comment_id ) {
			$wp_comments_nonce = wp_create_nonce( 'wp_comments_nonce' );
			if ( ! wp_verify_nonce( $wp_comments_nonce, 'wp_comments_nonce' ) ) {
				return;
			}
			$post_data = MoUtility::mo_sanitize_array( $_POST );
			if ( ( isset( $post_data['phone'] ) ) && ( sanitize_text_field( $post_data['phone'] ) !== '' ) ) {
				$phone = sanitize_text_field( $post_data['phone'] );
				$phone = wp_filter_nohtml_kses( $phone );
				add_comment_meta( $comment_id, 'phone', $phone );
			}
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

			wp_die( '<strong>Error:</strong> ' . esc_attr( MoUtility::get_invalid_otp_method() ), 'Comment Submission Failure', array( 'back_link' => true ) );
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
		 * @param  array $selector - the Jquery selector to be modified.
		 * @return array
		 */
		public function get_phone_number_selector( $selector ) {

			if ( $this->is_form_enabled() && $this->otp_type === $this->type_phone_tag ) {
				array_push( $selector, $this->phone_form_id );
			}
			return $selector;
		}


		/**
		 * Handles saving all the WordPress Comment form related options by the admin.
		 */
		public function handle_form_options() {
			if ( ! MoUtility::are_form_options_being_saved( $this->get_form_option() ) ) {
				return;
			}

			$this->is_form_enabled = $this->sanitize_form_post( 'wpcomment_enable' );
			$this->otp_type        = $this->sanitize_form_post( 'wpcomment_enable_type' );
			$this->by_pass_login   = $this->sanitize_form_post( 'wpcomment_enable_for_loggedin_users' );

			update_mo_option( 'wpcomment_enable', $this->is_form_enabled );
			update_mo_option( 'wpcomment_enable_type', $this->otp_type );
			update_mo_option( 'wpcomment_enable_for_loggedin_users', $this->by_pass_login );
		}
	}
}
