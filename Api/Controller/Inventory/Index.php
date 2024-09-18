<?php

namespace Auctane\Api\Controller\Inventory;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Index implements HttpGetActionInterface
{
    protected $jsonFactory;
    protected $getSourceItemsBySku;
    protected $productRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        JsonFactory $jsonFactory,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            // Create an empty search criteria to fetch all products
            $searchCriteria = $this->searchCriteriaBuilder->create();
            // Fetch all products
            $productCollection = $this->productRepository->getList($searchCriteria);
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
                'inventory' => $inventoryData
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
