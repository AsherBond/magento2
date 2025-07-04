<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

namespace Magento\AdminAnalytics\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;

/**
 * Data Provider for the Admin usage UI component.
 */
class AdminUsageNotificationDataProvider extends AbstractDataProvider
{
    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        return null;
    }
}
