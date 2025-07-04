<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Catalog Product Compare Widget
 */
class CompareProducts implements SectionSourceInterface
{
    /**
     * @var \Magento\Catalog\Helper\Product\Compare
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $productUrl;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    private $outputHelper;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param \Magento\Catalog\Helper\Product\Compare $helper
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param \Magento\Catalog\Helper\Output $outputHelper
     * @param ScopeConfigInterface|null $scopeConfig
     * @param StoreManagerInterface|null $storeManager
     * @param UrlInterface|null $urlBuilder
     */
    public function __construct(
        \Magento\Catalog\Helper\Product\Compare $helper,
        \Magento\Catalog\Model\Product\Url $productUrl,
        \Magento\Catalog\Helper\Output $outputHelper,
        ?ScopeConfigInterface $scopeConfig = null,
        ?StoreManagerInterface $storeManager = null,
        ?UrlInterface $urlBuilder = null
    ) {
        $this->helper = $helper;
        $this->productUrl = $productUrl;
        $this->outputHelper = $outputHelper;
        $this->scopeConfig = $scopeConfig ?? ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->urlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        $count = $this->helper->getItemCount();
        return [
            'count' => $count,
            'countCaption' => $count == 1 ? __('1 item') : __('%1 items', $count),
            'listUrl' => $this->urlBuilder->getUrl('catalog/product_compare/index'),
            'items' => $count ? $this->getItems() : [],
            'websiteId' => $this->storeManager->getWebsite()->getId()
        ];
    }

    /**
     * Get the list of compared product items
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getItems()
    {
        $items = [];
        $productsScope = $this->scopeConfig->getValue(
            'catalog/recently_products/scope',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($this->helper->getItemCollection() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product_url' => $this->productUrl->getUrl($item),
                'name' => $this->outputHelper->productAttribute($item, $item->getName(), 'name'),
                'remove_url' => $this->helper->getPostDataRemove($item),
                'productScope' => $productsScope
            ];
        }
        return $items;
    }
}
