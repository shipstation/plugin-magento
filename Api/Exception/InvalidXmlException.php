<?php

namespace Auctane\Api\Exception;

use libXMLError;
use Magento\Framework\Exception\LocalizedException;


/**
 * Class InvalidXmlException
 * @package Auctane\Api\Exception
 */
class InvalidXmlException extends LocalizedException
{
    /** @var LibXMLError[] */
    private $errors;

    /**
     * InvalidXmlException constructor.
     * @param LibXMLError[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct(__("Input Xml contains errors and couldn't be parsed."));
    }

    /**
     * @return libXMLError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
