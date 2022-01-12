<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model\Payment;

interface AdditionalInformationInterface
{
    const AFTERPAY_ORDER_ID = 'afterpay_order_id';
    const AFTERPAY_OPEN_TO_CAPTURE_AMOUNT = 'afterpay_open_to_capture_amount';
    const AFTERPAY_PAYMENT_STATE = 'afterpay_payment_state';
    const AFTERPAY_AUTH_EXPIRY_DATE = 'afterpay_auth_expiry_date';
    const AFTERPAY_ROLLOVER_DISCOUNT = 'afterpay_rollover_discount';
    const AFTERPAY_CAPTURED_DISCOUNT = 'afterpay_captured_discount';
}
