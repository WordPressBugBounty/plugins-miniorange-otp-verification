<?php
/**
 * Load admin view for Feedback Pop Up.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '
			<div class="mo_deactivation_popup_container" id="mo_otp_feedback_modal"  style="display: none;" >
				<div id="mo_deactivation_popup_wrapper" class="mo_deactivation_popup_wrapper" tabindex="-1" role="dialog" >
							<div class="mo-header" >
								<h4 class="font-bold grow" >Feedback</h4>
								<a class ="mo-button secondary" id="mo_feedback_cancel_btn" href="#" onclick="mo_otp_feedback_goback()">' . esc_html( mo_( 'Go Back' ) ) . '</a>
								
							</div>
							<form class="p-mo-6 flex flex-col gap-mo-6" id="mo_otp_feedback_form" name="f" method="post" action="">
									<div class="deactivation_message">' . esc_attr( $message ) . '</div>
									<div class="flex flex-col gap-mo-3">';

foreach ( $deactivationreasons as $code => $name ) {
	echo '
													<label class="mo-checkbox-container flex">
														<input  type="checkbox" name="reason[]" id="' . esc_attr( $code ) . '_checkbox" value=' . esc_attr( $code ) . ' class="sr-only"/>
														<div class="mo-checkmark"></div>
														<p class="font-normal pl-mo-8">' . esc_html( $name ) . '</p>
													</label> 
												';
}
echo '                                  
									</div>
										<input type="hidden" name="option" value="mo_otp_feedback_option"/>
										<input type="hidden" value="false" id="feedback_type" name="plugin_deactivated"/>';

									wp_nonce_field( $nonce );

echo '                                   <textarea id="query_feedback"
													class="mo-textarea"
													name="query_feedback" 
													style="width:100%" 
													rows="4" 
													placeholder="Type your feedback here"></textarea>
										<div class="mo_otp_note" hidden id="feedback_message" style="padding:10px;color:darkred;"></div>
										<textarea hidden id="feedback" name="feedback" style="width:100%" rows="2" placeholder="Type your feedback here"></textarea>
		';
echo '                           <div>    
									<input type="submit" name="miniorange_feedback_submit" class="mo-button primary"  style="float: right;"
										data-sm="' . esc_attr( $submit_message ) . '" data-sm2="' . esc_attr( $submit_message ) . '" value="' . esc_attr( $submit_message ) . '" />';
echo '                                        
								</div>
							</form>    
						
				</div>
			</div>
		';
