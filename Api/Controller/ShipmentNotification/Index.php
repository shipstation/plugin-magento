<?php
namespace Auctane\Api\Controller\ShipmentNotification;

use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Requests\ShipmentNotificationRequest;
use Auctane\Api\Model\OrderSourceAPI\ShipmentNotification;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\Order\Track;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Index extends BaseAuthorizedController implements HttpPostActionInterface
{
    /** @var OrderRepositoryInterface  */
    protected OrderRepositoryInterface $orderRepository;
    /** @var ShipmentRepositoryInterface  */
    protected ShipmentRepositoryInterface $shipmentRepository;
    /** @var ShipOrderInterface  */
    protected ShipOrderInterface $shipOrder;
    /** @var LoggerInterface  */
    protected LoggerInterface $logger;
    /** @var Track  */
    protected Track $shipmentTrack;
    /** @var ShipmentFactory  */
    protected ShipmentFactory $shipmentFactory;
    /** @var bool  */
    protected bool $supportsCustomInvoicing;

    public function __construct(
        ShipOrderInterface $shipOrder,
        OrderRepositoryInterface $orderRepository,
        ShipmentFactory $shipmentFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        Track $shipmentTrack,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipOrder = $shipOrder;
        $this->logger = $logger;
        $this->shipmentTrack = $shipmentTrack;
        $this->shipmentFactory = $shipmentFactory;
        $customInvoicing = 'shipstation_general/shipstation/custom_invoicing';
        $this->supportsCustomInvoicing = $this->scopeConfig->getValue(
            $customInvoicing,
            ScopeInterface::SCOPE_STORE
        ) == true;
    }

    /**
     * This method implements the ShipmentNotification Logic found here
     * https://connect.shipengine.com/orders/reference/operation/OrderSource_ShipmentNotification/
     *
     * @return array
     * @throws BadRequestException
     */
    public function executeAction(): array
    {

        $request = new ShipmentNotificationRequest(json_decode($this->request->getContent(), true));
        $results = [];
        foreach ($request->notifications as $notification) {
            try {
                $order = $this->orderRepository->get($notification->order_id);
            } catch (\Exception $exception) {
                $results[] = [
                    'notification_id' => $notification->notification_id,
                    'succeeded' => false,
                    'failure_reason' => $exception->getMessage(),
                    'status' => 'failure'
                ];
            }
        }
        return [
            'notification_results' => $results,
        ];
    }
}
