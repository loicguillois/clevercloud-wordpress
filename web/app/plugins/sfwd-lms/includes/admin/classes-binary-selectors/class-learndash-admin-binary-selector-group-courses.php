<?php
/**
 * LearnDash Binary Selector Group Courses Class.
 *
 * @package LearnDash
 * @subpackage Admin Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Group_Courses' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector Group Courses.
	 */
	class Learndash_Binary_Selector_Group_Courses extends Learndash_Binary_Selector_Posts {

		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'group_id'           => 0,
				'post_type'          => 'sfwd-courses',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( '%1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				) . '</h3>',
				'html_id'            => 'learndash_group_courses',
				'html_class'         => 'learndash_group_courses',
				'html_name'          => 'learndash_group_courses',
				'search_label_left'  => sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( 'Search All %1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
				'search_label_right' => sprintf(
					// translators: placeholders: Group, Courses.
					esc_html_x( 'Search Assigned %1$s %2$s', 'placeholders: Group, Courses', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' ),
					LearnDash_Custom_Label::get_label( 'courses' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['group_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['group_id'] . ']';

			parent::__construct( $args );
		}
	}
}
