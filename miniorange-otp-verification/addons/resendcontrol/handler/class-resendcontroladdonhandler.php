<?php

/**
 * Limit OTP Handler
 *
 * @package miniorange-otp-verification/addons
 */

namespace ROC\Handler;

use ROC\Handler\ResendControlHandler;
use ROC\Handler\Utilities;
use ROC\Handler\MoAddonDb;
use OTP\Objects\BaseAddOnHandler;
use OTP\Helper\AddOnList;

use ROC\Traits\Instance;

/**
 * The class is used to handle all Limit OTP related functionality.
 */
class ResendControlAddonHandler extends BaseAddonHandler {


	use Instance;

	/**
	 * Constructor checks if add-on has been enabled by the admin and initializes
	 * all the class variables. This function also defines all the hooks to
	 * hook into to make the add-on functionality work.
	 */
	public function __construct() {
		parent::__construct();
		if ( ! $this->moAddOnV() ) {
			return;
		}
		ResendControlHandler::instance();
		MoAddonDb::instance();
		Utilities::instance();
	}

	/** Set a unique key for the AddOn */
	public function set_addon_key() {
		$this->add_on_key = 'otp_control';
	}

	/** Set a AddOn Description */
	public function set_add_on_desc() {
		$this->add_on_desc = moroc_(
			'Allows you to control your OTP.'
				. 'Click on the settings button to the right to configure settings for the same.'
		);
	}

	/** Set an AddOnName */
	public function set_add_on_name() {
		$this->addon_name = moroc_( 'OTP Control' );
	}

	/** Set Settings Page URL */
	public function set_settings_url() {
		$req_url            = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$this->settings_url = add_query_arg( array( 'addon' => 'otp_control' ), $req_url );
	}

	/** Set an Addon Docs link */
	public function set_add_on_docs() {}

	/** Set an Addon Video link */
	public function set_add_on_video() {}
}
