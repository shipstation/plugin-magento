<?php

namespace Auctane\Api\Controller\Inventory;

use Auctane\Api\Exception\BadRequestException;
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
     */
    public function execute()
    {
        // Check the HTTP method using getMethod()
        $method = $this->request->getMethod();

        if ($method == 'GET') {
            return $this->handleGetRequest();
        } elseif ($method == 'POST') {
            return $this->handlePostRequest();
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
    protected function handleGetRequest(): array
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
     *  Handles updating the inventory records
     *
     * @throws ValidationException
     * @throws CouldNotSaveException
     * @throws BadRequestException
     * @throws InputException
     */
    protected function handlePostRequest(): array
    {
        $postData = $this->request->getContent(); // Get the raw POST data
        $data = json_decode($postData, true); // Decode JSON to array

        if (!isset($data['inventory']) || !is_array($data['inventory'])) {
            throw new BadRequestException('inventory not set to an array of inventory items');
        }

        $sourceItemsToUpdate = [];
        foreach ($data['inventory'] as $inventoryUpdate) {
            if (isset($inventoryUpdate['sku'], $inventoryUpdate['source_code'], $inventoryUpdate['quantity'])) {
                // Create a source item using ObjectManager or SourceItemsSaveInterface
                $sourceItem = ObjectManager::getInstance()->create(SourceItemInterface::class);
                $sourceItem->setSku($inventoryUpdate['sku']);
                $sourceItem->setSourceCode($inventoryUpdate['source_code']);
                $sourceItem->setQuantity($inventoryUpdate['quantity']);
                $sourceItem->setStatus($inventoryUpdate['status'] ?? 1); // Default to "In Stock"
                $sourceItemsToUpdate[] = $sourceItem;
            }
        }

        // Save updated source items
        $this->sourceItemsSave->execute($sourceItemsToUpdate);

        return [
            'status' => 'success',
            'message' => 'Inventory updated successfully.',
        ];
    }
}
