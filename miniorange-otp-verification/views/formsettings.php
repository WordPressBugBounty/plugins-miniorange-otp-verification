<?php
/**
 * Load admin view for Form list.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '  <div class="mo_registration_table_layout px-mo-4" id="selected_form_details">
			<div class="flex gap-mo-4 m-mo-4" id="mo_forms">
				<p class="text-lg font-medium pr-mo-44 py-mo-1 flex-1">
							' . esc_html( mo_( 'Form Settings' ) ) . '			
				</p>
				<div class="flex-1">
					<div class="flex gap-mo-4">
						<span>
							<a  class="mo-button medium secondary" 
								href="' . esc_url( $moaction ) . '">
								' . esc_html( mo_( 'Active Forms' ) ) . '
							</a>
						</span>
						<span>
							<a class="mo-button medium secondary"
								href="' . esc_url( $forms_list_page ) . '">
								' . esc_html( mo_( 'Forms List' ) ) . '
							</a>
						</span>
						<span>
								<input  name="save" id="ov_settings_button" ' . esc_attr( $disabled ) . ' 
										class="mo-button medium inverted" 
										value="' . esc_attr( mo_( 'Save Settings' ) ) . '" type="submit" />
						</span>
					</div>
				</div>
			</div>							
			<div id="new_form_settings">
				<div id="form_details">';
					require $controller . 'forms/class-' . strtolower( $form_name ) . '.php';
echo '          </div>
			</div>
		</div>';
