<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Catalog\Block\Product\View;

/**
 * Product details block.
 *
 * Holds a group of blocks to show as tabs.
 *
 * @api
 * @since 103.0.1
 */
class Details extends \Magento\Framework\View\Element\Template
{
    /**
     * Get sorted child block names.
     *
     * @param string $groupName
     * @param string $callback
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return array
     * @since 103.0.1
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getGroupSortedChildNames(string $groupName, string $callback): array
    {
        $groupChildNames = $this->getGroupChildNames($groupName);
        $layout = $this->getLayout();

        $childNamesSortOrder = [];

        foreach ($groupChildNames as $childName) {
            $alias = $layout->getElementAlias($childName);
            $sortOrder = (int)$this->getChildData($alias, 'sort_order') ?? 0;

            $childNamesSortOrder[$childName] = $sortOrder;
        }

        asort($childNamesSortOrder, SORT_NUMERIC);

        return array_keys($childNamesSortOrder);
    }
}
