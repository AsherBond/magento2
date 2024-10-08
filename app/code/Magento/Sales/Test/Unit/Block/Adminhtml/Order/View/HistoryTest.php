<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Sales\Test\Unit\Block\Adminhtml\Order\View;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Block\Adminhtml\Order\View\History;
use Magento\Sales\Helper\Admin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * @var Admin|MockObject
     */
    protected $adminHelperMock;

    /**
     * @var History
     */
    protected $viewHistory;

    protected function setUp(): void
    {
        $this->adminHelperMock = $this->getMockBuilder(Admin::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->viewHistory = (new ObjectManager($this))->getObject(
            History::class,
            [
                'adminHelper' => $this->adminHelperMock
            ]
        );
    }

    /**
     * @param string $data
     * @param string $expected
     * @param null|array $allowedTags
     * @dataProvider escapeHtmlDataProvider
     */
    public function testEscapeHtml($data, $expected, $allowedTags = null)
    {
        $this->adminHelperMock
            ->expects($this->any())
            ->method('escapeHtmlWithLinks')
            ->willReturn($expected);
        $actual = $this->viewHistory->escapeHtml($data, $allowedTags);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public static function escapeHtmlDataProvider()
    {
        return [
            [
                '<a>some text in tags</a>',
                '&lt;a&gt;some text in tags&lt;/a&gt;',
                'allowedTags' => null
            ],
            [
                'Transaction ID: "<a target="_blank" href="https://www.paypal.com/?id=XX123XX">XX123XX</a>"',
                'Transaction ID: &quot;<a target="_blank" href="https://www.paypal.com/?id=XX123XX">XX123XX</a>&quot;',
                'allowedTags' => ['b', 'br', 'strong', 'i', 'u', 'a']
            ],
            [
                '<a>some text in tags</a>',
                '<a>some text in tags</a>',
                'allowedTags' => ['a']
            ]
        ];
    }
}
