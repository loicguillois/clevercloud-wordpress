<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );



/*
** Custom WooCommerce checkout 
*/

/* make fields optional */

// Billing and shipping addresses fields
add_filter( 'woocommerce_default_address_fields' , 'filter_default_address_fields', 20, 1 );
function filter_default_address_fields( $address_fields ) {
    // Only on checkout page
    if( ! is_checkout() ) return $address_fields;

    // All field keys in this array
    $key_fields = array('country','first_name','last_name','company','address_1','address_2','city','state','postcode');

    // Loop through each address fields (billing and shipping)
    foreach( $key_fields as $key_field )
        $address_fields[$key_field]['required'] = false;

    return $address_fields;
}

// For billing email and phone - Make them not required
add_filter( 'woocommerce_billing_fields', 'filter_billing_fields', 20, 1 );
function filter_billing_fields( $billing_fields ) {
    // Only on checkout page
    if( ! is_checkout() ) return $billing_fields;

    $billing_fields['billing_phone']['required'] = false;
    //$billing_fields['billing_email']['required'] = false;
    return $billing_fields;
}

/* hide additional info */
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

/* customize form fields */



/* redirect after checkout */
add_action( 'woocommerce_thankyou', 'educawa_checkoutredirect');
function educawa_checkoutredirect( $order_id ){
 	$order = wc_get_order( $order_id );
	$url = home_url();
    	//$url = 'https://yoursite.com/custom-url';
	if ( ! $order->has_status( 'failed' ) ) {
		wp_safe_redirect( $url );
		exit;
	}
}




/* elementor pro custom single matière filter query */
add_action( 'elementor/query/educawa_matiere_1', function( $query ) {
	//$posttype=get_post_type();
	$postid=get_the_id();
	$course_id = get_post_meta( $postid, 'course_id', true );

	$meta_query[] = [          
	'key' => 'course_id',          
	'value' => [ $course_id ],          
	'compare' => 'in', ];  
	$meta_query[] = [          
	'key' => 'lesson_id',          
	'value' => [ $postid ],          
	'compare' => 'in', ]; 
	
	$query->set( 'meta_query', $meta_query );
} );



/* elementor pro custom single classe filter query */
add_action( 'elementor/query/educawa_classe_1', function( $query ) {
	//$posttype=get_post_type();
	$postid=get_the_id();

	$meta_query[] = [          
	'key' => 'course_id',          
	'value' => [ $postid ],          
	'compare' => 'in', ]; 

	$query->set( 'meta_query', $meta_query );
} );
