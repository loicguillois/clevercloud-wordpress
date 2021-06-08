<?php

class Computop_Admin_Account {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public function __construct() {
        add_action ('woocommerce_settings_setting_computop_account', array($this, 'settings_tab'));
        add_action ('woocommerce_update_options_setting_computop_account', array($this, 'update_settings'));
    }
    
    /**
     * Output settings
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public function settings_tab() {
        $action = $_GET['action'] ?? null;
        $id = $_GET['id'] ?? null;
        
        if($action === 'delete' && !is_null($id))
        {
            if(!empty(Computop_Api::delete_merchant_account($id)))
            {
                wp_redirect('?page=wc-settings&tab=settings_computop_credentials&delete_success=yes');
            } else {
                wp_redirect('?page=wc-settings&tab=settings_computop_credentials&delete_success=no');
            }
            
            exit;
        }
        
        if($action === 'edit' && !is_null($id))
        {
            echo "<a href=\"?page=wc-settings&tab=setting_computop_account&action=delete&id=$id\" class=\"button button-link-delete right\" id=\"delete_account\">" . __('Delete this account', 'computop') . "</a>";
            $account = Computop_Api::get_merchant_account($id);
            echo self::woocommerce_wp_select_multiple();
            woocommerce_admin_fields($this->get_settings($account));
        }
        else {
            echo '<h2>'.__('Axepta Account', 'computop').'</h2>';
            woocommerce_admin_fields($this->get_settings());
        }
        
        // Add button & Hide Save Button
        submit_button(__('Save Settings', 'computop'), 'button-primary', 'save');
        global $hide_save_button;
        $hide_save_button = true;
    }
    
    /**
     * Get options list from the activation key
     *
     * @return array
     */
    public static function allowed_options($key) {
        $options = explode(';', $key);
        array_pop($options);
        return $options;
    }

    /**
     * Save settings
     */
    public function update_settings() {

        $errors = $this->validate_settings();
        if (count($errors) > 0) {
            return false;
        }
        
        $id = $_GET['id'] ?? null;
        
        global $wpdb;
        
        if(!is_null($id))
        {
            $filtered_payments = '';
                
            foreach ($_POST as $key => $value) {
            
                if(strpos($key, 'computop_allow_') !== false)
                {                    
                    if($value)
                    {
                        $filtered_payments .= str_replace("computop_allow_","",$key).';';
                    }
                    
                }
            
            }
            
            $filtered_coutries = '';
            
            if(in_array('ALL', $_POST['computop_allowed_country']))
            {
                $filtered_coutries .= 'ALL';
            }else
            {
                foreach ($_POST['computop_allowed_country'] as $key => $value) {
            
                $filtered_coutries .= $value.';';
                
            }
            }
            
            $merchant = Computop_Api::get_merchant_account($id);
                   
            if($merchant->authorized_payment !== $_POST['computop_activation_key']) {
                $filtered_payments = 'ALL';
            }
            $filtered_coutries = rtrim($filtered_coutries, ';');

            $wpdb->update( 
                    $wpdb->prefix .'computop_merchant_account', 
                    array( 
                            'name' => trim($_POST['computop_merchant_id']),
                            'front_label' =>  trim($_POST['computop_front_label']),
                            'password' => trim($_POST['computop_password']),
                            'hmac_key' => trim($_POST['computop_hmac_key']),
                            'authorized_payment' => trim($_POST['computop_activation_key']),
                            'filtered_payments' => $filtered_payments,
                            'country'=> $filtered_coutries,
                            'allow_3ds' =>  trim($_POST['computop_anti_fraud_control_3ds'] ?? 0),
                            'allow_one_click' =>  trim($_POST['computop_one_click'] ?? 0),
                            'allow_abo' =>  trim($_POST['computop_abo'] ?? 0),
                            'min_amount_3ds' => trim($_POST['computop_min_amount_3ds'] ?? 1),
                            'display_card_method' => trim($_POST['computop_display_card_method']),
                            'presto_product_category_value' => trim($_POST['presto_product_category_value']),
                            'is_active' => trim($_POST['computop_active_account']),
                            'currency' => trim(get_option( 'woocommerce_currency' )),
                            'capture_method' => trim($_POST['computop_capture_method']),
                            'capture_hours' => trim($_POST['computop_capture_hours'])
                    ), 
                    array( 'account_id' => $id ),
                    array(
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%d',
                            '%s',
                            '%s',
                            '%d',
                            '%s',
                            '%s',
                            '%d'
                    ),
                    array( '%d' )
            );
            
        }
        else {
            $check_insert = $wpdb->insert($wpdb->prefix .'computop_merchant_account', array(
                        'name'=>trim($_POST['computop_merchant_id']),
                        'front_label'=>trim($_POST['computop_front_label']),
                        'password'=>trim($_POST['computop_password']),
                        'hmac_key'=>trim($_POST['computop_hmac_key']),
                        'authorized_payment'=>trim($_POST['computop_activation_key']),
                        'filtered_payments'=> 'ALL',
                        'country'=> 'ALL',
                        'is_active'=> 0,
                        'capture_method' => 'AUTO',
                        'currency' => trim(get_option( 'woocommerce_currency' ))
                            ),array(
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                                '%d',
                                '%s'
                                ));
        
            wp_redirect(($check_insert) ? '?page=wc-settings&tab=setting_computop_account&action=edit&id=' . $wpdb->insert_id : '?page=wc-settings&tab=settings_computop_credentials&computop_error=insert_fail');
            exit;
        }
        return true;
        
    }
    
