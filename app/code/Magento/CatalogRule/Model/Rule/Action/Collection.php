<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
namespace Magento\CatalogRule\Model\Rule\Action;

/**
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Rule\Model\Action\Collection
{
    /**
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Rule\Model\ActionFactory $actionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Rule\Model\ActionFactory $actionFactory,
        array $data = []
    ) {
        parent::__construct($assetRepo, $layout, $actionFactory, $data);
        $this->setType(\Magento\CatalogRule\Model\Rule\Action\Collection::class);
    }

    /**
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $actions = parent::getNewChildSelectOptions();
        $actions = array_merge_recursive(
            $actions,
            [
                ['value' => \Magento\CatalogRule\Model\Rule\Action\Product::class, 'label' => __('Update the Product')]
            ]
        );
        return $actions;
    }
}
