<?php

function getBaseUrl() 
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    
    $link = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 

    $sub = substr($link, 0, strpos($link,'wp-content'));
    
    if (empty($sub)) {
        $tab = explode('/', $_SERVER['REQUEST_URI']);

        $i = -1;
        $found = false;
        while(!$found) {
			$i++;
            if ($tab[$i] == "plugins") {
                $found = true;
            }
        }

        return $protocol . "://" . $_SERVER['HTTP_HOST'].'/'.$tab[$i - 2].'/';
    } else {
        return $sub;
    }
}

if(!empty($_GET))
{
    $params = '';
    foreach($_GET as $key => $value)
    {
        $params .= $key. '=' .$value . '&';
    }
    
    $params = substr($params, 0, -1);

    header('Location: '. getBaseUrl() .'?wc-api=Computop_Gateway_Recurring&'.$params);
    exit();
}
