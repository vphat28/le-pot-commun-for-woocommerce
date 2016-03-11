<?php
/**
 * * @copyright  Copyright (c) 2015 LePotCommun 
 * * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) 
 * * http://www.lepotcommun.fr 
 */

/**
 * Class LPC_Http
 */

class LPC_Http
{
    /**
     * @param $pathUrl
     * @param $data
     * @param $isPassword
     * @return mixed
     */
    public static function post($pathUrl, $data, $isPassword)
    {
        $lpcKey     = LPC_Configuration::getApiKey();        
        $response   = LPC_HttpCurl::doRequest('POST', $pathUrl, $data, $lpcKey, $isPassword);
        return $response;
    }    

    /**
     * @param $pathUrl
     * @param $data
     * @param $isPassword
     * @return mixed
     */
    public static function get($pathUrl, $data,  $isPassword)
    {
        $lpcKey     = LPC_Configuration::getApiKey();   
        $response   = LPC_HttpCurl::doRequest('GET', $pathUrl, $data, $lpcKey, $isPassword);
        return $response;
    }

    /**
     * @param $pathUrl
     * @param $data
     * @param $isPassword
     * @return mixed
     */
    public static function put($pathUrl, $data,  $isPassword)
    {        
        $lpcKey     = LPC_Configuration::getApiKey();   
        $response   = LPC_HttpCurl::doRequest('put', $pathUrl, $data, $lpcKey, $isPassword);
        return $response;
    }

    /**
     * @param $pathUrl
     * @param $data
     * @param $isPassword
     * @return mixed
     */
    public static function delete($pathUrl, $data, $isPassword)
    {        
        $lpcKey     = LPC_Configuration::getApiKey();   
        $response   = LPC_HttpCurl::doRequest('DELETE', $pathUrl, $data, $lpcKey,  $isPassword);
        return $response;
    }

    /**
     * @param      $statusCode
     * @param null $message
     * @throws LPC_Exception
     */
    public static function throwHttpStatusException($statusCode, $message = null)
    {
        switch ($statusCode) {
            default:
                throw new LPC_Exception('Unexpected HTTP code response', $statusCode);
                break;
            
            case 404:
                throw new LPC_Exception('Data indicated in the request is not available in the LPC system.');
                break;

            case 408:
                throw new LPC_Exception('Request timeout', $statusCode);
                break;

            case 500:
                throw new LPC_Exception('LPC system is unavailable or your order is not processed.');
                break;

            case 503:
                throw new LPC_Exception('Service unavailable', $statusCode);
                break;
        }
    }
}
