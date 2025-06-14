<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class CategoryTypeResolver implements TypeResolverInterface
{
    const CATEGORY = 'CATEGORY';
    const TYPE_RESOLVER = 'CategoryTree';

    /**
     * @inheritdoc
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id']) && $data['type_id'] == self::CATEGORY) {
            return self::TYPE_RESOLVER;
        }
        return '';
    }
}
