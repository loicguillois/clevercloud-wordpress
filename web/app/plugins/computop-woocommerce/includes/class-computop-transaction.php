<?php

class Computop_Transaction
{
    public static function save( $data, $transaction_type, $order, $row_data, $amount = null ) {
       
        global $wpdb;
        
        $merchant_id = $data['mid'];
        $transaction_reference = $data['TransID'];
        $pay_id = $data['PayID'];
        $XID = $data['XID'] ?? null;
        $bid = $data['billingagreementid'] ?? null;
        
        if(is_null($amount))
        {
            $amount = $order->get_total();
        }
        
        $payment_mean_brand = $data['CCBrand'] ?? null;
        $payment_mean_type = $data['Type'] ?? null;
        $response_code =$data['Code'];
        $description = $data['Description'];
        $raw_data = implode('&', $data);
        $pncr = $data['PCNr'] ?? null;
        $ccexpiry = $data['CCExpiry'] ?? null;
        $status = $data['Status'];
        $date = new \DateTime('now');
        $date = $date->format('Y-m-d H:i:s');
        
        $wpdb->insert("{$wpdb->prefix}computop_transaction",
            array(
                'transaction_date' => $date,
                'merchant_id' => $merchant_id,
                'transaction_reference' => $transaction_reference,
                'order_id' => $order->get_id(),
                'pay_id' => $pay_id,
                'xid' => $XID,
                'bid' => $bid,
                'amount' => $amount,
                'pcnr' => $pncr,
                'ccexpiry' => $ccexpiry,
                'status' => $status,
                'description' => $description,
                'transaction_type' => $transaction_type,
                'payment_mean_brand' => $payment_mean_brand,
                'payment_mean_type' => $payment_mean_type,
                'response_code' => $response_code,
                'raw_data' => ''.$row_data,
            )
        );
        return $wpdb->insert_id;
    }
    
    public static function check_already_exists($data, $raw_data, $order) {
        global $wpdb;
        
        $sql = "
            SELECT * FROM {$wpdb->prefix}computop_transaction
            WHERE `transaction_reference` = '".$data['TransID']."'
            AND `order_id` = ".$order->get_id()."
            AND `pay_id` = '".$data['PayID']."'
            AND `xid` = '".$data['XID']."'
            AND `amount` = '".(string)$order->get_total()."'
        ";
            
        if (!empty($data['PCNr'])) {
            $sql .= "
                AND `pcnr` = '".$data['PCNr']."'
            ";
        }
            
        $res = $wpdb->get_row($sql);
        
        return !empty($res);
    }
    
    public static function get_by_trans_id($transaction_reference) {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}computop_transaction WHERE transaction_reference = '$transaction_reference'");
    }

    public static function get_by_id($transaction_id) {
        
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}computop_transaction WHERE transaction_id = $transaction_id");
    }
    
    public static function get_last_transaction_by_customer_id( $customer_id ) {
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE customer_id = $customer_id ORDER BY transaction_id DESC LIMIT 1" );
    }

    public static function get_by_payid( $payid ) {
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE payid = '$payid'" );
    }
    
    public static function get_by_orderid( $order_id ) {
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE order_id = '$order_id' ORDER BY transaction_id DESC LIMIT 1" );
    }
    
    public static function check_transaction_ok_by_order_id( $order_id ) {
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE order_id = '$order_id' AND response_code = '00000000' ORDER BY transaction_id DESC LIMIT 1" );
    }
    
    public static function gets_by_order_id( $order_id ) {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE order_id = $order_id" );
    }
    
    public static function get_transaction_success_by_order($order_id) {
        global $wpdb;
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_transaction WHERE order_id = $order_id AND response_code = 00000000" );
    }

    public static function get_all() {
        global $wpdb;
        
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_transaction ORDER BY transaction_id DESC");
    }


    public static function get_all_limit( $first_entry, $transactions_pages ) {
        global $wpdb;
        
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_transaction ORDER BY transaction_id DESC LIMIT {$first_entry}, {$transactions_pages}");
    }
    
    public static function output() {
        global $post;
        $transactions = self::gets_by_order_id( $post->ID );

        if ( ! empty( $transactions ) ) { ?>
            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th scope="col"> <?php echo __( 'Payment brand', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'N° Transaction', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'N° Order', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'Merchant Id', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'Amount', 'computop' ) ?></th>
                        <th scope="col"> <?php echo strtoupper(__( 'Operation type', 'computop' )) ?></th>
                        <th scope="col"> <?php echo __( 'Status', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'Message response', 'computop' ) ?></th>
                        <th scope="col"> <?php echo __( 'Raw data', 'computop' ) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach( $transactions as $transact ) { ?>
                    <tr id="<?php echo $transact->transaction_id ?>">
                        <td><?php echo date_i18n( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), strtotime( $transact->transaction_date ) ); ?></td>
                        <td>
                            <?php if ( ! empty( $transact->payment_mean_brand ) ) { ?>
                                <img alt="<?php echo $transact->payment_mean_brand; ?>" src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname( __DIR__ ) ) . '/assets/img/' . strtoupper($transact->payment_mean_brand) . '.png'; ?>" heigth="32px" width="32px">
                            <?php } ?>
                        </td>
                        <td><?php echo $transact->transaction_reference; ?></td>
                        <td><?php echo $transact->order_id; ?></td>
                        <td><?php echo $transact->merchant_id; ?></td>
                        <td><?php echo $transact->amount; ?></td>
                        <td><?php echo $transact->transaction_type; ?></td>
                        <td><?php echo $transact->status; ?></td>
                        <td><?php echo $transact->description; ?></td>
                        <td><a href="admin.php?page=computop_transactions&transaction=<?php echo $transact->transaction_id ?>">Détails</a></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } else {
            echo '<p>' . __( 'No transactions for this order.', 'mercanet' ) . '<p>';
        }
    }
}
