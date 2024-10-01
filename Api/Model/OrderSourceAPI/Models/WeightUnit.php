<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

enum WeightUnit: string
{
    case GRAM = 'Gram';
    case OUNCE = 'Ounce';
    case KILOGRAM = 'Kilogram';
    case POUND = 'Pound';
}
