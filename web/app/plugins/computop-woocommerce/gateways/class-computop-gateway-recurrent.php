<?php

    add_action('plugins_loaded', 'init_computop_gateway_recurring_class');
    add_filter('woocommerce_payment_gateways', 'add_computop_gateway_recurring_class');
    add_action('save_post', 'Computop_Gateway_Recurring::save_recurring_payment');

function init_computop_gateway_recurring_class() {

    function add_computop_gateway_recurring_class($methods) {
        
        $methods[] = 'Computop_Gateway_Recurring';
        return $methods;
    }

    /**
     * Computop Recurrent Payement Gateway Class
     */
    class Computop_Gateway_Recurring extends Computop_Gateway_Onetime {

        const ID_STATUS_ACTIVE = 1;
        const ID_STATUS_PAUSE = 2;
        const ID_STATUS_EXPIRED = 3;
        const RECURRING = 'recurring';
        
        private $abo_enabled = false;
        
        public function __construct() {
            $this->id = 'computop_recurring';
            $this->method_title = __('Axepta recurring payment', 'computop');
            $this->has_fields = true;
            $this->title = __('Recurring Payment', 'computop');
            $this->order_button_text = __('Pay', 'computop');

            // Gateway name  
            $this->init_settings();
            $this->init_form_fields();

            //$check_enabled = Computop_Api::is_allowed('ABO');
            if (/*$check_enabled ||*/ Computop_Api::is_allowed_abo())
                $this->abo_enabled = true;

            $this->onetime_title_names = get_option('computop_recurring_title_names');
            if (empty($this->onetime_title_names)) {
                $this->onetime_title_names = array(
                    'en_US' => array(
                        'title_name' => 'Card recurring by Axepta BNP Paribas',
                    ),
                    'fr_FR' => array(
                        'title_name' => 'Paiement par abonnement sécurisé avec Axepta BNP Paribas',
                    )
                );
            }
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_computop_response'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_title_names'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('before_woocommerce_pay', array($this, 'check_order'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_available_payment_gateways', array($this, 'check_gateway'));
            $locale = Computop_Api::get_locale();
            if (!isset($this->onetime_title_names[$locale])) {
                $title_locale = $this->onetime_title_names['en_US'];
            } else {
                $title_locale = $this->onetime_title_names[$locale];
            }

            $this->settings['computop_recurring_title'] = $title_locale['title_name'];
            $this->title = $this->settings['computop_recurring_title'];

        }

        public function check_gateway($gateways) {
            global $woocommerce, $wp;
            if (isset($gateways[$this->id])) {
                $is_abo = false;
                if (is_object($woocommerce->cart) && sizeof($woocommerce->cart->get_cart()) > 0) {
                    $items = $woocommerce->cart->get_cart();
                    foreach ($items as $item) {
                        $infos = Computop_Recurring_Payment::get_recurring_infos($item['product_id']);
                        if (!empty($infos)) {
                            $is_abo = true;
                        }
                    }
                } else if (isset($wp->query_vars['order-pay'])) {
                    
                    if ($wp->query_vars['order-pay'] != "") {
                        $order_id = absint($wp->query_vars['order-pay']);
                        $order = new WC_Order($order_id);
                        $order_items = $order->get_items();
                        foreach ($order_items as $item) {
                            $infos = (is_object($item)) ?
                                    Computop_Recurring_Payment::get_recurring_infos($item->get_product_id()) :
                                    Computop_Recurring_Payment::get_recurring_infos($item['item_meta']['_product_id'][0]);

                            if (!empty($infos)) {
                                $is_abo = true;
                            }
                        }
                    }
                }

                if ($is_abo) {
                    $tmp_gateway = $gateways[$this->id];
                    $gateways = array();
                    $gateways[$this->id] = $tmp_gateway;
                } else {
                    unset($gateways[$this->id]);
                }
            }
            return $gateways;
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'computop_recurring_title' => array(
                    'type' => 'title_name'
                ),
                'enabled' => array(
                    'title' => __('Activation', 'computop'),
                    'type' => 'checkbox',
                    'label' => __('Active Recurring Payment', 'computop'),
                    'default' => 'no'
                )
            );

            if (get_option('label_translate_on') == 'yes') {
                unset($this->form_fields['title']);
            } else {
                unset($this->form_fields['computop_recurring_title']);
            }
        }

        /**
         * After payment completed
         * @param type $order_id
         */
        public function thankyou_page($order_id) {
            $transaction = Computop_Transaction::get_by_orderid($order_id);

            if (!empty($transaction)) {
                echo '<ul class="order_details">';
                echo '<li class="method">' . __( 'N° Transaction', 'computop' ) . '<strong>' . $transaction->transaction_reference . '</strong></li>';
                echo '</ul>';
            }
        }

        public function save_title_names() {
//            $locale = get_locale();
//            $title_names = $_POST['title_name'];
//            $names = array();
//
//            foreach ($title_names as $lang => $name) {
//                if ($lang == $locale) {
//                    if (empty(trim($name))) {
//                        $errors[] = __('You have to choose a name for the recurring payment in the current language.', 'computop');
//                    }
//                }
//                $tmp = array(
//                    $lang => array(
//                        'title_name' => $name
//                    )
//                );
//                $names = array_merge($names, $tmp);
//            }
//            if (!empty($errors)) {
//                $this->errors = $errors;
//                foreach ($errors as $key => $value) {
//                    WC_Admin_Settings::add_error($value);
//                }
//            } else {
//                update_option('computop_recurring_title_names', $names);
//            }
        }

        public static function add_form_computop_recurring_payment_admin() {
            echo '';
            
            if(/*Computop_Api::is_allowed('ABO') ||*/ Computop_Api::is_allowed_abo()) {
                $product = Computop_Recurring_Payment::get_recurring_infos(get_the_ID());
                $form_fields = array(
                    array(
                        'type' => 'hidden',
                        'id' => 'id_computop_payment_recurring',
                        'name' => 'id_computop_payment_recurring',
                        'value' => $product[0]->id_computop_payment_recurring ?? ''
                    ),
                    array(
                        'label' => 'Type',
                        'type' => 'select',
                        'style' => 'width: 95%',
                        'id' => 'computop_type',
                        'name' => 'computop_type',
                        'value' => $product[0]->type ?? '',
                        'options' => array(
                            '1' => __('Simple Payment', 'computop'),
                            '2' => __('Recurring Payment', 'computop')
                        )
                    ), array(
                        'label' => __('Periodicity', 'computop'),
                        'type' => 'text',
                        'id' => 'computop_number_periodicity',
                        'name' => 'computop_number_periodicity',
                        'value' => $product[0]->number_periodicity ?? ''
                    ), array(
                        'label' => __('Type of occurrence', 'computop'),
                        'type' => 'select',
                        'style' => 'width: 95%',
                        'id' => 'computop_periodicity',
                        'name' => 'computop_periodicity',
                        'value' => $product[0]->periodicity ?? '',
                        'options' => array(
                            'D' => __('Day', 'mercanet'),
                            'M' => __('Month', 'mercanet')
                        )
                    ), array(
                        'label' => __('Number of occurrences (0 = unlimited)', 'computop'),
                        'type' => 'text',
                        'id' => 'computop_number_occurrences',
                        'name' => 'computop_number_occurrences',
                        'value' => $product[0]->number_occurences ?? ''
                    ), array(
                        'label' => __('Recurring amount', 'computop'),
                        'type' => 'text',
                        'id' => 'computop_recurring_amount',
                        'name' => 'computop_recurring_amount',
                        'value' => $product[0]->recurring_amount ?? ''
                    )
                );

                foreach ($form_fields as $field) {
                    switch ($field['type']) {
                        case 'hidden' : woocommerce_wp_hidden_input($field);
                            break;
                        case 'text' : woocommerce_wp_text_input($field);
                            break;
                        case 'select' : woocommerce_wp_select($field);
                            break;
                    }
                }
            }
            
        }

        public static function validate_recurring_admin() {
            

            $errors = array();

            if (!empty($_POST['computop_number_occurrences']) && !empty($_POST['computop_recurring_amount'])) {

                $nb_occurences = $_POST['computop_number_occurrences'];
                $amount = $_POST['computop_recurring_amount'];

                if (filter_var($nb_occurences, FILTER_VALIDATE_INT) === false) {
                    $errors[] = __('The occurence number must contain only numeric.', 'computop');
                }
                if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
                    $errors[] = __('The amount must contain only numeric.', 'computop');
                }
            }
            if (!empty($errors)) {
                foreach ($errors as $key => $value) {
                    WC_Admin_Settings::add_error($value);
                }
            }

        }
        
        /**
         * add hidden field in checkout form
         * @param type $checkout
         */
        public function my_custom_checkout_hidden_field() {
            // Output the hidden link
            echo '<input type="hidden" id="payment_option_one" name="payment_option_one" />'.
               '<input type="hidden" id="payment_mean_brand_one" name="payment_mean_brand_one"/>'.
               '<input type="hidden" id="register_payment" name="register_payment" value="no" />';
        }
        
        public function payment_fields() {
            
                $this->my_custom_checkout_hidden_field();

                global $wp;
                $this->load_front_css_js();

                $html_list_payment = '';

                $cards_list = Computop_Api::get_all_available_cards(true);

                $html_list_payment .= '<div id="computop_one_time_cards">';

                $html_list_card = "";

                foreach ($cards_list as $key => $card) {
                    if($card->trigram == 'VIM' ||  $card->trigram == 'CVM')
                    {
                        unset($cards_list[$key]);
                        $array_code = explode('/', $card->code);
                        $array_label = explode('/', $card->code);

                        foreach ($array_code as $key2 => $code)
                        {
                            $new_card = [
                                'id' => null,
                                'trigram' => $card->trigram,
                                'code' => $code,
                                'method_id' => $card->method_id,
                                'currency' => $card->currency,
                                'country' => $card->country,
                                'iso' => $card->iso,
                                'iso' => $array_label[$key2],
                            ];


                            array_unshift($cards_list, (object)$new_card);
                        }
                    }
                }

                foreach ($cards_list as $card) {
                    $icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__DIR__)) . '/assets/img/' . strtoupper($card->code) . '.png';

                    $html_list_card .= 
                        "<div class=\"computop-display-card\">
                            <button onClick=\"check_option(null,'$card->trigram','one');\" value=\"$card->code\">
                                <img style=\"display:block;\" style=\"float:right;\" src=\"$icon\" title=\"$card->code\" alt=\"$card->code\"/>
                            </button>
                        </div>";

                }

                $html_list_payment .= $html_list_card .
                    '<div class="computop-clear" style="padding:0">
                </div>';

            $html_list_payment .= '</div>';

            echo $html_list_payment;
            
        }

        public function validate_fields() {
            $trigram = $_POST['payment_mean_brand_one'] ?? null;
            if (empty($trigram)) {
                wc_add_notice(__('Please choose a card to proceed at the payment.', 'computop'), 'error');
            } else {
                $order_id = $_GET['order-pay'] ?? null;
                if($trigram !== get_post_meta($order_id, 'payment_mean_brand_one')[0])
                {
                    update_post_meta($order_id, 'payment_mean_brand_one', $trigram);
                }
            }
        }

        public static function save_recurring_payment() {
            
            self::validate_recurring_admin();
            
            if (!empty($_POST['computop_type']) &&
                !empty($_POST['computop_periodicity']) &&
                !empty($_POST['computop_number_periodicity']) &&
                (int)$_POST['computop_number_occurrences'] >= 0 &&
                !empty($_POST['computop_recurring_amount'])) {
                
                global $wpdb;
                $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}computop_payment_recurring WHERE id_computop_payment_recurring = '{$_POST['id_computop_payment_recurring']}'");

                if (!empty($_POST['id_computop_payment_recurring'])) {
                    $wpdb->update($wpdb->prefix . 'computop_payment_recurring', array(
                        'type' => $_POST['computop_type'],
                        'periodicity' => $_POST['computop_periodicity'],
                        'number_periodicity' => $_POST['computop_number_periodicity'],
                        'number_occurences' => $_POST['computop_number_occurrences'],
                        'recurring_amount' => $_POST['computop_recurring_amount']
                            ), array(
                        'id_computop_payment_recurring' => $_POST['id_computop_payment_recurring']
                            )
                    );
                    
                } else {
                    $wpdb->insert($wpdb->prefix . 'computop_payment_recurring', array(
                        'id_product' => $_POST['post_ID'],
                        'type' => $_POST['computop_type'],
                        'periodicity' => $_POST['computop_periodicity'],
                        'number_periodicity' => $_POST['computop_number_periodicity'],
                        'number_occurences' => $_POST['computop_number_occurrences'],
                        'recurring_amount' => $_POST['computop_recurring_amount']
                            )
                    );
                   
                }
            }
        }

        public static function add_computop_customer_payment_recurring($order_id, $product_id, $pos_quantity, $is_sdd = false, $data) {
            
            global $wpdb;
            $transaction = Computop_Transaction::get_by_orderid($order_id);
            $id_customer = get_post_meta($order_id, '_customer_user', true);
            $infos_recurring = Computop_Recurring_Payment::get_recurring_infos($product_id, $is_sdd);
            $occurence = $infos_recurring[0]->number_occurences;
            $interval = intval($infos_recurring[0]->number_periodicity);
            
            $product = new WC_Product($product_id);
            $product->set_price($infos_recurring[0]->recurring_amount);
            $time = strtotime(date("Y/m/d h:i:s"));
            
            $next_schedule = ($infos_recurring[0]->periodicity == 'D') ?
                    date("Y-m-d h:i:s", strtotime("+$interval day", $time)) : date("Y-m-d h:i:s", strtotime("+$interval month", $time));

            $wpdb->insert($wpdb->prefix . 'computop_customer_payment_recurring', array(
                'id_product' => $product_id,
                'id_tax_rules_group' => $product->get_tax_class(),
                'id_order' => $transaction->order_id,
                'id_customer' => $id_customer,
                'id_computop_transaction' => $transaction->transaction_id,
                'status' => ($transaction->response_code == '00') ? self::ID_STATUS_ACTIVE : self::ID_STATUS_PAUSE,
                'number_periodicity' => $infos_recurring[0]->number_periodicity,
                'periodicity' => $infos_recurring[0]->periodicity,
                'bid' => $data['billingagreementid'],
                'pcnr' => $data['PCNr'],
                'ccexpiry' => $data['CCExpiry'],
                'ccbrand' => $data['CCBrand'],
                'number_occurences' => $occurence,
                'current_occurence' => '0',
                'date_add' => date('Y-m-d h:i:s'),
                'last_schedule' => date('Y-m-d h:i:s'),
                'next_schedule' => $next_schedule,
                'current_specific_price' => wc_get_price_including_tax($product),
                'id_cart_paused_currency' => $pos_quantity
                    )
            );
            return (empty($wpdb->last_error)) ? $wpdb->insert_id : false;
        }

        public static function update_computop_customer_payment_recurring($id, $params) {
            global $wpdb;
            if (is_object($params))
                $params = json_decode(json_encode($params), True);


            $wpdb->update(
                    $wpdb->prefix . 'computop_customer_payment_recurring', $params, array('id_computop_customer_payment_recurring' => $id)
            );

            return (empty($wpdb->last_error)) ? true : false;
        }

        public static function remove_computop_customer_payment_recurring($order_id, $user_id) {
            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'computop_customer_payment_recurring', array('id_order' => $order_id, 'id_customer' => $user_id));
            return (empty($wpdb->last_error)) ? true : false;
        }

        public function check_order() {
            global $wp;
            session_start();
            $order_id = $wp->query_vars['order-pay'];
            $order = wc_get_order($order_id);
            if (isset($_GET['pay_for_order'])) {
                $_SESSION['pay_for_order'] = $order_id;
            }

            if (isset($_SESSION['pay_for_order']) && $_SESSION['pay_for_order'] != $order_id && $order->get_total() != WC()->cart->total) {
                $order->remove_order_items('line_item');
                $order->remove_order_items('tax');
                foreach (WC()->cart->cart_contents as $item) {
                    $order->add_product($item['data']);
                }
                $tax_total = reset(WC()->cart->get_tax_totals());
                $order->add_tax($tax_total->tax_rate_id, WC()->cart->get_taxes_total());
                $order->set_total(WC()->cart->total, 'total');
            }
        }

        public function receipt_page($order_id) {
            $checkout_page_id = wc_get_page_id( 'checkout' );
            $url_back = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
            $order = wc_get_order($order_id);
            
            $payment_method = get_post_meta($order_id, 'payment_mean_brand_one')[0];
            
            $user_id = get_current_user_id();
            
            if(get_post_meta($order_id, 'payment_mean_brand_one')[0] !== $_COOKIE['payment_mean_brand_one'])
            {
                 update_post_meta($order_id, 'payment_mean_brand_one', $_COOKIE['payment_mean_brand_one']);
                 unset($_COOKIE['payment_mean_brand_one']);
            }
            
            if (empty($payment_method)) {
                wc_add_notice(__('Please choose a card to proceed at the payment.', 'computop'), 'error');
                wp_redirect($url_back);
                exit;
                return false;
            }
            
            if ($user_id == 0) {
                wc_add_notice( 'You must be connected to make recurring payment', 'error' );
                wp_redirect($url_back);
                exit;
                return false;
            }
            
            $order = new WC_Order($order_id);
            
            
            if (empty($order)) {
                return false;
            }
 
            
            $return_url = plugin_dir_url(__DIR__).'routes/recurring/success.php';
            if (strpos($return_url, 'https') === false) {
                $return_url = str_replace("http", "https", $return_url);
            }

            $params = Computop_Api::get_params($order, $return_url, Computop_API::PAYMENT, null, null, 'new_abo');

            $url = Computop_Api::get_payment_url('payment', $payment_method);
                    
            $computop = new Computop();
            $merchant = $computop->get_merchant_account(true);

            if($merchant->display_card_method == 'IFRAME' && $payment_method != 'PAL')
            {
                $url_back = plugin_dir_url(__DIR__).'routes/iframe/redirect.php';
                if (strpos($url_back, 'https') === false) {
                    $url_back = str_replace("http", "https", $url_back);
                }
                return Computop_Payment::generate_iframe_payment($merchant->name, $params['Data'], $params['Len'], $url, $url_back);
            } else {
                return Computop_Payment::generate_direct_payment($merchant->name, $params['Data'], $params['Len'], $url, $url_back);
            }
            
        }
        
        public static function check_iframe_redirect($order, $merchant) {
            $script_redirect_iframe = false;
            
            if ($merchant->display_card_method == 'IFRAME') {
                $script_redirect_iframe = <<<JS
                <script type="text/javascript">
                    top.location.href='{$order->get_checkout_order_received_url()}';
                </script>
JS;
            }

            return $script_redirect_iframe;
        }

        public static function check_computop_response() {
            global $woocommerce;
            $params = $_GET;
            
            $data = $_GET["Data"];
            $len = $_GET["Len"];
            
            $computop = new Computop();
            $merchant = $computop->get_merchant_account(true);
            
            $plaintext = Computop_Api::ctDecrypt($data, $len, $merchant->password);

            $a = explode('&', $plaintext);
            $data = Computop_Api::ctSplit($a);
            
            // LOG
            if (get_option('computop_log_active') == 'yes') {

                $message = 'Computop Response ';
                $message .= ' || ';
                $message .= ' Params: ';
                $message .= implode(', ', array_map(function ($v, $k) {
                            return $k . '=' . $v;
                        }, $data, array_keys($data)));
                $message .= ' ----------------------------------------------------------------------- ';
                Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
            }
            
            $order_id = Computop_Api::get_order_id_from_trans_id($data['TransID']);
            
            $order = new WC_Order($order_id);
            $iframe_redirect = self::check_iframe_redirect($order, $merchant);
            $return_url = plugin_dir_url(__DIR__).'routes/recurring/success.php';

            $transaction_id = Computop_Transaction::save($data, Computop_Api::RECCURING, $order, $plaintext);
            
            $transact = Computop_Transaction::get_by_id($transaction_id);

            if ($data['Code'] == '00000000') {
                
            $items = WC()->cart->get_cart();
            
            foreach ($items as $item) {
                for ($i = intval($item['quantity']); $i > 0; $i--) {
                // traitement récurrring
                $infos = Computop_Recurring_Payment::get_recurring_infos($item['product_id'], false);
                $result = null;
                if (!empty($infos) && empty($result)) {
                    $return = self::add_computop_customer_payment_recurring($order_id, $item['product_id'], $i, false, $data);
                    
                    if ($return && isset($item['rf_order_id'])) {
                        
                        self::remove_computop_customer_payment_recurring($item['rf_order_id'], get_current_user_id());
                        $time = strtotime(date("Y/m/d h:i:s"));
                        $interval = intval($infos[0]->number_periodicity);
                        $next_schedule = ($infos[0]->periodicity == 'D') ?
                                date("Y-m-d h:i:s", strtotime("+$interval day", $time)) : date("Y-m-d h:i:s", strtotime("+$interval month", $time));
                        self::update_computop_customer_payment_recurring($return, array("next_schedule" => $next_schedule));
                    }
                    
                }
            }
            }
            }
                        
            Computop_Api::updateOrder($data, $order);
            
            // redirect to the parent page
            if ($iframe_redirect) {
                echo $iframe_redirect;
                exit;
            }
            wp_redirect($order->get_checkout_order_received_url());
            exit;

        }
    }
}