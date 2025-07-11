<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model;

use \Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolver for layered filter type.
 */
class LayerFilterItemTypeResolver implements TypeResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data) : string
    {
        return isset($data['value_string'])
            && isset($data['label'])
            && isset($data['items_count'])
            && count($data) == 3
                ? 'LayerFilterItem'
                : '';
    }
}
