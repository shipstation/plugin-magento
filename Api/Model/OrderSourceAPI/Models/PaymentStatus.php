<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

enum PaymentStatus: string
{
    case AwaitingPayment = 'AwaitingPayment';
    case PaymentCancelled = 'PaymentCancelled';
    case PaymentFailed = 'PaymentFailed';
    case PaymentInProcess = 'PaymentInProcess';
    case Paid = 'Paid';
    case Other = 'Other';
}
