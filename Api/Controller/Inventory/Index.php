<?php

namespace Auctane\Api\Controller\Inventory;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http; // Use the correct Request class
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;

class Index implements HttpGetActionInterface, HttpPostActionInterface
{
    protected $jsonFactory;
    protected $getSourceItemsBySku;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $request;
    protected $sourceItemsSave;

    public function __construct(
        JsonFactory $jsonFactory,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Http $request, // Inject the correct Request class
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    public function execute()
    {
        // Check the HTTP method using getMethod()
        $method = $this->request->getMethod();

        if ($method == 'GET') {
            return $this->handleGetRequest();
        } elseif ($method == 'POST') {
            return $this->handlePostRequest();
        } else {
            return $this->jsonFactory->create()->setData([
                'status' => 'error',
                'message' => 'Method not allowed.'
            ]);
        }
    }

    // Handle GET request for retrieving paginated inventory
    protected function handleGetRequest()
    {
        $result = $this->jsonFactory->create();

        try {
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
                return $result->setData([
                    'status' => 'error',
                    'message' => 'Requested page exceeds total number of pages.',
                    'pagination' => [
                        'current_page' => $page,
                        'page_size' => $pageSize,
                        'total_products' => $totalProducts,
                        'total_pages' => $totalPages
                    ]
                ]);
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

            return $result->setData([
                'status' => 'success',
                'inventory' => $inventoryData,
                'pagination' => [
                    'current_page' => $page,
                    'page_size' => $pageSize,
                    'total_products' => $totalProducts,
                    'total_pages' => $totalPages
                ]
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Handle POST request to update inventory levels
    protected function handlePostRequest()
    {
        $result = $this->jsonFactory->create();

        try {
            $postData = $this->request->getContent(); // Get the raw POST data
            $data = json_decode($postData, true); // Decode JSON to array

            if (!isset($data['inventory']) || !is_array($data['inventory'])) {
                return $result->setData([
                    'status' => 'error',
                    'message' => 'Invalid input data format. Expected "inventory" array.'
                ]);
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

            return $result->setData([
                'status' => 'success',
                'message' => 'Inventory updated successfully.',
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => 'error',
                'message' => 'An error occurred while updating inventory.'
            ]);
        }
    }
}
