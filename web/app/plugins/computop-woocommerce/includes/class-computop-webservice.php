<?php

/**
 * 1961-2016 BNP Paribas
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 *  @author    Quadra Informatique <modules@quadra-informatique.fr>
 *  @copyright 1961-2016 BNP Paribas
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
class Computop_Webservice {

    /**
     * Send Schedules
     */
    public static function send_recurring_schedules() {
        $schedules = Computop_Recurring_Payment::get_schedules_to_capture();

        if (!empty($schedules)) {
            
            foreach ($schedules as $schedule) {
                // LOG
                if (get_option('computop_log_active') == 'yes') {

                    $message = '---------------------- START RECURRING ----------------------';
                    Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                }
                $infos = Computop_Recurring_Payment::get_computop_customer_payment_recurring($schedule->id_computop_customer_payment_recurring);
                $order = new WC_Order($schedule->id_order);
                
                $trigram = get_post_meta($schedule->id_order, 'payment_mean_brand_one')[0];
                $url = Computop_Api::get_payment_url('recurring', $trigram);

                $transaction = Computop_Transaction::get_by_id($schedule->id_computop_transaction);
                  
                $params = Computop_Api::get_params($order, null, 'recurring', $schedule->current_specific_price*100, $transaction, $schedule);

                $merchant = Computop_Api::get_merchant_account_by_name($transaction->merchant_id);
                
                $params['MerchantID'] = $transaction->merchant_id;
                
                $response = Computop_Api::check_computop_response_with_curl($url, $params);

                if ($response === false) {
                    continue;
                }

                $a = explode('&', $response);
                $data = Computop_Api::ctSplit($a);

                $plaintext = Computop_Api::ctDecrypt($data['Data'], $data['Len'], $merchant->password);

                $b = explode('&', $plaintext);
                $save_data = Computop_Api::ctSplit($b);
                
                if (get_option('computop_log_active') == 'yes') {

                    $message = ' Params: ';
                    $message .= implode(', ', array_map(function ($v, $k) {
                                return $k . '=' . $v;
                            }, $save_data, array_keys($save_data)));
                        Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                }
                
                unset($schedule->late);
                date_default_timezone_set('europe/paris');
                $schedule->current_occurence = intval($schedule->current_occurence) + 1;
                $schedule->last_schedule = date('Y-m-d h:i:s');
                $interval = $schedule->number_periodicity;
                $time = strtotime(date($schedule->last_schedule));
                
                $schedule->next_schedule = ($schedule->periodicity == 'D') ?
                    date("Y-m-d h:i:s", strtotime("+$interval day", $time)) : date("Y-m-d h:i:s", strtotime("+$interval month", $time));
                $schedule->status = 1;
                
                if ($save_data['Code'] != '00000000') {
                    $schedule->status = 2;
                    $schedule->nb_fail = +1;
                } else {
                    $order->update_status("wc-processing");
                }
                
                $schedule->current_specific_price = $infos[0]->current_specific_price;
                
                Computop_Gateway_Recurring::update_computop_customer_payment_recurring($schedule->id_computop_customer_payment_recurring, $schedule);
                
                // LOG
                if (get_option('computop_log_active') == 'yes') {

                    $message = '---------------------- END RECURRING ----------------------';
                    Computop_Logger::log($message, Computop_Logger::LOG_DEBUG, Computop_Logger::FILE_DEBUG);
                }
                
                Computop_Transaction::save($save_data, 'recurring', $order, $plaintext, $schedule->current_specific_price);

            }
        }
        
        $schedules_completed = Computop_Recurring_Payment::get_schedules_to_stop();
        
        if(!is_null($schedules_completed))
        {
            foreach ($schedules_completed as $schedule_completed)
            {
                $schedule_completed->status = Computop_Gateway_Recurring::ID_STATUS_EXPIRED;
                Computop_Gateway_Recurring::update_computop_customer_payment_recurring($schedule_completed->id_computop_customer_payment_recurring, $schedule_completed);
            }
        }
    }

}

new Computop_Webservice();
