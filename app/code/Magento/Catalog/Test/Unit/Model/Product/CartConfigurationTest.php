<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CartConfiguration;
use PHPUnit\Framework\TestCase;

class CartConfigurationTest extends TestCase
{
    /**
     * @param string $productType
     * @param array $config
     * @param boolean $expected
     * @dataProvider isProductConfiguredDataProvider
     */
    public function testIsProductConfigured($productType, $config, $expected)
    {
        $cartConfiguration = new CartConfiguration();
        $productMock = $this->createMock(Product::class);
        $productMock->expects($this->once())->method('getTypeId')->willReturn($productType);
        $this->assertEquals($expected, $cartConfiguration->isProductConfigured($productMock, $config));
    }

    /**
     * @return array
     */
    public static function isProductConfiguredDataProvider()
    {
        return [
            'simple' => ['simple', [], false],
            'virtual' => ['virtual', ['options' => true], true],
            'bundle' => ['bundle', ['bundle_option' => 'option1'], true],
            'some_option_type' => ['some_option_type', [], false]
        ];
    }
}
