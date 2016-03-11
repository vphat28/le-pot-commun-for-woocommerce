<?php

/**
 * * @copyright  Copyright (c) 2015 LePotCommun 
 * * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) 
 * * http://www.lepotcommun.fr 
 */

/**
 * Class LPC_Order
 */


class LPC_Order
{
    // service uri for order create request
    const ORDER_SERVICE = 'merchant/createOrder';
    
    // service uri for order cancel/refund request
    const CANCEL_ORDER_SERVICE = 'merchant/cancelOrder';
    
    // service uri for order retrieval request
    const RETRIEVE_ORDER_SERVICE = 'merchant/getOrder';


    const STATUS_NEW                      = 'NEW';
    const STATUS_SUCCESS                  = 'SUCCESS';
    const STATUS_PENDING                  = 'PENDING';
    const STATUS_CANCELED                 = 'CANCELED';
    const STATUS_REJECTED                 = 'REJECTED';
    const STATUS_COMPLETED                = 'COMPLETED';
    const STATUS_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';

    /**
     * Creates new Order
     * - Sends to LPC OrderCreateRequest
     *
     * @access public
     * @param array $order A array containing full Order
     * @return array $result Response array with OrderCreateResponse
     * @throws LPC_Exception
     */

    public static function create($order)
    {
        $pathUrl = LPC_Configuration::getServiceUrl() . self::ORDER_SERVICE;
        $urls = LPC_Configuration::getUrls();
        $rpos = strrpos($urls['notificationUrl'], "/");
//        $urls['notificationUrl'] = substr($urls['notificationUrl'], 0, $rpos) . "?orderId=" . $order['transactionId'];
        $order = array_merge($urls, $order);
        $data    = LPC_Util::buildStringFromArray($order);     
		 
        if (empty($data)) {
            throw new LPC_Exception('Empty message OrderCreateRequest');
        }   
        
        $isPassword = LPC_Configuration::getEnvironment() == 'testing';
        $result = LPC_Util::verifyResponse(LPC_Http::post($pathUrl, $data, $isPassword));
        return $result;
    }



    /**
     * Retrieves information about the order
     *  - Sends to PayU OrderRetrieveRequest
     *
     * @access public
     * @param string $orderId LPC OrderId sent back in OrderCreateResponse
     * @return array $result Response array with OrderRetrieveResponse
     * @throws LPC_Exception
     */
    public static function retrieve($orderId)
    {
        if (empty($orderId)) {
            throw new LPC_Exception('Empty value of orderId');
        }
        $pathUrl = OpenPayU_Configuration::getServiceUrl() . self::RETRIEVE_ORDER_SERVICE . "?transactionId=" . $orderId;
        $isPassword = LPC_Configuration::getEnvironment() == 'testing';
        $result  = self::verifyResponse(OpenPayU_Http::get($pathUrl, $isPassword));
        return $result;
    }

    /**
     * Cancels Order
     * - Sends to LPC OrderCancelRequest
     * 
     * @access public
     * @param string $data
     * @return array $result Response array with OrderCancelResponse
     * @throws LPC_Exception
     */
    public static function cancel($data)
    {
        $pathUrl = LPC_Configuration::getServiceUrl() . self::CANCEL_ORDER_SERVICE;        
        $data    = LPC_Util::buildStringFromArray($data);    
        
        if (empty($data)) {
            throw new LPC_Exception('Empty message OrderCancelRequest');
        }
        $isPassword = LPC_Configuration::getEnvironment() == 'testing';
        $result = LPC_Util::verifyResponse(LPC_Http::post($pathUrl, $data, $isPassword));
        return $result;
    }

}

