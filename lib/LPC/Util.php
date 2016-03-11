<?php

/**
 * * @copyright  Copyright (c) 2015 LePotCommun 
 * * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) 
 * * http://www.lepotcommun.fr 
 */

/**
 *  Class LPC_Util 
 */
class LPC_Util {
    /*
     * function converts array into string
     * @param array $array
     * @return string 
     */

    public static function buildStringFromArray($array) {
        $string = '';
        $i = 0;
        foreach ($array as $key => $value) {
            $var = $key . '=' . $value;
            if (++$i < count($array)) {
                $var .= "&";
            }
            $string .= $var;
        }
        return $string;
    }

    /**
     * @param $data
     * @param bool $assoc
     * @return mixed|null
     */
    public static function convertJsonToArray($data, $assoc = false) {
        if (empty($data)) {
            return null;
        }
        return json_decode($data, $assoc);
    }

    /**
     * Verify response from LePotCommun
     * @param string $response
     * @param string $messageName
     * @return null|array $result
     */
    public static function verifyResponse($response) {
        $httpStatus = $response['code'];
        $message = self::convertJsonToArray($response['response'], true);
   
        
        if ($httpStatus == 200 || $httpStatus == 201 || $httpStatus == 422 || $httpStatus == 301 || $httpStatus == 302 || $httpStatus == 404) {
            return $message;
        } else {

            if (isset($message['error'])) {
                throw new LPC_Exception(self::decodeMessage($message['error']['code']));
                return null;
            }
            LPC_Http::throwHttpStatusException($httpStatus);
        }
        return null;
    }

    /**
     * This function checks the availability of cURL
     * @access public
     * @return bool
     */
    public static function isCurlInstalled() {
        if (in_array('curl', get_loaded_extensions())) {
            return true;
        }
        return false;
    }

    /*
     * function decode message from error code
     * @param $code
     * @return string $message
     */
    public function decodeMessage($code) {
        $msg = '';
        switch ($code) {
            case 0 : $msg = "An error has occurred. An email has been sent to Le pot commun.fr team";
                break;
            case 1 : $msg = "Missing parameter: transactionId";
                break;
            case 2 : $msg = "Missing parameter: merchantId";
                break;
            case 3 : $msg = "Missing parameter: amount";
                break;
            case 4 : $msg = "Missing parameter: currency";
                break;
            case 5 : $msg = "Missing parameter: okUrl";
                break;
            case 6 : $msg = "Missing parameter: koUrl";
                break;
            case 7 : $msg = "Missing parameter: notificationUrl";
                break;
            case 8 : $msg = "Invalid parameter: amount";
                break;
            case 9 : $msg = "Invalid parameter: currency";
                break;
            case 10 : $msg = "Invalid parameter: okUrl";
                break;
            case 11 : $msg = "Invalid parameter: koUrl";
                break;
            case 12 : $msg = "Invalid parameter: notificationUrl";
                break;
            case 13 : $msg = "TransactionId already exists";
                break;
            case 14 : $msg = "merchant error 14";
                break;
            case 15 : $msg = "merchant error 15";
                break;
            case 16 : $msg = "originalLPCTransactionId does not exist";
                break;
            case 17 : $msg = "originalLPCTransactionId is not a created payment";
                break;
            case 18 : $msg = "originalLPCTransactionId has not been validated";
                break;
            case 19 : $msg = "The amount left on the transaction is less than the refunded amount";
                break;
            case 20 : $msg = "Authentication Error";
                break;
            case 21 : $msg = "lpcTransactionId or transactionId must be provided";
                break;
            case 22 : $msg = "originalLPCTransactionId or originalTransactionId must be provided";
                break;
            case 23 : $msg = "Transaction not found";
                break;
        }
        return $msg;
    }
    
    /**
     * @param $array
     * @return bool|stdClass
     */
    public static function parseArrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        if (self::isAssocArray($array)) {
            $object = new stdClass();
        } else {
            $object = array();
        }

        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = trim($name);
                if (isset($name)) {
                    if (is_numeric($name)) {
                        $object[] = self::parseArrayToObject($value);
                    } else {
                        $object->$name = self::parseArrayToObject($value);
                    }
                }
            }
            return $object;
        }

        return false;
    }

}
