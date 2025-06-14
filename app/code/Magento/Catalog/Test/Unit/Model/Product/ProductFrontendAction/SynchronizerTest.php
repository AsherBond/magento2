<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Product\ProductFrontendAction;

use Magento\Catalog\Api\Data\ProductFrontendActionInterface;
use Magento\Catalog\Model\FrontendStorageConfigurationInterface;
use Magento\Catalog\Model\FrontendStorageConfigurationPool;
use Magento\Catalog\Model\Product\ProductFrontendAction\Synchronizer;
use Magento\Catalog\Model\ProductFrontendActionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFrontendAction\Collection;
use Magento\Catalog\Model\ResourceModel\ProductFrontendAction\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Visitor;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SynchronizerTest extends TestCase
{
    /**
     * @var Synchronizer
     */
    protected $model;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var Visitor|MockObject
     */
    protected $visitorMock;

    /**
     * @var ProductFrontendActionFactory|MockObject
     */
    protected $productFrontendActionFactoryMock;

    /**
     * @var EntityManager|MockObject
     */
    protected $entityManagerMock;

    /**
     * @var MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var MockObject
     */
    protected $frontendStorageConfigurationPoolMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->visitorMock = $this->getMockBuilder(Visitor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productFrontendActionFactoryMock = $this->getMockBuilder(ProductFrontendActionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->frontendStorageConfigurationPoolMock = $this
            ->getMockBuilder(FrontendStorageConfigurationPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            Synchronizer::class,
            [
                'session' => $this->sessionMock,
                'visitor' => $this->visitorMock,
                'productFrontendActionFactory' => $this->productFrontendActionFactoryMock,
                'entityManager' => $this->entityManagerMock,
                'collectionFactory' => $this->collectionFactoryMock,
                'frontendStorageConfigurationPool' => $this->frontendStorageConfigurationPoolMock
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function testFilterProductActions(): void
    {
        $typeId = 'recently_compared_product';
        $productsData = [
            'website-1-1' => [
                'added_at' => 12,
                'product_id' => 1
            ],
            'website-1-2' => [
                'added_at' => 13,
                'product_id' => '2'
            ],
            'website-2-3' => [
                'added_at' => 14,
                'product_id' => 3
            ]
        ];
        $frontendConfiguration = $this->getMockForAbstractClass(FrontendStorageConfigurationInterface::class);
        $frontendConfiguration->expects($this->once())
            ->method('get')
            ->willReturn([
                'lifetime' => 2
            ]);
        $this->frontendStorageConfigurationPoolMock->expects($this->once())
            ->method('get')
            ->with('recently_compared_product')
            ->willReturn($frontendConfiguration);
        $action1 = $this->getMockBuilder(ProductFrontendActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action2 = $this->getMockBuilder(ProductFrontendActionInterface::class)
            ->getMockForAbstractClass();

        $frontendAction = $this->getMockForAbstractClass(ProductFrontendActionInterface::class);
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(1);
        $this->visitorMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(34);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collection);
        $collection->expects($this->once())
            ->method('addFilterByUserIdentities')
            ->with(1, 34);
        $collection
            ->method('addFieldToFilter')
            ->willReturnCallback(function ($arg1, $arg2) use ($typeId) {
                if ($arg1 == 'type_id' && $arg2 == $typeId) {
                    return null;
                } elseif ($arg1 == 'product_id' && $arg2 == [1, 2]) {
                    return null;
                }
            });
        $iterator = new \IteratorIterator(new \ArrayIterator([$frontendAction]));
        $collection->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);
        $this->entityManagerMock->expects($this->once())
            ->method('delete')
            ->with($frontendAction);
        $this->productFrontendActionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(function ($args) use ($action1, $action2) {
                if ($args['data']['added_at'] === 12) {
                    return $action1;
                } elseif ($args['data']['added_at'] === 13) {
                    return $action2;
                }
            });
        $this->entityManagerMock->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($arg) use ($action1, $action2) {
                if ($arg == $action1 || $arg == $action2) {
                    return null;
                }
            });
        $this->model->syncActions($productsData, 'recently_compared_product');
    }
}
