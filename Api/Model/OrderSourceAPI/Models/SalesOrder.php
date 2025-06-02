<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SalesOrder
{
    /** @var string|mixed The unique identifier of the sales order from the order source */
    public string $order_id;
    /** @var string|mixed|null The customer facing identifier of the sales order */
    public ?string $order_number;
    /** @var SalesOrderStatus|mixed The sales order status */
    public SalesOrderStatus $status;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when this sales order was paid for @example "2021-03-31T18:21:14.858Z" */
    public ?string $paid_date;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when this order shipped @example "2021-03-31T18:21:14.858Z" */
    public ?string $fulfilled_date;
    /** @var OriginalOrderSource|null Represents information from the source marketplace. (This is common with reselling goods) */
    public ?OriginalOrderSource $original_order_source;
    /** @var array|RequestedFulfillment[] The fulfillment requested by the marketplace or the buyer */
    public array $requested_fulfillments;
    /** @var Buyer|null The buyer of this sales order */
    public ?Buyer $buyer;
    /** @var BillTo|mixed|null The person being billed for this sales order */
    public ?BillTo $bill_to;
    /** @var string|mixed|null The three character ISO 4217 code of the currency used for all monetary amounts @example "USD", "EUR", "NZD" */
    public ?string $currency;
    /** @var TaxIdentifier|null Tax id information corresponding to tax (such as prepaid VAT) */
    public ?TaxIdentifier $tax_identifier;
    /** @var Payment|mixed|null Information about the payment */
    public ?Payment $payment;
    /** @var Address The source that the order is shipping from */
    public Address $ship_from;
    /** @var string|mixed|null A unique url associated with the order */
    public ?string $order_url;
    /** @var Note[] Notes about the order */
    public ?array $notes;
    /** @var string|mixed|null Data provided by the order source that should be included in calls back to the order source. This data is only meaningful to the integration and not otherwise used by the platform. */
    public ?string $integration_context;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when this order was created @example "2021-03-31T18:21:14.858Z" */
    public ?string $created_date_time;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when this order was last modified @example "2021-03-31T18:21:14.858Z" */
    public ?string $modified_date_time;
    /** @var string|mixed|null A value, specific to the order source, that indicates who is expected to fulfill the order. This value can represent whether an order will be fulfilled by seller fulfillment, merchant fulfillment or other fulfillment network. @example "SellerFulfilled" */
    public ?string $fulfillment_channel;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->order_id = $data['order_id'];
            $this->order_number = $data['order_number'] ?? null;
            $this->status = $data['status'];
            $this->paid_date = $data['paid_date'] ?? null;
            $this->fulfilled_date = $data['fulfilled_date'] ?? null;
            $this->original_order_source = !empty($data['original_order_source'])
                ? new OriginalOrderSource($data['original_order_source']) : null;
            $this->requested_fulfillments = array_map(
                fn($rf) => new RequestedFulfillment($rf),
                $data['requested_fulfillments'] ?? []
            );
            $this->buyer = new Buyer($data['buyer']);
            $this->bill_to = new BillTo($data['bill_to']);
            $this->currency = $data['currency'] ?? null;
            $this->tax_identifier = new TaxIdentifier($data['tax_identifier']);
            $this->payment = new Payment($data['payment']);
            $this->ship_from = new Address($data['ship_from']);
            $this->order_url = $data['order_url'] ?? null;
            $this->notes = $data['notes'] ?? [];
            $this->integration_context = $data['integration_context'] ?? null;
            $this->created_date_time = $data['created_date_time'] ?? null;
            $this->modified_date_time = $data['modified_date_time'] ?? null;
            $this->fulfillment_channel = $data['fulfillment_channel'] ?? null;
        }
    }
}
