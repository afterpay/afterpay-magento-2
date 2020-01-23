<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2020 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter\Afterpay;

/**
 * Class AfterpayResponse
 * @package Afterpay\Afterpay\Model\Adapter\Afterpay
 * @see \Zend\Http\Response
 */
class AfterpayResponse
{
	/**
     * The Response Body
     */
	private $body;
	
	/**
     * The Response Status
     */
	private $status;
	
	/**
     * Get Response Status
     *
     * @return string
     */
	public function getStatus()
    {
    	return $this->status;
    }

    /**
     * Set Response Status
     *
     * @param string $status 	HTTP Status
     */
	public function setStatus($status) 
    {
    	$this->status = $status;
    }
	
	/**
     * Get Response Body
     *
     * @return string
     */
	public function getBody() 
    {
    	return $this->body;
    }

    /**
     * Set Response Body
     *
     * @param string $body 	HTTP Body
     */
	public function setBody($body) 
    {
    	$this->body = $body;
    }
}