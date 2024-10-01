<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ShippingPreferences
{
    /** @var bool|mixed|null Indicates whether the item being delivered is a digital good or not */
    public ?bool $digital_fulfillment;
    /** @var bool|mixed|null Indicates whether this shipment will require additional handling */
    public ?bool $additional_handling;
    /** @var bool|mixed|null Indicates whether duties should be billed to the sender of the package */
    public ?bool $bill_duties_to_sender;
    /** @var bool|mixed|null Indicates whether you must pay for postage at drop off, for carriers that usually require prepaid postage. */
    public ?bool $do_not_prepay_postage;
    /** @var bool|mixed|null Indicates whether this order is a gift */
    public ?bool $gift;
    /** @var bool|mixed|null Indicates whether this order contains alcohol */
    public ?bool $has_alcohol;
    /** @var bool|mixed|null Indicates whether insurance has been requested for shipping this order */
    public ?bool $insurance_requested;
    /** @var bool|mixed|null Indicates whether this order is nonmachinable (must be sorted outside of the standard, automated mail process) */
    public ?bool $non_machinable;
    /** @var bool|mixed|null Indicates whether this order should be delivered on a saturday */
    public ?bool $saturday_delivery;
    /** @var bool|mixed|null Indicates whether to allow display of postage paid on the shipping label */
    public ?bool $show_postage;
    /** @var bool|mixed|null Indicates whether to suppress email notifications to the buyer */
    public ?bool $suppress_email_notify;
    /** @var bool|mixed|null Indicates whether to suppress email notifications to the seller */
    public ?bool $suppress_marketplace_notify;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when the order needs to be delivered by @example "2021-03-31T18:21:14.858Z" */
    public ?string $deliver_by_date;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with how long to hold the order @example "2021-03-31T18:21:14.858Z" */
    public ?string $hold_until_date;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when the order is ready to ship @example "2021-03-31T18:21:14.858Z" */
    public ?string $ready_to_ship_date;
    /** @var string|mixed|null The (ISO 8601) datetime (UTC) associated with when the order needs to be shipped by @example "2021-03-31T18:21:14.858Z" */
    public ?string $ship_by_date;
    /** @var string|mixed|null The identifier assigned by a fulfillment planning system at checkout (Delivery Options). */
    public ?string $preplanned_fulfillment_id;
    /** @var string|mixed|null The requested shipping service for this fulfillment */
    public ?string $shipping_service;
    /** @var string|mixed|null The requested package type for this fulfillment */
    public ?string $package_type;
    /** @var float|null The amount of money being request for insurance on this shipment */
    public ?float $insured_value;
    /** @var bool|mixed|null true if the order was placed under the terms of the order source's premium program (Amazon Prime, Walmart+, etc) */
    public ?bool $is_premium_program;
    /** @var string|mixed|null The name of the premium program, if any. This is for informational purposes. Consumers should base all logic on is_premium_program flag. */
    public ?string $premium_program_name;
    /** @var string|mixed|null The warehouse name associated with the requested warehouse */
    public ?string $requested_warehouse;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->digital_fulfillment = $data['digital_fulfillment'] ?? null;
            $this->additional_handling = $data['additional_handling'] ?? null;
            $this->bill_duties_to_sender = $data['bill_duties_to_sender'] ?? null;
            $this->do_not_prepay_postage = $data['do_not_prepay_postage'] ?? null;
            $this->gift = $data['gift'] ?? null;
            $this->has_alcohol = $data['has_alcohol'] ?? null;
            $this->insurance_requested = $data['insurance_requested'] ?? null;
            $this->non_machinable = $data['non_machinable'] ?? null;
            $this->saturday_delivery = $data['saturday_delivery'] ?? null;
            $this->show_postage = $data['show_postage'] ?? null;
            $this->suppress_email_notify = $data['suppress_email_notify'] ?? null;
            $this->suppress_marketplace_notify = $data['suppress_marketplace_notify'] ?? null;
            $this->deliver_by_date = $data['deliver_by_date'] ?? null;
            $this->hold_until_date = $data['hold_until_date'] ?? null;
            $this->ready_to_ship_date = $data['ready_to_ship_date'] ?? null;
            $this->ship_by_date = $data['ship_by_date'] ?? null;
            $this->preplanned_fulfillment_id = $data['preplanned_fulfillment_id'] ?? null;
            $this->shipping_service = $data['shipping_service'] ?? null;
            $this->package_type = $data['package_type'] ?? null;
            $this->insured_value = isset($data['insured_value']) ? (float)$data['insured_value'] : null;
            $this->is_premium_program = $data['is_premium_program'] ?? null;
            $this->premium_program_name = $data['premium_program_name'] ?? null;
            $this->requested_warehouse = $data['requested_warehouse'] ?? null;
        }
    }
}
