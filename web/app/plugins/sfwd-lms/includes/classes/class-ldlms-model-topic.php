<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Topic.
 *
 * @package LearnDash
 * @subpackage Topic
 * @since 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Topic' ) ) ) {
	class LDLMS_Model_Topic extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Topic Post ID to load.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		public function __construct( $topic_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'topic' );

			$this->load( $topic_id );
		}

		// End of functions.
	}
}
