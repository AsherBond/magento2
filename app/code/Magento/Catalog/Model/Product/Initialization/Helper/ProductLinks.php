<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Product\Initialization\Helper;

class ProductLinks
{
    /**
     * Init product links data (related, upsell, cross sell)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $links link data
     * @return \Magento\Catalog\Model\Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initializeLinks(\Magento\Catalog\Model\Product $product, array $links)
    {
        return $product;
    }
}
