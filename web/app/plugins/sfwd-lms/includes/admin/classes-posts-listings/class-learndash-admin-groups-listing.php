<?php
/**
 * LearnDash Groups (groups) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Groups_Listing' ) ) ) {
	/**
	 * Class for LearnDash Groups Listing Pages.
	 */
	class Learndash_Admin_Groups_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'group' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 */
		public function listing_init() {
			if ( $this->listing_init_done ) {
				return;
			}
			$this->selectors = array(
				'user_id'   => array(
					'type'                     => 'user',
					'show_all_value'           => '',
					'show_all_label'           => esc_html__( 'All Users', 'learndash' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_author' ),
					'selector_value_function'  => array( $this, 'selector_value_for_author' ),
				),
				'course_id' => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'listing_query_function'   => array( $this, 'selector_filter_for_group_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_filters'         => array( 'group_id' ),
				),
			);

			$this->columns = array(
				'groups_courses_users' => array(
					'label'   => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s / Users', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'display' => array( $this, 'show_column_course_users' ),
					'after'   => 'title',
				),
			);

			parent::listing_init();

			$this->listing_init_done = true;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				add_filter( 'learndash_listing_table_query_vars_filter', array( $this, 'listing_table_query_vars_filter_groups' ), 30, 3 );
			}
		}

		/** This function is documented in includes/admin/class-learndash-admin-posts-listing.php */
		public function listing_table_query_vars_filter_groups( $q_vars, $post_type, $query ) {
			$user_selector = $this->get_selector( 'user_id' );
			if ( ( $user_selector ) && ( isset( $user_selector['selected'] ) ) && ( ! empty( $user_selector['selected'] ) ) ) {
				$user_group_ids = learndash_get_users_group_ids( $user_selector['selected'], true );
				if ( ! empty( $user_group_ids ) ) {
					$q_vars['post__in'] = $user_group_ids;
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}

			// Filter the Groups listing for the Group Membership for the Post.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( isset( $_GET['ld-group-membership-post-id'] ) ) && ( ! empty( $_GET['ld-group-membership-post-id'] ) ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$group_membership_settings = learndash_get_post_group_membership_settings( absint( $_GET['ld-group-membership-post-id'] ) );
				if ( ! empty( $group_membership_settings['groups_membership_groups'] ) ) {
					$q_vars['post__in'] = $group_membership_settings['groups_membership_groups'];
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}

			return $q_vars;
		}

		/**
		 * Show Group Course Users column.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_course_users( $post_id = 0, $column_meta = array() ) {
			$group_users = learndash_get_groups_user_ids( $post_id );
			if ( ( empty( $group_users ) ) || ( ! is_array( $group_users ) ) ) {
				$group_users = array();
			}

			echo sprintf(
				// translators: placeholder: Group Users Count.
				esc_html_x( 'Users: %d', 'placeholder: Group Users Count', 'learndash' ),
				count( $group_users )
			);
			echo '<br />';

			// Group Courses.
			$group_courses = learndash_group_enrolled_courses( $post_id );
			if ( ( empty( $group_courses ) ) || ( ! is_array( $group_courses ) ) ) {
				$group_courses = array();
			}

			echo sprintf(
				// translators: placeholder: Goup Courses Count.
				esc_html_x( '%1$s: %2$d', 'placeholders: Courses, Group Courses Count', 'learndash' ),
				learndash_get_custom_label( 'courses' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				count( $group_courses )
			);
			echo '<br />';

			// Group Leaders.
			$group_leaders = learndash_get_groups_administrator_ids( $post_id );
			if ( ( empty( $group_leaders ) ) || ( ! is_array( $group_leaders ) ) ) {
				$group_leaders = array();
			}
			printf(
				// translators: placeholder: Group Leaders Count.
				esc_html_x( 'Leaders %d', 'placeholder: Group Leaders Count', 'learndash' ),
				count( $group_leaders )
			);
		}
		/**
		 * Filter the main query listing by the course_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 * @return object $q_vars.
		 */
		protected function selector_filter_for_group_course( $q_vars = array(), $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				$course_group_ids = learndash_get_course_groups( absint( $selector['selected'] ), true );
				if ( ! empty( $course_group_ids ) ) {
					if ( ! isset( $q_vars['post__in'] ) ) {
						$q_vars['post__in'] = array();
					}
					if ( empty( $q_vars['post__in'] ) ) {
						$q_vars['post__in'] = $course_group_ids;
					} else {
						$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $course_group_ids );
					}
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}

			return $q_vars;
		}

		// End of functions.
	}
}
new Learndash_Admin_Groups_Listing();
