<?php

namespace Auctane\Api\Tests\Unit\Model\OrderSourceAPI\Models;

use Auctane\Api\Model\OrderSourceAPI\Models\Dimensions;
use Auctane\Api\Model\OrderSourceAPI\Models\Product;
use Auctane\Api\Model\OrderSourceAPI\Models\ProductDetail;
use Auctane\Api\Model\OrderSourceAPI\Models\ProductIdentifiers;
use Auctane\Api\Model\OrderSourceAPI\Models\ProductUrls;
use Auctane\Api\Model\OrderSourceAPI\Models\Weight;
use Auctane\Api\Tests\Utilities\TestCase;

class ProductTest extends TestCase
{
    /**
     * Test Product construction with null data
     */
    public function testConstructionWithNullData(): void
    {
        $product = new Product(null);
        
        $this->assertNull($product->description);
        $this->assertNull($product->identifiers);
        $this->assertEquals([], $product->details);
        $this->assertNull($product->unit_cost);
        $this->assertNull($product->weight);
        $this->assertNull($product->dimensions);
        $this->assertNull($product->urls);
        $this->assertNull($product->location);
    }

    /**
     * Test Product construction with minimal required data
     */
    public function testConstructionWithMinimalData(): void
    {
        $data = [
            'product_id' => 'PROD123',
            'name' => 'Test Product'
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD123', $product->product_id);
        $this->assertEquals('Test Product', $product->name);
        $this->assertNull($product->description);
        $this->assertNull($product->identifiers);
        $this->assertEquals([], $product->details);
        $this->assertNull($product->unit_cost);
        $this->assertNull($product->weight);
        $this->assertNull($product->dimensions);
        $this->assertNull($product->urls);
        $this->assertNull($product->location);
    }

    /**
     * Test Product construction with complete data
     */
    public function testConstructionWithCompleteData(): void
    {
        $data = [
            'product_id' => 'PROD456',
            'name' => 'Complete Product',
            'description' => 'A fully featured product with all attributes',
            'identifiers' => [
                'sku' => 'SKU-456',
                'upc' => '123456789012',
                'ean' => '1234567890123',
                'isbn' => '978-3-16-148410-0'
            ],
            'details' => [
                [
                    'name' => 'Color',
                    'value' => 'Blue'
                ],
                [
                    'name' => 'Size',
                    'value' => 'Large'
                ]
            ],
            'unit_cost' => 29.99,
            'weight' => [
                'value' => 1.5,
                'unit' => 'lb'
            ],
            'dimensions' => [
                'length' => 10.0,
                'width' => 8.0,
                'height' => 6.0,
                'unit' => 'in'
            ],
            'urls' => [
                'product_url' => 'https://example.com/products/456',
                'image_url' => 'https://example.com/images/456.jpg'
            ],
            'location' => 'A1-B2-C3'
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD456', $product->product_id);
        $this->assertEquals('Complete Product', $product->name);
        $this->assertEquals('A fully featured product with all attributes', $product->description);
        $this->assertInstanceOf(ProductIdentifiers::class, $product->identifiers);
        $this->assertCount(2, $product->details);
        $this->assertInstanceOf(ProductDetail::class, $product->details[0]);
        $this->assertInstanceOf(ProductDetail::class, $product->details[1]);
        $this->assertEquals(29.99, $product->unit_cost);
        $this->assertInstanceOf(Weight::class, $product->weight);
        $this->assertInstanceOf(Dimensions::class, $product->dimensions);
        $this->assertInstanceOf(ProductUrls::class, $product->urls);
        $this->assertEquals('A1-B2-C3', $product->location);
    }

    /**
     * Test Product construction with empty details array
     */
    public function testConstructionWithEmptyDetails(): void
    {
        $data = [
            'product_id' => 'PROD789',
            'name' => 'Simple Product',
            'details' => []
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD789', $product->product_id);
        $this->assertEquals('Simple Product', $product->name);
        $this->assertEquals([], $product->details);
    }

    /**
     * Test Product construction with multiple product details
     */
    public function testConstructionWithMultipleDetails(): void
    {
        $data = [
            'product_id' => 'PROD999',
            'name' => 'Detailed Product',
            'details' => [
                [
                    'name' => 'Material',
                    'value' => 'Cotton'
                ],
                [
                    'name' => 'Brand',
                    'value' => 'TestBrand'
                ],
                [
                    'name' => 'Care Instructions',
                    'value' => 'Machine wash cold'
                ]
            ]
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('PROD999', $product->product_id);
        $this->assertEquals('Detailed Product', $product->name);
        $this->assertCount(3, $product->details);
        
        foreach ($product->details as $detail) {
            $this->assertInstanceOf(ProductDetail::class, $detail);
        }
    }

    /**
     * Test Product construction with weight information
     */
    public function testConstructionWithWeight(): void
    {
        $data = [
            'product_id' => 'HEAVY001',
            'name' => 'Heavy Product',
            'weight' => [
                'value' => 25.5,
                'unit' => 'kg'
            ]
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('HEAVY001', $product->product_id);
        $this->assertEquals('Heavy Product', $product->name);
        $this->assertInstanceOf(Weight::class, $product->weight);
    }

    /**
     * Test Product construction with dimensions information
     */
    public function testConstructionWithDimensions(): void
    {
        $data = [
            'product_id' => 'BIG001',
            'name' => 'Large Product',
            'dimensions' => [
                'length' => 100.0,
                'width' => 50.0,
                'height' => 25.0,
                'unit' => 'cm'
            ]
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('BIG001', $product->product_id);
        $this->assertEquals('Large Product', $product->name);
        $this->assertInstanceOf(Dimensions::class, $product->dimensions);
    }

    /**
     * Test Product construction with product URLs
     */
    public function testConstructionWithUrls(): void
    {
        $data = [
            'product_id' => 'URL001',
            'name' => 'Product with URLs',
            'urls' => [
                'product_url' => 'https://shop.example.com/products/url001',
                'image_url' => 'https://cdn.example.com/images/url001.png',
                'thumbnail_url' => 'https://cdn.example.com/thumbs/url001.png'
            ]
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('URL001', $product->product_id);
        $this->assertEquals('Product with URLs', $product->name);
        $this->assertInstanceOf(ProductUrls::class, $product->urls);
    }

    /**
     * Test Product construction with zero unit cost
     */
    public function testConstructionWithZeroUnitCost(): void
    {
        $data = [
            'product_id' => 'FREE001',
            'name' => 'Free Product',
            'unit_cost' => 0.0
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('FREE001', $product->product_id);
        $this->assertEquals('Free Product', $product->name);
        $this->assertEquals(0.0, $product->unit_cost);
    }

    /**
     * Test Product construction with negative unit cost
     */
    public function testConstructionWithNegativeUnitCost(): void
    {
        $data = [
            'product_id' => 'REFUND001',
            'name' => 'Refund Product',
            'unit_cost' => -15.50
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('REFUND001', $product->product_id);
        $this->assertEquals('Refund Product', $product->name);
        $this->assertEquals(-15.50, $product->unit_cost);
    }

    /**
     * Test Product construction with warehouse location
     */
    public function testConstructionWithWarehouseLocation(): void
    {
        $data = [
            'product_id' => 'LOC001',
            'name' => 'Located Product',
            'location' => 'Warehouse-A, Aisle-5, Shelf-B, Bin-12'
        ];
        
        $product = new Product($data);
        
        $this->assertEquals('LOC001', $product->product_id);
        $this->assertEquals('Located Product', $product->name);
        $this->assertEquals('Warehouse-A, Aisle-5, Shelf-B, Bin-12', $product->location);
    }
}