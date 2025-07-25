<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
namespace Magento\Cms\Ui\Component;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Ui\Component\Container;

/**
 * DataProvider for cms blocks and pages listing ui components.
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Authorization resource for CMS Save
     */
    private const CMS_SAVE_RESOURCE = 'Magento_Cms::save';

    /**
     * Authorization resource for CMS save design resource
     */
    private const CMS_SAVE_DESIGN_RESOURCE = 'Magento_Cms::save_design';

    /**
     * Constant for CMS listing data source name
     */
    private const CMS_LISTING_DATA_SOURCE = 'cms_page_listing_data_source';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var AddFilterInterface[]
     */
    private $additionalFilterPool;

    /**
     * @var array
     */
    private array $pageLayoutColumns = [
        PageInterface::PAGE_LAYOUT,
        PageInterface::CUSTOM_THEME,
        PageInterface::CUSTOM_THEME_FROM,
        PageInterface::CUSTOM_THEME_TO,
        PageInterface::CUSTOM_ROOT_TEMPLATE
    ];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     * @param array $additionalFilterPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = [],
        array $additionalFilterPool = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->meta = array_replace_recursive($meta, $this->prepareMetadata());
        $this->additionalFilterPool = $additionalFilterPool;
    }

    /**
     * Get authorization info.
     *
     * @deprecated 101.0.7
     * @see nothing
     *
     * @return AuthorizationInterface|mixed
     */
    private function getAuthorizationInstance()
    {
        if ($this->authorization === null) {
            $this->authorization = ObjectManager::getInstance()->get(AuthorizationInterface::class);
        }
        return $this->authorization;
    }

    /**
     * Prepares Meta
     *
     * @return array
     */
    public function prepareMetadata()
    {
        $metadata = [];

        if ($this->name === self::CMS_LISTING_DATA_SOURCE) {

            if (!$this->getAuthorizationInstance()->isAllowed(self::CMS_SAVE_RESOURCE)) {
                $metadata = [
                    'cms_page_columns' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'editorConfig' => [
                                        'enabled' => false
                                    ],
                                    'componentType' => Container::NAME
                                ]
                            ]
                        ]
                    ]
                ];
            }

            if (!$this->getAuthorizationInstance()->isAllowed(self::CMS_SAVE_DESIGN_RESOURCE)) {

                foreach ($this->pageLayoutColumns as $column) {
                    $metadata['cms_page_columns']['children'][$column] = [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'editor' => [
                                        'editorType' => false
                                    ],
                                    'componentType' => Container::NAME
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        return $metadata;
    }

    /**
     * Add Filter
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter): void
    {
        if (!empty($this->additionalFilterPool[$filter->getField()])) {
            $this->additionalFilterPool[$filter->getField()]->addFilter($this->searchCriteriaBuilder, $filter);
        } else {
            parent::addFilter($filter);
        }
    }
}