    /**
     * Validate setting
     * @return array
     */
    public function validate_settings() {
        $id = $_GET['id'] ?? null;
            
        $errors = array();
        // Merchant ID
        $merchant_id = trim($_POST['computop_merchant_id']);
        if (empty($merchant_id)) {
            $errors[] = __('You have to register a Merchant ID.', 'computop');
        }
        // Front Label
        $front_label = trim($_POST['computop_front_label']);
        if (empty($front_label) && isset($id) ) {
            $errors[] = __('You have to register a front label.', 'computop');
        }
        // Password
        $computop_password = trim($_POST['computop_password']);
        if (empty($computop_password)) {
            $errors[] = __('You have to register your password.', 'computop');
        }
        // activation key
        $computop_active_key = trim($_POST['computop_activation_key']);
        if (empty($computop_active_key)) {
            $errors[] = __('You have to register your activation key.', 'computop');
        } elseif(!Computop_Api::decrypt_activation_key($computop_active_key))
        {
            $errors[] = __('You have to register a valid activation key.', 'computop');
        }
        // hmac key
        $hmac_key = trim($_POST['computop_hmac_key']);
        if (empty($hmac_key)) {
            $errors[] = __('You have to register a hmac key.', 'computop');
        }
        if (isset($_POST['computop_min_amount_3ds'])) {
            $amount_3ds = trim($_POST['computop_min_amount_3ds']);
            if (!empty($amount_3ds)) {
                if (filter_var($amount_3ds, FILTER_VALIDATE_INT) === false) {
                    $errors[] = __('The 3DS minimum amount must contain only numeric.', 'computop');
                } elseif ((int) $amount_3ds <= 0) {
                    $errors[] = __('The 3DS minimum amount must be superior to 0.', 'computop');
                }
            }
        }
        if (isset($_POST['computop_capture_hours'])) {
            $capture_hours = trim($_POST['computop_capture_hours']);

            if (!empty($capture_hours)) {
                if (filter_var($capture_hours, FILTER_VALIDATE_INT) === false) {
                    $errors[] = __('The time to capture in hours must contain only numeric.', 'computop');
                } elseif ((int)$capture_hours <= 0 || (int)$capture_hours > 697) {
                    $errors[] = __('The time to capture in hours must be superior to 0 and less than 697.', 'computop');
                }
            }
        }
        
        if(!in_array(get_option( 'woocommerce_currency' ), self::allowed_options($computop_active_key)))
        {
            $errors[] = __('The key is not compatible with the currency selected in Woocommerce configuration', 'computop');
        }
        
        //check if another same config exist
        if(!is_null($id))
        {
            $countries = $_POST['computop_allowed_country'] ?? '';

            if(empty($countries))
            {
                $errors[] = __('You must choose at less one country', 'computop');
            } else {
                foreach ($countries as $key => $country)
                {
                    if(Computop_Api::get_merchant_account_by_name_and_country($merchant_id, $country, $id))
                    {
                        $errors[] = __('Another configuration exist for this merchant and this country', 'computop');
                        break;
                    }
                }
            }
                        
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                WC_Admin_Settings::add_error($error);
            }
        }
        return $errors;
    }
        
        /**
     * Return the available countries
     *
     * @return array
     */
    public function available_currencies() {
        $datas = Computop_Api::get_available_currencies();
        $options = [];
        foreach ($datas as $data) {
            $options[$data['id']] = $data['name'];
        }
        return $options;
    }
        
    // search by country
    public static function get_all_payments_method($country)
    {
        $get_payment_methods = Computop_Api::get_payment_methods($country);
        
        foreach ($get_payment_methods as $get_payment_method)
        {
            $array [$get_payment_method->trigram] = $get_payment_method->code;
        }
        return $array;
        
    }
    
    // search by countries
    public static function get_all_payments_method_by_countries($countries)
    {
        $get_payment_methods = Computop_Api::get_payment_methods_by_countries($countries);
        
        foreach ($get_payment_methods as $get_payment_method)
        {
            $array [$get_payment_method->trigram] = $get_payment_method->code;
        }
        return $array;
    }
    
    public static function get_all_payments_method_without_country_config()
    {
        $get_payment_methods = Computop_Api::get_payment_methods_without_country_config();
        
        foreach ($get_payment_methods as $get_payment_method)
        {
            $array [$get_payment_method->trigram] = $get_payment_method->code;
        }
        return $array;
    }

    /**
     * Get all the settings
     *
     * @return array
     */
    public function get_settings($account = null) {
        
        $style = <<<CSS
        <style>
            .form-table td {display: inline-block;}
            #computop_activation_key { float:left; }
            .form-table td p { display: inline-block!important; float: right !important; font-size: 13px; font-style: italic;}
            tr {margin-bottom: 20pximportant; }
            .woocommerce table.form-table .select2-container+span.description {float:right;}
        </style>
CSS;
        echo $style;
        
        if(is_null($account))
        {
            $name = $_POST['computop_merchant_id'] ?? null;
            $front_label = $_POST['computop_front_label'] ?? null;
            $password = $_POST['computop_password'] ?? null;
            $hmac_key = $_POST['computop_hmac_key'] ?? null;
            $authorized_payment = $_POST['computop_activation_key'] ?? null;
            $default_country = 'ALL';
            $default_currency = get_option( 'woocommerce_currency' );
            $id = null;
            $default_enable = $_POST['computop_active_account'] ?? 0;
        }
        else {
            $name = $account->name;
            $front_label = $account->front_label;
            $password = $account->password;
            $hmac_key = $account->hmac_key;
            $authorized_payment = $account->authorized_payment;
            $default_country = $account->country;
            $default_currency = $account->currency;
            $id = $account->account_id;
            $allow_3ds = $account->allow_3ds;
            $allow_one_click = $account->allow_one_click;
            $allow_abo = $account->allow_abo;
            $amount_3ds = $account->min_amount_3ds;
            $capture_method = $account->capture_method;
            $capture_hours = $account->capture_hours;
            $display_card_method = $account->display_card_method;
            $presto_product_category_value = $account->presto_product_category_value;
            if($account->is_active)
            {
                $default_enable = 1;
            }
            else
            {
                $default_enable = 0;
            }            
        }
        
        $countries_obj   = new WC_Countries();
        $countries   = $countries_obj->get_allowed_countries();
        
        $array_diff = array_diff($countries_obj->get_countries(), $countries_obj->get_allowed_countries());
        
        if(empty($array_diff))
        {
            $countries = array('ALL' => 'Tous les pays') + $countries;
        }
        
        $settings = array(
            'general' => array(
                'title' => '',
                'type' => 'title'
            ));
              
        if(!is_null($account))
        {
            $settings += array(
            'front_label' => array(
                'title' => __('Front Label', 'computop'), 
                'type' => 'text',
                'desc' => __('Label for this account in front.', 'computop'),
                'default' => 'Axepta BNP Paribas',
                'value' => $front_label,
                'id' => 'computop_front_label',
            ),
            'currency' => array(
                'title' => __('*Currency allowed', 'computop'),
                'type' => 'text',
                'custom_attributes' => array( 'disabled' => true),
                'desc' => __('Currency selected in woocommerce configuration.', 'computop'),
                'default' => get_option( 'woocommerce_currency' ),
                'value' => get_option('woocommerce_currency'),
                'id' => 'computop_allowed_currency',
            ),
            'country' => array(
                'title' => __('*Country allowed', 'computop'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'desc' => __('Select the country you want to connect to your account', 'computop'),
                'default' => explode(';', $default_country),
                'options' => $countries,
                'id' => 'computop_allowed_country',
            ));
        }
        
        
        $settings += array(
                'merchant_id' => array(
                'title' => __('*Enter your Merchant ID', 'computop'),
                'type' => 'text',
                'desc' => __('This ID is provided by Axepta', 'computop'),
                'id' => 'computop_merchant_id',
                'value' => $name
            ),
            'password' => array(
                'title' => __('*Enter your password', 'computop'),
                'type' => 'password',
                'css' => 'min-width: 300px;',
                'desc' => __('This password is provided by Axepta', 'computop'),
                'id' => 'computop_password',
                'value' => $password
            ),
            'hmac_key' => array(
                'title' => __('*Enter your hmac key', 'computop'),
                'type' => 'text',
                'desc' => __('This hmac key is provided by Axepta', 'computop'),
                'id' => 'computop_hmac_key',
                'value' => $hmac_key
            ),
            'active_key' => array(
                'title' => __('*Enter your activation key', 'computop'),
                'type' => 'textarea',
                'css' => 'width: 550px; min-height: 110px;',
                'desc' => __('This key is provided by Axepta', 'computop'),
                'id' => 'computop_activation_key',
                'value' => $authorized_payment
            ),
            'sectionend' => array(
                'type' => 'sectionend'
            )
            );
        
            if(!is_null($account))
            {
                
                $authorized_payment = $account->authorized_payment;
                
                $settings[] = array(
                        'title' => __('Payment options', 'computop'),
                        'type' => 'title',
                        'id' => 'section_options'
                    );
                /*if(in_array('3DS', explode(';',$authorized_payment)))
                {*/
                    $settings[] = array(
                        'title' => __('*Activate 3D-Secure', 'computop'),
                        'type' => 'radio',
                        'desc_tip' => true,
                        'desc' => __('Add the 3DS Secure control based on the 3DS authentication', 'computop'),
                        'value' => $allow_3ds,
                        'options' => array(
                            1 => __('Yes', 'computop'),
                            0 => __('No', 'computop')
                        ),
                        'id' => 'computop_anti_fraud_control_3ds'
                    );
                    $settings[] = array(
                        'title' => __('Minimum amount for which activate 3D-Secure', 'computop'),
                        'type' => 'text',
                        'desc' => __('Minimum amount to add the 3DS', 'computop'),
                        'default' => '1',
                        'value' => (int)$amount_3ds,
                        'id' => 'computop_min_amount_3ds'
                    );
                //}

                /*if(in_array('ONE', explode(';',$authorized_payment)))
                {*/
                    $settings[] = array(
                        'title' => __('*Activate One-Click Payment', 'computop'),
                        'type' => 'radio',
                        'desc_tip' => true,
                        'desc' => __('Allow One-Click payment for customers', 'computop'),
                        'value' => $allow_one_click,
                        'options' => array(
                            1 => __('Yes', 'computop'),
                            0 => __('No', 'computop')
                        ),
                        'id' => 'computop_one_click'
                    );
                //}
                
                /*if(in_array('ABO', explode(';',$authorized_payment)))
                {*/
                    $settings[] = array(
                        'title' => __('*Activate Subcriptions', 'computop'),
                        'type' => 'radio',
                        'desc_tip' => true,
                        'desc' => __('Allow subcriptions for customers', 'computop'),
                        'value' => $allow_abo,
                        'options' => array(
                            1 => __('Yes', 'computop'),
                            0 => __('No', 'computop')
                        ),
                        'id' => 'computop_abo'
                    );
                //}
                
                $settings[] = array(
                        'type' => 'sectionend',
                        'id' => 'sectionend_options'
                    );

                
                $settings[] = array(
                        'title' => __('Payment page', 'computop'),
                        'type' => 'title',
                        'id' => 'section_options'
                    );
                $settings[] = array(
                    'title' => __('Card data input method', 'computop'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'css' => 'min-width: 150px;',
                    'desc' => __('Redirection : Redirect to payment page'.'<br>'.'Iframe : Payment page in checkout process', 'computop'),
                    'value' => $display_card_method,
                    'options' => array(
                        'DIRECT' => __('Redirection', 'computop'),
                        'IFRAME' => __('Iframe', 'computop')
                    ),
                    'id' => 'computop_display_card_method'
                );
                
                $settings[] = array(
                    'title' => __('Capture mode', 'computop'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'css' => 'min-width: 300px;',
                    'value' => $capture_method,
                    'options' => array(
                        'AUTO' => __('Automatic capture payment (by default)', 'computop'),
                        'TIMED' => __('Capture after time defined', 'computop')
                    ),
                    'id' => 'computop_capture_method'
                );
                
                $settings[] = array(
                    'title' => __('Time before capture', 'computop'),
                    'type' => 'text',
                    'desc' => __('Delay in hours until the capture (whole number: 1 to 696).', 'computop'),
                    'value' => $capture_hours,
                    'id' => 'computop_capture_hours',
                );
                $settings[] = array(
                        'type' => 'sectionend',
                        'id' => 'sectionend_page'
                    );

                $settings[] = array(
                    'title' => '',
                    'type' => 'title',
                    'id' => 'end'
                );
                
                if (in_array('PRE', explode(';',$authorized_payment))) {
                    $settings[] = array(
                        'title' => __('Type de produit Presto', 'computop'),
                        'type' => 'select',
                        'class' => 'wc-enhanced-select',
                        'css' => 'min-width: 300px;',
                        'value' => $presto_product_category_value,
                        'options' => array(
                            '320' => __('Household appliances', 'computop'),
                            '322' => __('Refrigerator - freezer', 'computop'),
                            '323' => __('Dishwasher', 'computop'),
                            '324' => __('Washhing Machine', 'computop'),
                            '325' => __('Group Cleaning', 'computop'),
                            '326' => __('Refrigerator', 'computop'),
                            '327' => __('Freezer', 'computop'),
                            '328' => __('Cooker/Cooker hob', 'computop'),
                            '329' => __('Dryer', 'computop'),
                            '330' => __('Furniture', 'computop'),
                            '331' => __('Living Room', 'computop'),
                            '332' => __('Dining Room', 'computop'),
                            '333' => __('Bedroom', 'computop'),
                            '334' => __('Sofa', 'computop'),
                            '335' => __('Group furniture', 'computop'),
                            '336' => __('Armchair', 'computop'),
                            '337' => __('Library/Cabinet', 'computop'),
                            '338' => __('Bedding', 'computop'),
                            '339' => __('Bedroom', 'computop'),
                            '340' => __('Furniture Textile', 'computop'),
                            '341' => __('Office furniture', 'computop'),
                            '342' => __('Bathroom furniture', 'computop'),
                            '343' => __('Kitchen furniture', 'computop'),
                            '610' => __('TV-Hifi, Digital', 'computop'),
                            '611' => __('Video recorder - video', 'computop'),
                            '613' => __('Hifi', 'computop'),
                            '615' => __('Colour tv', 'computop'),
                            '616' => __('Information technology', 'computop'),
                            '619' => __('Group purchase TV - hifi', 'computop'),
                            '620' => __('Photo - Cinema - Optics', 'computop'),
                            '621' => __('Telephony', 'computop'),
                            '622' => __('Home Cinema', 'computop'),
                            '623' => __('LCD/Plasma Display', 'computop'),
                            '624' => __('Camcorder', 'computop'),
                            '625' => __('Computer', 'computop'),
                            '626' => __('Printer/Scanner', 'computop'),
                            '631' => __('Holiday Trips', 'computop'),
                            '640' => __('Clothing', 'computop'),
                            '650' => __('Books', 'computop'),
                            '660' => __('Leisure Activities', 'computop'),
                            '663' => __('DIY - Gardening', 'computop'),
                            '730' => __('Jewellery Store', 'computop'),
                            '733' => __('Radiator', 'computop'),
                            '855' => __('Piano', 'computop'),
                            '857' => __('Organ', 'computop'),
                            '858' => __('Various Music', 'computop'),
                        ),
                        'id' => 'presto_product_category_value'
                    );
                }
                
                $settings[] = array(
                    'title' => __('*Enabled this account', 'computop'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'desc' => __('Enable or disable this account', 'computop'),
                    'id' => 'computop_active_account',
                    'value' => $default_enable,
                    'options' => [
                        0 => __('Disable', 'computop'),
                        1 => __('Enable', 'computop'),
                    ]
                );
            }
        
            $settings += array(
                'section_end' => array(
                'type' => 'sectionend',
            ),
        );
        
        return apply_filters('wc_settings_computop_credentials', $settings);
    }
    
    public static function woocommerce_wp_select_multiple()
    {
        $id = $_GET['id'] ?? null;
        $merchant = Computop_Api::get_merchant_account($id);
        
        $field = self::get_all_payments_method_by_countries($merchant->country);
        
        if (in_array('ALL', explode(';', $merchant->country))) {
            $field = self::get_all_payments_method_without_country_config();
        }
        
        $field_no_country_config = self::get_all_payments_method_without_country_config();
        
        $allow_payments = Computop_Api::allowed_options();
        
        $filtered_payments = $merchant->filtered_payments;
        
        $diff = array_diff($field_no_country_config, $field);
        
        $method_not_possible = '';
        if (!empty($diff)) {
            foreach ($diff as $key => $value) {
                $method_not_possible .= '<tr valign="top"><th scope="row" class="titledesc"><label for="computop_one_click">*'.esc_html( $value ).'</label></th><td class="forminp forminp-radio"><fieldset><ul style="display: inline-flex;"><li><label><input disabled value="1" type="radio" style="" class=""> Oui</label></li><li style="margin-left:20px"><label><input disabled value="0" type="radio" style="" class=""> Non</label></li></ul><p style="margin-left:15px;">'.__('Payment method not allow for select countries', 'computop').'</p></fieldset></td></tr>';
            }
        }
        
        $explode = explode(';', $filtered_payments);
        
        $html = '<h2>'.__('My payment methods','computop').'</h2><table class="form-table form-list">';
        $value_title_not_allow = '';
        
        foreach ($field as $key => $value) {
           
            $title = false;
            for ($i=0; $i<sizeof($allow_payments); $i++) {
                if ($allow_payments[$i] == $key) {
                    $title = true;
                }
            }

            $value_title = '';
            
            
            if (in_array($key, $explode) || in_array('ALL', $explode) && $title) {
                if ($key == 'ALL') {
                    continue;
                }
                    $value_title = '<tr valign="top"><th scope="row" class="titledesc"><label for="computop_one_click">*'.esc_html( $value ).'</label></th><td class="forminp forminp-radio"><fieldset><ul style="display: inline-flex;"><li><label><input name="computop_allow_'.$key.'" value="1" type="radio" style="" class="" checked> Oui</label></li><li style="margin-left:20px"><label><input name="computop_allow_'.$key.'" value="0" type="radio" style="" class=""> Non</label></li></ul></fieldset></td></tr>';
            } elseif ($title) {
                $value_title = '<tr valign="top"><th scope="row" class="titledesc"><label for="computop_one_click">*'.esc_html( $value ).'</label></th><td class="forminp forminp-radio"><fieldset><ul style="display: inline-flex;"><li><label><input name="computop_allow_'.$key.'" value="1" type="radio" style="" class=""> Oui</label></li><li style="margin-left:20px"><label><input name="computop_allow_'.$key.'" value="0" type="radio" style="" class="" checked="checked"> Non</label></li></ul></fieldset></td></tr>';
            }
            
            if( !$title) {
                if ($key == 'ALL') {
                    continue;
                }
                
                if ($key == 'CVM' || $key == 'VIM') {
                    if (in_array('CVM', $allow_payments) || in_array('CVM', $allow_payments)) {
                        continue;
                    }
                }
                
                $value_title_not_allow .= '<tr valign="top"><th scope="row" class="titledesc"><label for="computop_one_click">*'.esc_html( $value ).'</label></th><td class="forminp forminp-radio"><fieldset><p>'.__('Please contact Axepta support to active this payment method.', 'computop').'</p></fieldset></td></tr>';
            }
            
            $html .= $value_title;
        }
        
        $html .= $method_not_possible;
        $html .= $value_title_not_allow;
        
        return $html;
    }
}

new Computop_Admin_Account();
