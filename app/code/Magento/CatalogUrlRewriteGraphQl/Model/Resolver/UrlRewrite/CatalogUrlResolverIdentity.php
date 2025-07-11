<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogUrlRewriteGraphQl\Model\Resolver\UrlRewrite;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Get ids from catalog url rewrite
 */
class CatalogUrlResolverIdentity implements IdentityInterface
{
    /** @var string */
    private $categoryCacheTag = \Magento\Catalog\Model\Category::CACHE_TAG;

    /** @var string */
    private $productCacheTag = \Magento\Catalog\Model\Product::CACHE_TAG;

    /**
     * Get identities cache ID from a catalog url rewrite entities
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $entity_id = $resolvedData['id'] ?? $resolvedData['entity_id'] ?? null;
        if (isset($entity_id)) {
            $selectedCacheTag = isset($resolvedData['type']) ?
                $this->getTagFromEntityType($resolvedData['type']) : '';
            if (!empty($selectedCacheTag)) {
                $ids = [$selectedCacheTag, sprintf('%s_%s', $selectedCacheTag, $entity_id)];
            }
        }
        return $ids;
    }

    /**
     * Match tag to entity type
     *
     * @param string $entityType
     * @return string
     */
    private function getTagFromEntityType(string $entityType) : string
    {
        $selectedCacheTag = '';
        $type = strtolower($entityType);
        switch ($type) {
            case 'product':
                $selectedCacheTag = $this->productCacheTag;
                break;
            case 'category':
                $selectedCacheTag = $this->categoryCacheTag;
                break;
        }
        return $selectedCacheTag;
    }
}
