<?php

class Computop_Admin_Transactions
{

    public function __construct() {
        add_action('admin_menu', array($this, 'register_computop_transactions_submenu_page'));
    }


    function register_computop_transactions_submenu_page() {
        add_submenu_page( 'woocommerce', 'Computop transactions', 'Transactions', 'manage_woocommerce', 'computop_transactions', array($this, 'computop_transactions_page_callback') );
    }


    function computop_transactions_page_callback() {
        
        global $wpdb;

        if ( isset( $_GET['transaction'] ) ) {
            $transaction_id = $_GET['transaction'];
            $this->title = __( 'Raw data detail - Transaction n° '.$transaction_id, 'computop' );
            $transaction_obj = Computop_Transaction::get_by_id( $transaction_id );
            $response_message = Computop_Api::format_error_message($transaction_obj->response_code);
            $this->transaction = $transaction_obj->raw_data;
            include_once plugin_dir_path( __DIR__ ) . 'views/html-computop-transaction.php';
        } else {
            $this->title = __( 'Transactions', 'computop' );
            $transactions_pages = 40;
            $this->current_page = 0;
            $nb_transactions = $wpdb->get_var( "SELECT COUNT(*) AS total FROM {$wpdb->prefix}computop_transaction" );
            $this->nb_pages = ceil( floatval( $nb_transactions ) / $transactions_pages );

            if ( isset( $_GET['paged'] ) ) {
                 $this->current_page = intval( $_GET['paged'] );

                 if ( $this->current_page > $this->nb_pages ) {
                    $current_page = $this->nb_pages;
                 }
            }
            else {
                 $this->current_page = 1;
            }

            $first_entry = ( $this->current_page - 1 ) * $transactions_pages;
            $this->transactions = Computop_Transaction::get_all_limit( $first_entry, $transactions_pages );
            include_once plugin_dir_path( __DIR__ ) . 'views/html-computop-transactions.php';
        }
    }

}

new Computop_Admin_Transactions();
