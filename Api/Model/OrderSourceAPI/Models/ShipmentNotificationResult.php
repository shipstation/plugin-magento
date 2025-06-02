<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class ShipmentNotificationResult
{
    /** @var string The notification id from the request */
    public string $notification_id;

    /** @var ShipmentNotificationStatus|null Indicates the status for the third party receiving this notification */
    public ?ShipmentNotificationStatus $status;

    /** @var string|null An optional confirmation code associated with this notification */
    public ?string $confirmation_code;

    /** @var string|null If succeeded was false use this field to indicate why */
    public ?string $failure_reason;

    /**
     * A unique identifier that is used to query the third party about the status of this notification in the
     * notification_status endpoint Example "123245AB23", "{'id1': 123, 'id2': 'unique'}"
     *
     * @var string|null
     */
    public ?string $submission_id;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->notification_id = $data['notification_id'] ?? null;
            $this->status = $data['status'] ?? null;
            $this->confirmation_code = $data['confirmation_code'] ?? null;
            $this->failure_reason = $data['failure_reason'] ?? null;
            $this->submission_id = $data['submission_id'] ?? null;
        }
    }
}
