<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Product\Gallery;

use Magento\Catalog\Model\Product;

/**
 * Manage entryes
 */
class EntryResolver
{
    /**
     * Retrieve file path that corresponds to the given gallery entry ID
     *
     * @param Product $product
     * @param int $entryId
     * @return string|null
     */
    public function getEntryFilePathById(Product $product, $entryId)
    {
        $mediaGalleryData = $product->getData('media_gallery');
        if (!isset($mediaGalleryData['images']) || !is_array($mediaGalleryData['images'])) {
            return null;
        }

        foreach ($mediaGalleryData['images'] as $image) {
            if (isset($image['value_id']) && $image['value_id'] == $entryId) {
                return $image['file'] ?? null;
            }
        }
        return null;
    }

    /**
     * Retrieve gallery entry ID that corresponds to the given file path
     *
     * @param Product $product
     * @param string $filePath
     * @return int|null
     */
    public function getEntryIdByFilePath(Product $product, $filePath)
    {
        $mediaGalleryData = $product->getData('media_gallery');
        if (!isset($mediaGalleryData['images']) || !is_array($mediaGalleryData['images'])) {
            return null;
        }

        foreach ($mediaGalleryData['images'] as $image) {
            if (isset($image['file']) && $image['file'] == $filePath) {
                return $image['value_id'] ?? null;
            }
        }
        return null;
    }
}
