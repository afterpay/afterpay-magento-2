<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Adapter\V1;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Afterpay\Afterpay\Helper\Data as Helper;

use \Magento\Directory\Model\CountryFactory as CountryFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

/**
 * Class AfterpayClientTokenV1
 * @package Afterpay\Afterpay\Model\Adapter\V1
 */
class AfterpayOrderTokenV1
{
    /**
     * Constant data
     */
    const DECIMAL_PRECISION = 2;
    const PAYMENT_TYPE_CODE = 'PBI';

    /**
     * @var Call
     */
    protected $_afterpayApiCall;
    protected $_afterpayConfig;
    protected $_objectManagerInterface;
    protected $_storeManagerInterface;
    protected $_jsonHelper;
    protected $_helper;

    protected $_countryFactory;
    protected $_scopeConfig;

    /**
     * AfterpayOrderToken constructor.
     * @param Call $afterpayApiCall
     * @param PayovertimeConfig $afterpayConfig
     * @param ObjectManagerInterface $objectManagerInterface
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Call $afterpayApiCall,
        PayovertimeConfig $afterpayConfig,
        ObjectManagerInterface $objectManagerInterface,
        StoreManagerInterface $storeManagerInterface,
        JsonHelper $jsonHelper,
        CountryFactory $countryFactory,
        ScopeConfig $scopeConfig,
        Helper $afterpayHelper
    ) {
        $this->_afterpayApiCall = $afterpayApiCall;
        $this->_afterpayConfig = $afterpayConfig;
        $this->_objectManagerInterface = $objectManagerInterface;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_jsonHelper = $jsonHelper;
        $this->_helper = $afterpayHelper;
        $this->_countryFactory = $countryFactory;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param $object
     * @param $code
     * @param array $override
     * @return mixed|\Zend_Http_Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generate($object, $code, $override = [])
    {
        $requestData = $this->_buildOrderTokenRequest($object, $code, $override);

        //handle possibility of Postal Code not being mandatory 
	    //e.g. Gift Cards
        $requestData = $this->_handlePostcode($requestData);
        $requestData = $this->_handleState($requestData);
        $this->_handleValidation($requestData);

        try {
            $response = $this->_afterpayApiCall->send(
                $this->_afterpayConfig->getApiUrl('v1/orders/'),
                $requestData,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {

            $this->_helper->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));       
        }

        return $response;
    }

    private function _handlePostcode($requestData) {

        $billing_postcode = $requestData['billing']['postcode'];
        $shipping_postcode = $requestData['shipping']['postcode'];

        if( (empty($billing_postcode) || strlen( trim($billing_postcode) ) < 3)
            && !empty($shipping_postcode) && strlen( trim($shipping_postcode) >= 3 ) ) {

            $requestData['billing']['postcode'] = $shipping_postcode;
        }

        return $requestData;
    }

    private function _getStateRequired() {
        $destinations = (string)$this->_scopeConfig->getValue(
            'general/region/state_required'
        );

        $state_required = !empty($destinations) ? explode(',', $destinations) : [];

        return $state_required; 
    }

    private function _handleState($requestData) {

        //get the country to obtain state required data
        $billing_country = ( !empty($requestData['billing']['countryCode']) ? $requestData['billing']['countryCode'] : NULL);
        $list_state_required = $this->_getStateRequired();

        //if the country doesn't require state, make Suburb goes to State Field
        if( !in_array( $billing_country, $list_state_required ) ) {
            $requestData['billing']['state'] = $requestData['billing']['suburb'];
            $requestData['shipping']['state'] = $requestData['shipping']['suburb'];
        }


        $billing_state = ( !empty($requestData['billing']['state']) ? $requestData['billing']['state'] : NULL);
        $shipping_state = ( !empty($requestData['shipping']['state']) ? $requestData['shipping']['state'] : NULL);

        //if the Billing or Shipping State is empty, enforce a transfer of values
        if( empty($billing_state) && !empty($shipping_state) ) {
            $requestData['billing']['state'] = $shipping_state;
        }

        return $requestData;
    }

    private function _handleValidation($requestData) {

        $errors = array();

        $billing_state = ( !empty($requestData['billing']['state']) ? $requestData['billing']['state'] : NULL);
        $billing_postcode = ( !empty($requestData['billing']['postcode']) ? $requestData['billing']['postcode'] : NULL);

        //in case Magento 2 default validation somehow failed
        $billing_name = ( !empty($requestData['billing']['name']) ? $requestData['billing']['name'] : NULL);
        $billing_line1 = ( !empty($requestData['billing']['line1']) ? $requestData['billing']['line1'] : NULL);
        $billing_suburb = ( !empty($requestData['billing']['suburb']) ? $requestData['billing']['suburb'] : NULL);
        $billing_country = ( !empty($requestData['billing']['countryCode']) ? $requestData['billing']['countryCode'] : NULL);
        $billing_phone = ( !empty($requestData['billing']['phoneNumber']) ? $requestData['billing']['phoneNumber'] : NULL);

        if( empty($billing_name) ) {
            $errors[] = 'Name is required';
        }
        else if( empty($billing_line1) ) {
            $errors[] = 'Address is required';
        }
        else if( empty($billing_suburb) ) {
            $errors[] = 'Suburb/City is required';
        }
        else if( empty($billing_state) || strlen(trim($billing_state)) < 3 ) {
            $errors[] = 'State is required';
        }
        else if( empty($billing_postcode) || strlen(trim($billing_postcode)) < 3 ) {
            $errors[] = 'Zip/Postal is required';
        }
        else if( empty($billing_country) ) {
            $errors[] = 'Country is required';
        }
        else if( empty($billing_phone) ) {
            $errors[] = 'Phone is required';
        }


        if( count($errors) ) {
            throw new \Magento\Framework\Exception\LocalizedException(__( implode($errors, '') ));
        }
    }

    /**
     * Build XML for order token
     *
     * @param \Magento\Sales\Model\Order $object Order to get token for
     * @param array $override
     * @return array
     */
    protected function _buildOrderTokenRequest($object, $code, $override = [])
    {
        $precision = self::DECIMAL_PRECISION;

        $params = array(
            'paymentType' => $code
        );
        $data = $object->getData();
        $billingAddress  = $object->getBillingAddress();
        $shippingAddress = $object->getShippingAddress();


        //check if shipping address is missing - e.g. Gift Cards
        if( empty($shippingAddress) || empty($shippingAddress->getStreetLine(1)) ) {
            $shippingAddress = $object->getBillingAddress();
        }
        else if( empty($billingAddress) || empty($billingAddress->getStreetLine(1)) ) {
            $billingAddress = $object->getShippingAddress();
        }


        $email = $object->getCustomerEmail();
        // $email = "steven.gunarso@touchcorp.com";

        $params['consumer'] = array(
            'email'      => (string)$email,
            'givenNames' => $object->getCustomerFirstname() ? (string)$object->getCustomerFirstname() : $billingAddress->getFirstname(),
            'surname'    => $object->getCustomerLastname() ? (string)$object->getCustomerLastname() : $billingAddress->getLastname(),
            'mobile'     => (string)$billingAddress->getTelephone()
        );
        
        $params['merchantReference'] = array_key_exists('merchantOrderId', $override) ? $override['merchantOrderId'] : $object->getIncrementId();

        $params['merchant'] = array(
            'redirectConfirmUrl'    => $this->_storeManagerInterface->getStore($object->getStore()->getId())->getBaseUrl() . 'afterpay/payment/response', 
            'redirectCancelUrl'     => $this->_storeManagerInterface->getStore($object->getStore()->getId())->getBaseUrl() . 'afterpay/payment/response'
        );

        foreach ($object->getAllVisibleItems() as $item) {
            if (!$item->getParentItem()) {
                $params['items'][] = array(
                    'name'     => (string)$item->getName(),
                    'sku'      => (string)$item->getSku(),
                    'quantity' => (int)$item->getQty(),
                    'price'    => array(
                        'amount'   => round((float)$item->getPriceInclTax(), $precision),
                        'currency' => (string)$data['store_currency_code']
                    )
                );
            }
        }
        if ($object->getShippingInclTax()) {
            $params['shippingAmount'] = array(
                'amount'   => round((float)$object->getShippingInclTax(), $precision), // with tax
                'currency' => (string)$data['store_currency_code']
            );
        }
        if (isset($data['discount_amount'])) {
            $params['discounts']['displayName'] = 'Discount';
            $params['orderDetail']['amount']     = array(
                'amount'   => round((float)$data['discount_amount'], $precision),
                'currency' => (string)$data['store_currency_code']
            );
        }
        $taxAmount = array_key_exists('tax_amount',$data) ? $data['tax_amount'] : $shippingAddress->getTaxAmount();
        $params['taxAmount'] = array(
            'amount'   => isset($taxAmount) ? round((float)$taxAmount, $precision) : 0,
            'currency' => (string)$data['store_currency_code']
        );
        $street = $shippingAddress->getStreet();
        $params['shipping'] = array(
            'name'          => (string)$shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'line1'         => (string)$shippingAddress->getStreetLine(1),
            'line2'         => (string)$shippingAddress->getStreetLine(2),
            'suburb'        => (string)$shippingAddress->getCity(),
            'postcode'      => (string)$shippingAddress->getPostcode(),
            'state'         => (string)$shippingAddress->getRegion(),
            'countryCode'   => (string)$shippingAddress->getCountryId(),
            // 'countryCode'   => 'AU',
            'phoneNumber'   => (string)$shippingAddress->getTelephone(),
        );
        $street = $billingAddress->getStreet();
        $params['billing'] = array(
            'name'          => (string)$billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'line1'         => (string)$billingAddress->getStreetLine(1),
            'line2'         => (string)$billingAddress->getStreetLine(2),
            'suburb'        => (string)$billingAddress->getCity(),
            'postcode'      => (string)$billingAddress->getPostcode(),
            'state'         => (string)$billingAddress->getRegion(),
            'countryCode'   => (string)$billingAddress->getCountryId(),
            // 'countryCode'   => 'AU',
            'phoneNumber'   => (string)$billingAddress->getTelephone(),
        );
        $params['totalAmount'] = array(
            'amount'   => round((float)$object->getGrandTotal(), $precision),
            'currency' => (string)$data['store_currency_code'],
        );

        return $params;
    }
}