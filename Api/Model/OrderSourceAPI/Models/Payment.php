<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Payment
{
    /** @var string|null */
    public ?string $payment_id;

    /** @var PaymentStatus|null */
    public ?PaymentStatus $payment_status;

    /** @var array|null */
    public ?array $taxes;

    /** @var array|null */
    public ?array $shipping_charges;

    /** @var array|null */
    public ?array $adjustments;

    /** @var float|null */
    public ?float $amount_paid;

    /** @var string|null */
    public ?string $coupon_code;

    /** @var array|null */
    public ?array $coupon_codes;

    /** @var string|null */
    public ?string $payment_method;

    /** @var LabelVoucher|null */
    public ?LabelVoucher $label_voucher;

    /** @var array|null */
    public ?array $prepaid_vat;

    /** @var string|null */
    public ?string $purchase_order_number;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->payment_id = $data['payment_id'] ?? null;
            $this->payment_status = $data['payment_status'] ?? null;
            $this->taxes = $data['taxes'] ?? null;
            $this->shipping_charges = $data['shipping_charges'] ?? null;
            $this->adjustments = $data['adjustments'] ?? null;
            $this->amount_paid = $data['amount_paid'] ?? null;
            $this->coupon_code = $data['coupon_code'] ?? null;
            $this->coupon_codes = $data['coupon_codes'] ?? null;
            $this->payment_method = $data['payment_method'] ?? null;
            $this->label_voucher = new LabelVoucher($data['label_voucher'] ?? null);
            $this->prepaid_vat = $data['prepaid_vat'] ?? null;
            $this->purchase_order_number = $data['purchase_order_number'] ?? null;
        }
    }
}
