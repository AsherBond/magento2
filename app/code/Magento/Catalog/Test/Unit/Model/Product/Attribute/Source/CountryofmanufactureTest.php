<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Product\Attribute\Source;

use Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CountryofmanufactureTest extends TestCase
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var Store
     */
    protected $storeMock;

    /**
     * @var Config
     */
    protected $cacheConfig;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /** @var Countryofmanufacture */
    private $countryOfManufacture;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var ResolverInterface
     */
    private $localeResolverMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->cacheConfig = $this->createMock(Config::class);
        $this->localeResolverMock = $this->getMockForAbstractClass(ResolverInterface::class);
        $this->objectManagerHelper = new ObjectManager($this);
        $this->countryOfManufacture = $this->objectManagerHelper->getObject(
            Countryofmanufacture::class,
            [
                'storeManager' => $this->storeManagerMock,
                'configCacheType' => $this->cacheConfig,
                'localeResolver' => $this->localeResolverMock,
            ]
        );

        $this->serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->objectManagerHelper->setBackwardCompatibleProperty(
            $this->countryOfManufacture,
            'serializer',
            $this->serializerMock
        );
    }

    /**
     * Test for getAllOptions method
     *
     * @param $cachedDataSrl
     * @param $cachedDataUnsrl
     *
     * @dataProvider getAllOptionsDataProvider
     */
    public function testGetAllOptions($cachedDataSrl, $cachedDataUnsrl)
    {
        $this->storeMock->expects($this->once())->method('getCode')->willReturn('store_code');
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($this->storeMock);
        $this->localeResolverMock->expects($this->once())->method('getLocale')->willReturn('en_US');
        $this->cacheConfig->expects($this->once())
            ->method('load')
            ->with($this->equalTo('COUNTRYOFMANUFACTURE_SELECT_STORE_store_code_LOCALE_en_US'))
            ->willReturn($cachedDataSrl);
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($cachedDataUnsrl);
        $this->assertEquals($cachedDataUnsrl, $this->countryOfManufacture->getAllOptions());
    }

    /**
     * Data provider for testGetAllOptions
     *
     * @return array
     */
    public static function getAllOptionsDataProvider()
    {
        return
            [
                ['cachedDataSrl' => json_encode(['key' => 'data']), 'cachedDataUnsrl' => ['key' => 'data']]
            ];
    }
}
