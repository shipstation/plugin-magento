<?php
namespace Auctane\Api\Model;

use Auctane\Api\Api\CheckInterface;
use Auctane\Api\Request\Authenticator;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\AuthorizationException;

class Check implements CheckInterface
{
    /**
     * @var Authenticator
     */
    private $authenticator;

    public function __construct(
        Authenticator $authenticator
     )
     {
        $this->authenticator = $authenticator;
     }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if(!$this->authenticator->authenticate())
        {
            throw new \Magento\Framework\Webapi\Exception(__('Authentication failed.'),
            0, \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
        }

        return true;
    }
}