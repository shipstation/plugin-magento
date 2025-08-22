<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

class Address
{
    /** @var string|null */
    public ?string $name;

    /** @var string|null */
    public ?string $company;

    /** @var string|null */
    public ?string $phone;

    /** @var string|null */
    public ?string $address_line_1;

    /** @var string|null */
    public ?string $address_line_2;

    /** @var string|null */
    public ?string $address_line_3;

    /** @var string|null */
    public ?string $city;

    /** @var string|null */
    public ?string $state_province;

    /** @var string|null */
    public ?string $postal_code;

    /** @var string|null */
    public ?string $country_code;

    /** @var ResidentialIndicator|null */
    public ?ResidentialIndicator $residential_indicator;

    /** @var bool|null */
    public ?bool $is_verified;

    /** @var PickupLocation|null */
    public ?PickupLocation $pickup_location;

    /**
     * Address constructor.
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data !== null) {
            $this->name = $data['name'] ?? null;
            $this->company = $data['company'] ?? null;
            $this->phone = $data['phone'] ?? null;
            $this->address_line_1 = $data['address_line_1'] ?? null;
            $this->address_line_2 = $data['address_line_2'] ?? null;
            $this->address_line_3 = $data['address_line_3'] ?? null;
            $this->city = $data['city'] ?? null;
            $this->state_province = $data['state_province'] ?? null;
            $this->postal_code = $data['postal_code'] ?? null;
            $this->country_code = $data['country_code'] ?? null;
            $this->residential_indicator = $data['residential_indicator'] ?? null;
            $this->is_verified = $data['is_verified'] ?? null;
            $this->pickup_location = isset($data['pickup_location'])
                ? new PickupLocation($data['pickup_location'])
                : null;
        }
    }
}
