<?php

add_action( 'init', 'my_account_new_endpoints' );
add_action( 'woocommerce_account_cards_endpoint', 'cards_endpoint_content' );
add_action( 'woocommerce_account_subscriptions_endpoint', 'subscriptions_endpoint_content' );
add_filter ( 'woocommerce_account_menu_items', 'my_account_menu_order' );
add_filter( 'the_title', 'my_custom_endpoint_title' );

// init news endpoints
function my_account_new_endpoints() {
    add_rewrite_endpoint( 'cards', EP_ROOT | EP_PAGES );
    add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
}

//content payment cards
function cards_endpoint_content() {
    
    $url_deleted = '?page_id=9&cards&delete_payment_card_id=';
    
    if(!isset($_GET['page_id']))
    {
        $url_deleted = '?delete_payment_card_id=';
    }
    
    $delete_id = $_GET['delete_payment_card_id'] ?? null;
    
    if(!is_null($delete_id))
    {
        Computop_Oneclick::delete_payment_card($delete_id);
    }
        
    $oneclick_cards = Computop_Oneclick::get_payment_cards_by_user_id();

    $html_list_payment = '';
    
    $html_list_payment .= '
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
		<thead>
			<tr>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">'.__('Card', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">'.__('Card number', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr">'.__('Expire date', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">'.__('Action', 'computop').'</span></th>
                        </tr>
		</thead>
        <tbody>';
    
    foreach ($oneclick_cards as $oneclick_card) {
        $icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__DIR__)) . '/assets/img/' . strtoupper($oneclick_card->ccbrand) . '.png';
        $html_list_payment .= 
                '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" style="padding:0 !important;"  data-title="Commande">
                        <img style="display:block;margin:auto;width:50px;padding:0;" src="'.$icon.'" title="$oneclick_card->ccbrand" alt="$oneclick_card->ccbrand"/>
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
                        XXXX XXXX XXXX X' .substr($oneclick_card->pcnr, -3). '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="État">
                        '. substr($oneclick_card->ccexpiry, 0, 4) .' / ' .substr($oneclick_card->ccexpiry, -2). '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
                        <a class="woocommerce-button button cancel" href="'.$url_deleted.$oneclick_card->id. '">'.__('Delete', 'computop').'</a>
                    </td>';
    }
    
    $html_list_payment .= '</tbody></table>';
    
    if(empty($oneclick_cards))
    {
        $html_list_payment = "<p style=\"text-align:center;\">".__('No payment card register yet.', 'computop')."</p>";
    }
    
    echo $html_list_payment;
}

// subscriptions content
function subscriptions_endpoint_content() {
    
    $url_deleted = '?page_id=9&subscriptions&delete_recurring_payment_id=';
    
    if(!isset($_GET['page_id']))
    {
        $url_deleted = '?delete_recurring_payment_id=';
    }
    
    $delete_id = $_GET['delete_recurring_payment_id'] ?? null;
    
    if(!is_null($delete_id))
    {
        Computop_Recurring_Payment::cancel_computop_customer_payment_recurring_by_id($delete_id);
    }
    
    $user_id = get_current_user_id();
        
    $subscriptions = Computop_Recurring_Payment::get_recucrring_payment_list($user_id);
    
    $html_list_recurrent = '';
    
    $html_list_recurrent .= '
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
		<thead>
			<tr>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">'.__('Status', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">'.__('Product', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">'.__('Last Payment', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr">'.__('Next Payment', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">'.__('Remaining payment(s)', 'computop').'</span></th>
                                <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">'.__('Action', 'computop').'</span></th>
                        </tr>
		</thead>
        <tbody>';
    
    foreach ($subscriptions as $subscription) {
        
        $last_schedule = new DateTime($subscription->last_schedule);
        $last_schedule = $last_schedule->getTimestamp();
        
        $next_schedule = new DateTime($subscription->next_schedule);
        $next_schedule = $next_schedule->getTimestamp();      
        
        $next_schedule_date = '-';
        $remaining_payment = '-';
        $action_recurring_payment = '-';
        switch ($subscription->status) {
            case Computop_Gateway_Recurring::ID_STATUS_ACTIVE:
                
                $status = __('Enabled', 'computop');
                $next_schedule_date = strftime("%x", $next_schedule);
                $remaining_payment = $subscription->number_occurences - $subscription->current_occurence;
                $action_recurring_payment =  '<a class="woocommerce-button button cancel" href="'.$url_deleted.$subscription->id_computop_customer_payment_recurring. '">'.__('Cancel', 'computop').'</a>';
                if($subscription->number_occurences == 0)
                {
                    $remaining_payment = __('Illimited', 'computop');
                }
                
            break;
            case Computop_Gateway_Recurring::ID_STATUS_PAUSE:
                
                $status = __('Problem', 'computop');
                
            break;
            case Computop_Gateway_Recurring::ID_STATUS_EXPIRED:
                
                $status = __('Disabled', 'computop');
                
            break;
        }
        
        $product = new WC_Product($subscription->id_product);
        
        $html_list_recurrent .= 
                '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Commande">
                        ' .$status. '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
                       ' .$product->get_name(). '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
                       ' .strftime("%x", $last_schedule). '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="État">
                        ' .$next_schedule_date. '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" data-title="État">
                        ' .$remaining_payment. '
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
                        '.$action_recurring_payment.'
                    </td>';
    }
    
    $html_list_recurrent .= '</tbody></table>';
    
    if(empty($subscriptions))
    {
        $html_list_recurrent = "<p style=\"text-align:center;\">".__('No subscription register yet.', 'computop')."</p>";
    }
    
    echo $html_list_recurrent;
}

// changer menu account order
 function my_account_menu_order() {
 	$menuOrder = array(
            'dashboard'          => __( 'Dashboard', 'woocommerce' ),
            'orders'             => __( 'Your Orders', 'computop' ),
            'cards'              => __( 'Payment Cards', 'computop' ),
            'subscriptions'      => __( 'Subscriptions', 'computop' ),
            'downloads'          => __( 'Download', 'woocommerce' ),
            'edit-address'       => __( 'Addresses', 'woocommerce' ),
            'edit-account'       => __( 'Account details', 'woocommerce' ),
            'customer-logout'    => __( 'Logout', 'woocommerce' )
 	);
 	return $menuOrder;
 }

// change title of endpoint
function my_custom_endpoint_title( $title ) {
	global $wp_query;
	if ((isset( $wp_query->query_vars['cards'])) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
             //New page title.
            $title = __( 'My Payment Cards', 'computop' );

            remove_filter( 'the_title', 'my_custom_endpoint_title' );
	} elseif ((isset( $wp_query->query_vars['subscriptions'])) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            $title = __( 'My Subscriptions', 'woocommerce' );

            remove_filter( 'the_title', 'my_custom_endpoint_title' );
        }

	return $title;
}
