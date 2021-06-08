<div class="wrap">
    <h2><?php echo $this->title ?></h2>
    <?php echo $this->show_notices(); ?>
    <?php if ( ! empty( $this->accounts ) ) { ?>
        <table class="wp-list-table widefat fixed">
            <thead>
                <tr>
                    <th scope="col"> <?php echo __( 'N° Account', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Merchant Id', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Country', 'computop' ) ?></th>
                    <th scope="col"> <?php echo __( 'Status', 'computop' ) ?></th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach( $this->accounts as $account ) { ?>
                <tr id="<?php echo $account->account_id ?>">
                    <td><?php echo $account->account_id; ?></td>
                    <td><a href="?page=wc-settings&tab=setting_computop_account&action=edit&id=<?php echo $account->account_id; ?>"><?php echo $account->name; ?></a></td>
                    <td><?php 
                    $countries_obj   = new WC_Countries();
                    $countries   = $countries_obj->get_allowed_countries();
                    $countries = array('ALL' => 'Tous les pays') + $countries;
                    
                    $list_countries = '';
                    foreach(explode(';', $account->country) as $value)
                    {
                        $list_countries .= $countries[$value].', ';
                    }
                    
                    echo substr($list_countries, 0, -2); 
                    
                    ?></td>
                    <td><a class="wc-merchant-account-toggle-enabled" href="?page=wc-settings&tab=settings_computop_credentials&toggle_account_merchant_id=<?php echo $account->account_id ?>">
                    <?php if ($account->is_active) { ?>
                    <span class="woocommerce-input-toggle woocommerce-input-toggle--enabled" aria-label="<?php echo __( 'This account is active', 'computop' ) ?>"></span>
                    <?php } else { ?>
                    <span class="woocommerce-input-toggle woocommerce-input-toggle--disabled" aria-label="<?php echo __( 'This account is disable', 'computop' ) ?>"></span>
                    <?php } ?>
                    </a></td>
                    <td><a class="button alignright" href="?page=wc-settings&tab=setting_computop_account&action=edit&id=<?php echo $account->account_id; ?>">Configuration</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php 
    } else {
        echo '<p>' . __( 'No accounts.', 'computop' ) . '<p>';
    } ?>
</div>
