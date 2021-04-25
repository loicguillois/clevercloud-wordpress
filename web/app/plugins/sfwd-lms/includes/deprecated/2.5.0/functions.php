<?php
/**
 * Deprecated functions from LD 2.6.4
 * The functions will be removed in a later version.
 *
 * @package LearnDash
 * @subpackage Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leandash_redirect_post_location' ) ) {
	/**
	 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add
	 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
	 *
	 * @deprecated 2.6.4 Use learndash_redirect_post_location()
	 *
	 * @since 2.5.0
	 *
	 * @param string $location Optional. Location.  Default empty.
	 */
	function leandash_redirect_post_location( $location = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_redirect_post_location()' );
		}

		return learndash_redirect_post_location( $location );
	}
}
