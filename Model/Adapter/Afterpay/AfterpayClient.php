<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\Adapter\Afterpay;

/**
 * Class AfterpayCurl
 * @package Afterpay\Afterpay\Model\Adapter\Afterpay
 * @see \Zend\Http\Client
 */
class AfterpayClient
{
    /**
     * Request URI
     *
     * Afterpay API URL
     */
    protected $uri = null;

    /**
     * Associative array of request headers
     *
     * @var array
     */
    protected $headers = array();


    /**
     * Request body content type (for POST requests)
     *
     * @var string
     */
    protected $enctype = null;

    /**
     * The raw post data to send. Could be set by setRawData($data, $enctype).
     *
     * @var string
     */
    protected $raw_post_data = null;

    /**
     * HTTP Authentication settings
     *
     * Will always be set to Basic for Afterpay API
     *
     * @var array|null
     */
    protected $auth;

    /**
     * STart the cURL Request process
     *
     * @param string $method            The HTTP Verb used (GET, POST)
     * @return AfterpayResponse         Formatted Afterpay Response Object
     */
    public function request($method = 'GET')
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $response_object = $objectManager->get('Afterpay\Afterpay\Model\Adapter\Afterpay\AfterpayResponse');

        $response_object = $this->execute($method, $response_object);

        return $response_object;
    }

    /**
     * Execute the cURL Request
     *
     * @param string $method            The HTTP Verb used (GET, POST)
     * @param AfterpayResponse $object  Afterpay Response object
     * @return AfterpayResponse         Formatted Afterpay Response Object
     * @throws Exception
     */
    protected function execute($method, $object)
    {
        //Check if CURL module exists. 
        if (!function_exists("curl_init")) {
            throw new \Exception("Please enable cURL on the website server!");
        }

        try {
            //Curl Implementation

            // create a new cURL resource
            $ch = curl_init();

            //Call function to create CURL Headers
            $curlHeaders = $this->createCurlHeaders();

            //Call CURL URL
            curl_setopt($ch, CURLOPT_URL, $this->uri);
            
            if (!empty($this->headers) && !empty($this->headers['timeout'])) {
                curl_setopt($ch, CURLOPT_TIMEOUT, (int) $this->headers['timeout']); // Set timeout
                unset($this->headers['timeout']);
            }
            else {
                curl_setopt($ch, CURLOPT_TIMEOUT,80); // Set timeout to 80s
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers); //Pass CURL HEADERS
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //Do not output response on screen
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if(!empty($this->raw_post_data) && $this->raw_post_data != '') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->raw_post_data);   
            }
            
            // grab URL and pass it to the browser
            $curl_response = curl_exec($ch);
            $object->setStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));

            if (empty($curl_response)) {
                throw new \Exception("Invalid Response from Afterpay API");
            }
            else {
                $object->setBody($curl_response);
            }

            // close cURL resource, and free up system resources
            curl_close($ch);
            return $object;
        }
        catch (\Exception $e) {
            throw new \Exception("Something went wrong in Afterpay API Connection");

        }

    }
    
    /**
     * Process the headers Content-Type and Auth
     */
    protected function createCurlHeaders()
    {
        $this->headers[] = 'Content-Type:' . $this->enctype;
        $this->headers[] = 'Authorization: Basic '. base64_encode($this->auth['user'] . ':' . $this->auth['password']);
    }

    /**
     * Set Target URI
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get Target URI
     *
     * @return string
     */
    public function getUri($uri) 
    {
        return $this->uri;
    }

    /**
     * Set Config Headers
     *
     * @param string $data  Body Data
     * @param string $type  Encoding Type
     */
    public function setRawData($data, $type) 
    {
        $this->raw_post_data = $data;
        $this->enctype = $type;
    }  

    /**
     * Get Request Body
     *
     * @return array
     */
    public function getRawData() 
    {
        return array(
            'raw_post_data' => $this->raw_post_data,
            'type' => $this->enctype
        );
    }

    /**
     * Set Config Headers
     *
     * @param string $merchant_id
     * @param string $merchant_key
     */
    public function setAuth($merchant_id, $merchant_key) 
    {
        $this->auth = array(
            'user' => (string) $merchant_id,
            'password' => (string) $merchant_key,
            'type' => 'basic'
        );
    }

    /**
     * Get Auth Details
     *
     * @return array
     */
    public function getAuth() 
    {
        return $this->auth;
    }

    /**
     * Set Config Headers
     *
     * @param string $config
     */
    public function setConfig($config) 
    {
        $this->headers = $config;
    }

    /**
     * Get Config Header
     *
     * @return array
     */
    public function getConfig() 
    {
        return $this->headers;
    }
}