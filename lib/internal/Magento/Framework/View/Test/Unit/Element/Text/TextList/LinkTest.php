<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test for view BlockPool model
 */
namespace Magento\Framework\View\Test\Unit\Element\Text\TextList;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Text\TextList\Link;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    /**
     * @var Link
     */
    protected $link;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->link = $objectManager->getObject(Link::class);
    }

    public function testSetLink()
    {
        $liParams = ['class' => 'some-css-class'];
        $aParams = ['href' => 'url'];
        $innerText = 'text';
        $afterText = 'afterText';

        $this->assertNull($this->link->getLiParams());
        $this->assertNull($this->link->getAParams());
        $this->assertNull($this->link->getInnerText());
        $this->assertNull($this->link->getAfterText());

        $this->link->setLink($liParams, $aParams, $innerText, $afterText);

        $this->assertEquals($liParams, $this->link->getLiParams());
        $this->assertEquals($aParams, $this->link->getAParams());
        $this->assertEquals($innerText, $this->link->getInnerText());
        $this->assertEquals($afterText, $this->link->getAfterText());
    }

    /**
     * @dataProvider toHtmlDataProvider
     */
    public function testToHtml($liParams, $aParams, $innerText, $afterText, $expectedHtml)
    {
        $this->link->setLink($liParams, $aParams, $innerText, $afterText);

        $this->assertEquals($expectedHtml, $this->link->toHtml());
    }

    /**
     * @return array
     */
    public static function toHtmlDataProvider()
    {
        return [
            [
                'liParams' => ['class' => 'some-css-class'],
                'aParams' => ['href' => 'url'],
                'innerText' => 'text',
                'afterText' => 'afterText',
                'expectedHtml' => '<li class="some-css-class"><a href="url">text</a>afterText</li>' . "\r\n"
            ],
            [
                'liParams' => 'class="some-css-class"',
                'aParams' => 'href="url"',
                'innerText' => 'text',
                'afterText' => 'afterText',
                'expectedHtml' => '<li class="some-css-class"><a href="url">text</a>afterText</li>' . "\r\n"
            ]
        ];
    }
}
