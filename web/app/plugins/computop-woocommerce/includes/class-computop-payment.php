<?php

class Computop_Payment
{
    /**
     * hidden form post direct payment
     * @param type $merchantId
     * @param type $data
     * @param type $len
     * @param type $url
     * @param type $url_back
     */
    public static function generate_direct_payment($merchantId, $data, $len, $url, $url_back) {
        echo "<form name=\"redirectForm\" method=\"POST\" action=\"" . $url . "\" >" .
        "<input type=\"hidden\" name=\"MerchantID\" value=\"" . $merchantId ."\">" .
        "<input type=\"hidden\" name=\"Data\" value=\"" . $data . "\">" .
        "<input type=\"hidden\" name=\"Len\" value=\"". $len ."\">" .
        "<input type=\"hidden\" name=\"URLBack\" value=\"". $url_back ."\">" .
        "<noscript><input type=\"submit\" name=\"Go\" value=\"Click to continue\"/></noscript> </form>" .
        "<script type=\"text/javascript\"> document.redirectForm.submit(); </script>";
    }
    
    /**
     * iframe
     * @param type $merchantId
     * @param type $data
     * @param type $len
     * @param type $url
     * @param type $url_back
     * @return type
     */
    public static function generate_iframe_payment($merchantId, $data, $len, $url, $url_back) {
        echo "<iframe id=\"iframe\" name=\"redirectForm\" style=\"border: none;height:900px; width:100%;\" scrolling=\"no\"></iframe> " .
        "<form id=\"redirectForm\" target=\"redirectForm\" method=\"POST\" action=\"" . $url . "\" >" .
            "<input type=\"hidden\" name=\"MerchantID\" value=\"" . $merchantId ."\">" .
            "<input type=\"hidden\" name=\"Data\" value=\"" . $data . "\">" .
            "<input type=\"hidden\" name=\"Len\" value=\"". $len ."\">" .
            "<input type=\"hidden\" name=\"URLBack\" value=\"". $url_back ."\">" .
            "<noscript><input type=\"submit\" name=\"Go\" value=\"Click to continue\"/></noscript> </form>" .
            "<script type=\"text/javascript\"> document.getElementById('redirectForm').submit();
            </script>";
    }

}
