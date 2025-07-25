<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Catalog\Model\ProductLink;

use Magento\Catalog\Model\Product;

/**
 * Provide link data for products.
 *
 * @api
 */
interface MapProviderInterface
{
    /**
     * Whether a provider can provide data for given link type.
     *
     * @param string $linkType
     * @return bool
     */
    public function canProcessLinkType(string $linkType): bool;

    /**
     * Load linked products.
     *
     * Must return map with keys as product objects, values as maps of link types and products linked.
     *
     * @param Product[] $products With SKUs as keys.
     * @param string[] $linkTypes List of supported link types to process, keys - names, values - codes.
     * @return Product[][]
     */
    public function fetchMap(array $products, array $linkTypes): array;
}
