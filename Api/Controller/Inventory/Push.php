<?php
namespace Auctane\Api\Controller\Inventory;

use Auctane\Api\Controller\BaseController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Exception\NotFoundException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class Push extends BaseController implements HttpPostActionInterface
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
     * @param JsonFactory $jsonFactory
     * @param Http $request
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemsSaveInterface $sourceItemsSave
     */
    public function __construct(
        JsonFactory $jsonFactory,
        Http $request,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        parent::__construct($jsonFactory, $request);
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * This is called when a user hits the /inventory endpoint
     *
     * @throws ValidationException
     * @throws CouldNotSaveException
     * @throws BadRequestException
     * @throws InputException
     * @throws NotFoundException
     */
    public function executeAction(): array
    {
        $request = $this->getRequest();
        $success = [];
        $errors = [];
        foreach ($request as $inventoryItem) {
            try {
                $this->saveUpdate(
                    $inventoryItem['sku'],
                    $inventoryItem['source_code'],
                    $inventoryItem['quantity'],
                    $inventoryItem['in_stock']
                );
                $success[] = $inventoryItem;
            } catch (NotFoundException $nfException) {
                $errors[] = [
                    'sku' => $inventoryItem['sku'],
                    'source_code' => $inventoryItem['source_code'],
                    'quantity' => $inventoryItem['quantity'],
                    'in_stock' => $inventoryItem['in_stock'],
                    'message' => $nfException->getMessage(),
                    'type' => 'not_found'
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'sku' => $inventoryItem['sku'],
                    'source_code' => $inventoryItem['source_code'],
                    'quantity' => $inventoryItem['quantity'],
                    'in_stock' => $inventoryItem['in_stock'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'updates' => $success,
            'errors' => $errors,
        ];
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
     *  Attempts to get the current request
     *
     * @throws BadRequestException
     */
    private function getRequest(): array
    {
        $body = $this->request->getContent();
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new BadRequestException('Invalid JSON body, expected an array and received ' . $data);
        }
        return $data;
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
