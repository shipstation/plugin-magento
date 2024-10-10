<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

enum TaxIdentifierType: string
{
    /** Tax Identification Number */
    case TIN = 'tin';
    /** Employer Identification Number */
    case EIN = 'ein';
    /** Social Security Number */
    case SSN = 'ssn';
    /** VAT Identification Number */
    case VAT = 'vat';
    /** Australian tax id */
    case ABN = 'abn';
    /** EU or UK IOSS number */
    case IOSS = 'ioss';
    /** New Zealand tax id */
    case IRD = 'ird';
    /** Germany VAT id */
    case OSS = 'oss';
    /** Norway tax id */
    case VOEC = 'voec';
    /** Other tax id */
    case OTHER = 'other';
}
