<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\ViewModel\Product;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Product;

/**
 * Product options data view model
 */
class OptionsData implements ArgumentInterface
{
    /**
     * Returns options data array
     *
     * @param Product $product
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getOptionsData(Product $product) : array
    {
        return [];
    }
}
