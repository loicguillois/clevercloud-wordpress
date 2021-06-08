<?php

/**
 * Computop_Xml_Import class
 */
class Computop_Xml_Import {

    protected function loadDictionnaryXml() {
        return simplexml_load_file((__DIR__).'/tools/computop_dictionnary.xml');
    }

    /**
     * Imports XML file in Computop tables
     * @return boolean true if success
     */
    public function importDictionnaryXml() {
        $xml = $this->loadDictionnaryXml();

        if (!$xml) {
            return false;
        }

        $this->insertDictionnaryInTables($xml);

        return true;
    }

    public function insertDictionnaryInTables($xml) {
        global $wpdb;

        if (get_option('computop_xml_dictionnary_version')) {
            if (version_compare(get_option('computop_xml_dictionnary_version'), (string) $xml->version) >= 0) {
                return;
            } else {
                update_option('computop_xml_dictionnary_version', (string) $xml->version);
            }
        } else {
            add_option('computop_xml_dictionnary_version', (string) $xml->version, '', 'yes');
        }

        //truncate
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_paygate_action_code`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_paygate_category_code`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_paygate_detail_code`");

        // Start insert data in database
        // insert error codes
        $errors_code = $xml->paygate_action_codes;

        $action_codes = $errors_code->action_codes->action_code;
        $categories_codes = $errors_code->categories->category;
        $details_codes = $errors_code->details_codes->detail_code;

        foreach ($action_codes as $action_code) {
            $wpdb->insert(
                    "{$wpdb->prefix}computop_xml_paygate_action_code", array(
                'code' => (string) $action_code->code,
                'message' => (string) $action_code->message,
                'description' => (string) $action_code->description
                    )
            );
        }

        foreach ($categories_codes as $categorie_code) {
            $wpdb->insert(
                    "{$wpdb->prefix}computop_xml_paygate_category_code", array(
                'code' => (string) $categorie_code->code,
                'message' => (string) $categorie_code->message,
                'description' => (string) $categorie_code->description
                    )
            );
        }

        foreach ($details_codes as $detail_code) {
            $wpdb->insert(
                    "{$wpdb->prefix}computop_xml_paygate_detail_code", array(
                'code' => (string) $detail_code->code,
                'message' => (string) $detail_code->message,
                'description' => (string) $detail_code->description
                    )
            );
        }
    }

    protected function loadXml() {
        return simplexml_load_file((__DIR__).'/tools/computop_methods.xml');
    }

    /**
     * Imports XML file in Computop tables
     * @return boolean true if success
     */
    public function importXml() {
        $xml = $this->loadXml();

        if (!$xml) {
            return false;
        }

        $this->insertInTables($xml);

        return true;
    }

    public function insertInTables($xmlMethods) {
        global $wpdb;
        
        if (get_option('computop_xml_methods_version')) {
            if (version_compare(get_option('computop_xml_methods_version'), (string) $xmlMethods->version) >= 0) {
                return;
            } else {
                update_option('computop_xml_methods_version', (string) $xmlMethods->version);
            }
        } else {
            add_option('computop_xml_methods_version', (string) $xmlMethods->version, '', 'yes');
        }
        
        //truncate
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_method`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_parameter_set`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_parameter`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_method_lang`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}computop_xml_allow_countries`");

        // Import methods            
        // TODO CHECK VERSION XML METHODS
        foreach ($xmlMethods->methods->children() as $method) {

            $wpdb->insert(
                    "{$wpdb->prefix}computop_xml_method", array(
                'trigram' => (string) $method->code->attributes()['trigram'],
                'code' => (string) $method->code
                    )
            );

            $method_id = $wpdb->insert_id;

            foreach ($method->labels->children() as $label) {
                $wpdb->insert(
                        "{$wpdb->prefix}computop_xml_method_lang", array(
                    'method_id' => $method_id,
                    'iso' => (string) $label->attributes()['language'],
                    'label' => (string) $label,
                        )
                );
            }

            foreach ($method->operations->children() as $operation) {
                // add parameter_set data
                $wpdb->insert(
                        "{$wpdb->prefix}computop_xml_parameter_set", array(
                    'operation' => (string) $operation->attributes()['code'],
                    'parameter_set_id' => (string) $operation->attributes()['parameter_set'],
                    'url' => (string) $operation->action_urls->url,
                    'url_test' => (string) $operation->action_urls->test_url,
                    'method_id' => $method_id
                        )
                );
            }
            foreach ($method->allow_countries->children() as $country) {
                $wpdb->insert(
                        "{$wpdb->prefix}computop_xml_allow_countries", array(
                    'method_id' => $method_id,
                    'currency' => (string) $country->attributes()['currency'],
                    'country' => (string) $country,
                        )
                );
            }
        }
        foreach ($xmlMethods->parameter_sets->children() as $parameterSet) {
            $parameterSetId = (string) $parameterSet->attributes()['id'];
            foreach ($parameterSet->parameters->children() as $parameter) {
                $wpdb->insert(
                        "{$wpdb->prefix}computop_xml_parameter", array(
                    'name' => (string) $parameter->code,
                    'format' => (string) $parameter->format,
                    'required' => (string) $parameter->required,
                    'parameter_set_id' => $parameterSetId
                        )
                );
            }
        }
    }

}

new Computop_Xml_Import();
