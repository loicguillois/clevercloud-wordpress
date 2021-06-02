<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * My Account navigation.
 *
 * @since 2.6.0
 */
//do_action( 'woocommerce_account_navigation' ); ?>

<div class="woocommerce-MyAccount-content">
	<h2>My Account custom </h2> 
	<?php
		/**
		 * My Account content.
		 *
		 * @since 2.6.0
		 */

		//add_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );
		//add_action( 'woocommerce_account_content', 'woocommerce_account_content' );

		//do_action( 'woocommerce_account_edit-address_endpoint', 'woocommerce_account_edit_address' );
		
		
		do_action( 'woocommerce_account_edit-account_endpoint', 'woocommerce_account_edit_account' );
		do_action( 'woocommerce_account_orders_endpoint', 'woocommerce_account_orders' );

		do_action( 'woocommerce_account_payment-methods_endpoint', 'woocommerce_account_payment_methods' );
		do_action( 'woocommerce_account_add-payment-method_endpoint', 'woocommerce_account_add_payment_method' );
		//do_action( 'woocommerce_account_view-order_endpoint', 'woocommerce_account_view_order' );

		//do_action( 'woocommerce_account_content' );

	?>
</div>
