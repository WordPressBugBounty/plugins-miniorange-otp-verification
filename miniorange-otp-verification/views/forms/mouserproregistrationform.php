<?php
/**
 * Load admin view for User Pro Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoConstants;

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="userpro_registration" class="app_enable" data-toggle="userpro_registration_options" name="mo_customer_validation_userpro_registration_enable" value="1"
										' . esc_attr( $userpro_enabled ) . ' /><strong>' . esc_attr( $form_name ) . '</strong>';

echo '							<div class="mo_registration_help_desc"  id="userpro_registration_options">
									<div><input type="checkbox" ' . esc_attr( $disabled ) . ' class="form_options" ' . esc_attr( $automatic_verification ) . ' id="mo_customer_validation_userpro_verify" name="mo_customer_validation_userpro_verify" value="1"/> &nbsp;<strong>' . esc_html( mo_( 'Verify users after registration' ) ) . '</strong><br/></div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="userpro_registration_email" class="app_enable" data-toggle="userpro_registration_email_instructions" name="mo_customer_validation_userpro_registration_type" value="' . esc_attr( $userpro_type_email ) . '"
										' . ( esc_attr( $userpro_enabled_type ) === esc_attr( $userpro_type_email ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
									</div>
									<div ' . ( esc_attr( $userpro_enabled_type ) !== esc_attr( $userpro_type_email ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="userpro_registration_email_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Email Verification for UserPro Form' ) ) . ': 
											<ol>
												<li><a href="' . esc_attr( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
												<li>' . esc_html( mo_( 'Click on the Edit option of the page which has your UserPro form' ) ) . '.</li>
												<li>' . esc_html( mo_( 'Add the following short code just below your' ) ) . esc_html( mo_( 'UserPro Form shortcode on the profile and registration pages' ) ) . ': <code>[mo_verify_email_userpro]</code> </li>
												<li>
													' . esc_html( mo_( 'Add a New Custom Field to your Form. Give the following parameters to the new field' ) ) . ': 
													<ol>
														<li>' . wp_kses( mo_( 'Give the <b>Field Title</b> as ' ), array( 'b' => array() ) ) . '<code>Verify Email</code></li>
														<li>' . wp_kses( mo_( 'Give the <b>Field Type</b> as ' ), array( 'b' => array() ) ) . '<code>Text Input</code></li>
														<li>' . wp_kses( mo_( 'Give the <b>Unique Field Key</b> as ' ), array( 'b' => array() ) ) . '<code>' . esc_attr( MoConstants::USERPRO_VER_FIELD_META ) . '</code></li>
														<li>' . esc_html( mo_( 'Make the Field as a required field.' ) ) . '</li>
													</ol>
												</li>
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
											</ol>
									</div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="userpro_registration_phone" class="app_enable" data-toggle="userpro_registration_phone_instructions" name="mo_customer_validation_userpro_registration_type" value="' . esc_attr( $userpro_type_phone ) . '"
										' . ( esc_attr( $userpro_enabled_type ) === esc_attr( $userpro_type_phone ) ? 'checked' : '' ) . ' />
											<strong>' . esc_attr(
											mo_( 'Enable Phone Verification' )
										) . '</strong>
									</div>
									<div ' . ( esc_attr( $userpro_enabled_type ) !== esc_attr( $userpro_type_phone ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="userpro_registration_phone_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification for UserPro Form' ) ) . ': 
											<ol>
												<li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of the page which has your UserPro form.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Add the following short code just below your UserPro Form shortcode on the profile and registration pages' ) ) . ': <code>[mo_verify_phone_userpro]</code> </li>
												<li><a href="' . esc_url( $userpro_field_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( 'to see your list of UserPro fields.' ) ) . '</li>
												<li>' . esc_html( mo_( 'Add a Phone Number Field to your Form from the available fields list' ) ) . '.</li>
												<li>' . esc_html( mo_( 'Add Ajax Call Check for your Phone Number field' ) ) . ': <code>mo_phone_validation</code></li>
												<li>
													' . esc_html( mo_( 'Add a New Custom Field to your Form. Give the following parameters to the new field' ) ) . ': 
													<ol>
														<li>' . wp_kses( mo_( 'Give the <b>Field Title</b> as ' ), array( 'b' => array() ) ) . '<code>Verify Phone</code></li>
														<li>' . wp_kses( mo_( 'Give the <b>Field Type</b> as ' ), array( 'b' => array() ) ) . '<code>Text Input</code></li>
														<li>' . wp_kses( mo_( 'Give the <b>Unique Field Key</b> as ' ), array( 'b' => array() ) ) . '<code>' . esc_attr( MoConstants::USERPRO_VER_FIELD_META ) . '</code></li>
														<li>' . esc_html( mo_( 'Make the Field as a required field.' ) ) . '</li>
													</ol>
												</li>
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
											</ol>
									</div>
								</div>
							</div>';
