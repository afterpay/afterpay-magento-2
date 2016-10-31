<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Model\Adapter;

use \Afterpay\Afterpay\Model\Adapter\Afterpay\Call;
use \Afterpay\Afterpay\Model\Config\Payovertime as PayovertimeConfig;
use \Magento\Framework\ObjectManagerInterface as ObjectManagerInterface;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Class AfterpayClientToken
 * @package Afterpay\Afterpay\Model\Adapter
 */
class AfterpayOrderToken
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
    protected $jsonHelper;

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
        JsonHelper $jsonHelper
    ) {
        $this->afterpayApiCall = $afterpayApiCall;
        $this->afterpayConfig = $afterpayConfig;
        $this->objectManagerInterface = $objectManagerInterface;
        $this->jsonHelper = $jsonHelper;
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
	
        $billing_postcode = $requestData['orderDetail']['billingAddress']['postcode'];
        $shipping_postcode = $requestData['orderDetail']['shippingAddress']['postcode'];

        //handle possibility of Postal Code not being mandatory 
	//e.g. Gift Cards
        if( empty($shipping_postcode) || strlen( trim($shipping_postcode) ) < 3  ) {
            $requestData['orderDetail']['shippingAddress']['postcode'] = $billing_postcode;
        }
        if( empty($billing_postcode) || strlen( trim($billing_postcode) ) < 3  ) {
            $requestData['orderDetail']['billingAddress']['postcode'] = $shipping_postcode;
        }
        if( empty($shipping_postcode) || strlen( trim($shipping_postcode) ) < 3  ) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Zip/Postal code cannot be empty'));
        }

        try {
            $response = $this->afterpayApiCall->send(
                $this->afterpayConfig->getApiUrl('merchants/orders/'),
                $requestData,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
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
        if( empty($shippingAddress) ) {
            $shippingAddress = $object->getBillingAddress();
        }

        $params['consumer'] = array(
            'email'      => (string)$object->getCustomerEmail(),
            'givenNames' => $object->getCustomerFirstname() ? (string)$object->getCustomerFirstname() : $billingAddress->getFirstname(),
            'surname'    => $object->getCustomerLastname() ? (string)$object->getCustomerLastname() : $billingAddress->getLastname(),
            'mobile'     => (string)$billingAddress->getTelephone()
        );
        $params['orderDetail'] = array(
            'merchantOrderDate' => strtotime($object->getCreatedAt()) * 1000,
            'merchantOrderId'   => array_key_exists('merchantOrderId', $override) ? $override['merchantOrderId'] : $object->getIncrementId(),
            'shippingPriority'  => 'STANDARD',
            'items'             => array()
        );
        foreach ($object->getAllVisibleItems() as $item) {
            if (!$item->getParentItem()) {
                $params['orderDetail']['items'][] = array(
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
            $params['orderDetail']['shippingCost'] = array(
                'amount'   => round((float)$object->getShippingInclTax(), $precision), // with tax
                'currency' => (string)$data['store_currency_code']
            );
        }
        if (isset($data['discount_amount'])) {
            $params['orderDetail']['discountType'] = 'Discount';
            $params['orderDetail']['discount']     = array(
                'amount'   => round((float)$data['discount_amount'], $precision),
                'currency' => (string)$data['store_currency_code']
            );
        }
        $taxAmount = array_key_exists('tax_amount',$data) ? $data['tax_amount'] : $shippingAddress->getTaxAmount();
        $params['orderDetail']['includedTaxes'] = array(
            'amount'   => isset($taxAmount) ? round((float)$taxAmount, $precision) : 0,
            'currency' => (string)$data['store_currency_code']
        );
        $params['orderDetail']['subTotal'] = array(
            'amount'   => round((float)$data['subtotal'], $precision),
            'currency' => (string)$data['store_currency_code'],
        );
        $street = $shippingAddress->getStreet();
        $params['orderDetail']['shippingAddress'] = array(
            'name'     => (string)$shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
            'address1' => (string)$shippingAddress->getStreetLine(1),
            'address2' => (string)$shippingAddress->getStreetLine(2),
            'suburb'   => (string)$shippingAddress->getCity(),
            'postcode' => (string)$shippingAddress->getPostcode(),
        );
        $street = $billingAddress->getStreet();
        $params['orderDetail']['billingAddress'] = array(
            'name'     => (string)$billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
            'address1' => (string)$billingAddress->getStreetLine(1),
            'address2' => (string)$billingAddress->getStreetLine(2),
            'suburb'   => (string)$billingAddress->getCity(),
            'postcode' => (string)$billingAddress->getPostcode()
        );
        $params['orderDetail']['orderAmount'] = array(
            'amount'   => round((float)$object->getGrandTotal(), $precision),
            'currency' => (string)$data['store_currency_code'],
        );

        return $params;
    }
}