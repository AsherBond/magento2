<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * {@inheritdoc}
 */
class CatalogProductTypeResolver implements TypeResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id'])) {
            if ($data['type_id'] == 'simple') {
                return 'SimpleProduct';
            } elseif ($data['type_id'] == 'virtual') {
                return 'VirtualProduct';
            }
        }
        return '';
    }
}
