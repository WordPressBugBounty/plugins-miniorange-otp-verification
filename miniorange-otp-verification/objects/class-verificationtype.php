<?php
/**Load Class VerificationType
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VerificationType' ) ) {
	/**
	 * Class for defining verification types used in the plugin.
	 */
	class VerificationType {

		const EMAIL    = 'email';
		const PHONE    = 'phone';
		const BOTH     = 'both';
		const EXTERNAL = 'external';
		const TEST     = 'test';
	}
}
