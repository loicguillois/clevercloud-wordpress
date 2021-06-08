<?php

/**
 * Check if the number is between the min and max value
 *
 * @param int
 * @return bool
 */
function is_between($number, $min, $max) {
    if (empty($number) || !isset($min) || empty($max)) {
        return false;
    }
    if ((int) $number >= (int) $min && (int) $number <= (int) $max) {
        return true;
    }
    return false;
}

/**
 * Validate the text length
 *
 * @param string, int
 * @return bool
 */
function validation_length($text, $number) {
    if (empty($text) || empty($number)) {
        return false;
    }
    if (strlen($text) <= $number) {
        return true;
    }
    return false;
}

/**
 * Get available cards list
 *
 * @return array
 */
function get_available_cards($country) {
    
    $payments = Computop_Api::get_payment_methods($country);
    
    $locale = Computop_Api::get_locale();
    
    $array = [];
    foreach ($payments as $payment)
    {
        if($payment->iso == $locale)
        {
            $array[$payment->code] = [
                'id' => $payment->code,
                'name' => $payment->label,
                'type' => 'CARD'
            ]; 
        }
        
    }

    return $array;
}
