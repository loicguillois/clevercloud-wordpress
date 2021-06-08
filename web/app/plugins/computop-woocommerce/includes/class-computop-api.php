<?php

class Computop_Api {

    const PAYMENT = 'payment';
    const REFUND = 'REFUND';
    const CANCEL = 'CANCEL';
    const ANTICIPATE_REFUND = 'ANTICIPATE_REFUND';
    const CARDS_WITHOUT_TRI_TO_DISABLE = '';
    const CARDS_WITH_N_TIMES = 'CB,VISA,MASTERCARD,AMEX';
    const INITIAL_PAYMENT = 'I';
    const INITIAL_RECURRING_PAYMENT = 'R';
    const INITIAL_ONECLICK = 'R';
    const URL_INQUIRE_WITH_PAYID = 'https://paymentpage.axepta.bnpparibas/inquire.aspx';
    const ONECLICK = 'oneclick';
    const RECCURING = 'recurring';
    const MAX_TRANSID_SIZE = 13;
    
    /**
     * Decrypt Activation key
     */
    public static function decrypt_activation_key($key) {
        $datas = (explode("\n", $key));
        $data = trim($datas[0]);

        $public_key_res = openssl_pkey_get_public(file_get_contents(plugin_dir_path(__DIR__) . 'tools/rsa.pub'));
        $signature = trim(substr($key, strpos($key, "\n") + 1));
        $signature_decode = base64_decode($signature);

        if (function_exists('openssl_verify')) {
            $result = openssl_verify($data, $signature_decode, $public_key_res);
            if ($result == 1) {
                return true;
            }
        }
        return false;
    }
    
    public static function get_merchant_accounts()
    {     
        global $wpdb;
        
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account" );
    }
    
    public static function enable_or_disable_merchand_account($id)
    {
        $marchand_account = self::get_merchant_account($id);
        
        $countries = explode(';', $marchand_account->country);
        
        foreach ($countries as $key => $country)
        {
            if(self::get_merchant_account_by_name_and_country($marchand_account->name, $country, $id))
            {
                return false;
                break;
            }
        }
        
        global $wpdb;
        
        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}computop_merchant_account SET is_active = ABS(is_active - 1) WHERE account_id = %d", $id));
        
