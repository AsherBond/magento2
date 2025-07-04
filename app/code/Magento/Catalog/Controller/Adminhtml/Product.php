<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Controller\Adminhtml;

use Magento\Backend\App\Action;

/**
 * Catalog product controller
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class Product extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var Product\Builder
     */
    protected $productBuilder;

    /**
     * @param Action\Context $context
     * @param Product\Builder $productBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Product\Builder $productBuilder
    ) {
        $this->productBuilder = $productBuilder;
        parent::__construct($context);
    }
}
