<?php

class Computop_Admin_General {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public function __construct() {
        include_once plugin_dir_path(__DIR__) . 'settings/computop-settings.php';
    }

    /**
     * Return the available currencies
     *
     * @return array
     */
    public function available_currencies() {
        $data = get_available_currencies();
        $options = array();
        foreach ($data as $key => $value) {
            $options[$key] = $value['name'];
        }
        return $options;
    }

    /**
     * Return the available cards
     *
     * @param bool
     * @return array
     */
    public static function available_cards($all = false) {

        $trigs = get_restricted_cards();
        $allowedCards = Computop_Api::allowed_options();
        $cards = array();
        $cards_filtered = [];
        $available_cards = get_available_cards(WC()->customer->get_billing_country());
        
        $cards_filtered['ALL'] = __('All available payments', 'computop');
        
        foreach ($available_cards as $keyCard => $valueCard) {
            if (!in_array($keyCard, $trigs)) {
                $cards[$keyCard] = $valueCard['name'];
            }
            if (!empty($allowedCards)) {
                if (in_array($keyCard, $allowedCards)) {
                    $cards_filtered[$keyCard] = $valueCard['name'];
                }
            }
        }
        
        return $cards_filtered;
    }
}

new Computop_Admin_General();