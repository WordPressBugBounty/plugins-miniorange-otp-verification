<?php
/**
 * Limit OTP Helper
 *
 * @package miniorange-otp-verification/addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class lists out name, description and settings the AddOn.
 */
if ( ! class_exists( 'ResendControlUtils' ) ) {
	/**
	 * ResendControlUtils class
	 */
	class ResendControlUtils {

		use Instance;
		/**
		 * Intializes the values
		 */
		public function __construct() {
			add_action( 'miniorange_validation_show_addon_list', array( $this, 'show_addon_list' ) );
		}

		/**
		 * Shows the Addon List
		 *
		 * @return void
		 */
		public function show_addon_list() {
			$addon_list = AddOnList::instance();
			$addon_list = $addon_list->getList();

			foreach ( $addon_list as $addon ) {
				echo '<tr>
                    <td class="addon-table-list-status">
                        ' . esc_html( $addon->get_add_on_name() ) . '
                    </td>
                    <td class="addon-table-list-name">
                        <i>
                            ' . esc_html( $addon->getAddOnDesc() ) . '
                        </i>
                    </td>
                    <td class="addon-table-list-actions">
                        <a  class="button-primary button tips" 
                            href="' . esc_url( $addon->getSettingsUrl() ) . '">
                            ' . esc_html( moroc_( 'Settings' ) ) . '
                        </a>
                    </td>
                </tr>';
			}
		}

	}
}
