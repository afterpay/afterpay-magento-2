<?php declare(strict_types=1);

namespace Afterpay\Afterpay\Model;

interface PaymentStateInterface
{
    const AUTH_APPROVED = 'AUTH_APPROVED';
    const PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';
    const CAPTURED = 'CAPTURED';
    const VOIDED = 'VOIDED';
}
