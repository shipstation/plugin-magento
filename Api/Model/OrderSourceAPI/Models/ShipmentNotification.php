<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ShipmentNotification
{
    /** @var string|mixed  */
    public string $notification_id;
    /** @var string|mixed  */
    public string $order_id;
    /** @var string|mixed|null  */
    public ?string $order_number;
    /** @var string|mixed|null  */
    public ?string $tracking_number;
    /** @var string|mixed|null  */
    public ?string $tracking_url;
    /** @var string|mixed|null  */
    public ?string $carrier_code;
    /** @var string|mixed|null  */
    public ?string $carrier_service_code;
    /** @var string|mixed|null  */
    public ?string $ext_location_id;
    /** @var ShipmentNotificationItem[]  */
    public array $items;
    /** @var Address|null  */
    public ?Address $ship_to;
    /** @var Address|null  */
    public ?Address $ship_from;
    /** @var Address|null  */
    public ?Address $return_address;
    /** @var string|mixed|null  */
    public ?string $ship_date;
    /** @var string|mixed|null  */
    public ?string $currency;
    /** @var float|mixed|null  */
    public ?float $fulfillment_cost;
    /** @var array|Note[]|null  */
    public ?array $notes;
    /** @var float|mixed|null  */
    public ?float $insurance_cost;
    /** @var bool|mixed|null  */
    public ?bool $notify_buyer;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->notification_id = $data['notification_id'];
            $this->order_id = $data['order_id'];
            $this->order_number = $data['order_number'] ?? null;
            $this->tracking_number = $data['tracking_number'] ?? null;
            $this->tracking_url = $data['tracking_url'] ?? null;
            $this->carrier_code = $data['carrier_code'] ?? null;
            $this->carrier_service_code = $data['carrier_service_code'] ?? null;
            $this->ext_location_id = $data['ext_location_id'] ?? null;
            $this->items = array_map(
                function ($item) {
                    return new ShipmentNotificationItem($item);
                },
                $data['items']
            );
            $this->ship_to = isset($data['ship_to']) ? new Address($data['ship_to']) : null;
            $this->ship_from = isset($data['ship_from']) ? new Address($data['ship_from']) : null;
            $this->return_address = isset($data['return_address']) ? new Address($data['return_address']) : null;
            $this->ship_date = $data['ship_date'] ?? null;
            $this->currency = $data['currency'] ?? null;
            $this->fulfillment_cost = $data['fulfillment_cost'] ?? null;
            $this->notes = array_map(
                function ($note) {
                    return new Note($note);
                },
                $data['notes'] ?? []
            );
            $this->insurance_cost = $data['insurance_cost'] ?? null;
            $this->notify_buyer = $data['notify_buyer'] ?? null;
        }
    }
}
