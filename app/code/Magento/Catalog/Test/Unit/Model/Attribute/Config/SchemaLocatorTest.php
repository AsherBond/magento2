<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Attribute\Config;

use Magento\Catalog\Model\Attribute\Config\SchemaLocator;
use Magento\Framework\Module\Dir\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchemaLocatorTest extends TestCase
{
    /**
     * @var SchemaLocator
     */
    protected $_model;

    /**
     * @var Reader|MockObject
     */
    protected $_moduleReader;

    protected function setUp(): void
    {
        $this->_moduleReader = $this->createPartialMock(Reader::class, ['getModuleDir']);
        $this->_moduleReader->expects(
            $this->once()
        )->method(
            'getModuleDir'
        )->with(
            'etc',
            'Magento_Catalog'
        )->willReturn(
            'fixture_dir'
        );
        $this->_model = new SchemaLocator($this->_moduleReader);
    }

    public function testGetSchema()
    {
        $actualResult = $this->_model->getSchema();
        $this->assertEquals('fixture_dir/catalog_attributes.xsd', $actualResult);
        // Makes sure the value is calculated only once
        $this->assertEquals($actualResult, $this->_model->getSchema());
    }

    public function testGetPerFileSchema()
    {
        $actualResult = $this->_model->getPerFileSchema();
        $this->assertEquals('fixture_dir/catalog_attributes.xsd', $actualResult);
        // Makes sure the value is calculated only once
        $this->assertEquals($actualResult, $this->_model->getPerFileSchema());
    }
}
