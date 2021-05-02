=== LearnDash LMS ===
Author: LearnDash
Author URI: https://learndash.com 
Plugin URI: https://learndash.com
Slug: learndash-core
Tags: learndash
Requires at least: 5.5
Tested up to: 5.7
Requires PHP: 7.0
Stable tag: 3.4.0.8
Last Update: 2021-04-28

LearnDash LMS Plugin - Turn your WordPress site into a learning management system.

== Description ==

Turn your WordPress site into a learning management system.

Easily create & sell courses, deliver quizzes, award certificates, manage users, download reports, and so much more! By using LearnDash you have access to the latest e-learning industry trends for creating robust learning experiences.

See the [Features](https://www.learndash.com/wordpress-course-plugin-features/) page for more information.

== Installation ==

If the auto-update is not working, you always have the option to update manually. Please note, a full backup of your site is always recommended prior to updating. 

1. Deactivate and delete your current version of LearnDash LMS.
2. Download the latest version of LearnDash from our [support site](https://support.learndash.com/articles/my-downloads/).
3. Upload the zipped file via PLUGINS > ADD NEW, or to wp-content/plugins.
4. Activate the LearnDash LMS plugin via the PLUGINS menu.

== Changelog ==

= 3.4.0.8 = 

* Fixed quizzes not showing in the course builder
* Fixed Fixed activity table records not getting updated when manually marking content as completed

= 3.4.0.7 =

* Fixed recurring payment duration resetting to 1 day
* Fixed quizzes not showing on course builder 
* Fixed sprint() too few arguments warnings 
* Fixed warning illegal offset percentage 

= 3.4.0.6 = 

* Fixed Course Grid pagination resetting after going to the next page on a filtered grid 
* Fixed issue with course progression where some users were unable to progress to the next step when taking a quiz multiple times 
* Fixed learndash_get_global_quiz_list() causing infinite loop in Legacy Mode templates 

= 3.4.0.5 = 

* Fixed lesson progress bar showing in focus mode 
* Fixed issue where course grid was still showing in progress even when it should show completed
* Fixed lesson/topic content not showing if the course builder is disabled 
* Fixed Error Call to undefined function learndash_get_custom_lower_label()

= 3.4.0.4 = 

* Updated use php timestamp for quiz completion times rather than a JS based value
* Fixed modifying course progress from the backend user profile in the wp-admin resets all incomplete courses
* Fixed sprintf warning too few arguments

= 3.4.0.3 = 

* Added show progress bar in lesson overview page
* Added support for duplicate post plugins
* Updated import block dependencies instead of using the wp global
* Updated compatibility for Spotlightr API v2 videos for video player
* Fixed warning messages under overview section (class-simplepie.php)
* Fixed [ld_course_list] using legacy templates causes infinite looping 
* Fixed group access mode reverting to previous saved value when updating 
* Fixed PHP notice on transaction listing 
* Fixed expired course alert not showing 

= 3.4.0.2 =

* Added associated lesson/topic/quiz course selectors
* Fixed span html tag never closes
* Fixed lesson list not visible to those without a user role 
* Fixed users list inaccessible if the admin user also has the group leader role 
* Fixed group leader can't edit assignments 

= 3.4.0.1 = 

* Fixed problem where content was not showing on the front-end
* Fixed the label missing from the drip lesson 

= 3.4.0 = 

* Added return to current lesson button
* Added filter to check for the duration of uploaded videos in assignments
* Added REST support on the video_resume and video_focus_pause settings fields for Lesson and Topics
* Added disable auto complete/input on form fields in quizzes
* Added progress_status to REST API V2
* Added Gutenberg block for [quizinfo] shortcode (only for the new certificate builder post type)
* Updated performance improvement throughout the plugin
* Updated REST API V2 to show success/failure messages when deleting a user from a course
* Updated improved quizzes list display loading time (part of performance improvements)
* Fixed showing incorrect DB version in support information overview
* Fixed delete LearnDash MU plugin from the mu-plugins folder automatically when LearnDash is deleted
* Fixed group leader can edit any assignment 
* Fixed public step in private course viewable 
* Fixed in Progress parameter showing complete courses as well for non-admin user
* Fixed group Leader cannot create/modify LD content with Gutenberg with manage courses/groups option
* Fixed quiz only lessons not displaying steps in Focus Mode sidebar
* Fixed "All Certificates" label not translatable
* Fixed accessing free course via the REST API
* Fixed essay type upload questions: File format not recognized if uppercase extension is used
* Fixed register_rest_field() does not work with v2
* Fixed incorrect breadcrumb order when course/step title using RTL language
* Fixed removing user through REST API V2 not working
* Fixed PHP warnings and notices

= 3.3.0.3 = 

* Fixed fatal error on WordPress 5.5 based LearnDash websites

= 3.3.0.2 =

* Added filter "learndash_use_wp_safe_redirect" to be used if a user is being redirected to the wp-admin when marking a lesson or course as complete
* Added warning when using an older PHP version
* Updated ReactDND library
* Fixed download quiz certificate link not showing when a quiz has an essay type question with "Not graded" status
* Fixed styling issue with Elementor and the [ld_profile] shortcode 
* Fixed subsequent pages do not respect the filter condition, for example when filtering completed courses only
* Fixed styling issue on the quiz question overview page where the second row isn't correctly aligned to the left
* Fixed warning when foreach() isn't run over an array
* Fixed unexpected token < in JSON when Elementor and LearnDash are activated
* Fixed essay bulk approval not working 
* Fixed essay/open type questions showing a default white space
* Fixed course grid block not saving the column numbers
* Fixed two buttons showing (publish and update) when reviewing a submitted essay in Gutenberg/the block editor 
* Fixed not being able to approve submitted essays 
* Fixed Video Pause on Window Unfocused causing YT videos to autoplay
* Fixed PHP notices
* Fixed matrix sorting view questions results not displaying matching answers correctly

= 3.3.0.1 = 

* Fixed E_PARSE error causing Error message: syntax error, unexpected "?"" In REST API v2 for PHP versions below 7.3 
* Fixed an issue where when loading the REST API endpoints metabox_init_filter caused problems with third-party plugins and groups
* Fixed users can�t upload assignments
* Fixed custom course label not applying to LD user status widget
* Fixed unsupported operand types
* Fixed deprecation warnings when using PHP 8

= 3.3.0 =

* Added LearnDash REST API v2 (beta)
* Added course export performance improvements
* Added quiz messages for the question answered state
* Updated quiz retry logic 
* Updated packages used in the builder
* Updated coding standards 
* Fixed quiz answer spacing 
* Fixed translations not updating 
* Fixed header distorted in focus mode
* Fixed marking a course complete via the profile page in the wp-admin
* Fixed course list in ld_profile shortcode not showing 
* Fixed missing latest quiz statistic 
* Fixed mark complete button cut off in focus mode
* Fixed lesson title not changing when using the_title filter
* Fixed group leaders not being able to filter assingments/essays 
* Fixed LearnDash blocks throwing a warning
* Fixed RTL breadcrumbs being the wrong direction
* Fixed PayPal recurring payments not enrolling users

View our full changelog [here](https://www.learndash.com/changelog/).

== Upgrade Notice ==

= 3.1.3 = 
Important security update: please update immediately.

== FAQ ==

= Do I need to update? =

It is always recommended to update. However given the nature of WordPress and the option to have many other plugins installed, custom code, etc. it is possible that a conflict would arise. This is why we always recommend testing the update on a development environment first. 

= Why am I getting an error notice when trying to update? =

If you are getting an error while trying to update your version of LearnDash LMS, verify that your license is still valid. 

Both your license key and email address should be entered via LEARNDASH LMS > SETTINGS > LMS LICENSE. You should then see a "Your license is valid" message appear. 

If not, you can find your correct information via our [Support Site](https://support.learndash.com/articles/my-downloads/).

If your license has expired, you can purchase a new one [here](https://www.learndash.com/pricing-and-purchase/).

= What will happen to my customizations when updating? =

As long as the customizations were not done directly in the core LearnDash plugin files, there should be no problem. We provide many template files and hooks for this purpose. 

