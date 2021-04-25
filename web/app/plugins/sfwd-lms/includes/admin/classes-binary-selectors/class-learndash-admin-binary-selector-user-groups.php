<?php
/**
 * LearnDash Binary Selector Users Grups Class.
 *
 * @package LearnDash
 * @subpackage Admin Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_User_Groups' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
	/**
	 * Class for LearnDash binary Selector User Groups.
	 */
	class Learndash_Binary_Selector_User_Groups extends Learndash_Binary_Selector_Posts {
		/**
		 * Public constructor for class
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			$this->selector_class = get_class( $this );

			$defaults = array(
				'user_id'            => 0,
				'post_type'          => 'groups',
				'html_title'         => '<h3>' . sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'User Enrolled in %s', 'placeholder: Group', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'group' )
				) . '</h3>',
				'html_id'            => 'learndash_user_groups',
				'html_class'         => 'learndash_user_groups',
				'html_name'          => 'learndash_user_groups',
				'search_label_left'  => sprintf(
					// translators: Groups.
					esc_html_x( 'Search All %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
				'search_label_right' => sprintf(
					// translators: Groups.
					esc_html_x( 'Search Enrolled %s', 'placeholder: Groups', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'groups' )
				),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['html_id']   = $args['html_id'] . '-' . $args['user_id'];
			$args['html_name'] = $args['html_name'] . '[' . $args['user_id'] . ']';

			parent::__construct( $args );
		}
	}
}
