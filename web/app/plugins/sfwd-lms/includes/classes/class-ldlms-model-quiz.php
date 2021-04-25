<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Quiz.
 *
 * @package LearnDash
 * @subpackage Quiz
 * @since 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Quiz' ) ) && ( class_exists( 'LDLMS_Model_Post' ) ) ) {
	class LDLMS_Model_Quiz extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Quiz Post ID to load.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		public function __construct( $quiz_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'quiz' );
			$this->load( $quiz_id );
		}

		// End of functions.
	}
}
