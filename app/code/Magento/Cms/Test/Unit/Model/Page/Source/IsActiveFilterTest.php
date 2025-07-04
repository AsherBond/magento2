<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Cms\Test\Unit\Model\Page\Source;

use Magento\Cms\Model\Page\Source\IsActiveFilter;

class IsActiveFilterTest extends IsActiveTest
{
    /**
     * {@inheritdoc}
     */
    protected function getSourceClassName()
    {
        return IsActiveFilter::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAvailableStatusesDataProvider()
    {
        return [
            [
                [],
                [['label' => '', 'value' => '']],
            ],
            [
                ['testStatus' => 'testValue'],
                [['label' => '', 'value' => ''], ['label' => 'testValue', 'value' => 'testStatus']],
            ],
        ];
    }
}
