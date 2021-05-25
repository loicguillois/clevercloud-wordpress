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



/**
 * Example usage for learndash_settings_fields filter.
 */
add_filter(
    'learndash_settings_fields',
    function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
        // Check the metabox includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php line 23 where
        // settings_metabox_key is set. Each metabox or section has a unique settings key.
		if ( 'learndash-topic-access-settings' === $settings_metabox_key ) {
            // Add field here.
            $post_id           = get_the_ID();
            $my_settings_value = get_post_meta( $post_id, 'topic_custom_key', true );
            if ( empty( $my_settings_value ) ) {
                        $my_settings_value = '';
            }
 
            if ( ! isset( $setting_option_fields['topic-custom-field'] ) ) {
                $setting_option_fields['topic-custom'] = array(
 					'name'      => 'topic-custom-field',
                    'label'     => sprintf(
                        // translators: placeholder: Topic.
                        esc_html_x( '%s Topic Access', 'placeholder: Topic', 'learndash' ),
                        learndash_get_custom_label( 'topic' )
                    ),
					'type'      => 'checkbox-switch',
					'value'     => $my_settings_value,
					'help_text' => sprintf( 
						esc_html_x( 'Check this if you want this %1$s to be available for free.', 'placeholders: topic', 'learndash' ), 
						learndash_get_custom_label_lower( 'topic' ), learndash_get_custom_label_lower( 'topic' ) 
					),
					'default'   => '',
					'options'   => array(						
						'on' => '',
						''   => '',
					),
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'topic_custom_key',
								// translators: placeholder: Lesson.
								'description' => sprintf( 
									esc_html_x( '%s free content', 'placeholder: Topic', 'learndash' ), 
									learndash_get_custom_label( 'topic' ) 
								),
								'type'        => 'boolean',
								'default'     => false,
							),
						),
					),				
                );
            }
        }
        // Always return $setting_option_fields
        return $setting_option_fields;
    },
    30,
    2
);
 
// You have to save your own field. This is no longer handled by LD. This is on purpose.
add_action(
    'save_post',
    function( $post_id = 0, $post = null, $update = false ) {
        // All the metabox fields are in sections. Here we are grabbing the post data
        // within the settings key array where the added the custom field.
        if ( isset( $_POST['learndash-topic-access-settings']['topic-custom-field'] ) ) {
            $my_settings_value = esc_attr( $_POST['learndash-topic-access-settings']['topic-custom-field'] );
            // Then update the post meta
            update_post_meta( $post_id, 'topic_custom_key', $my_settings_value );
        }
    },
    30,
    3
);
