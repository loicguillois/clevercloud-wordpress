<?php

class Computop_Admin_Credentials {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public function __construct() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 40);
        add_action('woocommerce_settings_tabs_settings_computop_credentials', array($this, 'computop_merchands_list_accounts'));
    }

    public function show_notices() {
        if (isset($_GET['computop_error'])) {
            $msg = "";
            $type = "updated";
            switch ($_GET['computop_error']) {
                case 'insert_fail' :
                    $type = "error";
                    $msg = __('An error occurred while saving the account', 'computop');
                    break;
            }
            echo '<div id="message" class="' . $type . ' inline"><p><strong>' . $msg . '</strong></p></div>';
        }

        if (isset($_GET['delete_success'])) {
            if ("yes" == $_GET['delete_success']) {
                echo '<div id="message" class="updated inline"><p><strong>' . __('Account correctly deleted !', 'computop') . '</strong></p></div>';
            } elseif ("no" == $_GET['delete_success']) {
                echo '<div id="message" class="error inline"><p><strong>' . __('Account not found, cannot be deleted !', 'computop') . '</strong></p></div>';
            } elseif ("yes" == $_GET['account_add_success']) {
                echo '<div id="message" class="updated inline"><p><strong>' . __('Account correctly added !', 'computop') . '</strong></p></div>';
            } elseif ("yes" == $_GET['account_update_success']) {
                echo '<div id="message" class="updated inline"><p><strong>' . __('Account correctly updated !', 'computop') . '</strong></p></div>';
            }
        }
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['settings_computop_credentials'] = __('Axepta Setup', 'computop');
        return $settings_tabs;
    }

    /**
     * list account
     * @global type $wpdb
     * @global boolean $hide_save_button
     */
    public function computop_merchands_list_accounts() {
        $toggle_account_merchant_id = $_GET['toggle_account_merchant_id'] ?? null;

        if (!is_null($toggle_account_merchant_id)) {

            if (!Computop_Api::enable_or_disable_merchand_account($toggle_account_merchant_id)) {
                echo '<div id="message" class="error inline"><p><strong>' . __('Another configuration exist for this merchant and this country', 'computop') . '</strong></p></div>';
            }
        }

        global $hide_save_button;
        $hide_save_button = true;

        echo "<a href=\"?page=wc-settings&tab=setting_computop_account\" class=\"button button-primary right\">" . __('Add an account', 'computop') . "</a>";

        $this->title = __('Axepta Accounts', 'computop');
        $this->accounts = Computop_Api::get_merchant_accounts();

        include_once plugin_dir_path(__DIR__) . 'views/html-computop-credentials.php';
    }

    /**
     * Save the general option, for the first activation
     */
    public function init_general_settings() {

        update_option('computop_activation_one', 'yes');

        //Config defaut pour afficher le onetime dès l'installation
        $default_config_onetime = array(
            "computop_onetime_title" => "",
            "enabled" => "yes",
            "computop_onetime_min_amount" => "",
            "computop_onetime_max_amount" => "",
        );
        update_option('woocommerce_computop_onetime_settings', $default_config_onetime);
    }

}

new Computop_Admin_Credentials();
