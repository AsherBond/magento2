<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
namespace Magento\AdminNotification\Model;

/**
 * Notification service model
 *
 * @api
 * @since 100.0.2
 */
class NotificationService
{
    /**
     * @var \Magento\AdminNotification\Model\InboxFactory $notificationFactory
     */
    protected $_notificationFactory;

    /**
     * @param \Magento\AdminNotification\Model\InboxFactory $notificationFactory
     */
    public function __construct(\Magento\AdminNotification\Model\InboxFactory $notificationFactory)
    {
        $this->_notificationFactory = $notificationFactory;
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function markAsRead($notificationId)
    {
        $notification = $this->_notificationFactory->create();
        $notification->load($notificationId);
        if (!$notification->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Wrong notification ID specified.'));
        }
        $notification->setIsRead(1);
        $notification->save();
    }
}
