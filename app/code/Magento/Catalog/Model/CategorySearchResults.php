<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model;

use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Service Data Object with Category search results.
 */
class CategorySearchResults extends SearchResults implements CategorySearchResultsInterface
{
}
