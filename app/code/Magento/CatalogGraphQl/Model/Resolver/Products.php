<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\App\ObjectManager;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class Products implements ResolverInterface
{
    /**
     * @var ProductQueryInterface
     */
    private $searchQuery;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchApiCriteriaBuilder;

    /** @var Uid */
    private $uidEncoder;

    /**
     * @param ProductQueryInterface $searchQuery
     * @param SearchCriteriaBuilder|null $searchApiCriteriaBuilder
     * @param Uid|null $uidEncoder
     */
    public function __construct(
        ProductQueryInterface $searchQuery,
        ?SearchCriteriaBuilder $searchApiCriteriaBuilder = null,
        ?Uid $uidEncoder = null
    ) {
        $this->searchQuery = $searchQuery;
        $this->searchApiCriteriaBuilder = $searchApiCriteriaBuilder ??
            ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        $this->uidEncoder = $uidEncoder ?: ObjectManager::getInstance()->get(Uid::class);
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
        $this->validateInput($args);

        $searchResult = $this->searchQuery->getResult($args, $info, $context);

        if ($searchResult->getCurrentPage() > $searchResult->getTotalPages() && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$searchResult->getCurrentPage(), $searchResult->getTotalPages()]
                )
            );
        }

        $data = [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $searchResult->getProductsSearchResult(),
            'suggestions' => $searchResult->getSuggestions(),
            'page_info' => [
                'page_size' => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurrentPage(),
                'total_pages' => $searchResult->getTotalPages()
            ],
            'search_result' => $searchResult,
            'layer_type' => isset($args['search']) ? Resolver::CATALOG_LAYER_SEARCH : Resolver::CATALOG_LAYER_CATEGORY,
        ];

        if (isset($args['filter']['category_uid'])) {
            $args['filter']['category_id'] = $this->getFilterCategoryIdFromCategoryUid($args['filter']['category_uid']);
        }

        if (isset($args['filter']['category_id'])) {
            $data['categories'] = $args['filter']['category_id']['eq'] ?? $args['filter']['category_id']['in'];
            $data['categories'] = is_array($data['categories']) ? $data['categories'] : [$data['categories']];
        }

        return $data;
    }

    /**
     * Get filter category_id by category_uid
     *
     * @param array $filterCategoryUid
     * @return array|null
     */
    private function getFilterCategoryIdFromCategoryUid(array $filterCategoryUid): ?array
    {
        $filterCategoryId = null;
        if (isset($filterCategoryUid['eq'])) {
            $filterCategoryId['eq'] = $this->uidEncoder
                ->decode((string)$filterCategoryUid['eq']);
        } elseif (!empty($filterCategoryUid['in'])) {
            foreach ($filterCategoryUid['in'] as $uid) {
                $filterCategoryId['in'][] = $this->uidEncoder->decode((string) $uid);
            }
        }
        return $filterCategoryId;
    }

    /**
     * Validate input arguments
     *
     * @param array $args
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    private function validateInput(array $args)
    {
        if (isset($args['searchAllowed']) && $args['searchAllowed'] === false) {
            throw new GraphQlAuthorizationException(__('Product search has been disabled.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if (!isset($args['search']) && !isset($args['filter'])) {
            throw new GraphQlInputException(
                __("'search' or 'filter' input argument is required.")
            );
        }
    }
}
