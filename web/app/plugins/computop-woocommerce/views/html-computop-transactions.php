<div class="wrap">
    <h2><?php echo $this->title ?></h2>
    <?php if ( ! empty( $this->transactions ) ) { ?>
        <table class="wp-list-table widefat fixed">
            <thead>
                <tr>
                    <th>Date</th>
                    <th scope="col"> <?php echo __( 'Payment brand', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'N° Transaction', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'N° Order', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Merchant Id', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'PayID', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'XID', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'CCExpiry', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Amount', 'computop' ) ?></th>
                    <th scope="col"> <?php echo strtoupper(__( 'Operation type', 'computop' )) ?></th>
                    <th scope="col"> <?php echo __( 'Status', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Message response', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Code response', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Raw data', 'computop' ) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach( $this->transactions as $transact ) { ?>
                <tr id="<?php echo $transact->transaction_id ?>">
                    <td><?php echo date_i18n( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ), strtotime( $transact->transaction_date ) ); ?></td>
                    <td>
                        <?php $payment_method_array = explode('-', get_post_meta($transact->order_id, 'payment_mean_brand_one')[0]) ?? null; ?>
                        <?php if ( ! empty( $transact->payment_mean_brand ) ) { ?>
                            <img alt="<?php echo $transact->payment_mean_brand; ?>" src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname( __DIR__ ) ) . '/assets/img/' . strtoupper($transact->payment_mean_brand) . '.png'; ?>" heigth="32px" width="32px">
                        <?php } elseif(!is_null($payment_method_array)) { ?>
                        <?php if($payment_method_array[0] == 'oneclick') { 
                            $payment_method_array_trigram = $payment_method_array[1];
                        } else {
                            $payment_method_array_trigram = $payment_method_array[0];                            
                        }
                        ?>
                            
                            <img alt="<?php echo $payment_method_array_trigram; ?>" src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname( __DIR__ ) ) . '/assets/img/' . strtoupper($payment_method_array_trigram) . '.png'; ?>" heigth="32px" width="32px">
                        <?php } ?>
                    </td>
                    <td><?php echo $transact->transaction_reference; ?></td>
                    <td><?php echo $transact->order_id; ?></td>
                    <td><?php echo $transact->merchant_id; ?></td>
                    <td><?php echo $transact->pay_id; ?></td>
                    <td><?php echo $transact->xid; ?></td>
                    <td><?php echo $transact->ccexpiry; ?></td>
                    <td><?php echo $transact->amount; ?></td>
                    <td><?php echo $transact->transaction_type; ?></td>
                    <td><?php echo $transact->status; ?></td>
                    <td><?php echo $transact->description; ?></td>
                    <td><?php echo $transact->response_code; ?></td>
                    <td><a href="admin.php?page=computop_transactions&transaction=<?php echo $transact->transaction_id ?>">Détails</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <p align="center">Page :
        <?php for ( $i = 1; $i <= $this->nb_pages; $i++ ) {
             if ( $i == $this->current_page ) {
                 echo '[' . $i . ']';
             }
             else {
                echo '<a href="admin.php?page=computop_transactions&paged=' . $i . '">' . $i . '</a> ';
             }
        }
        echo '</p>';
    } else {
        echo '<p>' . __( 'No transactions.', 'computop' ) . '<p>';
    } ?>
</div>
