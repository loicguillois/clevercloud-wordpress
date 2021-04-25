<?php
/**
 * LearnDash Settings Page Add-ons.
 *
 * @package LearnDash
 * @subpackage Add-on Updates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Export' ) ) {
	/**
	 * Class to create Addons list table.
	 */
	class Learndash_Admin_Import_Export {

		/**
		 * List table constructor.
		 */
		public function __construct() {
		}

		/**
		 * Show the Import/Export module UI.
		 */
		public function show() {
		}
		// End of functions.
	}
	// End of Class.
}
