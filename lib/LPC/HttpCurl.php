<?php

/**
 * * @copyright  Copyright (c) 2015 LePotCommun 
 * * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) 
 * * http://www.lepotcommun.fr 
 */

/**
 * Class LPC_HttpCurl
 */
class LPC_HttpCurl {

    /**
     * @var
     */
    static $headers;
    static $userNameAndPassword = "lpc:123commun";

    /**
     * @param $requestType
     * @param $pathUrl
     * @param $data
     * @param $isPassword
     * @param $signatureKey
     * @return array
     * @throws LPC_Exception
     */
    public static function doRequest($requestType, $pathUrl, $data, $lpcKey, $isPassword) {
        if ($pathUrl == NULL) {
            throw new LPC_Exception('The end point is empty');
        }
        if ($lpcKey == NULL) {
            throw new LPC_Exception('Lpc Key is empty');
        }
        $header = array();
        $header[] = 'Content-Type:application/x-www-form-urlencoded';
        $header[] = 'Accept:application/json';
        $header[] = 'lpc-key:' . $lpcKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pathUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'LPC_HttpCurl::readHeader');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        if ($isPassword) {
            curl_setopt($ch, CURLOPT_USERPWD, self::$userNameAndPassword);
        }
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            throw new LPC_Exception(curl_error($ch));
        }
        curl_close($ch);
        return array('code' => $httpStatus, 'response' => trim($response));
    }

    /**
     * @param $ch
     * @param $header
     * @return int
     */
    public static function readHeader($ch, $header) {
        if (preg_match('/([^:]+): (.+)/m', $header, $match)) {
            self::$headers[$match[1]] = trim($match[2]);
        }
        return strlen($header);
    }

    /**
     * @param  $headers
     */
    public static function setHeaders($headers) {
        self::$headers = $headers;
    }

    /**
     * @return mixed
     */
    public static function getHeader() {
        return self::$headers;
    }

}
