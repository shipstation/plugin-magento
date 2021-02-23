<?php

namespace Auctane\Api\Exception;

use libXMLError;


/**
 * Class InvalidXmlException
 * @package Auctane\Api\Exception
 */
class InvalidXmlException extends \Exception
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

        parent::__construct("Input Xml contains errors and couldn't be parsed.");
    }

    /**
     * @return libXMLError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
