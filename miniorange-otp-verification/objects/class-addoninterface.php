<?php
/**Load Interface AddOnInterface
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
interface AddOnInterface {

	/**Function to be defined by the form class extending this class
	 */
	public function initialize_handlers();
	/**Function to be defined by the form class extending this class
	 */
	public function initialize_helpers();
	/**Function to be defined by the form class extending this class
	 */
	public function show_addon_settings_page();
}
