<?php

class Computop_Recurring_Payment {

    private $translated = array();
    private $abo_enabled = false;

    public function __construct() {

        //$check_enabled = Computop_Api::is_allowed('ABO');
            if (/*$check_enabled &&*/ Computop_Api::is_allowed_abo())
                $this->abo_enabled = true;
        
        if ($this->abo_enabled) {
            
            if (!wp_next_scheduled('computop_recurring_cronjob')) {
                wp_schedule_event(time(), "daily", "computop_recurring_cronjob");
            }

            add_action('computop_recurring_cronjob', 'send_reccurring_schedule');

            function send_reccurring_schedule() {
                Computop_Webservice::send_recurring_schedules();
            }

        }
    }

    public static function get_recucrring_payment_list($user_id) {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_customer_payment_recurring WHERE `id_customer` = '$user_id' ORDER BY date_add DESC");
    }
    
    public static function get_recucrring_payment_list_with_filter($filters = array()) {        
        global $wpdb;
        $condition = "WHERE 1=1 ";
        foreach($filters as $filter) {
            $condition .= "AND " . $filter['field'] . " " . $filter['operator'] . " "  . $filter['value'] . " ";
        }
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_customer_payment_recurring $condition ORDER BY id_customer, date_add DESC");
    }
    
    public static function get_recurring_products() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_payment_recurring ORDER BY id_computop_payment_recurring DESC");
    }

    public static function get_schedules_to_capture() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_customer_payment_recurring WHERE `status` = '1' AND DATEDIFF(NOW(), `next_schedule`) >= 0 AND (number_occurences > current_occurence OR number_occurences = 0) ORDER BY date_add DESC");
    }
    
    public static function get_schedules_to_stop()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_customer_payment_recurring WHERE `status` = '1' AND number_occurences = current_occurence AND current_occurence > 0 ORDER BY date_add DESC");
    }
    
    public static function remove_computop_customer_payment_recurring_by_id($id)
    {
            $user_id = get_current_user_id();
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'computop_customer_payment_recurring', array('id_computop_customer_payment_recurring' => $id, 'id_customer' => $user_id));
            return (empty($wpdb->last_error)) ? true : false;
    }
    
    public static function cancel_computop_customer_payment_recurring_by_id($id)
    {
            $user_id = get_current_user_id();
            global $wpdb;
            
            $wpdb->update( 
                    $wpdb->prefix .'computop_customer_payment_recurring', 
                    array( 
                            'status' => 3,
                    ), 
                    array('id_computop_customer_payment_recurring' => $id),
                    array('%d'),
                    array('%d')
            );
    }

    public static function get_recurring_infos($product_id, $is_sdd = false) {
        global $wpdb;
        return ($is_sdd) ? 
            $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_payment_recurring WHERE id_product = '$product_id' AND type IN ('3','4') ") : 
            $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_payment_recurring WHERE id_product = '$product_id' AND type IN ('1','2')");
    }

    public static function get_computop_customer_payment_recurring($field) {
        global $wpdb;
        $sql = <<<SQL
        SELECT * FROM {$wpdb->prefix}computop_customer_payment_recurring 
        WHERE 1 
SQL;

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $sql .= " AND $key = '$value' ";
            }
        } else {
            $sql .= " AND id_computop_customer_payment_recurring = '$field' ";
        }
        $result = $wpdb->get_results($sql);
        return $result;
    }

}

new Computop_Recurring_Payment();
