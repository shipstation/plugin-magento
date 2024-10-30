<?php

namespace Auctane\Api\Model\OrderSourceAPI\Models;

class TaxIdentifier
{
    /** @var string|null The seller's tax id number */
    public ?string $value = null;

    /** @var TaxIdentifierType|null The tax identifier type */
    public ?TaxIdentifierType $type = null;

    /**
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->value = $data['value'] ?? null;
            $this->type = $data['type'] ?? null;
        }
    }
}
