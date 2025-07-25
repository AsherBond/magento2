<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\AdminNotification\Model\ResourceModel\Inbox;

/**
 * AdminNotification Inbox model
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\AdminNotification\Model\Inbox::class,
            \Magento\AdminNotification\Model\ResourceModel\Inbox::class
        );
    }

    /**
     * Add remove filter
     *
     * @return $this
     */
    public function addRemoveFilter()
    {
        $this->getSelect()->where('is_remove=?', 0);
        return $this;
    }
}
