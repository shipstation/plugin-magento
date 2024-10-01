<?php

namespace Auctane\Api\Model\OrderSourceAPI\Requests;

class RegisterDeliveryOptionsRequest extends RequestBase
{
    /** @var string */
    public $callback_url;

    /** @var string */
    public $marketplace_key;

    /** @var string */
    public $option_key;

    /**
     * RegisterDeliveryOptionsRequest constructor.
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        parent::__construct($data);
        if ($data) {
            if (isset($data['callback_url'])) {
                $this->callback_url = $data['callback_url'];
            }
            if (isset($data['marketplace_key'])) {
                $this->marketplace_key = $data['marketplace_key'];
            }
            if (isset($data['option_key'])) {
                $this->option_key = $data['option_key'];
            }
        }
    }
}
