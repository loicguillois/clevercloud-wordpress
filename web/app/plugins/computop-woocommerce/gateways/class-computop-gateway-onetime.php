<?php
add_action('plugins_loaded', 'init_computop_gateway_onetime_class');
add_filter('woocommerce_payment_gateways', 'add_computop_gateway_onetime_class');

function init_computop_gateway_onetime_class() {

    function add_computop_gateway_onetime_class($methods) {
        $methods[] = 'Computop_Gateway_Onetime';
        return $methods;
    }

    /**
     * Computop One-time Gateway Class
     */
    class Computop_Gateway_Onetime extends WC_Payment_Gateway {
        
        const MIN_CETELEM_AMOUNT = 9000;
        const MAX_CETELEM_AMOUNT = 300000;
        const MIN_PRESTO_AMOUNT = 15000;
        const MAX_PRESTO_AMOUNT = 1600000;

        public function __construct() {
            $this->id = 'computop_onetime';
            $this->method_title = __('One-time payment by Axepta', 'computop');
            $this->has_fields = true;
            $this->title = __('One-time Payment', 'computop');
            $this->order_button_text = __('Pay', 'computop');
            $this->supports = array('refunds');
            $this->init_settings();
            $this->init_form_fields();

            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this,
                'check_computop_response'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'validate_admin_onetime'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'save_title_names'));
            add_action('woocommerce_thankyou_' . $this->id, array(
                $this,
                'thankyou_page'));
            add_action('woocommerce_receipt_' . $this->id, array(
                $this,
                'receipt_page'));
            add_action('woocommerce_available_payment_gateways', array(
                $this,
                'check_gateway'));

            // Gateway name
            $this->onetime_title_names = get_option('computop_onetime_title_names');
            if (empty($this->onetime_title_names)) {
                $this->onetime_title_names = array(
                    'en_US' => array(
                        'title_name' => 'Pay with Axepta BNP Paribas',
                    ),
                    'fr_FR' => array(
                        'title_name' => 'Payer avec Axepta BNP Paribas',
                    )
                );
            }
            
            $computop = new Computop();
            $merchant = $computop->get_merchant_account();
            
            if ($merchant !== null && $merchant->front_label != 'Axepta BNP Paribas') {
                $this->settings['computop_onetime_title'] = $merchant->front_label;
            } else {
                $locale = Computop_Api::get_locale();
                if (!isset($this->onetime_title_names[$locale])) {
                    $title_locale = $this->onetime_title_names['en_US'];
                } else {
                    $title_locale = $this->onetime_title_names[$locale];
                }
                $this->settings['computop_onetime_title'] = $title_locale['title_name'];
            }

            $this->title = $this->settings['computop_onetime_title'];

            add_action('woocommerce_checkout_update_order_meta', array(
                $this,
                'save_custom_checkout_hidden_field'), 10, 1);
        }

        /**
         * Save custome field form
         * @param type $order_id
         */
        public function save_custom_checkout_hidden_field($order_id) {
            if (!empty($_POST['payment_mean_brand_one']))
                update_post_meta($order_id, 'payment_mean_brand_one', sanitize_text_field($_POST['payment_mean_brand_one']));
            if (!empty($_POST['register_payment']))
                update_post_meta($order_id, 'register_payment', sanitize_text_field($_POST['register_payment']));
            if (!empty($_POST['social_security_number']))
                update_post_meta($order_id, 'social_security_number', sanitize_text_field($_POST['social_security_number']));
        }

        /**
         * add hidden field in checkout form
         * @param type $checkout
         */
        public function my_custom_checkout_hidden_field() {
            // Output the hidden link
            echo '<input type="hidden" id="payment_option_one" name="payment_option_one" />' .
            '<input type="hidden" id="payment_mean_brand_one" name="payment_mean_brand_one"/>' .
            '<input type="hidden" id="register_payment" name="register_payment" value="no" />';
        }

        /**
         * Save the gateways names by languages
         */
        public function save_title_names() {
//            $locale = get_locale();
//            $title_names = $_POST['title_name'] ?? null;
//            $names = array();
//
//            if (is_null($title_names)) {
//                        $errors[] = __('You have to choose a name for the onetime payment in the current language.', 'computop');
//            }
//            
//            if (!empty($errors)) {
//                $this->errors = $errors;
//                foreach ($errors as $key => $value) {
//                    WC_Admin_Settings::add_error($value);
//                }
//            } else {
//                update_option('computop_onetime_title_names', $names);
//            }
        }

        /**
         * load css/js
         */
        public function load_front_css_js() {
            $plugin_base_name = explode('/', plugin_basename(__FILE__))[0];
            wp_register_style('computop-front', plugins_url("$plugin_base_name/assets/css/front.css"));
            wp_enqueue_style('computop-front');
            wp_register_script('computop-front', plugins_url("$plugin_base_name/assets/js/front.js"));
            wp_enqueue_script('computop-front');
        }

        /**
         * After payment completed
         * @param type $order_id
         */
        public function thankyou_page($order_id) {
            $transaction = Computop_Transaction::get_by_orderid($order_id);

            if (!empty($transaction)) {
                echo '<ul class="order_details">';
                echo '<li class="method">' . __('N° Transaction', 'computop') . '<strong>' . $transaction->transaction_reference . '</strong></li>';
                echo '</ul>';
            }
        }

        /**
         * config in admin panel
         */
        public function validate_admin_onetime() {
            $errors = array();

//            if (isset($_POST['woocommerce_computop_onetime_computop_onetime_min_amount']) && isset($_POST['woocommerce_computop_onetime_computop_onetime_max_amount'])) {
//                $min_amount = $_POST['woocommerce_computop_onetime_computop_onetime_min_amount'];
//                $max_amount = $_POST['woocommerce_computop_onetime_computop_onetime_max_amount'];
//
//                if (!empty($min_amount) && ( filter_var($min_amount, FILTER_VALIDATE_INT) === false || filter_var($min_amount, FILTER_VALIDATE_FLOAT) === false )) {
//                    $errors[] = __('The minimum amount must contain only numeric.', 'computop');
//                }
//                if (!empty($max_amount) && ( filter_var($max_amount, FILTER_VALIDATE_INT) === false || filter_var($max_amount, FILTER_VALIDATE_FLOAT) === false )) {
//                    $errors[] = __('The maximum amount must contain only numeric.', 'computop');
//                }
//                
//            }
            
            $log = $_POST['woocommerce_computop_onetime_computop_log_active'] ?? 'no';
            
            if($log == '1')
            {
                $log = 'yes';
            }
            
            if (get_option('computop_log_active')) {
                if(get_option('computop_log_active') != $log)
                {
                    update_option('computop_log_active', $log);
                }
            } else {
                add_option('computop_log_active', $log);
            }

            if (!empty($errors)) {
                $this->errors = $errors;
                foreach ($errors as $key => $value) {
                    WC_Admin_Settings::add_error($value);
                }
            }
        }

        /**
         * Generate gateway names form
         */
        public function generate_title_name_html() {
            
        }

        /**
         * One-time gateway admin form
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'computop_onetime_title' => array(
                    'type' => 'title_name'
                ),
                'enabled' => array(
                    'title' => __('Activation', 'computop'),
                    'type' => 'checkbox',
                    'label' => __('Active One Time Payment', 'computop'),
                    'default' => 'yes'
                ),
                'computop_log_active' => array(
                    'title' => __('Active logs', 'computop'),
                    'type' => 'checkbox',
                    'label' => __('Active logs', 'computop'),
                    'default' => 'no'
                ),
//                array(
//                    'title' => __('RESTRICTION ON THE AMOUNTS', 'computop'),
//                    'type' => 'title',
//                    'description' => ('<hr>'),
//                ),
//                'computop_onetime_min_amount' => array(
//                    'title' => __('Minimum amount', 'computop'),
//                    'type' => 'text',
//                    'description' => __('Minimum amount for which this payment method is available', 'computop'),
//                ),
//                'computop_onetime_max_amount' => array(
//                    'title' => __('Maximum amount', 'computop'),
//                    'type' => 'text',
//                    'description' => __('Maximum amount for which this payment method is available', 'computop'),
//                ),
            );

            if (get_option('label_translate_on') == 'yes') {
                unset($this->form_fields['title']);
            } else {
                unset($this->form_fields['computop_onetime_title']);
            }
        }

        /**
         * Check if the gatteway is allowed for the order amount
         *
         * @param array
         * @return array
         */
        public function check_gateway($gateways) {
            if (isset($gateways[$this->id])) { 
                if ($gateways[$this->id]->id == $this->id) {
                    $order_amount = WC_Payment_Gateway::get_order_total();
                    $min_amount = floatval($this->settings['computop_onetime_min_amount']);
                    $max_amount = floatval($this->settings['computop_onetime_max_amount']);
                    if (!empty($min_amount)) {
                        if (!( $order_amount > $min_amount )) {
                            unset($gateways[$this->id]);
                        }
                    }
                    if (!empty($max_amount)) {
                        if (!( $order_amount < $max_amount )) {
                            unset($gateways[$this->id]);
                        }
                    }
                }
            }

            return $gateways;
        }

        public function process_payment($order_id) {
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        public function payment_fields()
        {    
            $computop = new Computop();
            $merchant = $computop->get_merchant_account();
            
            if (!is_null($merchant)) {
                $this->my_custom_checkout_hidden_field();

                global $wp;
                $this->load_front_css_js();

                $html_list_payment = '';

                $cards_list = Computop_Api::get_all_available_cards();

                $html_list_payment .= '<div id="axepta_selecte_payment">' . __('Please select a payment method :', 'computop') . '</div><div id="computop_one_time_cards">';

                $html_list_card = "";

                $oneclick_cards = Computop_Oneclick::get_payment_cards_by_user_id();


                if ($oneclick_cards && /*Computop_Api::is_allowed('ONE') &&*/ $merchant->allow_one_click) {
                    foreach ($oneclick_cards as $oneclick_card) {
                        $icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__DIR__)) . '/assets/img/' . strtoupper($oneclick_card->ccbrand) . '.png';
                        $html_list_card .= "<div class=\"computop-display-card-oneclick\">
                            <button onClick=\"check_option(null,'oneclick-$oneclick_card->ccbrand-$oneclick_card->id','one');\" value=\"$oneclick_card->ccbrand\">
                                <img style=\"display:block;\" style=\"float:right;\" src=\"$icon\" title=\"$oneclick_card->ccbrand\" alt=\"$oneclick_card->ccbrand\"/>
                                <p>XXXX XXXX XXXX X" . substr($oneclick_card->pcnr, -3) . "<BR><small>" . substr($oneclick_card->ccexpiry, 0, 4) . '/' . substr($oneclick_card->ccexpiry, -2) . "</small>
                                    
                                </p>
                            </button>
                        </div>";
                    }
                }

                foreach ($cards_list as $key => $card) {
                    if ($card->trigram == 'VIM' || $card->trigram == 'CVM') {
                        unset($cards_list[$key]);

                        if ($card->trigram == 'VIM') {
                            $array_code = ['MasterCard', 'VISA'];
                            $array_label = $array_code;
                        }

                        if ($card->trigram == 'CVM') {
                            $array_code = ['MasterCard', 'VISA', 'CB'];
                            $array_label = $array_code;
                        }

                        foreach ($array_code as $key2 => $code) {
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

                            array_unshift($cards_list, (object) $new_card);
                        }
                    }
                }

                foreach ($cards_list as $card) {
                    // Cetelem FC3 - FC4 disponible entre 90€ et 3000€ uniquement
                    if ($card->trigram === 'FC3' || $card->trigram === 'FC4') {
                        
                        global $woocommerce;
                        global $wp;
                        
                        if(isset($wp->query_vars['order-pay'])) {
                            $order_id = $wp->query_vars['order-pay'];
                            $order = new WC_Order( $order_id );
                            $total = floatval($order->get_total())*100;
                        } else {
                            $total = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_total() ) );
                        }
                        if($total > self::MAX_CETELEM_AMOUNT || $total < self::MIN_CETELEM_AMOUNT) {
                            continue;
                        }
                    }
                    
                    // Cetelem Presto disponible entre 150€ et 16000€ uniquement
                    if ($card->trigram === 'PRE') {
                        global $woocommerce;
                        global $wp;
                        
                        if (isset($wp->query_vars['order-pay'])) {
                            $order_id = $wp->query_vars['order-pay'];
                            $order = new WC_Order( $order_id );
                            $total = floatval($order->get_total())*100;
                        } else {
                            $total = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_total() ) );
                        }
                        
                        if ($total > self::MAX_PRESTO_AMOUNT || $total < self::MIN_PRESTO_AMOUNT) {
                                continue;
                        }
                    }
                    
                    $icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__DIR__)) . '/assets/img/' . strtoupper($card->code) . '.png';

                    $html_list_card .= "<div class=\"computop-display-card\">
                        <button onClick=\"check_option(null,'$card->trigram','one');\" value=\"$card->code\">
                            <img style=\"display:block;\" style=\"float:right;\" src=\"$icon\" title=\"$card->code\" alt=\"$card->code\"/>
                        </button>
                    </div>";
                }

                $html_list_payment .= $html_list_card .
                        '<div class="computop-clear" style="padding:0">
            </div>';

                if (get_current_user_id() /*&& Computop_Api::is_allowed('ONE')*/ && $merchant->allow_one_click) {
                    $html_list_payment .= '<div>
              <input type="checkbox" id="computop_register" onclick="computop_customer_onclick()" name="computop_register">
              <label for="computop_register">' . __('Register my card for future payments', 'computop') . '</label>
            </div>';
                }

                $html_list_payment .= '</div>';

                echo $html_list_payment;
            }
        }

        public function validate_fields() {
            $trigram = $_POST['payment_mean_brand_one'] ?? null;
            $social_security_number = $_POST['social_security_number'] ?? null;
            
            if (empty($trigram)) {
                wc_add_notice(__('Please choose a card to proceed at the payment.', 'computop'), 'error');
            } else {
                $order_id = $_GET['order-pay'] ?? null;
                if ($trigram !== get_post_meta($order_id, 'payment_mean_brand_one')[0]) {
                    update_post_meta($order_id, 'payment_mean_brand_one', $trigram);
                }
            }
            
            if ($trigram === 'BOL' && ($social_security_number == null || empty($social_security_number))) {
                wc_add_notice(__('Please register your social security number.', 'computop'), 'error');
            } 
            
            if ($trigram === 'BOL' && strlen($social_security_number) > 30) {
                wc_add_notice(__('Your social security number is invalid.', 'computop'), 'error');
            } 
        }

        public function receipt_page($order_id)
        {    
            $order = new WC_Order($order_id);
            
            if (empty($order)) {
                return false;
            }

            $checkout_page_id = wc_get_page_id('checkout');
            $url_back = $checkout_page_id ? get_permalink($checkout_page_id) : '';
            
            if (get_post_meta($order_id, 'payment_mean_brand_one')[0] !== $_COOKIE['payment_mean_brand_one']) {
                update_post_meta($order_id, 'payment_mean_brand_one', $_COOKIE['payment_mean_brand_one']);
            }
            
            if (empty(get_post_meta($order_id, 'payment_mean_brand_one')[0])) {
                wc_add_notice(__('Please choose a card to proceed at the payment.', 'computop'), 'error');
                wp_redirect($url_back);
                exit;
                return false;
            }

            $payment_method = get_post_meta($order_id, 'payment_mean_brand_one')[0];
            $payment_method_array = explode('-', $payment_method);

            $return_url = plugin_dir_url(__DIR__) . 'routes/one-time/success.php';
            if (strpos($return_url, 'https') === false) {
                $return_url = str_replace("http", "https", $return_url);
            }

            if ($payment_method_array[0] == 'oneclick') {
                $card_id = $payment_method_array[2];
                $card = Computop_Oneclick::get_current_customer_payment_card_by_card_id($card_id);

                if (empty($card)) {
                    return false;
                }

                $params = Computop_Api::get_params($order, $return_url, Computop_Api::ONECLICK, null, null, null, $card);

                $trigram = Computop_Api::get_trigram_like_ccbrand($payment_method_array[1]);
                
                $url = Computop_Api::get_payment_url(Computop_Api::ONECLICK, $trigram);
            } else {
                $params = Computop_Api::get_params($order, $return_url, 'payment');

                $url = Computop_Api::get_payment_url(Computop_Api::PAYMENT, $payment_method);
            }

            $computop = new Computop();
            $merchant = $computop->get_merchant_account();

            if ($merchant->display_card_method == 'IFRAME' && $payment_method != 'PAL' && $payment_method != 'FC3' && $payment_method != 'FC4' && $payment_method != 'PRE') {

                $url_back = $order->get_checkout_payment_url() . '&iframe=yes';
                if (WC()->cart->get_cart_contents_count() != 0) {
                    $url_back = plugin_dir_url(__DIR__) . 'routes/iframe/redirect.php';
                }

                if (strpos($return_url, 'https') === false) {
                    $return_url = str_replace("http", "https", $return_url);
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

        /**
         * Check the Computop response, save the transaction, complete the order and redirect
         */
        public static function check_computop_response() {
            global $woocommerce;

            $data = $_GET["Data"];
            $len = $_GET["Len"];

            $computop = new Computop();
            $merchant = $computop->get_merchant_account();

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
            
            // Back to Cart
            switch (substr($data['Code'], -4)) {
                case '0053':
                case '0073':
                case '0723':
                case '0946':
                    Computop_Logger::log("Abandonment by user : back to Cart", Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                    $checkout_page_id = wc_get_page_id('checkout');
                    $url_back = $checkout_page_id ? get_permalink($checkout_page_id) : '';
                    wp_redirect($url_back);
                    exit;
            }
            
            // Check if this is a timeout notification
            $timeout = false;
            switch (substr($data['Code'], -4)) {
                case '0051':
                case '0056':
                case '0368':
                case '0931':
                case '2206':
                case '9040':
                case '110A':
                    $timeout = true;
                    break;
            }
            
            $order = new WC_Order($order_id);
            $iframe_redirect = self::check_iframe_redirect($order, $merchant);
            
            if (!$timeout) {
                // Check if transaction is already exists
                if (!Computop_Transaction::check_already_exists($data, $plaintext, $order)) {
                    $transaction_id = Computop_Transaction::save($data, Computop_Api::PAYMENT, $order, $plaintext);
                    Computop_Logger::log("Transaction created. ID : ".$transaction_id, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                } else {
                    Computop_Logger::log("This transaction already exists, no need to create it again", Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                }

                Computop_Api::updateOrder($data, $order);
            } else {
                Computop_Logger::log("This is a timeout", Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                
                // get transaction
                $transaction = Computop_Transaction::get_by_orderid($order_id);
                
                if ($transaction) {
                    Computop_Logger::log("There is already a transaction for the order ".$order_id, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                    
                    if (!(strtoupper($transaction->transaction_type) == 'PAYMENT' && $transaction->response_code == '00000000' && strtoupper($transaction->status) == 'OK' && strtoupper($transaction->description == 'SUCCESS'))) {
                        Computop_Logger::log("This transaction is not a successful payment, so we update the order ".$order_id, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                        
                        Computop_Api::updateOrder($data, $order);
                    } else {
                        Computop_Logger::log("This transaction is a successful payment, so we don't update the order ".$order_id, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                    }
                } else {
                    Computop_Logger::log("There is no transaction for the order, so we update the order ".$order_id, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                    Computop_Api::updateOrder($data, $order);
                }
            }

            // redirect to the parent page
            if ($iframe_redirect != false) {
                echo $iframe_redirect;
                exit;
            }
            wp_redirect($order->get_checkout_order_received_url());
            exit;
        }

        /**
         * process refund
         * @param type $order_id
         * @param type $amount
         * @param type $reason
         * @return boolean
         */
        public function process_refund($order_id, $amount = null, $reason = '') {
            $order = wc_get_order($order_id);

            if (empty($order)) {
                return false;
            }

            $transaction = Computop_Transaction::get_transaction_success_by_order($order_id);

            $transaction_status = Computop_Api::check_transaction_status($transaction->transaction_reference);

            if ($transaction_status['Code'] != '00000000') {
                return false;
            }

            $operation = 'refund';
            if ((int) $transaction_status['AmountCap'] == 0) {
                if ((int) $transaction_status['AmountAuth'] == $amount * 100) {
                    $operation = 'cancellation';
                }
            }

            $date_transaction = new DateTime($transaction->transaction_date);
            $date_now = new DateTime('now');
            $diff = $date_transaction->diff($date_now);

            $nb_months = $diff->m;

            if ($nb_months > 11) {
                return false;
            }

            $payment_method_array = explode('-', get_post_meta($order_id, 'payment_mean_brand_one')[0]);

            if ($payment_method_array[0] == 'oneclick') {
                $trigram = Computop_Api::get_trigram_like_ccbrand($payment_method_array[1]);
                $oneclick_card = Computop_Oneclick::get_current_customer_payment_card_by_card_id($payment_method_array[2]);
            } else {
                $trigram = $payment_method_array[0];
                $oneclick_card = null;
            }

            $url = Computop_Api::get_payment_url($operation, $trigram);

            if (is_null($url)) {
                $operation = 'refund';
                $url = Computop_Api::get_payment_url($operation, $trigram);
            }

            $params = Computop_Api::get_params($order, null, $operation, $amount * 100, $transaction, null, $oneclick_card);

            $merchant = Computop_Api::get_merchant_account_by_name($transaction->merchant_id);

            $params['MerchantID'] = $transaction->merchant_id;

            $response = Computop_Api::check_computop_response_with_curl($url, $params);

            if ($response === false) {
                return false;
            }

            $a = explode('&', $response);
            $data = Computop_Api::ctSplit($a);

            $plaintext = Computop_Api::ctDecrypt($data['Data'], $data['Len'], $merchant->password);

            $b = explode('&', $plaintext);
            $save_data = Computop_Api::ctSplit($b);

            if ($save_data['Code'] != '00000000') {
                return false;
            }

            Computop_Transaction::save($save_data, $operation, $order, $plaintext, -$amount);
            return true;
        }

    }

}
