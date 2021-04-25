<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Group.
 *
 * @package LearnDash
 * @subpackage Group
 * @since 3.2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Group' ) ) ) {
	class LDLMS_Model_Group extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $post_id Group Post ID to load.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		public function __construct( $group_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'group' );

			$this->load( $group_id );
		}

		// Endof functions.
	}
}
