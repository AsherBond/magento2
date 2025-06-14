<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Ui\Component\Listing\Columns;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Ui\Component\Listing\Columns\StatusText;
use PHPUnit\Framework\MockObject\MockObject;

class StatusTextTest extends AbstractColumnTestCase
{
    private const STATUS_ID = 1;
    private const STATUS_TEXT = 'Enabled';

    /**
     * @var Status|MockObject
     */
    protected $statusMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusMock = $this->getMockBuilder(Status::class)
            ->onlyMethods(['getOptionText'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return StatusText
     */
    protected function getModel()
    {
        return $this->objectManager->getObject(StatusText::class, [
            'context' => $this->contextMock,
            'uiComponentFactory' => $this->uiComponentFactoryMock,
            'status' => $this->statusMock,
            'components' => [],
            'data' => [],
        ]);
    }

    public function testPrepareDataSource()
    {
        $dataSource = [
            'data' => [
                'items' => [
                    [
                        ProductInterface::STATUS => self::STATUS_ID,
                    ]
                ],
            ],
        ];
        $expectedDataSource = [
            'data' => [
                'items' => [
                    [
                        ProductInterface::STATUS => self::STATUS_ID,
                        '' => self::STATUS_TEXT,
                    ]
                ],
            ],
        ];

        $this->statusMock->expects($this->once())
            ->method('getOptionText')
            ->with(self::STATUS_ID)
            ->willReturn(self::STATUS_TEXT);

        $this->assertEquals($expectedDataSource, $this->getModel()->prepareDataSource($dataSource));
    }
}
