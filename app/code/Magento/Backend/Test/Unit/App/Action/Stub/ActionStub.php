<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Backend\Test\Unit\App\Action\Stub;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class ActionStub extends Action
{
    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        // Empty method stub for test
    }
}
