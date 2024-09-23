<?php

namespace Auctane\Api\Controller\Inventory;

use Auctane\Api\Exception\BadRequestException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;

class Fetch implements HttpGetActionInterface
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
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Http $request
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface   $productRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        Http                         $request,
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
    }

    /**
     * This is called when a user hits the /inventory endpoint
     *
     * @throws BadRequestException
     */
    public function execute()
    {
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
                $in_stock = filter_var($sourceItem->getStatus(), FILTER_VALIDATE_BOOLEAN);
                $productInventory[] = [
                    'source_code' => $sourceItem->getSourceCode(),
                    'quantity' => $sourceItem->getQuantity(),
                    'in_stock' => $in_stock,
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
            'products' => $inventoryData,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_products' => $totalProducts,
                'total_pages' => $totalPages,
                'has_more_pages' => $page < $totalPages
            ]
        ];
    }
}
