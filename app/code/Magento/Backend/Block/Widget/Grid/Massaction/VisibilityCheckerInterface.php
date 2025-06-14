<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
namespace Magento\Backend\Block\Widget\Grid\Massaction;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @api
 * @since 100.2.0
 */
interface VisibilityCheckerInterface extends ArgumentInterface
{
    /**
     * Check that action can be displayed on massaction list
     *
     * @return bool
     * @since 100.2.0
     */
    public function isVisible();
}
