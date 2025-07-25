<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
namespace Magento\Bundle\Model\ResourceModel\Selection\Plugin;

use Magento\Store\Model\Store;

class Collection
{
    /**
     * Join website product limitation override
     * we don't need the website->product filter for children on selections
     *
     * @param \Magento\Bundle\Model\ResourceModel\Selection\Collection $subject
     * @param \Closure $proceed
     * @param null|string|bool|int|Store $store
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddStoreFilter(
        \Magento\Bundle\Model\ResourceModel\Selection\Collection $subject,
        \Closure $proceed,
        $store = null
    ) {
        return $subject;
    }
}
