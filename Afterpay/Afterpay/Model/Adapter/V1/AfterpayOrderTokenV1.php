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
    protected $afterpayApiCall;
    protected $afterpayConfig;
    protected $objectManagerInterface;
    protected $storeManagerInterface;
    protected $jsonHelper;
    protected $helper;

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
        Helper $afterpayHelper
    ) {
        $this->afterpayApiCall = $afterpayApiCall;
        $this->afterpayConfig = $afterpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->jsonHelper = $jsonHelper;
        $this->helper = $afterpayHelper;
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
	
        $billing_postcode = $requestData['billing']['postcode'];
        $shipping_postcode = $requestData['shipping']['postcode'];

        //handle possibility of Postal Code not being mandatory 
	    //e.g. Gift Cards
        if( empty($shipping_postcode) || strlen( trim($shipping_postcode) ) < 3  ) {
            $requestData['shipping']['postcode'] = $billing_postcode;
        }
        if( empty($billing_postcode) || strlen( trim($billing_postcode) ) < 3  ) {
            $requestData['billing']['postcode'] = $shipping_postcode;
        }
        if( empty($shipping_postcode) || strlen( trim($shipping_postcode) ) < 3  ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Zip/Postal code cannot be empty'));
        };

        try {
            $response = $this->afterpayApiCall->send(
                $this->afterpayConfig->getApiUrl('v1/orders/'),
                $requestData,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {

            $this->helper->debug($e->getMessage());
            
            $response = $this->objectManagerInterface->create('Afterpay\Afterpay\Model\Payovertime');
            $response->setBody($this->jsonHelper->jsonEncode(array(
                'error' => 1,
                'message' => $e->getMessage()
            )));
        }

        return $response;
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
            'redirectConfirmUrl'    => $this->storeManagerInterface->getStore()->getBaseUrl() . 'afterpay/payment/response', 
            'redirectCancelUrl'     => $this->storeManagerInterface->getStore()->getBaseUrl() . 'afterpay/payment/response'
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
            'state'         => (string)$shippingAddress->getRegionCode(),
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
            'state'         => (string)$billingAddress->getRegionCode(),
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