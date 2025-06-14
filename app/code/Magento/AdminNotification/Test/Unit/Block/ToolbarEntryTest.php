<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

/**
 * Test class for \Magento\AdminNotification\Block\ToolbarEntry
 */
namespace Magento\AdminNotification\Test\Unit\Block;

use Magento\AdminNotification\Block\ToolbarEntry;
use Magento\AdminNotification\Model\ResourceModel\Inbox\Collection\Unread;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ToolbarEntryTest extends TestCase
{
    /**
     * Retrieve toolbar entry block instance
     *
     * @param int $unreadNotifications number of unread notifications
     * @return ToolbarEntry
     */
    protected function _getBlockInstance($unreadNotifications)
    {
        $objectManagerHelper = new ObjectManager($this);
        $objects = [
            [
                JsonHelper::class,
                $this->createMock(JsonHelper::class)
            ],
            [
                DirectoryHelper::class,
                $this->createMock(DirectoryHelper::class)
            ]
        ];
        $objectManagerHelper->prepareObjectManager($objects);
        // mock collection of unread notifications
        $notificationList = $this->createPartialMock(
            Unread::class,
            ['getSize', 'setCurPage', 'setPageSize']
        );
        $notificationList->method('getSize')->willReturn($unreadNotifications);

        $block = $objectManagerHelper->getObject(
            ToolbarEntry::class,
            ['notificationList' => $notificationList]
        );

        return $block;
    }

    public function testGetUnreadNotificationCount()
    {
        $notificationsCount = 100;
        $block = $this->_getBlockInstance($notificationsCount);
        $this->assertEquals($notificationsCount, $block->getUnreadNotificationCount());
    }

    public function testGetLatestUnreadNotifications()
    {
        $helper = new ObjectManager($this);

        $objects = [
            [
                JsonHelper::class,
                $this->createMock(JsonHelper::class)
            ],
            [
                DirectoryHelper::class,
                $this->createMock(DirectoryHelper::class)
            ]
        ];
        $helper->prepareObjectManager($objects);

        // 1. Create mocks
        $notificationList = $this->createMock(Unread::class);

        /** @var ToolbarEntry $model */
        $model = $helper->getObject(
            ToolbarEntry::class,
            ['notificationList' => $notificationList]
        );

        // 2. Set expectations
        $notificationList->expects($this->atLeastOnce())
            ->method('setPageSize')
            ->with(ToolbarEntry::NOTIFICATIONS_NUMBER)->willReturnSelf();

        // 3. Run tested method
        $result = $model->getLatestUnreadNotifications();

        // 4. Compare actual result with expected result
        $this->assertEquals($notificationList, $result);
    }
}
