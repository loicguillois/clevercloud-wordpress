<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Question.
 *
 * @package LearnDash
 * @subpackage Question
 * @since 3.2.0
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Question' ) ) ) {
	class LDLMS_Model_Question extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Question Post ID to load.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		public function __construct( $question_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'question' );
			$this->load( $question_id );
		}

		// End of functions.
	}
}
