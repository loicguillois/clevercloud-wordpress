<?php

/**
 * Plugin Name: Axepta Online BNP Paribas
 * Version: 2.0.9
 * Author: Quadra Informatique
 * Author URI: http://shop.quadra-informatique.fr
 * Description: Axepta Online BNP Paribas essential POST WooCommerce
 * WC requires at least: 3.6
 * WC tested up to: 4.2.2
 */

/**
 * Computop Class
 */
class Computop {
    
    private $merchant;
        
    public function __construct() {
        $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
        if (in_array( 'woocommerce/woocommerce.php', $active_plugins)) {
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-blowfish.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-api.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-payment.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-admin-credentials.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-admin-general.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-admin-recurring-payment-list.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-logger.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-oneclick.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-webservice.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-recurring-payment.php';
            include_once plugin_dir_path(__FILE__) . 'account/class-computop-customer-account.php';
            include_once plugin_dir_path(__FILE__) . 'gateways/class-computop-gateway-onetime.php';
            include_once plugin_dir_path(__FILE__) . 'gateways/class-computop-gateway-recurrent.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-admin-transactions.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-transaction.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-computop-admin-account.php';

            include_once plugin_dir_path(__FILE__) . 'computop-install.php';
            include_once plugin_dir_path(__FILE__) . 'computop-xml-import.php';
            register_activation_hook(__FILE__, array('Computop_Install', 'install'));
            register_deactivation_hook(__FILE__, array('Computop_Install', 'deactivation'));

            add_action('admin_enqueue_scripts', array($this, 'my_admin_scripts'));
            add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
            add_action('admin_notices', array($this, 'notice'));
            add_filter('woocommerce_available_payment_gateways', array($this, 'computop_gateway_disable'));
            load_theme_textdomain('computop', plugin_dir_path(__FILE__) . '/languages');
            $current_version = get_file_data(plugin_dir_path(__FILE__).'computop-woocommerce.php', array('Version'))[0];
            
            if (is_admin()) {
                if (get_option('computop_current_version')) {
                    if(version_compare(get_option('computop_current_version'), $current_version) < 0) {
                        Computop_Install::install();
                        update_option('computop_current_version', $current_version);
                    }
                } else {
                    add_option('computop_current_version', $current_version, '', 'yes');
                }
            } else {
                $this->check_if_iframe();
                add_action('wp_enqueue_scripts', array($this, 'iframe_js'));
            }
        } else {
            add_action('admin_notices', array($this, 'notice_if_woocommerce_not_active'));
        }

    }

    public function add_meta_boxes()
    {
        add_meta_box('woocommerce-order-transactions', __('Transactions history', 'computop'), 'Computop_Transaction::output', 'shop_order', 'normal', 'low');
        add_meta_box('woocommerce-order-schedules', __('Transactions schedule', 'computop'), 'Computop_Schedule::output', 'shop_order', 'normal', 'low');
        add_meta_box('woocommerce-computop-recurrent-payment', __('Recurring Payment', 'computop'), 'Computop_Gateway_Recurring::add_form_computop_recurring_payment_admin', 'product', 'side', 'low');
    }
    
    public function my_admin_scripts()
    {
        wp_enqueue_script('back', plugin_dir_url(__FILE__) . 'assets/js/back.js', array('jquery'));
    }

    public function notice()
    {
        if (extension_loaded('curl') == false) {
            echo '<div class="update-nag">' . __('You have to enable the cURL extension on your server to use this module.', 'computop') . '</div>';
        }
        if (extension_loaded('openssl') == false) {
            echo '<div class="update-nag">' . __('You have to enable the OpenSSL extension on your server to use this module.', 'computop') . '</div>';
        }
    }
    
    public function notice_if_woocommerce_not_active()
    {
        echo '<div class="update-nag">' . __('Woocommerce\'s plugin must be active to use Axepta', 'computop') . '</div>';
    }
    
    public function get_merchant_account($abo = false)
    {
        if ($abo) {
            return $this->merchant = Computop_Api::get_merchant_id_by_country_and_currency_with_abo();
        } else {
            return $this->merchant = Computop_Api::get_merchant_id_by_country_and_currency();
        }
        
    }
    
    public function computop_gateway_disable($gateways)
    {   
        if (!is_admin()) {
            global $woocommerce;
        
            $merchant = $this->get_merchant_account();
            
            if(is_null($merchant)) {
                unset($gateways['computop_onetime']);
                unset($gateways['computop_recurring']);
            } elseif (/*!Computop_Api::is_allowed('ABO') ||*/ !Computop_Api::is_allowed_abo ()) {
                unset($gateways['computop_recurring']);
            }
            return $gateways;
        }

    }
    
    public function iframe_js()
    {
        wp_enqueue_script('iframe', plugin_dir_url(__FILE__) . 'assets/js/iframe.js', array('jquery'));
    }
    
    public function iframe_js_redirect()
    {
        wp_enqueue_script('iframe_redirect', plugin_dir_url(__FILE__) . 'assets/js/iframe_redirect.js', array('jquery'));
    }
    
    public function check_if_iframe()
    {
        if(isset($_GET['iframe'])) {
            if($_GET['iframe'] == 'yes') {
                add_action('wp_enqueue_scripts', array($this, 'iframe_js_redirect'));
            }
        }
    }
    
} 
new Computop();