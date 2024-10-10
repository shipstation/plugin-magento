<?php
namespace Auctane\Api\Controller\InventoryPush;

use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Controller\BaseController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Exception\NotFoundException;
use Auctane\Api\Model\OrderSourceAPI\Models\InventoryActionErrorCategoryType;
use Auctane\Api\Model\OrderSourceAPI\Models\InventoryItemError;
use Auctane\Api\Model\OrderSourceAPI\Requests\InventoryPushRequest;
use Auctane\Api\Model\OrderSourceAPI\Responses\InventoryPushResponse;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class Index extends BaseAuthorizedController implements HttpPostActionInterface
{

    /**
     * @var GetSourceItemsBySkuInterface
     */
    protected GetSourceItemsBySkuInterface $getSourceItemsBySku;
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSave;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemsSaveInterface $sourceItemsSave
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        parent::__construct();
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * This is called when a user hits the /inventory endpoint
     */
    public function executeAction(): InventoryPushResponse
    {
        $request = new InventoryPushRequest(json_decode($this->request->getContent(), true));
        $response = new InventoryPushResponse();
        foreach ($request->items as $inventory) {
            try {
                $id = json_decode($inventory->integration_inventory_item_id);
                $this->saveUpdate(
                    $inventory->sku,
                    $id->source,
                    $inventory->available_quantity,
                    $inventory->available_quantity > 0
                );
            } catch (NotFoundException $nfException) {
                $error =  new InventoryItemError();
                $error->message = $nfException->getMessage();
                $error->sku = $inventory->sku;
                $error->integration_inventory_item_id = $inventory->integration_inventory_item_id;
                $error->category = InventoryActionErrorCategoryType::NotFound;
                $response->errors[] = $error;
            } catch (\Exception $e) {
                $error =  new InventoryItemError();
                $error->message = $e->getMessage();
                $error->sku = $inventory->sku;
                $error->integration_inventory_item_id = $inventory->integration_inventory_item_id;
                $response->errors[] = $error;
            }
        }
        return $response;
    }

    /**
     * Attempts to update and save the inventory levels
     *
     * @param string $sku
     * @param string $source_code
     * @param int $quantity
     * @param bool $in_stock
     *
     * @throws ValidationException
     * @throws NotFoundException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function saveUpdate(string $sku, string $source_code, int $quantity, bool $in_stock): void
    {
        $sourceItem = $this->getInventoryItem($sku, $source_code);
        $sourceItem->setSku($sku);
        $sourceItem->setSourceCode($source_code);
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($in_stock ? 1 : 0);
        $sourceItemsToUpdate[] = $sourceItem;
        $this->sourceItemsSave->execute($sourceItemsToUpdate);
    }

    /**
     * This attempts to return an inventory item to be updated
     *
     * @param string $sku
     * @param string $source_code
     * @return SourceItemInterface
     * @throws NotFoundException
     */
    private function getInventoryItem(string $sku, string $source_code): SourceItemInterface
    {
        $inventoryExists = null;
        $existingItems = $this->getSourceItemsBySku->execute($sku);
        foreach ($existingItems as $existingItem) {
            if ($existingItem->getSourceCode() == $source_code) {
                $inventoryExists = $existingItem;
                break;
            }
        }
        if (!$inventoryExists) {
            throw new NotFoundException('Inventory not found for sku ' . $sku . ' and source code ' . $source_code);
        }
        return $inventoryExists;
    }
}
