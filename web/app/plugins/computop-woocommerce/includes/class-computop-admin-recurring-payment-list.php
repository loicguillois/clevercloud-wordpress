<?php

class Computop_Admin_Recurring_Payment_List {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public function __construct() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_settings_computop_recurring_payment_list', array($this, 'settings_recurring_payment_list_tab'));
        add_action('wp_ajax_get_list_customer_autocomp', array($this, 'get_list_customer_autocomp'), 50);
        add_action('wp_ajax_get_content_recurring_payment_list', array($this, 'get_content_recurring_payment_list_ajax'), 50);
        add_action('wp_ajax_update_recurring_payment_status', array($this, 'update_recurring_payment_status'), 50);
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['settings_computop_recurring_payment_list'] = __('Axepta recurring payment list', 'computop');

        return $settings_tabs;
    }

    /**
     * Get config of tab
     *
     * @return array
     */
    public function get_setting_payment_list_tab() {
        
        $settings = array(
            'computop_general' => array(
                'title' => __('Axepta recurring payment list', 'computop'),
                'type' => 'title',
                'id' => 'computop_general_title'
            ),
            'section_end' => array(
                'type' => 'sectionend',
            )
        );
        return apply_filters('wc_settings_computop_recurring_payment_list', $settings);
    }

    /**
     * Get user list for autocomplete
     * 
     * @global type $wpdb
     */
    public function get_list_customer_autocomp() {
        global $wpdb;

        $customer_ids = $wpdb->get_col(<<<SQL
            SELECT DISTINCT user_id FROM $wpdb->usermeta 
            WHERE  (meta_key = 'last_name' AND meta_value LIKE "{$_POST['term']}%")
                OR (meta_key = 'first_name' AND meta_value LIKE "{$_POST['term']}%")
SQL
        );
        $list_autocomp = [];
        foreach ($customer_ids as $customer_id) {
            $customer = new WP_User($customer_id);
            $list_autocomp[] = array(
                "id" => $customer_id,
                "firstname" => $customer->user_firstname,
                "lastname" => $customer->user_lastname
            );
        }
        echo json_encode($list_autocomp);
        wp_die();
    }

    /**
     * Show reccuring payment list table
     *
     * @return void
     */
    public function settings_recurring_payment_list_tab() {
        global $hide_save_button;
        $hide_save_button = true;
        woocommerce_admin_fields($this->get_setting_payment_list_tab());
        
        $plugin_base_name = explode('/', plugin_basename(__FILE__))[0];
        wp_register_script('computop-back-recurring-payment-list', plugins_url("$plugin_base_name/assets/js/back-recurring-payment-list.js"));
        wp_enqueue_script('computop-back-recurring-payment-list');
        
        $fn = function ($fn) {
            return $fn;
        };
        $styles = $this->get_content_styles_table();
        $filter = $this->get_content_filter_table();
        $tbody = $this->get_content_recurring_payment_list(Computop_Recurring_Payment::get_recucrring_payment_list_with_filter());
        echo <<<HTML
            $styles
            <table class="wp-list-table widefat fixed striped posts sortable" id="recurring-payment-list">
                <thead>
                    $filter
                    <tr>
                        <td class="manage-column column-sku sortable desc">{$fn(__('Customer', 'computop'))}</td>
                        <td colspan=2>{$fn(__('Product', 'computop'))}</td>
                        <td>{$fn(__('Type', 'computop'))}</td>
                        <td>{$fn(__('Subscription price', 'computop'))}</td>
                        <td>{$fn(__('Periodicity', 'computop'))}</td>
                        <td>{$fn(__('Order date', 'computop'))}</td>
                        <td>{$fn(__('Nb valid payment', 'computop'))}</td>
                        <td>{$fn(__('Last payment', 'computop'))}</td>
                        <td>{$fn(__('Status', 'computop'))}</td>
                        <td colspan=2 style="text-align:center">{$fn(__('Actions', 'computop'))}</td>
                    </tr>
                </thead>
                <tbody id="recurring-payment-list-content">
                    $tbody
                </tbody>
            </table>
HTML;
    }

    /**
     * Get DOM CSS
     * 
     * @return string
     */
    public function get_content_styles_table() {
        $style = <<<CSS
        <style>
            #filter-lign {width: 100%; table-layout: fixed;}
            #filter-lign td input[type=text] {max-width: 90%; padding: 0 2px; margin: 0; line-height: 28px;}
            #filter-lign td #filter_date_add, 
            #filter-lign td #filter_date_add_end {width: 45%;} 
            #recurring-payment-list .actions-lign .dashicons {padding:0 5px; font-size:2em;}
            #recurring-payment-list .actions-lign .dashicons:hover { cursor: pointer; }
            #recurring-payment-list .actions-lign .dashicons.dashicons-yes {color: #1bb11b;}
            #recurring-payment-list .actions-lign .dashicons.dashicons-no-alt {color: #d42424;}
            #recurring-payment-list-content .loading {text-align: center;}
            #recurring-payment-list-content .loading  img{height: 30px; padding: 10px;}
        </style>
CSS;
        return $style;
    }

    /**
     * Get DOM filter row
     * 
     * @return string
     */
    public function get_content_filter_table() {
        $fn = function ($fn) {
            return $fn;
        };
        $recurring_products = Computop_Recurring_Payment::get_recurring_products();
        $options_product = "<option value='-1'> {$fn(__('Product', 'computop'))} ... </option>";
        foreach ($recurring_products as $recurring_product) {
            $product = wc_get_product($recurring_product->id_product);
            $options_product .= "<option value='{$recurring_product->id_product}'>{$product->get_title()}</option>";
        }

        $html = <<<HTML
            <tr id="filter-lign">         
                <td>
                    <input type="text" id="filter_id_customer" class="filter-recurring-payment"  placeholder="{$fn(__('Customer', 'computop'))} ...">
                    <input type="hidden" id="filter_id_customer_key" /> 
                </td>
                <td colspan=2><select id="filter_id_product" class="filter-recurring-payment" >$options_product</select></td>
                <td></td>
                <td></td>
                <td>
                    <select id="filter_periodicity" class="filter-recurring-payment" >
                        <option value="-1">{$fn(__('Periodicity', 'computop'))} ...</option>
                        <option value="D">{$fn(__('Day', 'computop'))}</option>
                        <option value="M">{$fn(__('Month', 'computop'))}</option>
                    </select>
                </td>
                <td colspan=2>
                    <input type="text" id="filter_date_add" class="filter-recurring-payment"  placeholder="{$fn(__('Beginning', 'computop'))} ...">
                    <input type="text" id="filter_date_add_end" class="filter-recurring-payment"  placeholder="{$fn(__('End', 'computop'))}  ...">
                </td>
                <td></td>
                <td></td>
                <td>
                    <select id="filter_status" class="filter-recurring-payment" >
                        <option value="-1">{$fn(__('Status', 'computop'))} ...</option>
                        <option value="1">{$fn(__('In progress', 'computop'))}</option>
                        <option value="2">{$fn(__('Problem', 'computop'))}</option>
                        <option value="3">{$fn(__('Canceled', 'computop'))}</option>
                    </select>
                </td>
                <td>
                    <input type="submit" class="button-primary woocommerce-save-button" value="{$fn(__('Reset filters', 'computop'))}" id="reset-filter" />
                </td>
            </tr>
HTML;
        return $html;
    }

    /**
     * Get DOM of recurring payment list
     * 
     * @return string
     */
    public function get_content_recurring_payment_list($list) {
        $fn = function ($fn) {
            return $fn;
        };
        $tbody = (sizeof($list) == 0) ? "<tr><td colspan=12>".__('No subscription register yet.', 'computop')."</td></tr>" : "";
        
        /*if (!Computop_Api::is_allowed('ABO')) {
            return '<tr><td colspan=12 style=text-align:center>'.__('Please contact Axepta BNP Paribas to active subscriptions in your module.', 'computop')."</td></tr>";
        }*/
        
        foreach ($list as $recurring_payment) {
            $status = "";
            switch ($recurring_payment->status) {
                case "1" :
                    $status = "<a href='post.php?post=".$recurring_payment->id_order."&action=edit#woocommerce-order-transactions'><mark class='order-status status-processing'><span>{$fn(__('In progress', 'computop'))}</span></mark></a>";
                    break;
                case "2" :
                    $status = "<a href='post.php?post=".$recurring_payment->id_order."&action=edit#woocommerce-order-transactions'><mark class='order-status status-failed'><span>{$fn(__('Problem', 'computop'))}</span></mark></a>";
                    break;
                case "3" :
                    $status = "<a href='post.php?post=".$recurring_payment->id_order."&action=edit#woocommerce-order-transactions'><mark class='order-status status-pending'><span>{$fn(__('Canceled', 'computop'))}</span></mark></a>";
                    break;
            }
            
            if ($recurring_payment->periodicity == "D") {
                if ($recurring_payment->number_periodicity > 1) {
                    $periodicity_lang = __('Days', 'computop');
                } else {
                    $periodicity_lang = __('Day', 'computop');
                }
            } else {
                $periodicity_lang = __('Month', 'computop');
            }

            $customer = get_user_by('ID', $recurring_payment->id_customer);
            $user_link = "<a href='" . get_edit_user_link($recurring_payment->id_customer) . "' target='_blank'>{$customer->user_firstname} {$customer->user_lastname}</a>";
            $product = wc_get_product($recurring_payment->id_product);
            $product_link = "<a href='" . get_edit_post_link($recurring_payment->id_product) . "' target='_blank'>{$product->get_title()}</a>";
            $currency = get_woocommerce_currency_symbol(get_option('woocommerce_currency'));
            $periodicity = $periodicity_lang;
            $date_add = new DateTime($recurring_payment->date_add);
            $last_schedule = new DateTime($recurring_payment->last_schedule);
            $transaction = Computop_Transaction::get_by_id($recurring_payment->id_computop_transaction);            
            $type = ($transaction->payment_mean_brand === "SEPA_DIRECT_DEBIT") ? "SDD" : "Abonnement";
            
            $tbody .= <<<HTML
                <tr name="{$recurring_payment->id_computop_customer_payment_recurring}">
                    <td>$user_link</td>
                    <td colspan=2>$product_link</td>
                    <td>$type</td>
                    <td>{$recurring_payment->current_specific_price} $currency</td>
                    <td>{$recurring_payment->number_periodicity} $periodicity</td>
                    <td>{$date_add->format('d/m/Y')}</td>
                    <td>{$recurring_payment->current_occurence}</td>
                    <td>{$last_schedule->format('d/m/Y')}</td>
                    <td class="status-lign">$status</td>
                    <td class="actions-lign" colspan=2 style="text-align:center">
                        <span class="dashicons dashicons-yes" name="enable" title="{$fn(__('Enable', 'computop'))}"></span>
                        <span class="dashicons dashicons-no-alt" name="disable" title="{$fn(__('Disable', 'computop'))}"></span>
                        <a href='post.php?post={$recurring_payment->id_order}&action=edit#woocommerce-order-transactions'><i class="dashicons dashicons-visibility" title="{$fn(__('View', 'computop'))}"></i></a>
                        <span class="dashicons dashicons-trash" name="remove" title="{$fn(__('Remove', 'computop'))}"></span>
                    </td>
                </tr>
                        
HTML;
        }

        return $tbody;
    }

    public function get_content_recurring_payment_list_ajax() {

        unset($_POST['action']);
        $filters = array();
        foreach ($_POST['filters'] as $key => $value) {
            $field = substr($key, 7);
            switch ($field) {
                case "date_add" :
                    $date = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    $filters[] = array(
                        "field" => $field,
                        "operator" => " >= ",
                        "value" => '"' . $date . ' 00:00:00"'
                    );
                    break;
                case "date_add_end" :
                    $date = date('Y-m-d', strtotime(str_replace('-', '/', $value)));
                    $filters[] = array(
                        "field" => "date_add",
                        "operator" => " <= ",
                        "value" => '"' . $date . ' 23:59:59"'
                    );
                    break;
                default :
                    $filters[] = array(
                        "field" => $field,
                        "operator" => "=",
                        "value" => '"' . $value . '"'
                    );
                    break;
            }
        }

        $recurring_payment_list = Computop_Recurring_Payment::get_recucrring_payment_list_with_filter($filters);
        $content = $this->get_content_recurring_payment_list($recurring_payment_list);

        echo json_encode(array(
            "html" => $content
        ));
        wp_die();
    }

    /**
     * Update status of recurring payment
     * 
     * @global type $wpdb
     * @return void
     */
    public function update_recurring_payment_status() {
        global $wpdb;
        $datas = $_POST;
        switch ($datas['action_libelle']) {
            case "enable" :
                $result = $wpdb->update(
                        $wpdb->prefix . "computop_customer_payment_recurring", array(
                    'status' => 1
                        ), array(
                    'id_computop_customer_payment_recurring' => $_POST['recurring_payment_id']
                        )
                );
                $status = '<mark class="order-status status-processing"><span>' . __('In progress', 'computop') . '</span></mark>';
                break;
            case "disable" :
                $result = $wpdb->update(
                        $wpdb->prefix . "computop_customer_payment_recurring", array(
                    'status' => 3
                        ), array(
                    'id_computop_customer_payment_recurring' => $_POST['recurring_payment_id']
                        )
                );
                $status = '<mark class="order-status status-pending"><span>' . __('Canceled', 'computop') . '</span></mark>';
                break;
            case "remove" :
                $result = $wpdb->delete("{$wpdb->prefix}computop_customer_payment_recurring", array("id_computop_customer_payment_recurring" => $_POST['recurring_payment_id']));
                $status = "";
                break;
        }

        echo json_encode(array(
            "result" => $result,
            "html" => $status
        ));

        wp_die();
    }

}

new Computop_Admin_Recurring_Payment_List();
