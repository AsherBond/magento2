<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Sales\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\ResourceModel\GridInterface;
use Magento\Sales\Observer\GridSyncRemoveObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridSyncRemoveObserverTest extends TestCase
{
    /**
     * @var GridSyncRemoveObserver
     */
    protected $unit;

    /**
     * @var GridInterface|MockObject
     */
    protected $gridAggregatorMock;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * @var AbstractModel|MockObject
     */
    protected $salesModelMock;

    protected function setUp(): void
    {
        $this->gridAggregatorMock = $this->getMockBuilder(GridInterface::class)
            ->getMockForAbstractClass();
        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->addMethods(
                [
                    'getObject',
                    'getDataObject'
                ]
            )
            ->getMock();
        $this->salesModelMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getId'
                ]
            )
            ->getMockForAbstractClass();
        $this->unit = new GridSyncRemoveObserver(
            $this->gridAggregatorMock
        );
    }

    public function testSyncRemove()
    {
        $this->eventObserverMock->expects($this->once())
            ->method('getDataObject')
            ->willReturn($this->salesModelMock);
        $this->salesModelMock->expects($this->once())
            ->method('getId')
            ->willReturn('sales-id-value');
        $this->gridAggregatorMock->expects($this->once())
            ->method('purge')
            ->with('sales-id-value');
        $this->unit->execute($this->eventObserverMock);
    }
}
