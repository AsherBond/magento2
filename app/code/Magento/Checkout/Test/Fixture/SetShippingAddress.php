<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Checkout\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class SetShippingAddress implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        AddressInterface::KEY_TELEPHONE => 3340000000,
        AddressInterface::KEY_POSTCODE => 36104,
        AddressInterface::KEY_COUNTRY_ID => 'US',
        AddressInterface::KEY_CITY => 'Montgomery',
        AddressInterface::KEY_COMPANY => 'Magento',
        AddressInterface::KEY_STREET => ['Green str, 67'],
        AddressInterface::KEY_LASTNAME => 'Doe',
        AddressInterface::KEY_FIRSTNAME => 'John',
        AddressInterface::KEY_REGION_ID => 1,
    ];

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(
        ServiceFactory $serviceFactory
    ) {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'cart_id' => (int) Cart ID. Required.
     *      'address' => (array) Address Data. Optional. Default: SetShippingAddress::DEFAULT_DATA
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        $service = $this->serviceFactory->create(ShippingAddressManagementInterface::class, 'assign');

        $service->execute(
            [
                'cartId' => $data['cart_id'],
                'address' => array_merge(self::DEFAULT_DATA, $data['address'] ?? [])
            ]
        );
        return null;
    }
}
