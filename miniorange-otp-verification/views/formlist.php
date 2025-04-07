<?php
/**
 * Load admin view for header of forms.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;

$class_name  = 'YourOwnForm';
$class_name  = $class_name . '#' . $class_name;
$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
$url         = add_query_arg(
	array(
		'page' => 'mosettings',
		'form' => $class_name,
	),
	$request_uri
);

echo '			<div class="mo-form-list-container"	id="form_search" style = "' . ( $form_name ? 'display:none;' : '' ) . '">
					<div class="w-full flex gap-mo-8 px-mo-8 pt-mo-4">
								<p class="text-lg flex-1 font-medium pr-mo-44 py-mo-1">
								    ' . esc_html( mo_( 'Select Your Form From The List Below' ) ) . ':</p>';
echo '							<div class="flex flex-2 gap-mo-8">
									<span>
							            <a  class="mo-button medium secondary" 
                                            href="' . esc_url( $moaction ) . '">
                                            ' . esc_html( mo_( 'Active Forms' ) ) . '
                                        </a>
                                    </span>
									<span>
							            <a  class="mo-button medium inverted"  target = "_blank"
                                            href="' . esc_url( 'https://plugins.miniorange.com/step-by-step-guide-for-wordpress-otp-verification' ) . '">
                                            ' . esc_html( mo_( 'Plugin Set up guide' ) ) . '
                                        </a>
                                    </span>
								</div>    
							</div>';
echo '                     	
							<div class="py-mo-4 px-mo-8 text-mo-lg"><b>' . esc_html( MoMessages::showMessage( MoMessages::FORM_NOT_FOUND ) ) . '</b>';
echo '                          	<span class="tooltip">
									<span class="dashicons dashicons-editor-help"></span>
									<span class="tooltiptext">
										<span class="header"><b><i>' . esc_html( MoMessages::showMessage( MoMessages::FORM_NOT_AVAIL_HEAD ) ) . '</i></b></span><br/><br/>
										<span class="body">' . wp_kses( MoMessages::showMessage( MoMessages::FORM_NOT_AVAIL_BODY ), MoUtility::mo_allow_html_array() ) . '</span>

										</span>
								</span>
							</div>';
							get_otp_verification_form_dropdown();
echo '
				</div>';
