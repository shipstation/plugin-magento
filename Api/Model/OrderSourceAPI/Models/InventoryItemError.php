<?php

namespace Auctane\Api\Model\OrderSourceAPI;

class InventoryItemError
{
    /**
     * @var string
     */
    private $integrationInventoryItemId;

    /**
     * @var string|null
     */
    private $sku;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string|null
     */
    private $category;

    /**
     * Constructor to initialize properties with JSON payload or as empty
     *
     * @param array|null $data JSON data to initialize properties
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->integrationInventoryItemId = $data['integration_inventory_item_id'] ?? '';
            $this->sku = $data['sku'] ?? null;
            $this->message = $data['message'] ?? '';
            $this->category = $data['category'] ?? null;
        }
    }

    // Getter and setter methods for each property

    public function getIntegrationInventoryItemId(): string
    {
        return $this->integrationInventoryItemId;
    }

    public function setIntegrationInventoryItemId(string $integrationInventoryItemId): void
    {
        $this->integrationInventoryItemId = $integrationInventoryItemId;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }
}