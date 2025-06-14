<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Cms\Test\Unit\Model\Page\Source;

use Magento\Cms\Model\Page\Source\PageLayoutFilter;

class PageLayoutFilterTest extends PageLayoutTest
{
    /**
     * @return string
     */
    protected function getSourceClassName()
    {
        return PageLayoutFilter::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptionsDataProvider()
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
