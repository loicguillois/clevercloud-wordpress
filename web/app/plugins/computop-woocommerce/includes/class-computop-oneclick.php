<?php

class Computop_Oneclick
{
    public static function get_last_transaction_by_customer_id() {
        $customer_id = get_current_user_id();
        
        if(!$customer_id)
        {
            return null;
        }
        
        $transaction = Computop_Transaction::get_last_transaction_by_customer_id($customer_id);
        
        return $transaction;
    }
    
    public static function get_payment_cards_by_user_id()
    {
        $customer_id = get_current_user_id();
        
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_customer_oneclick_payment_card WHERE customer_id = $customer_id" );
    }
    
    public static function get_current_customer_payment_card_by_card_id($card_id)
    {
        $customer_id = get_current_user_id();
        
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_customer_oneclick_payment_card WHERE customer_id = $customer_id AND id = $card_id" );
    }
    
    public static function check_customer_payment_card_already_exist_by_pcnr($pcnr)
    {
        $customer_id = get_current_user_id();
        
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_customer_oneclick_payment_card WHERE customer_id = $customer_id AND pcnr = $pcnr" );
    }
    
    public static function register_payment_card($pcnr, $ccexpiry, $ccbrand)
    {
        
        if(!empty(self::check_customer_payment_card_already_exist_by_pcnr($pcnr)))
        {
            return;
        }
        
        $customer_id = get_current_user_id();
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}computop_customer_oneclick_payment_card",
            array(
                'customer_id' => $customer_id,
                'pcnr' => $pcnr,
                'ccexpiry' => $ccexpiry,
                'ccbrand' => $ccbrand
            )
        );
    }
    
    public static function delete_payment_card($card_id)
    {
        $card = self::get_current_customer_payment_card_by_card_id($card_id);
        
        if(!is_null($card))
        {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix.'computop_customer_oneclick_payment_card', array( 'id' => $card_id ), array( '%d' ) );
        }
    }
}
