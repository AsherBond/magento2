<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Ui\DataProvider\Product\Related;

/**
 * Checks cross-sell products data provider
 *
 * @see \Magento\Catalog\Ui\DataProvider\Product\Related\CrossSellDataProvider
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class CrossSellDataProviderTest extends AbstractRelationsDataProviderTest
{
    /**
     * @dataProvider productDataProvider
     *
     * @magentoDataFixture Magento/Catalog/_files/products_crosssell.php
     * @magentoDataFixture Magento/Catalog/_files/product_with_price_on_second_website.php
     *
     * @param string $storeCode
     * @param float $price
     * @return void
     */
    public function testGetData(string $storeCode, float $price): void
    {
        $this->prepareRequest('simple_with_cross', 'simple', $storeCode);
        $result = $this->getComponentProvidedData('crosssell_product_listing')['items'];
        $this->assertResult(1, ['sku' => 'second-website-price-product', 'price' => $price], $result);
    }

    /**
     * @return array
     */
    public static function productDataProvider(): array
    {
        return [
            'without_store_code' => [
                'storeCode' => 'default',
                'price' => 20,
            ],
            'with_store_code' => [
                'storeCode' => 'fixture_second_store',
                'price' => 10,
            ],
        ];
    }
}
