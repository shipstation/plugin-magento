<?php

namespace Auctane\Api\Tests\Unit\Model\OrderSourceAPI\Models;

use Auctane\Api\Model\OrderSourceAPI\Models\Address;
use Auctane\Api\Model\OrderSourceAPI\Models\PickupLocation;
use Auctane\Api\Model\OrderSourceAPI\Models\ResidentialIndicator;
use Auctane\Api\Tests\Utilities\TestCase;

class AddressTest extends TestCase
{
    /**
     * Test Address construction with null data
     */
    public function testConstructionWithNullData(): void
    {
        $address = new Address(null);
        
        $this->assertNull($address->name);
        $this->assertNull($address->company);
        $this->assertNull($address->phone);
        $this->assertNull($address->address_line_1);
        $this->assertNull($address->address_line_2);
        $this->assertNull($address->address_line_3);
        $this->assertNull($address->city);
        $this->assertNull($address->state_province);
        $this->assertNull($address->postal_code);
        $this->assertNull($address->country_code);
        $this->assertNull($address->residential_indicator);
        $this->assertNull($address->is_verified);
        $this->assertNull($address->pickup_location);
    }

    /**
     * Test Address construction with empty array
     */
    public function testConstructionWithEmptyArray(): void
    {
        $address = new Address([]);
        
        $this->assertNull($address->name);
        $this->assertNull($address->company);
        $this->assertNull($address->phone);
        $this->assertNull($address->address_line_1);
        $this->assertNull($address->address_line_2);
        $this->assertNull($address->address_line_3);
        $this->assertNull($address->city);
        $this->assertNull($address->state_province);
        $this->assertNull($address->postal_code);
        $this->assertNull($address->country_code);
        $this->assertNull($address->residential_indicator);
        $this->assertNull($address->is_verified);
        $this->assertNull($address->pickup_location);
    }

    /**
     * Test Address construction with complete data
     */
    public function testConstructionWithCompleteData(): void
    {
        $data = [
            'name' => 'John Doe',
            'company' => 'Acme Corp',
            'phone' => '+1-555-123-4567',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'address_line_3' => 'Building A',
            'city' => 'New York',
            'state_province' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'residential_indicator' => ResidentialIndicator::RESIDENTIAL,
            'is_verified' => true,
            'pickup_location' => [
                'location_id' => 'pickup123',
                'name' => 'Store Pickup'
            ]
        ];
        
        $address = new Address($data);
        
        $this->assertEquals('John Doe', $address->name);
        $this->assertEquals('Acme Corp', $address->company);
        $this->assertEquals('+1-555-123-4567', $address->phone);
        $this->assertEquals('123 Main St', $address->address_line_1);
        $this->assertEquals('Apt 4B', $address->address_line_2);
        $this->assertEquals('Building A', $address->address_line_3);
        $this->assertEquals('New York', $address->city);
        $this->assertEquals('NY', $address->state_province);
        $this->assertEquals('10001', $address->postal_code);
        $this->assertEquals('US', $address->country_code);
        $this->assertEquals(ResidentialIndicator::RESIDENTIAL, $address->residential_indicator);
        $this->assertTrue($address->is_verified);
        $this->assertInstanceOf(PickupLocation::class, $address->pickup_location);
    }

    /**
     * Test Address construction with partial data
     */
    public function testConstructionWithPartialData(): void
    {
        $data = [
            'name' => 'Jane Smith',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state_province' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US'
        ];
        
        $address = new Address($data);
        
        $this->assertEquals('Jane Smith', $address->name);
        $this->assertNull($address->company);
        $this->assertNull($address->phone);
        $this->assertEquals('456 Oak Ave', $address->address_line_1);
        $this->assertNull($address->address_line_2);
        $this->assertNull($address->address_line_3);
        $this->assertEquals('Los Angeles', $address->city);
        $this->assertEquals('CA', $address->state_province);
        $this->assertEquals('90210', $address->postal_code);
        $this->assertEquals('US', $address->country_code);
        $this->assertNull($address->residential_indicator);
        $this->assertNull($address->is_verified);
        $this->assertNull($address->pickup_location);
    }

    /**
     * Test Address construction with pickup location data
     */
    public function testConstructionWithPickupLocationData(): void
    {
        $data = [
            'name' => 'Store Customer',
            'pickup_location' => [
                'location_id' => 'store001',
                'name' => 'Downtown Store',
                'address' => [
                    'address_line_1' => '789 Store St',
                    'city' => 'Chicago',
                    'state_province' => 'IL',
                    'postal_code' => '60601',
                    'country_code' => 'US'
                ]
            ]
        ];
        
        $address = new Address($data);
        
        $this->assertEquals('Store Customer', $address->name);
        $this->assertInstanceOf(PickupLocation::class, $address->pickup_location);
    }

    /**
     * Test Address construction with boolean verification flag
     */
    public function testConstructionWithVerificationFlag(): void
    {
        $verifiedData = [
            'name' => 'Verified Customer',
            'is_verified' => true
        ];
        
        $unverifiedData = [
            'name' => 'Unverified Customer',
            'is_verified' => false
        ];
        
        $verifiedAddress = new Address($verifiedData);
        $unverifiedAddress = new Address($unverifiedData);
        
        $this->assertTrue($verifiedAddress->is_verified);
        $this->assertFalse($unverifiedAddress->is_verified);
    }

    /**
     * Test Address construction with international address
     */
    public function testConstructionWithInternationalAddress(): void
    {
        $data = [
            'name' => 'International Customer',
            'company' => 'Global Ltd',
            'address_line_1' => '10 Downing Street',
            'city' => 'London',
            'state_province' => 'England',
            'postal_code' => 'SW1A 2AA',
            'country_code' => 'GB',
            'phone' => '+44 20 7930 4832'
        ];
        
        $address = new Address($data);
        
        $this->assertEquals('International Customer', $address->name);
        $this->assertEquals('Global Ltd', $address->company);
        $this->assertEquals('10 Downing Street', $address->address_line_1);
        $this->assertEquals('London', $address->city);
        $this->assertEquals('England', $address->state_province);
        $this->assertEquals('SW1A 2AA', $address->postal_code);
        $this->assertEquals('GB', $address->country_code);
        $this->assertEquals('+44 20 7930 4832', $address->phone);
    }
}