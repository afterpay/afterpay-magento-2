<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay <steven.gunarso@touchcorp.com>
 * @copyright 2016 Afterpay https://www.afterpay.com.au/
 */
namespace Afterpay\Afterpay\Controller\Payment;

/**
 * Class Response
 * @package Afterpay\Afterpay\Controller\Payment
 */
class Response extends \Magento\Framework\App\Action\Action
{
    const DEFAULT_REDIRECT_PAGE = 'checkout/cart';
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    protected $response;
    protected $helper;
    protected $checkoutSession;
    protected $jsonHelper;

    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     * @param \Afterpay\Afterpay\Model\Response $response
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Afterpay\Afterpay\Model\Response $response,
        \Afterpay\Afterpay\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->response = $response;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;

        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Actual action when accessing url
     */
    public function execute()
    {
        // debug mode
        $this->helper->debug('Start \Afterpay\Afterpay\Controller\Payment\Response::execute() with request ' . $this->jsonHelper->jsonEncode($this->getRequest()->getParams()));

        $query = $this->getRequest()->getParams();
        $order = $this->checkoutSession->getLastRealOrder();
        $redirect = self::DEFAULT_REDIRECT_PAGE;

        // Check if not fraud detected not doing anything (let cron update the order if payment successful)
        if (!$this->response->validCallback($order, $query)) {
            $this->helper->debug('Request redirect url is not valid.');
        } else {
            try {
                switch ($query['status']) {
                    case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_CANCELLED:
                        // adding comment
                        $order->addStatusHistoryComment(__('Customer cancelled the payment'));

                        // perform cancel order and save the order
                        $this->response->cancelOrder($order, __('Payment is cancelled by Gateway.'));
                        $this->response->returnProductsToCart($order);
                        $this->messageManager->addError(__('You have cancelled your Afterpay payment. Please select an alternative payment method.'));
                        break;
                    case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_FAILURE:
                        // adding comment
                        $order->addStatusHistoryComment(__('Customer payment was declined'));

                        // perform cancel order and return product to cart
                        $this->response->cancelOrder($order, __('Payment has detected failure by Gateway.'));
                        $this->response->returnProductsToCart($order);
                        $this->messageManager->addError(__('Your Afterpay payment was declined. Please select an alternative payment method.'));
                        break;
                    case \Afterpay\Afterpay\Model\Response::RESPONSE_STATUS_SUCCESS:
                        if (!array_key_exists('orderId', $query)) {
                            throw new \Magento\Framework\Exception\LocalizedException(__('There are issues when processing your payment'));
                        } else {
                            if ($this->response->processSuccessPayment($order, $query['orderId'])) {
                                $redirect = 'checkout/onepage/success';
                            } else {
                                throw new \Magento\Framework\Exception\LocalizedException(__('Afterpay Payment cannot be processes. Please contact administrator.'));
                            }
                        }
                        break;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError(
                    $e->getMessage()
                );
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t initialize Afterpay Payment.')
                );
            }
        }

        // debug mode
        $this->helper->debug('Finished \Afterpay\Afterpay\Controller\Payment\Response::execute()');

        // Redirect to cart
        $this->_redirect($redirect);
    }
}