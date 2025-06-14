<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Backend\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Backend\Block\Dashboard\Tab\Customers\Most;
use Magento\Backend\Controller\Adminhtml\Dashboard\CustomersMost;

/**
 * Test for \Magento\Backend\Controller\Adminhtml\Dashboard\CustomersMost
 */
class CustomersMostTest extends AbstractTestCase
{
    public function testExecute()
    {
        $this->assertExecute(
            CustomersMost::class,
            Most::class
        );
    }
}
