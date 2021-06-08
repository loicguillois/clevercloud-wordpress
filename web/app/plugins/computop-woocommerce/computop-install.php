<?php

/**
 * Computop_Install class
 */
class Computop_Install {
    
    public static function deactivation() {
        delete_option( 'computop_activation_key' );
    }

    public static function install() {
        $admin_credential = new Computop_Admin_Credentials();
        $admin_credential->init_general_settings();

        global $wpdb;

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_transaction` (
            `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
            `merchant_id` varchar(255) NULL,
            `transaction_reference` varchar(255) NULL,
            `transaction_date` datetime NULL,
            `order_id` int(11) NULL,
            `pay_id` varchar(255) NULL,
            `bid` varchar(255) NULL,
            `xid` varchar(255) NULL,
            `amount` decimal(20,2) NULL,
            `transaction_type` varchar(255) NULL,
            `payment_mean_brand` varchar(255) NULL,
            `payment_mean_type` varchar(255) NULL,
            `response_code` varchar(255) NULL,
            `pcnr` varchar(255) NULL,
            `ccexpiry` varchar(255) NULL,
            `status` varchar(255) NULL,
            `description` varchar(255) NULL,
            `raw_data` text NULL,
            PRIMARY KEY (`transaction_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_customer_oneclick_payment_card` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NULL,
            `pcnr` varchar(255) NULL,
            `ccexpiry` varchar(255) NULL,
            `ccbrand` varchar(255) NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
                
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_payment_recurring` (
            `id_computop_payment_recurring` int(10) NOT NULL AUTO_INCREMENT,
            `id_product` int(10) NOT NULL,
            `type` int(10) DEFAULT NULL,
            `number_periodicity` int(10) NOT NULL,
            `periodicity` varchar(10) NOT NULL,
            `number_occurences` int(10) NOT NULL,
            `recurring_amount` float DEFAULT NULL,
            PRIMARY KEY (`id_computop_payment_recurring`),
            KEY `id_product` (`id_product`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
        );
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_customer_payment_recurring` (
            `id_computop_customer_payment_recurring` int(10) NOT NULL AUTO_INCREMENT,
            `id_product` int(10) NOT NULL,
            `id_tax_rules_group` int(10) NOT NULL,
            `id_order` int(10) NOT NULL,
            `id_customer` int(10) NOT NULL,
            `id_computop_transaction` int(10) NOT NULL,
            `bid` varchar(255) NULL,
            `pcnr` varchar(255) NULL,
            `ccexpiry` varchar(255) NULL,
            `ccbrand` varchar(255) NULL,
            `status` int(10) NOT NULL,
            `amount_tax_exclude` float NOT NULL,
            `number_periodicity` int(10) NOT NULL,
            `periodicity` varchar(10) NOT NULL,
            `number_occurences` int(10) NOT NULL,
            `current_occurence` int(10) NOT NULL DEFAULT '0',
            `date_add` datetime DEFAULT NULL,
            `last_schedule` datetime DEFAULT NULL,
            `next_schedule` datetime DEFAULT NULL,
            `current_specific_price` decimal(5,2) NOT NULL DEFAULT '0',
            `id_cart_paused_currency` int(10) NOT NULL DEFAULT '1',
            `nb_fail` int(10) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id_computop_customer_payment_recurring`),
            KEY `id_product` (`id_product`,`id_tax_rules_group`,`id_order`,`id_customer`,`id_computop_transaction`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
        );

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_method` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `trigram` varchar(255) NOT NULL,
            `code` varchar(255) NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
            
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_allow_countries` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `method_id` int(11) NULL,
            `currency` varchar(255) NULL,
            `country` varchar(255) NULL,
            FOREIGN KEY (`method_id`) REFERENCES `{$wpdb->prefix}computop_xml_method`(`id`),
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_parameter_set` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `operation` varchar(255) NULL,
            `parameter_set_id` int(11) NOT NULL,
            `url` varchar(255) NULL,
            `url_test` varchar(255) NULL,
            `method_id` int(11) NOT NULL,
            FOREIGN KEY (`method_id`) REFERENCES `{$wpdb->prefix}computop_xml_method`(`id`),
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );

        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_parameter` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NULL,
            `format` varchar(255) NULL,
            `required` varchar(255) NULL,
            `parameter_set_id` int(11) NOT NULL,
            FOREIGN KEY (`parameter_set_id`) REFERENCES `{$wpdb->prefix}computop_xml_parameter_set`(`id`),
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
            
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_method_lang` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `method_id` int(11) NOT NULL,
            `iso` varchar(255) NULL,
            `label` varchar(255) NULL,
            FOREIGN KEY (`method_id`) REFERENCES `{$wpdb->prefix}computop_xml_method`(`id`),
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
            
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_merchant_account` (
            `account_id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NULL,
            `front_label` varchar(255)NOT NULL DEFAULT 'Axepta BNP Paribas',
            `password` varchar(255) NULL,
            `hmac_key` varchar(255) NULL,
            `authorized_payment` text NULL,
            `filtered_payments` varchar(1000) NOT NULL DEFAULT 'ALL',
            `allow_3ds` tinyint(1) NOT NULL DEFAULT '1',
            `allow_abo` tinyint(1) NOT NULL DEFAULT '1',
            `allow_one_click` tinyint(1) NOT NULL DEFAULT '1',
            `min_amount_3ds` int(11) NOT NULL DEFAULT '1',
            `capture_method` varchar(255) NOT NULL DEFAULT 'AUTO',
            `capture_hours` int(11) NOT NULL DEFAULT '1',
            `display_card_method` varchar(255) NOT NULL DEFAULT 'DIRECT',
            `presto_product_category_value` varchar(255) NOT NULL DEFAULT '320',
            `country` varchar(255) NULL,
            `currency` varchar(255) NULL,
            `is_active` tinyint(1) NULL,
            PRIMARY KEY (`account_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
        
        $check_if_presto_category_exist = $wpdb->query("SHOW COLUMNS FROM {$wpdb->prefix}computop_merchant_account LIKE 'presto_product_category_value'");

        if(empty($check_if_presto_category_exist)){
        $wpdb->query("
            ALTER TABLE `{$wpdb->prefix}computop_merchant_account` ADD `presto_product_category_value` VARCHAR(255)
            NOT NULL DEFAULT '320' AFTER `display_card_method`");
        }
            /*ALTER TABLE `wp_computop_merchant_account` ADD `presto_product_category_value` VARCHAR(255) NOT NULL DEFAULT '320' AFTER `display_card_method`;*/
        $check_if_front_label_exist = $wpdb->query("SHOW COLUMNS FROM {$wpdb->prefix}computop_merchant_account LIKE 'front_label'");

        if(empty($check_if_front_label_exist)){
        $wpdb->query("
            ALTER TABLE `{$wpdb->prefix}computop_merchant_account` ADD `front_label` VARCHAR(255)
            NOT NULL DEFAULT 'Axepta BNP Paribas' AFTER `name`");
        }
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_paygate_action_code` (
            `code` varchar(11) NULL,
            `message` varchar(255) NULL,
            `description` varchar(255) NULL,
            KEY `code` (`code`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
                    
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_paygate_category_code` (
            `code` varchar(11) NULL,
            `message` varchar(255) NULL,
            `description` varchar(255) NULL,
            KEY `code` (`code`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        );
                            
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}computop_xml_paygate_detail_code` (
            `code` varchar(11) NULL,
            `message` varchar(255) NULL,
            `description` varchar(255) NULL,
            KEY `code` (`code`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8"
        ); 
        
        // INSERT DATA XML
        $importXml = new Computop_Xml_Import();
        $importXml->importDictionnaryXml();
        $importXml->importXml();
    }
}
new Computop_Install();