<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.afterpay.com
 */
namespace Afterpay\Afterpay\Model\PaymentInformationManagement;

class Plugin
{
    /**
     * @var \Afterpay\Afterpay\Model\Token
     */
    protected $token;

    /**
     * Plugin constructor.
     * @param \Afterpay\Afterpay\Model\Token $token
     */
    public function __construct(\Afterpay\Afterpay\Model\Token $token)
    {
        $this->token = $token;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param $returnValue
     * @return string
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        $returnValue
    ) {
        return $this->token->saveAndReturnToken($returnValue);
    }
}
