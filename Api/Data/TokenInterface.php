<?php
namespace Afterpay\Afterpay\Api\Data;

interface TokenInterface
{
    public const LOG_ID_FIELD = 'log_id';
    public const ORDER_ID_FIELD = 'order_id';
    public const TOKEN_FIELD = 'token';
    public const EXPIRATION_DATE_FIELD = 'expiration_date';
}
