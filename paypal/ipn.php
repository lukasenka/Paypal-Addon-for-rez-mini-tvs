<?php



class PayPal_IPN {



    function ipn($ipn_data) {

        define('SSL_P_URL', 'https://www.paypal.com/cgi-bin/webscr');

        define('SSL_SAND_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr');

        $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);

        if (!preg_match('/paypal\.com$/', $hostname)) {

            $ipn_status = 'Validation post isn\'t from PayPal';

            if ($ipn_data == true) {

                //You can send email as well		

            }

            return false;

        }

        // parse the paypal URL

        $paypal_url = ($_REQUEST['test_ipn'] == 1) ? SSL_SAND_URL : SSL_P_URL;

        $url_parsed = parse_url($paypal_url);



        $post_string = '';

        foreach ($_REQUEST as $field => $value) {

            $post_string .= $field . '=' . urlencode(stripslashes($value)) . '&';

        }

        $post_string.="cmd=_notify-validate"; // append ipn command

        // get the correct paypal url to post request to

        $paypal_mode_status = $ipn_data; //get_option('im_sabdbox_mode');

        if ($paypal_mode_status == true)

            $fp = fsockopen('ssl://www.sandbox.paypal.com', "443", $err_num, $err_str, 60);

        else

            $fp = fsockopen('ssl://www.paypal.com', "443", $err_num, $err_str, 60);



        $ipn_response = '';



        if (!$fp) {

            // could not open the connection.  If loggin is on, the error message

            // will be in the log.

            $ipn_status = "fsockopen error no. $err_num: $err_str";

            if ($ipn_data == true) {

                echo 'fsockopen fail';

            }

            return false;

        } else {

            // Post the data back to paypal

            fputs($fp, "POST $url_parsed[path] HTTP/1.1\r\n");

            fputs($fp, "Host: $url_parsed[host]\r\n");

            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");

            fputs($fp, "Content-length: " . strlen($post_string) . "\r\n");

            fputs($fp, "Connection: close\r\n\r\n");

            fputs($fp, $post_string . "\r\n\r\n");



            // loop through the response from the server and append to variable

            while (!feof($fp)) {

                $ipn_response .= fgets($fp, 1024);

            }

            fclose($fp); // close connection

        }

        // Invalid IPN transaction.  Check the $ipn_status and log for details.

        if (!preg_match("/VERIFIED/s", $ipn_response)) {

            $ipn_status = 'IPN Validation Failed';



            if ($ipn_data == true) {

                echo 'Validation fail';

                print_r($_REQUEST);

            }

            return false;

        } else {

            $ipn_status = "IPN VERIFIED";

            if ($ipn_data == true) {

                echo 'SUCCESS';

            }



            return true;

        }

    }



    function ipn_response($request) {

        $ipn_data = true;

        if ($this->ipn($ipn_data)) {

            // if paypal sends a response code back let's handle it        

            if ($ipn_data == true) {

                echo "OK";

            }

            // process the membership since paypal gave us a valid +

            $this->insert_data($request);

        }

    }



    function issetCheck($post, $key) {

        if (isset($post[$key])) {

            $return = $post[$key];

        } else {

            $return = '';

        }

        return $return;

    }


}



$obj = New PayPal_IPN();

$obj->ipn_response($_REQUEST);

