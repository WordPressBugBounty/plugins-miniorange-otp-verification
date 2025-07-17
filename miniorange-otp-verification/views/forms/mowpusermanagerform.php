<?php
/**
 * Load admin view for WP User Manager Form.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="wp_user_manager_basic" class="app_enable" data-toggle="wp_user_manager_options" 
                name="mo_customer_validation_wp_user_manager_enable" value="1" ' . esc_attr( $is_wp_user_manager_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
            <div class="mo_registration_help_desc" ' . esc_attr( $is_wp_user_manager_hidden ) . ' id="wp_user_manager_options">
               
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="wp_user_manager_form_email" class="app_enable" 
                    data-toggle="wp_user_manager_email_option" name="mo_customer_validation_wp_user_manager_enable_type" 
                    value="' . esc_attr( $wp_user_manager_email_type ) . '" ' . ( ( $wp_user_manager_enabled_type ) === ( $wp_user_manager_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( $wp_user_manager_enabled_type !== $wp_user_manager_email_type ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="wp_user_manager_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $wp_user_manager_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Customize Form</b> option of your Registration Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add an <b>Email Field</b> to your form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Make sure Email Field is required Field.' ) ) . '</li>';

					echo '   </ol>
                </div>
            </div>
        </div>';

