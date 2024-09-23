<?php

namespace Auctane\Api\Controller\Inventory;

use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Exception\NotFoundException;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Framework\App\ObjectManager;

class Index implements HttpGetActionInterface, HttpPostActionInterface
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
     * @var Http
     */
    protected Http $request;
    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSave;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Http $request
     * @param SourceItemsSaveInterface $sourceItemsSave
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Http $request, // Inject the correct Request class
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
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
    public function execute()
    {
        // Check the HTTP method using getMethod()
        $method = $this->request->getParam('action');

        if ($method == 'fetch') {
            return $this->fetchInventory();
        } elseif ($method == 'push') {
            return $this->pushInventory();
        } else {
            throw new BadRequestException($method . " is not a supported request method");
        }
    }

    /**
     * Handles returning the inventory items
     *
     * @return array
     * @throws BadRequestException
     */
    protected function fetchInventory(): array
    {
        // Get paging parameters from the query string
        $page = (int)$this->request->getParam('page', 1); // Default to page 1
        $pageSize = (int)$this->request->getParam('page_size', 100); // Default to 100 items per page

        // Create search criteria to fetch all products
        $searchCriteria = $this->searchCriteriaBuilder
            ->setCurrentPage($page)
            ->setPageSize($pageSize)
            ->create();

        $productList = $this->productRepository->getList($searchCriteria);
        $totalProducts = $productList->getTotalCount();
        $totalPages = ceil($totalProducts / $pageSize);

        if ($page > $totalPages && $totalProducts > 0) {
            throw new BadRequestException('Invalid page');
        }

        $products = $productList->getItems();
        $inventoryData = [];

        foreach ($products as $product) {
            $sourceItems = $this->getSourceItemsBySku->execute($product->getSku());
            $productInventory = [];

            foreach ($sourceItems as $sourceItem) {
                $productInventory[] = [
                    'source_code' => $sourceItem->getSourceCode(),
                    'quantity' => $sourceItem->getQuantity(),
                    'status' => $sourceItem->getStatus(),
                ];
            }

            $inventoryData[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'inventory' => $productInventory
            ];
        }

        return [
            'status' => 'success',
            'inventory' => $inventoryData,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_products' => $totalProducts,
                'total_pages' => $totalPages,
                'has_more_pages' => $page < $totalPages
            ]
        ];
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

    /**
     *  Handles updating the inventory records
     *
     * @throws ValidationException
     * @throws CouldNotSaveException
     * @throws BadRequestException
     * @throws InputException
     * @throws NotFoundException
     */
    protected function pushInventory(): array
    {
        $sku = $this->request->getParam('sku');
        $source_code = $this->request->getParam('source_code');
        $quantity = (int)$this->request->getParam('quantity');
        $in_stock = (bool)$this->request->getParam('in_stock');

        if (!$sku || !$source_code || !$quantity) {
            throw new BadRequestException('The sku, source_code, and quantity are required.');
        }

        $sourceItem = $this->getInventoryItem($sku, $source_code);
        $sourceItem->setSku($sku);
        $sourceItem->setSourceCode($source_code);
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($in_stock ? 1 : 0); // Default to "In Stock"
        $sourceItemsToUpdate[] = $sourceItem;
        $this->sourceItemsSave->execute($sourceItemsToUpdate);

        return [
            'status' => 'success',
            'message' => 'Inventory updated successfully.',
        ];
    }
}
