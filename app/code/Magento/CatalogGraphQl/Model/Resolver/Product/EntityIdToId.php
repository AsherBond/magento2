<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Product;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Fixed the id related data in the product data
 *
 * @deprecated Use UID
 * @see \Magento\CatalogGraphQl\Model\Resolver\Product\EntityIdToUid
 */
class EntityIdToId implements ResolverInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $productId = $product->getData(
            $this->metadataPool->getMetadata(ProductInterface::class)->getIdentifierField()
        );

        return $productId;
    }
}
