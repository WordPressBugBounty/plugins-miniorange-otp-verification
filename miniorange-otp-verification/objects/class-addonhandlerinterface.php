<?php
/**Load Interface AddOnHandlerInterface
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
interface AddOnHandlerInterface {

	/**  Function to be defined by the form class extending this class
	 */
	public function set_addon_key();     // to force add-ons to set a form key.
	/**To force add-ons to set a add-on desc.
	 */
	public function set_add_on_desc();
	/** To force add-ons to set a name.
	 */
	public function set_add_on_name();
	/** To force add-ons to set a settings page url.
	 */
	public function set_settings_url();
	/**To force add-ons to set a docs link.
	 */
	public function set_add_on_docs();
	/**To force add-ons to set a video link.
	 */
	public function set_add_on_video();
}
