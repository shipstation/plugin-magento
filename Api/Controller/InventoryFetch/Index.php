<?php

namespace Auctane\Api\Controller\InventoryFetch;

use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Models\InventoryFetchItem;
use Auctane\Api\Model\OrderSourceAPI\Requests\InventoryFetchRequest;
use Auctane\Api\Model\OrderSourceAPI\Responses\InventoryFetchResponse;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;

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
     * @var Http
     */
    protected Http $request;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     *
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        ProductRepositoryInterface   $productRepository,
        SearchCriteriaBuilder        $searchCriteriaBuilder,
    ) {
        parent::__construct();
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * This is called when a user hits the /inventory endpoint
     *
     * @throws BadRequestException
     * @return InventoryFetchResponse
     */
    public function executeAction(): InventoryFetchResponse
    {
        $request = new InventoryFetchRequest(json_decode($this->request->getContent(), true));
        $criteria = $request->criteria;
        $skus = $criteria->skus ?? [];
        $cursor = $request->cursor ?? [];

        $cursor = $this->getCursor($cursor);
        $page = $cursor['page'];
        $pageSize = $cursor['page_size'];

        // Create search criteria to fetch all products
        $searchCriteria = $this->searchCriteriaBuilder
            ->setCurrentPage($page)
            ->setPageSize($pageSize);

        if ($skus) {
            $searchCriteria->addFilter('sku', $skus, 'in');
        }

        $productList = $this->productRepository->getList($searchCriteria->create());
        $totalProducts = $productList->getTotalCount();
        $totalPages = ceil($totalProducts / $pageSize);

        if ($page > $totalPages && $totalProducts > 0) {
            throw new BadRequestException('Invalid page');
        }

        $products = $productList->getItems();
        $items = [];
        $timestamp = date('c');
        foreach ($products as $product) {
            $sourceItems = $this->getSourceItemsBySku->execute($product->getSku());
            foreach ($sourceItems as $sourceItem) {
                $items[] = new InventoryFetchItem([
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'integration_inventory_item_id' => json_encode([
                        'sku' => $sourceItem->getSku(),
                        'source' => $sourceItem->getSourceCode()
                    ]),
                    'available_quantity' => (int)$sourceItem->getQuantity(),
                    'fetched_at' => $timestamp,
                ]);
            }
        }

        $hasMorePages = $page < $totalPages;

        $response = new InventoryFetchResponse();
        $response->items = $items;
        if ($hasMorePages) {
            $response->cursor = json_encode([
               'page' => $page + 1,
               'page_size' => $pageSize,
               'total_pages' => $totalPages,
               'total_products' => $totalProducts,
            ]);
        }
        return $response;
    }

    private function getCursor(mixed $cursor)
    {
        if (!$cursor) {
            return [
               'page' => 1,
               'page_size' => 100,
            ];
        }
        $cursorData = json_decode($cursor, true);
        return [
            'page' => $cursorData['page'] ?? 1,
            'page_size' => $cursorData['page_size'] ?? 100,
        ];
    }
}
