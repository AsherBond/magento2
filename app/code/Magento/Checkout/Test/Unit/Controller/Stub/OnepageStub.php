<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Checkout\Test\Unit\Controller\Stub;

use Magento\Checkout\Controller\Onepage;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class OnepageStub extends Onepage
{
    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        // Empty method stub for test
    }
}
