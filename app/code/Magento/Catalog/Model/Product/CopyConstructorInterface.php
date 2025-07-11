<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Product;

/**
 * Contains duplication logic for variety of product types
 *
 * @api
 * @since 100.0.2
 */
interface CopyConstructorInterface
{
    /**
     * Build product duplicate
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Catalog\Model\Product $duplicate
     * @return void
     */
    public function build(\Magento\Catalog\Model\Product $product, \Magento\Catalog\Model\Product $duplicate);
}
