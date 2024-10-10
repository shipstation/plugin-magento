<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * The unit of measurement of dimensions
 */
enum DimensionsUnit: string
{
    case CENTIMETER = "Centimeter";
    case INCH = "Inch";
}
