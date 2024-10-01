<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class SourceDetails
{
    /**
     * @var string|null
     * @description The name of the plan the user is on
     * @example "Plus Store Monthly"
     */
    public ?string $subscription_plan_name;

    /**
     * @var string|null
     * @description The level of the plan the user is on
     * @example "Gold"
     */
    public ?string $subscription_plan_level;

    /**
     * @var string|null
     * @description The name for the order source
     * @example "Justin's Flower Imporiuim"
     */
    public ?string $source_name;

    /**
     * @var string|null
     * @description The email associated with the order source
     * @example "justin@flowerimporium.com"
     */
    public ?string $email;

    /**
     * @var string|null
     * @description The phone number associated with the order source
     * @example "555-555-5555"
     */
    public ?string $phone;

    /**
     * @var string|null
     * @description The locale of the user's account (ISO 639-1 standard language codes)
     * @example "en-us"
     */
    public ?string $locale;

    /**
     * @var string|null
     * @description The website of the order source
     * @example "https://www.selling.com"
     */
    public ?string $website;

    /**
     * @var string|null
     * @description Indicates whether the order source supports multiple ship from locations
     * @example "true" or "false"
     */
    public ?string $multilocation_enabled;

    /**
     * @var string|null
     * @description The default unit of measurement for weight used by the order source
     * @example "Gram", "Ounce", "Kilogram", "Pound"
     */
    public ?string $weight_unit;

    /**
     * @var string|null
     * @description The default currency code used by the order source (ISO 4217)
     * @example "USD", "CAD", "EUR", "GBP"
     */
    public ?string $currency_code;

    /**
     * @var array
     * @description Additional properties
     */
    private array $data = [];

    public function __construct($jsonPayload = null)
    {
        if ($jsonPayload) {
            foreach ($jsonPayload as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                } else {
                    $this->data[$key] = $value;
                }
            }
        } else {
            $this->subscription_plan_name = null;
            $this->subscription_plan_level = null;
            $this->source_name = null;
            $this->email = null;
            $this->phone = null;
            $this->locale = null;
            $this->website = null;
            $this->multilocation_enabled = null;
            $this->weight_unit = null;
            $this->currency_code = null;
        }
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }
}