        return true;
    }
    
    public static function get_merchant_account($id)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE account_id = $id" );
    }
    
        
    public static function get_merchant_account_by_name($name)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE name = '$name' AND is_active = 1" );
    }
    
    public static function get_merchant_account_by_name_and_country($name, $country, $id)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE name = '$name' AND country LIKE '%$country%' AND account_id != $id" );
    }
    
    public static function get_available_currencies()
    {
        global $wpdb;
        
        $currencies = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_xml_currency" );
        
        $array = [];
        foreach ($currencies as $currency)
        {
            $array[$currency->code] = [
                'id'   => $currency->code,
                'name' => $currency->name. ' ('.$currency->code.')'
            ];
        }
        
        return $array;
    }
    
    public static function delete_merchant_account($id)
    {
        global $wpdb;
        
        return $wpdb->delete( $wpdb->prefix.'computop_merchant_account', array( 'account_id' => $id ), array( '%d' ) );
    }
        
    /**
     * get the methods of payment filtered by currency defined in woocommerce parameters
     */
    public static function get_payment_methods($country, $abo = false)
    {
        $currency = get_option( 'woocommerce_currency' );
        
        global $wpdb;
        
        $operation = self::PAYMENT;
        if($abo)
            $operation = self::RECCURING;
        
        $methods = null;
        
        if(is_null($country))
        {
            $country = WC()->customer->get_billing_country();
        }  
            
        $methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_xml_method"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_parameter_set ON {$wpdb->prefix}computop_xml_parameter_set.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_allow_countries ON {$wpdb->prefix}computop_xml_allow_countries.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_method_lang ON {$wpdb->prefix}computop_xml_method_lang.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " WHERE {$wpdb->prefix}computop_xml_parameter_set.operation = '$operation'"
            . " AND {$wpdb->prefix}computop_xml_allow_countries.currency IN ('$currency', 'ALL')"
            . " AND {$wpdb->prefix}computop_xml_allow_countries.country IN ('$country', 'ALL')"

        );
            
        return $methods;
    }
    
        /**
     * get the methods of payment filtered by currency defined in woocommerce parameters and countries
     */
    public static function get_payment_methods_by_countries($countries)
    {
        $currency = get_option( 'woocommerce_currency' );
        
        global $wpdb;
        
        $operation = self::PAYMENT;
        
        $countries = str_replace(';', "', '", $countries);
        
        $methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_xml_method"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_parameter_set ON {$wpdb->prefix}computop_xml_parameter_set.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_allow_countries ON {$wpdb->prefix}computop_xml_allow_countries.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_method_lang ON {$wpdb->prefix}computop_xml_method_lang.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " WHERE {$wpdb->prefix}computop_xml_parameter_set.operation = '$operation'"
            . " AND {$wpdb->prefix}computop_xml_allow_countries.currency IN ('$currency', 'ALL')"
            . " AND {$wpdb->prefix}computop_xml_allow_countries.country IN ('$countries', 'ALL')"

        );
            
        return $methods;
    }
    
    /**
     * get the methods of payment filtered by currency defined in woocommerce parameters
     */
    public static function get_payment_methods_without_country_config()
    {
        $currency = get_option( 'woocommerce_currency' );
        
        global $wpdb;
        
        $operation = self::PAYMENT;
        
        $methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_xml_method"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_parameter_set ON {$wpdb->prefix}computop_xml_parameter_set.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_allow_countries ON {$wpdb->prefix}computop_xml_allow_countries.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " LEFT JOIN {$wpdb->prefix}computop_xml_method_lang ON {$wpdb->prefix}computop_xml_method_lang.method_id = {$wpdb->prefix}computop_xml_method.id"
            . " WHERE {$wpdb->prefix}computop_xml_parameter_set.operation = '$operation'"
            . " AND {$wpdb->prefix}computop_xml_allow_countries.currency IN ('$currency', 'ALL')"
        );
            
        return $methods;
    }
    
    public static function get_trigram_like_ccbrand($ccbrand)
    {
        $methods = [
                'VISA' => 'VIM',
                'VISA Electron' => 'VIM',
                'MasterCard' => 'VIM',
                'Maestro' => 'VIM',
                'Cartes Bancaires' => 'CVM'
            ];

        foreach($methods as $key => $value)
        {
            if($key == $ccbrand)
            {
                $trigram = $value;
            }
        }
        
        if(isset($trigram))
        {
            return $trigram;
        }
        
        global $wpdb;
        
        $method = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_xml_method WHERE code LIKE '%$ccbrand%'" );

        return $method->trigram;
    }
    
    public static function get_method_by_trigram($trigram)
    {
        global $wpdb;
        
        $method = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_xml_method WHERE trigram = '$trigram'" );

        return $method;
    }

    /**
     * Get the payment URL
     *
     * @return string
     */
    public static function get_payment_url($operation, $trigram) {
        
        $method = self::get_method_by_trigram($trigram);
        
        $method_id = (int)$method->id;
        
        $column = 'url';
        
        if (get_option('computop_test_mode') == 'yes') {
            $column = 'url_test';
        }
        
        global $wpdb;
        
        $url = $wpdb->get_row( "SELECT $column FROM {$wpdb->prefix}computop_xml_parameter_set WHERE operation = '$operation' AND method_id = $method_id" );

        return $url->url;
    }
    
    public static function get_all_available_cards($abo = false)
    {
        $computop = new Computop();
        $merchant = $computop->get_merchant_account();
        $all_authorized_methods = explode(';',$merchant->filtered_payments);
        
        $all_methods = self::get_payment_methods(null, $abo);

        if(in_array('ALL', $all_authorized_methods))
        {
            $all_authorized_methods = explode(';',$merchant->authorized_payment);
        }
        
        $methods = [];
        
        foreach ($all_authorized_methods as $key => $value)
        {
            foreach ($all_methods as $method)
            {
                if($value == $method->trigram)
                {
                    $methods[] = $method;
                    break;
                }
            }
        }

        return $methods;
    }

    /**
     * Get options list from the activation key
     *
     * @return array
     */
    public static function allowed_options() {
        $edit = $_GET['action'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if(!is_null($edit) && !is_null($id))
        {
            $merchant = self::get_merchant_account($id);
            $key = $merchant->authorized_payment;
            return explode(';', $key);
        }
        
    }

    /**
     * Check if the option is allowed
     *
     * @param array
     * @return booloean
     */
    public static function is_allowed($options) {
        global $wpdb;
        
        if ($options == 'ABO' || $options == 'ONE' || $options == '3DS') {
            return true;
        }
        
        $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE authorized_payment LIKE '%$options%'" );
 
        if (!empty($merchants)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if the option is allowed
     *
     * @param array
     * @return booloean
     */
    public static function is_allowed_abo() {
        global $wpdb;
        
        $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE allow_abo = 1" );

        if(!empty($merchants))
        {
            return true;
        }
        return false;
    }
    
    /**
     * Create MAC
     * @param type $TransID
     * @param type $Amount
     * @return type
     */
    public static function creatingMacValue($TransID, $Amount = '', $PayId = '')
    {
        $order_id = self::get_order_id_from_trans_id($TransID);

        $transaction = Computop_Transaction::check_transaction_ok_by_order_id($order_id);

        if(!is_null($transaction))
        {
            $merchant_name = $transaction->merchant_id;
            $merchant = self::get_merchant_account_by_name($merchant_name);
            $hmac_key = $merchant->hmac_key;
        } else {
            $computop = new Computop();
            $merchant = $computop->get_merchant_account();
            $merchant_name = $merchant->name;
            $hmac_key = $merchant->hmac_key;
        }

        return Computop_Api::ctHMAC($PayId, $TransID, $merchant_name, $Amount, get_option( 'woocommerce_currency' ), $hmac_key);
    }
    
    /**
     * format params
     * @param type $params
     */
    public static function format_params($params)
    {
        // delete empty parameters
        foreach ($params as $key => $value)
        {
            if(is_null($value) || empty($value))
            {
                unset($params[$key]);
            }
        }
        
        $pay_id = $params['PayID'] ?? '';
        $amount = $params['Amount'] ?? '';
        $trans_id = $params['TransID'] ?? '';
        $MAC = "MAC=".Computop_Api::creatingMacValue($trans_id, $amount, $pay_id);
        
        $data = [];
                
        foreach ($params as $key => $value)
        {
            $data[] = "$key=$value";
        }
        
        $data[] = $MAC;
        
        $plaintext = join("&", $data);
        
        $Len = mb_strlen($plaintext);
                
        if(!empty($trans_id))
        {
            $order_id = self::get_order_id_from_trans_id($trans_id);
            $transaction = Computop_Transaction::get_by_orderid($order_id);
        } else {
            $transaction = null;
        }
        
        if(!is_null($transaction)) {
            $merchant_name = $transaction->merchant_id;
            $merchant = self::get_merchant_account_by_name($merchant_name);
            $password = $merchant->password;
        } else {
            $computop = new Computop();
            $merchant = $computop->get_merchant_account();
            $merchant_name = $merchant->name;
            $password = $merchant->password;
        }
        
        $dataEncrypted = Computop_Api::ctEncrypt($plaintext, $Len, $password);
        
        if(!$dataEncrypted)
        {
            wc_add_notice(__('They are a problem with the payment process. Please contact Axepta BNP Paribas support.'), 'error' );
            wp_redirect( wc_get_checkout_url() );
            exit;
        }
        
        // LOG
        if (get_option('computop_log_active') == 'yes') {
            $message = '';
            $message .= ' MAC: '. $MAC;
            $message .= ' || ';
            $message .= ' DataEncrypted: '. $dataEncrypted;
            $message .= ' || ';
            $message .= ' Len: '. $Len;
            $message .= ' Params: ';
            $message .= implode(', ', array_map(function ($v, $k) {
                        return $k . '=' . $v;
                    }, $data, array_keys($data)));
            $message .= ' ----------------------------------------------------------------------- ';
            Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
        }

        return [
            'Data' => $dataEncrypted,
            'Len'  => $Len
        ];
        
    }
    
    /**
     * Encrypt the passed text (any encoding) with Blowfish.
     *
     * @param string $plaintext
     * @param integer $len
     * @param string $password
     * @return bool|string
     */
    public static function ctEncrypt($plaintext, $len, $password)
    {
        $blowfish = new Computop_Blowfish();
        if (mb_strlen($password) <= 0) $password = ' ';
        if (mb_strlen($plaintext) != $len) {
            echo 'Length mismatch. The parameter len differs from actual length.';
            return false;
        }
        $plaintext = $blowfish->expand($plaintext);
        
        $blowfish->bf_set_key($password);
        return bin2hex($blowfish->encrypt($plaintext));
    }
    
    
    /**
     * Get parameters in bdd
     * @global type $wpdb
     * @param type $operation
     * @return type
     */
    public static function get_params_in_bdd($operation, $order, $ccBrand = null)
    {
        global $wpdb;
        
        if(!is_null($ccBrand))
        {
            
            $methods = [
                'VISA' => 'VIM',
                'VISA Electron' => 'VIM',
                'MasterCard' => 'VIM',
                'Maestro' => 'VIM',
                'Cartes Bancaires' => 'CVM'
            ];

            foreach($methods as $key => $value)
            {
                if($key == $ccBrand)
                {
                    $trigram = $value;
                }
            }
            
            if(!isset($trigram))
            {
                
                $methods = self::get_all_methods_in_bdd();
                foreach($methods as $method)
                {
                    $cc_brand_explode = explode('/', $method->code);
                    foreach ($cc_brand_explode as $key => $value)
                    {
                        if($value == $ccBrand)
                        {
                            $trigram = $method->trigram;
                        }
                    }
                }
            }
            
        } else {
            $trigram = get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] ?? 'VIM';
        }
        
        return $wpdb->get_results( "SELECT cp.id, cp.name, cp.format, cp.required FROM {$wpdb->prefix}computop_xml_parameter as cp"
        . " LEFT JOIN {$wpdb->prefix}computop_xml_parameter_set ON cp.parameter_set_id = {$wpdb->prefix}computop_xml_parameter_set.parameter_set_id"
        . " LEFT JOIN {$wpdb->prefix}computop_xml_method ON {$wpdb->prefix}computop_xml_method.id = {$wpdb->prefix}computop_xml_parameter_set.method_id"
        . " WHERE {$wpdb->prefix}computop_xml_method.trigram = '$trigram'"
        . " AND {$wpdb->prefix}computop_xml_parameter_set.operation = '$operation'", ARRAY_A );
    }
    
    public static function get_all_methods_in_bdd()
    {
        global $wpdb;
        
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_xml_method" );
    }
    
    public static function get_merchant_id_by_country_and_currency()
    {
        $customer = WC()->customer;
        
        if ($customer !== null) {
            $country = $customer->get_billing_country();
            $currency = get_option( 'woocommerce_currency' );

            global $wpdb;

            $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE currency = '$currency' AND is_active = 1 AND (country LIKE  '%$country%')" );

            if(empty($merchants))
            {
                $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE currency = '$currency' AND is_active = 1 AND (country = 'ALL')" );
            }
        }
        
        return $merchants[0] ?? null;
    }
    
        public static function get_merchant_id_by_country_and_currency_with_abo()
    {
        $country = WC()->customer->get_billing_country();
        $currency = get_option( 'woocommerce_currency' );
        
        global $wpdb;
        
        $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE currency = '$currency' AND is_active = 1 AND allow_abo = 1 AND (country LIKE  '%$country%')" );
        
        if(empty($merchants))
        {
            $merchants = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}computop_merchant_account WHERE currency = '$currency' AND is_active = 1 AND allow_abo = 1 AND (country = 'ALL')" );
        }
        
        return $merchants[0] ?? null;
    }
    
    public static function generate_string($strength)
    {
        $permitted_chars = 'BCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($permitted_chars);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $permitted_chars[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }
    
    public static function generate_uniq_trans_id($order_id)
    {
        $trans_id_first = strlen($order_id).'A'.$order_id;
        
        $diff = self::MAX_TRANSID_SIZE - (strlen($trans_id_first));
        
        return $trans_id_first.self::generate_string($diff);
    }
    
    public static function get_order_id_from_trans_id($trans_id)
    {
        $order_id_len_pos = strpos($trans_id, 'A', 0);
        $order_id_len = (int) substr($trans_id, 0, $order_id_len_pos);

        return substr($trans_id, $order_id_len_pos + 1, $order_id_len);
    }

    /**
     * Get the payment params
     * 
     */
    public static function get_params($order, $return_url, $operation, $amount = null, $transaction = null, $abo = null, $oneclick_card = null) {

        if(is_null($amount)) {
            $amount = $order->get_total() * 100;
        }
        
        // init merchant
        if(!is_null($transaction))
        {
            $merchantId = $transaction->merchant_id;
            $merchant = Computop_Api::get_merchant_account_by_name($merchantId);
        } else {
            $merchant = Computop_Api::get_merchant_id_by_country_and_currency();
            $merchantId = $merchant->name;
        }
        
        // init 3ds
        if($merchant)
        {
            if((int)$merchant->allow_3ds && (int)$merchant->min_amount_3ds*100<$amount)
            {
                $amount3d = $amount;
                $amount = $amount;
            } else {
                $amount3d = 0;
            }
        }

        if(is_null($merchantId))
        {
            wc_add_notice(__('Payment method is not allowed for your country.', 'computop'));
            wp_redirect(WC()->cart->get_checkout_url());
            exit;
        }
        
        if(get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] == 'FC3'){
            $payType = 1;
        } elseif(get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] == 'FC4'){
            $payType = 2;
        }
        
        if(!is_null($abo))
        {
            $params = Computop_Api::get_params_in_bdd($operation, $order, null);
        } else {
            if(!is_null($oneclick_card))
            {
                $params = Computop_Api::get_params_in_bdd($operation, $order, $oneclick_card->ccbrand);
            } else
            {
                $params = Computop_Api::get_params_in_bdd($operation, $order, null);
            }
            
        }
        
        $arrayParams = [];
        
         //One click config
        if(!is_null($oneclick_card))
        {
            $CCNr = $oneclick_card->pcnr;
            $CCExpiry = $oneclick_card->ccexpiry;
            $CCBrand = $oneclick_card->ccbrand;
        } else {
            $CCNr = null;
            $CCExpiry = null;
            $CCBrand = null;
        }
        
        if($merchant->capture_method == 'TIMED' && get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] != 'PAL')
        {
            $capture = (int)$merchant->capture_hours;
        } else {
            $capture = 'AUTO';
        }
        
        if($merchant->presto_product_category_value){
            $presto_product_category_value = (int)$merchant->presto_product_category_value;
        }else{
            $presto_product_category_value = '320';
        }
        
        // I = Initial payment for the new subscription
        if($abo == 'new_abo') {
            $RTF = self::INITIAL_PAYMENT;
        } else if (!is_null ($abo)) {
            $RTF = self::INITIAL_RECURRING_PAYMENT;
            if(get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] == 'PAL'){
                $BillingAgreementID = $abo->bid;
            } else {
                $CCNr = $abo->pcnr;
                $CCExpiry = $abo->ccexpiry;
                $CCBrand = $abo->ccbrand;
            }
            

        } elseif(!is_null($oneclick_card)) {
            $RTF = self::INITIAL_ONECLICK;
        }  else {
            $RTF = null;
        }
        // paypal specifications
        if(get_post_meta($order->get_id(), 'payment_mean_brand_one')[0] == 'PAL' && ($merchant->currency == 'HUF' || $merchant->currency == 'TWD'))
        {
            $amount = $amount/100;
        }
        foreach ($params as $param)
        {
            switch ($param['name'])
            {
                case 'MerchantID':
                    $arrayParams['MerchantID'] = $merchantId;
                    break;
                case 'TransID':
                    $arrayParams['TransID'] = self::generate_uniq_trans_id($order->get_id());
                    break;
                case 'Amount':
                    $arrayParams['Amount'] = $amount;
                    break;
                case 'Amount3D':
                    $arrayParams['Amount3D'] = $amount3d;
                    break;
                case 'Currency':
                    $arrayParams['Currency'] = get_option('woocommerce_currency');
                    break;
                case 'URLSuccess':
                    $arrayParams['URLSuccess'] = $return_url;
                    break;
                case 'URLFailure':
                    $arrayParams['URLFailure'] = $return_url;
                    break;
                case 'Response':
                    $arrayParams['Response'] = 'encrypt';
                    break;
                case 'URLNotify':
                    $arrayParams['URLNotify'] = $return_url;
                    break;
                case 'UserData':
                    $arrayParams['UserData'] = null;
                    break;
                case 'Capture':
                    $arrayParams['Capture'] = $capture;
                    break;
                case 'OrderDesc':
                    $arrayParams['OrderDesc'] = get_bloginfo('name').' Order N°'.$order->get_id();
                    break;
                case 'ReqID':
                    $arrayParams['ReqID'] = strtoupper(uniqid($order->get_id()));
                    break;
                case 'Custom':
                    $arrayParams['Custom'] = 'order_id='.$order->get_id();
                    break;
                // Must capture after payment if accverify is active
//                case 'AccVerify':
//                    $arrayParams['AccVerify'] = 'Yes';
//                    break;
                case 'RTF':
                    $arrayParams['RTF'] = $RTF;
                    break;
                case 'ChDesc':
                    $arrayParams['ChDesc'] = null;
                    break;
                case 'Template':
                    $arrayParams['Template'] = null;
                    break;
                case 'Language':
                    $arrayParams['Language'] = substr(self::get_locale(), 0 ,2);
                    break;
                case 'PayID':
                    $arrayParams['PayID'] = $transaction->pay_id;
                    break;
                case 'Textfeld1':
                    $arrayParams['Textfeld1'] = null;
                    break;
                case 'Textfeld2':
                    $arrayParams['Textfeld2'] = null;
                    break;
                case 'CCNr':
                    $arrayParams['CCNr'] = $CCNr;
                    break;
                case 'CCExpiry':
                    $arrayParams['CCExpiry'] = $CCExpiry;
                    break;
                case 'CCBrand':
                    $arrayParams['CCBrand'] = $CCBrand;
                    break;
                case 'AddrCountryCode':
                    $arrayParams['AddrCountryCode'] = $order->get_billing_country();
                    break;
                case 'AccOwner':
                    $arrayParams['AccOwner'] = $order->get_billing_last_name();
                    break;
                case 'CustomerID':
                    $arrayParams['CustomerID'] = (int)get_post_meta($order->get_id(), '_customer_user', true);
                    break;
                case 'FirstName':
                    $arrayParams['FirstName'] = $order->get_billing_first_name();
                    break;
                case 'LastName':
                    $arrayParams['LastName'] = $order->get_billing_last_name();
                    break;
                case 'AddrStreet':
                    $arrayParams['AddrStreet'] = $order->get_billing_address_1();
                    break;
                case 'AddrStreet2':
                    $arrayParams['AddrStreet2'] = $order->get_billing_address_2();
                    break;
                case 'AddrCity':
                    $arrayParams['AddrCity'] = $order->get_billing_city();
                    break;
                case 'AddrState':
                    $arrayParams['AddrState'] = $order->get_billing_state();
                    break;
                case 'AddrZip':
                    $arrayParams['AddrZip'] = $order->get_billing_postcode();
                    break;
                case 'AddrZIP':
                    $arrayParams['AddrZIP'] = $order->get_billing_postcode();
                    break;
                case 'UI':
                    $arrayParams['UI'] = 'hermes';
                    break;
                case 'BuyerEMail':
                    $arrayParams['BuyerEMail'] = $order->get_billing_email();
                    break;
                case 'Email':
                    $arrayParams['Email'] = $order->get_billing_email();
                    break;
                case 'Phone':
                    $arrayParams['Phone'] = self::format_phone_number($order->get_billing_phone());
                    break;
                case 'BillingAgreementID':
                    $arrayParams['BillingAgreementID'] = $BillingAgreementID;
                    break;
                case 'SocialSecurityNumber':
                    $arrayParams['SocialSecurityNumber'] = get_post_meta($order->get_id(), 'social_security_number', true);
                    break;
                case 'PayType':
                    $arrayParams['PayType'] = $payType;
                    break;
                case 'Salutation':
                    $arrayParams['Salutation'] = 'MME';
                    break;
                case 'bdFirstName':
                    $arrayParams['bdFirstName'] = $order->get_billing_first_name();
                    break;
                case 'bdLastName':
                    $arrayParams['bdLastName'] = $order->get_billing_last_name();
                    break;
                case 'bdStreet':
                    $arrayParams['bdStreet'] = $order->get_billing_address_1();
                    break;
                case 'bdZip':
                    $arrayParams['bdZip'] = $order->get_billing_postcode();
                    break;
                case 'bdCity':
                    $arrayParams['bdCity'] = $order->get_billing_city();
                    break;
                case 'bdCountryCode':
                    $arrayParams['bdCountryCode'] = $order->get_billing_country();
                    break;
                case 'UseBillingData':
                    $arrayParams['UseBillingData'] = 'yes';
                    break;
                case 'GoodsCategory':
                    $arrayParams['GoodsCategory'] = $presto_product_category_value;
                    break;
            }
        }
        
        /* surcharge pour Presto */
        if($arrayParams['GoodsCategory']){
        $arrayParams['AddrStreet'] = substr($order->get_billing_address_1(),0, 32);
        }
        
        if(!Computop_Api::verifyParam($params, $arrayParams))
        {
            wp_redirect( wc_get_checkout_url() );
            exit;
        }
        
        // LOG
        if (get_option('computop_log_active') == 'yes') {
            
            $user = $order->get_user();
            if($user)
            {
                $message = 'Customer: ' . $user->user_firstname . ' ' . $user->user_lastname . ' ' . $user->display_name;
            }
            
            $message = 'Operation: ' . $operation;
            $message .= ' || ';
            Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
        }
        
        return self::format_params($arrayParams);
    }
    
    /**
     * Verify parameters
     * 
     */
    public static function verifyParam($paramsInBdd, $params)
    {

        $required = 'M';
        
        foreach ($params as $paramName => $data)
        {
            for($i=0; $i<sizeof($paramsInBdd); $i++ )
            {
                if($paramsInBdd[$i]['name']===$paramName)
                {
                    $isRequired = $paramsInBdd[$i]['required'];
                    $format = $paramsInBdd[$i]['format'];
                    break;
                }
            }
            
            if($isRequired !== $required)
            {
                return true;
            }
            
            // Lenght is fixed or is max ?
            if(strpos($format, '.'))
            {
                $lenghtFixed = false;
            }
            else
            {
                $lenghtFixed = true;
            }

            // verify lenght of string
            $lenght = preg_replace('/[^0-9]/', '', $format);

            if($lenghtFixed)
            {
                
                if(strlen($data) != $lenght)
                {
                    wc_add_notice( 'Lenght of '. $paramName .' is not equal to '. $lenght, 'error' );
                    return false;
                }
            }
            else
            {
                if(strlen($data) > $lenght)
                {
                    wc_add_notice( 'Lenght of '. $paramName .' is greater than '. $lenght, 'error' );
                    return false;
                }
            }

            //verify data
            $alphaNumFormat = preg_replace('/[^a-zA-Z]/', '', $format);

            switch (strtolower($alphaNumFormat)){
                // alphanumeric with special characters
                case 'ans':
                    break;
                // alphabetical only
                case 'a':
                    if(!preg_match("/[a-z\s]/i", $data))
                    {
                        wc_add_notice( $paramName .' is not only alphabetical string', 'error' );
                        return false;
                    }
                    break;
                // alphabetical with special characters
                case 'as':
                    if(preg_match('~[0-9]~', $data))
                    {
                        wc_add_notice( $paramName .' contains digit number', 'error' );
                        return false;
                    }
                    break;
                // numeric only
                case 'n':
                    if(!is_numeric($data))
                    {
                        wc_add_notice( $paramName .' is not only numeric', 'error' );
                        return false;
                    }
                    break;
                // alphanumeric
                case 'an':
                    if(preg_match('/[^a-z_\-0-9]/i', $data))
                    {
                        wc_add_notice( $paramName .' is not only alphanumeric', 'error' );
                        return false;
                    }
                    break;
                case 'ns':
                    if(preg_match('~[a-zA-Z]~', $data))
                    {
                        wc_add_notice( $paramName .' contains alphabetical letter', 'error' );
                        return false;
                    }
                    break;
                case 'bool':
                    if(!is_bool($data))
                    {
                        wc_add_notice( $paramName .' is not a boolean', 'error' );
                        return false;
                    }
                    break;
                }
        }

        return true;
        
    }
    
    /**
     * check transaction status
     * @param type $trans_id
     */
    public static function check_transaction_status($trans_id)
    {
        $trans = Computop_Transaction::get_by_trans_id($trans_id);
        
        if(is_null($trans))
        {
            return null;
        }

        $array = [
            'MerchantID' => $trans->merchant_id,
            'TransID' => $trans_id,
            'PayID' => $trans->pay_id
        ];

        $data = Computop_Api::format_params($array);
        $data['MerchantID'] = $trans->merchant_id;

        $content = self::check_computop_response_with_curl(self::URL_INQUIRE_WITH_PAYID, $data);
        
        $a = explode('&', $content);
        $content = Computop_Api::ctSplit($a);
        
        $computop = new Computop();
        $merchant = $computop->get_merchant_account();
        
        $plaintext = Computop_Api::ctDecrypt($content['Data'], $content['Len'], $merchant->password);

        $a = explode('&', $plaintext);
        return  Computop_Api::ctSplit($a);
    }
    
    /**
     * capture a payment
     * @param type $transaction
     * @param type $order
     */
    public static function capture_payment($transaction, $order)
    {      
        $params = self::get_params($order, null, 'capture', $order->get_total() * 100, $transaction);
        
        $params['MerchantID'] = $transaction->merchant_id;

        $content = self::check_computop_response_with_curl('https://paymentpage.epayment.bnpparibas/capture.aspx', $params);
        
        $a = explode('&', $content);
        $content = Computop_Api::ctSplit($a);
        
        $computop = new Computop();
        $merchant = $computop->get_merchant_account();
        
        $plaintext = Computop_Api::ctDecrypt($content['Data'], $content['Len'], $merchant->password);

        return $plaintext;
                
    }
    
    /**
     * check computop with curl
     * @param type $url
     * @param type $postFields
     * @return mix
     */
    public static function check_computop_response_with_curl($url, $postFields)
    {
        $options=array(
              CURLOPT_URL            => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HEADER         => false,
              CURLOPT_FAILONERROR    => true,
              CURLOPT_POST           => true,
              CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_VERBOSE => true,
        );

        $CURL=curl_init();
        
        curl_setopt_array($CURL,$options);
        
        $content=curl_exec($CURL);
        
        if(curl_errno($CURL)){
              return false;
        }
        
        curl_close($CURL);
        
        return $content;
    }

    /**
     * Get the wordpress locale
     *
     * @return string
     */
    public static function get_locale() {
        $locale = get_locale();

        if (empty($locale)) {
            $locale = 'en_US';
        }

        return $locale;
    }
    
    /**
     * return hash hmac
     * 
     * @param type $PayId
     * @param type $TransID
     * @param type $MerchantID
     * @param type $Amount
     * @param type $Currency
     * @param type $HmacPassword
     * @return type
     */
    public static function ctHMAC($PayId = "", $TransID = "", $MerchantID, $Amount, $Currency, $HmacPassword)
    {
        return hash_hmac("sha256", "$PayId*$TransID*$MerchantID*$Amount*$Currency", $HmacPassword);
    }
    
        /**
     * Decrypt the passed HEX string with Blowfish.
     *
     * @param string $cipher
     * @param integer $len
     * @param string $password
     * @return bool|string
     */
    public static function ctDecrypt($cipher, $len, $password)
    {
        $blowfish = new Computop_Blowfish();
        if (mb_strlen($password) <= 0) $password = ' ';
        # converts hex to bin
        $cipher = pack('H' . strlen($cipher), $cipher);
        if ($len > strlen($cipher)) {
            echo 'Length mismatch. The parameter len is too large.';
            return false;
        }
        $blowfish->bf_set_key($password);
        return mb_substr($blowfish->decrypt($cipher), 0, $len);
    }
    
    /**
     * create array
     *
     */
    public static function ctSplit($value)
    {
        $array = [];
        for($i=0; $i<sizeof($value); $i++)
        {
            $explode = explode('=', $value[$i]);
            $array[$explode[0]] = $explode[1];
        }

        return $array;
    }
    
    /**
     * Update order status
     */
    public static function updateOrder($data, $order)
    {
        global $woocommerce;
        
        if ($data['Code'] == '00000000' && $data['Type'] !== 'CetPresto') {
            // Payment succeeded. Correct response.
            $order->payment_complete($order->get_id());
            $woocommerce->cart->empty_cart();
            $order->add_order_note(__('Payment accepted', 'computop'));

            //register card for oneclick
            if (get_post_meta($order->get_id(), 'register_payment')[0] == 'yes') {
                if (isset($data['PCNr']) && isset($data['CCExpiry']) && isset($data['CCBrand'])) {
                    Computop_Oneclick::register_payment_card($data['PCNr'], $data['CCExpiry'], $data['CCBrand']);
                }
            }
            
            return true;
        } elseif ($data['Code'] == '00000000' && $data['Type'] == 'CetPresto') {
            // Payment waiting. TEST POUR PRESTO ATTENTION
            
            if (strtoupper($data['Description']) == 'PENDING') {
                $order->update_status('on-hold');
                $order->add_order_note(__('Payment waiting from Cetelem Presto', 'computop'));
                return true;
            }
            
            if ($data['Description'] == 'success') {
                $order->payment_complete($order->get_id());
                $woocommerce->cart->empty_cart();
                $order->add_order_note(__('Cetlem Presto Payment accepted', 'computop'));
                return true;
            }                  
        } else {
            // Payment failed.
            $order->update_status('failed');
            $order->add_order_note(__('Payment failed', 'computop'));
            return false;
        }

    }
    
    /**
     * get action code in db
     * @global type $wpdb
     * @param type $code
     * @return type
     */
    public static function get_action_code($code)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_xml_paygate_action_code WHERE code = $code" );
    }
    
    /**
     * get category code in db
     * @global type $wpdb
     * @param type $code
     * @return type
     */
    public static function get_category_code($code)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_xml_paygate_category_code WHERE code = $code" );
    }
    
    /**
     * get detail code in db
     * @global type $wpdb
     * @param type $code
     * @return type
     */
    public static function get_detail_code($code)
    {
        global $wpdb;
        
        return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}computop_xml_paygate_detail_code WHERE code = $code" );
    }
    
    /**
     * return format error message
     * @param type $error_code
     */
    public static function format_error_message($error_code)
    {
        $action_code = self::get_action_code(substr($error_code, 0, 1));
        $category_code = self::get_category_code(substr($error_code, 1, 3));
        $detail_code = self::get_detail_code(substr($error_code, -4));
        
        $message = [];
        
        if($error_code === '00000000')
        {
            $message[$action_code->message] = [
                'description' => $action_code->description
            ];
        } else {
            $message[$action_code->message] = [
                'description' => $action_code->description
            ];
            
            $message[$category_code->message] = [
                'description' => $category_code->description
            ];
            
            $message[$detail_code->message] = [
                'description' => $detail_code->description
            ];
        }
        
        return $message;
        
    }
    
    public static function format_phone_number($phone)
    {
        if (preg_match("#(^\+[0-9]{2}|^\+[0-9]{2}\(0\)|^\(\+[0-9]{2}\)\(0\)|^00[0-9]{2}|^0)([0-9]{9}$|[0-9\-\s]{10}$)#", $phone))
        {
            if (stripos($phone, '+33') !== false) {
                $phone = str_replace("+33", "0", $phone);
            }
        } else {
            $phone = '0606060606';
        }
        
        return $phone;
    }
}
