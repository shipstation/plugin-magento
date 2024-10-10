<?php

namespace Auctane\Api\Plugin\Controller\Auctane;

use Auctane\Api\Controller\Auctane\Index;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

class IndexPlugin
{
    /** @var DirectoryList */
    private $directoryList;
    /** @var ScopeConfigInterface */
    private $scopeConfig;


    /**
     * IndexPlugin constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
    }

    /**
     * Logs controller response to file.
     *
     * @param Index $index
     * @throws FileSystemException
     */
    public function afterExecute(Index $index)
    {
        if ($this->scopeConfig->getValue('shipstation_general/shipstation/debug_mode')) {
            $time = (new \DateTime())->format('YmdHis');

            libxml_use_internal_errors(true);

            if ($xml = simplexml_load_file('php://input')) {
                $xml->asXML("{$this->directoryList->getPath(DirectoryList::LOG)}/shipstation-request-body-{$time}.log");
            }

            $filename = "{$this->directoryList->getPath(DirectoryList::LOG)}/shipstation-response-{$time}.log";
            $statusCode = $index->getResponse()->getStatusCode();
            $body = $index->getResponse()->getBody();
            $data = "Http status code : {$statusCode}\nBody :\n--- BEGIN BODY ---\n{$body}\n--- END BODY ---\n";

            file_put_contents($filename, $data);
        }
    }
}
