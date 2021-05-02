=== Stripe for LearnDash ===
Author: LearnDash
Author URI: https://learndash.com 
Plugin URI: https://learndash.com/add-on/stripe/
LD Requires at least: 3.0
Slug: learndash-stripe
Tags: integration, payment gateway, stripe
Requires at least: 5.0
Tested up to: 5.6
Requires PHP: 7.0
Stable tag: 1.8.0

Integrate LearnDash LMS with Stripe.

== Description ==

Integrate LearnDash LMS with Stripe.

LearnDash comes with the ability to accept payment for courses by leveraging PayPal. Using this add-on, you can quickly and easily accept payments using the Stripe payment gateway. Use it with PayPal, or just use Stripe - the choice is yours!

= Integration Features = 

* Accept payments using Stripe
* Automatic user creation and enrollment
* Compatible with built-in PayPal option
* Lightbox overlay

See the [Add-on](https://learndash.com/add-on/stripe/) page for more information.

== Installation ==

If the auto-update is not working, verify that you have a valid LearnDash LMS license via LEARNDASH LMS > SETTINGS > LMS LICENSE. 

Alternatively, you always have the option to update manually. Please note, a full backup of your site is always recommended prior to updating. 

1. Deactivate and delete your current version of the add-on.
1. Download the latest version of the add-on from our [support site](https://support.learndash.com/article-categories/free/).
1. Upload the zipped file via PLUGINS > ADD NEW, or to wp-content/plugins.
1. Activate the add-on plugin via the PLUGINS menu.

== Changelog ==

= 1.8.0 =

* Updated the Stripe PHP SDK                                                                 
* Fixed syntax error                                                                                   
* Fixed 404 /customers/ Stripe error in legacy checkout                                          
* Fixed undefined property error                                                                 
* Fixed 404 error response when retrieving Stipe customer object

= 1.7.0 =

* Added dependencies check
* Added allow_promotion_codes arg in Stripe session creation API
* Updated add 'customer' arg in Stripe session API so that Stripe only create 1 customer object for each WP user
* Fixed token used more than once error on legacy checkout
* Fixed fatal error due to uncaught Stripe API Exception
* Fixed Stripe JS is loaded multiple times if there's multiple payment button on a page

View the full changelog [here](https://www.learndash.com/add-on/stripe/).