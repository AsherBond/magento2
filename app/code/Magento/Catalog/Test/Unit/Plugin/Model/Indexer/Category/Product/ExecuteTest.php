<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Plugin\Model\Indexer\Category\Product;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Catalog\Plugin\Model\Indexer\Category\Product\Execute;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExecuteTest extends TestCase
{
    /** @var Execute */
    protected $execute;

    /** @var Config|MockObject */
    protected $config;

    /** @var TypeListInterface|MockObject */
    protected $typeList;

    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEnabled'])
            ->getMock();
        $this->typeList = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['invalidate'])
            ->getMockForAbstractClass();

        $this->execute = new Execute($this->config, $this->typeList);
    }

    public function testAfterExecute()
    {
        $subject = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $result = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->typeList->expects($this->never())
            ->method('invalidate');

        $this->assertEquals(
            $result,
            $this->execute->afterExecute($subject, $result)
        );
    }

    public function testAfterExecuteInvalidate()
    {
        $subject = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $result = $this->getMockBuilder(AbstractAction::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->typeList->expects($this->once())
            ->method('invalidate')
            ->with('full_page');

        $this->assertEquals(
            $result,
            $this->execute->afterExecute($subject, $result)
        );
    }
}
