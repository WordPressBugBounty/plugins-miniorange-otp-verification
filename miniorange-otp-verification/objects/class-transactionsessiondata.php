<?php
/**Load Class TransactionSessionData
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TransactionSessionData' ) ) {
	/**
	 * This class is for transaction session data which defines some common
	 * functionality for all of our transaction details.
	 */
	class TransactionSessionData {
		/**Variable declaration
		 *
		 * @var string
		 */
		private $email_transaction_id;
		/**Variable declaration
		 *
		 * @var string
		 */
		private $phone_transaction_id;

		/**MoInternal Function
		 *
		 * @return mixed
		 */
		public function get_email_transaction_id() {
			return $this->email_transaction_id;
		}

		/**MoInternal Function
		 *
		 * @param mixed $email_transaction_id email transaction id.
		 */
		public function set_email_transaction_id( $email_transaction_id ) {
			$this->email_transaction_id = $email_transaction_id;
		}

		/**MoInternal Function
		 *
		 * @return mixed
		 */
		public function get_phone_transaction_id() {
			return $this->phone_transaction_id;
		}

		/**MoInternal Function
		 *
		 * @param mixed $phone_transaction_id phone transaction id.
		 */
		public function set_phone_transaction_id( $phone_transaction_id ) {
			$this->phone_transaction_id = $phone_transaction_id;
		}
	}
}
