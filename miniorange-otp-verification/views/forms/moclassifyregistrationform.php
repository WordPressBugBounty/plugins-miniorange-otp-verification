<?php
/**
 * Load admin view for Classify Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '			<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
                    <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
                            id="classify_theme" 
                            class="app_enable" 
                            data-toggle="classify_options" 
                            name="mo_customer_validation_classify_enable" 
                            value="1"
                            ' . esc_attr( $classify_enabled ) . ' />
                        <strong>' . esc_html( $form_name ) . '</strong>
                    <div class="mo_registration_help_desc" id="classify_options">			
                        <div>
                            <input  type="radio" 
                                    ' . esc_attr( $disabled ) . ' 
                                    id="classify_email" 
                                    class="app_enable" 
                                    data-toggle="classify_email_instructions" 
                                    name="mo_customer_validation_classify_type" 
                                    value="' . esc_attr( $classify_type_email ) . '"
                                    ' . ( esc_attr( $classify_enabled_type ) === esc_attr( $classify_type_email ) ? 'checked' : '' ) . ' />
                                <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                        </div>							
                        <div    ' . ( esc_attr( $classify_enabled_type ) !== esc_attr( $classify_type_email ) ? 'hidden' : '' ) . ' 
                                class="mo_registration_help_desc_internal" id="classify_email_instructions" >
                                <b>
                            ' . esc_html( mo_( 'Follow the below steps to configure your Registration form' ) ) . ': 
                                </b>
                            <ol>
                                <li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see the list of pages.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Edit option of the "Register" page' ) ) . '</li>
                                <li>' . esc_html( mo_( 'From the page Attributes section ,set "Register Page" from your template dropdown menu.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Update button to save your settings.' ) ) . '</li>
                            </ol><b>
                            ' . esc_html( mo_( 'Follow the below steps to configure your Profile form' ) ) . ': 
                            </b><ol>
                                <li>' .
									'<a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' .
									esc_html( mo_( ' to see the list of pages.' ) ) .
								'</li>
                                <li>' .
									( esc_attr( $classify_enabled_type ) === 'classify_email_enable' ? 'checked' : '' ) .
									esc_html( mo_( 'Click on the Edit option of the "Profile" page' ) ) .
								'</li>
                                <li>' .
									( esc_attr( $classify_enabled_type ) === 'classify_email_enable' ? 'checked' : '' ) .
									esc_html( mo_( 'From the page Attributes section ,set "Profile Page" from your template dropdown menu.' ) ) .
								'</li>
                                <li>' .
									( esc_attr( $classify_enabled_type ) === 'classify_email_enable' ? 'checked' : '' ) .
									esc_html( mo_( 'Click on the Update button to save your settings.' ) ) . '
                                </li>
                            </ol>
                            ' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '
                        </div>
																															
                        <div>
                            <input  type="radio" ' . esc_attr( $disabled ) . ' 
                                    id="classify_phone" 
                                    class="app_enable" 
                                    data-toggle="classify_phone_instructions" 	
                                    name="mo_customer_validation_classify_type" 
                                    value="' . esc_attr( $classify_type_phone ) . '"
                                    ' . ( esc_attr( $classify_enabled_type ) === esc_attr( $classify_type_phone ) ? 'checked' : '' ) . ' />
                                <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                        </div>
                    
                        <div    ' . ( esc_attr( $classify_enabled_type ) !== esc_attr( $classify_type_phone ) ? 'hidden' : '' ) . ' 
                                class="mo_registration_help_desc_internal" 
                                id="classify_phone_instructions" >
                                <b>
                            ' . esc_html( mo_( 'Follow the following to configure your Registration form ' ) ) . ': 
                                </b>
                            <ol>
                                <li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see the list of pages.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Edit option of the "Register" page' ) ) . '</li>
                                <li>' . esc_html( mo_( 'From the page Attributes section ,set "Register Page" from your template dropdown menu.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Update button to save your settings.' ) ) . '</li>
                            </ol>
                               <b>
                            ' . esc_html( mo_( 'Follow the following to configure your Profile form ' ) ) . ': 
                               </b> 
                            <ol>
                                <li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see the list of pages.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Edit option of the "Profile" page' ) ) . '</li>
                                <li>' . esc_html( mo_( 'From the page Attributes section ,set "Profile" Page from your template dropdown menu.' ) ) . '</li>
                                <li>' . esc_html( mo_( 'Click on the Update button to save your settings.' ) ) . '</li>
                            </ol>
                            ' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '
                        </div>                    
                    </div>
                </div>';
