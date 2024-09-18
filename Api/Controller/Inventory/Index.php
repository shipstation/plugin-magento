<?php

namespace Auctane\Api\Controller\Inventory;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;

class Index implements HttpGetActionInterface
{
    protected $jsonFactory;
    protected $getSourceItemsBySku;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $request;
    public function __construct(
        JsonFactory $jsonFactory,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Get paging parameters from the query string
            $page = (int)$this->request->getParam('page', 1); // Default to page 1
            $pageSize = (int)$this->request->getParam('page_size', 100); // Default to 100 items per page

            // Create search criteria with paging
            $searchCriteria = $this->searchCriteriaBuilder
                ->setCurrentPage($page)
                ->setPageSize($pageSize)
                ->create();
            // Fetch all products
            $productCollection = $this->productRepository->getList($searchCriteria);
            $totalProducts = $productCollection->getTotalCount();
            $totalPages = ceil($totalProducts / $pageSize);

            // Check if the requested page is valid
            if (($page > $totalPages && $totalProducts > 0) || $page < 1) {
                return $result->setData([
                    'status' => 'error',
                    'message' => 'Requested page outside of page range.',
                    'pagination' => [
                        'current_page' => $page,
                        'page_size' => $pageSize,
                        'total_products' => $totalProducts,
                        'total_pages' => $totalPages
                    ]
                ]);
            }
            $inventoryData = [];

            foreach ($productCollection->getItems() as $product) {
                $sourceItems = $this->getSourceItemsBySku->execute($product->getSku());
                $productInventory = [];

                foreach ($sourceItems as $sourceItem) {
                    $productInventory[] = [
                        'source_code' => $sourceItem->getSourceCode(),
                        'quantity' => $sourceItem->getQuantity(),
                        'status' => $sourceItem->getStatus(), // 1 for in stock, 0 for out of stock
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
                    'total_pages' => ceil($totalProducts / $pageSize)
                ]
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
