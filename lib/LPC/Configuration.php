<?php

/**
 * * @copyright  Copyright (c) 2015 LePotCommun 
 * * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) 
 * * http://www.lepotcommun.fr 
 */

/**
 *  * Class LPC_Configuration 
 */
class LPC_Configuration {

    // url for testing environment    
    const TEST_URL = 'https://preprod.lepotcommuntest.fr/';
    // url for production and live environment    
    const PRODUCTION_URL = 'https://www.lepotcommun.fr/';

    private static $_availableEnvironment = array('testing', 'production', 'live');
    private static $env = 'testing';
    private static $serviceUrl = '';
    private static $merchantId = '';
    private static $apiKey = '';
    private static $okUrl = '';
    private static $koUrl = '';
    private static $notification = '';
    private static $apiVersion = 1;

    /*
     *  function to set the api version   
     *  @params $version     
     */

    public static function setApiVersion($version) {
        if (empty($version)) {
            throw new LPC_Exception('Invalid API version');
        }
        self::$apiVersion = intval($version);
    }

    /*     * 
     * function to get api version     
     * @return int     
     */

    public static function getApiVersion() {
        return self::$apiVersion;
    }

    /*
     * function to set environment   
     * @params string $environment
     * @param array $returnUrls  
     */

    public static function setEnvironment($env = 'testing', $returnUrls) {
        if (!LPC_Util::isCurlInstalled()) {
            throw new LPC_Exception('cURL is not installed');
        }
        $env = strtolower($env);
        $domain = 'lepotcommun.fr/';
        $api = 'api/';
        if ($env == 'testing') {
            $domain = 'lepotcommuntest.fr/';
        }
        if (!in_array($env, self::$_availableEnvironment)) {
            throw new LPC_Exception($env . ' - is not valid environment');
        }
        if (empty($returnUrls)) {
            throw new LPC_Exception('return urls not provided');
        }
        self::$okUrl = $returnUrls['okUrl'];
        self::$koUrl = $returnUrls['koUrl'];
        self::$notification = $returnUrls['notificationUrl'];
        self::$env = $env;
        $subdomain = '';
        switch ($env) {
            case 'testing': $subdomain = "preprod";
                break;
            case 'production': $subdomain = "www";
                break;
        }
        self::$serviceUrl = "https://" . $subdomain . '.' . $domain . $api;
    }

    /**
     *  @access public
     *  @param string   
     */
    public static function setMerchantId($value) {
        self::$merchantId = trim($value);
    }
    
    /**
     * @access public   
     * @return string
     */
    public static function getEnvironment(){
        return self::$env;
    } 

    /**
     * @access public   
     * @return string
     */
    public static function getMerchantId() {
        return self::$merchantId;
    }

    /**
     * @access public   
     * @param string  
     */
    public static function setApiKey($value) {
        self::$apiKey = trim($value);
    }

    /**
     * @access public
     * @return string
     */
    public static function getApiKey() {
        return self::$apiKey;
    }

    /**
     * @access public
     * @return array
     */
    public static function getUrls() {
        $urls = array();
        $urls['notificationUrl'] = self::$notification;
        $urls['okUrl'] = self::$okUrl;
        $urls['koUrl'] = self::$koUrl;
        return $urls;
    }

    /*
     * @access public
     * @return string
     */

    public static function getServiceUrl() {
        return self::$serviceUrl;
    }

}
