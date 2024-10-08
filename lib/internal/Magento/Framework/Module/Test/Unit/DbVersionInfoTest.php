<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\Module\Test\Unit;

use Magento\Framework\Module\DbVersionInfo;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\Output\ConfigInterface;
use Magento\Framework\Module\ResourceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbVersionInfoTest extends TestCase
{
    /**
     * @var DbVersionInfo
     */
    private $dbVersionInfo;

    /**
     * @var MockObject
     */
    private $moduleList;

    /**
     * @var ResourceInterface|MockObject
     */
    private $moduleResource;

    /**
     * @var ConfigInterface|MockObject
     */
    private $_outputConfig;

    protected function setUp(): void
    {
        $this->moduleList = $this->getMockForAbstractClass(ModuleListInterface::class);
        $this->moduleList->expects($this->any())
            ->method('getOne')
            ->willReturnMap([
                ['Module_One', ['name' => 'Module_One', 'setup_version' => '1']],
                ['Module_Two', ['name' => 'Module_Two', 'setup_version' => '2']],
                ['Module_No_Schema', []],
            ]);
        $this->moduleList->expects($this->any())
            ->method('getNames')
            ->willReturn(['Module_One', 'Module_Two']);

        $this->_outputConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->moduleResource = $this->getMockForAbstractClass(ResourceInterface::class);

        $this->dbVersionInfo = new DbVersionInfo(
            $this->moduleList,
            $this->moduleResource
        );
    }

    /**
     * @param string $moduleName
     * @param string|bool $dbVersion
     * @param bool $expectedResult
     *
     * @dataProvider isDbUpToDateDataProvider
     */
    public function testIsDbSchemaUpToDate($moduleName, $dbVersion, $expectedResult)
    {
        $this->moduleResource->expects($this->once())
            ->method('getDbVersion')
            ->with($moduleName)
            ->willReturn($dbVersion);
        $this->moduleList->expects(self::once())
            ->method('getOne')
            ->with($moduleName)
            ->willReturn(
                ['setup_version' => $dbVersion]
            );
        $this->assertEquals(
            $expectedResult,
            $this->dbVersionInfo->isSchemaUpToDate($moduleName)
        );
    }

    /**
     * @param string $moduleName
     * @param string|bool $dbVersion
     * @param bool $expectedResult
     *
     * @dataProvider isDbUpToDateDataProvider
     */
    public function testIsDbDataUpToDate($moduleName, $dbVersion, $expectedResult)
    {
        $this->moduleResource->expects($this->once())
            ->method('getDataVersion')
            ->with($moduleName)
            ->willReturn($dbVersion);
        $this->moduleList->expects(self::once())
            ->method('getOne')
            ->with($moduleName)
            ->willReturn(
                ['setup_version' => $dbVersion]
            );
        $this->assertEquals(
            $expectedResult,
            $this->dbVersionInfo->isDataUpToDate($moduleName)
        );
    }

    /**
     * @return array
     */
    public static function isDbUpToDateDataProvider()
    {
        return [
            'version in config == version in db' => ['Module_One', '1', true],
            'version in config < version in db' => [
                'Module_One',
                '2',
                false
            ],
            'version in config > version in db' => [
                'Module_Two',
                '1',
                false
            ],
            'no version in db' => [
                'Module_One',
                false,
                false
            ],
        ];
    }

    public function testGetDbVersionErrors()
    {
        $this->moduleResource->expects($this->any())
            ->method('getDataVersion')
            ->willReturn(2);
        $this->moduleResource->expects($this->any())
            ->method('getDbVersion')
            ->willReturn(2);

        $expectedErrors = [
            [
                DbVersionInfo::KEY_MODULE => 'Module_One',
                DbVersionInfo::KEY_CURRENT => '2',
                DbVersionInfo::KEY_REQUIRED => '1',
                DbVersionInfo::KEY_TYPE => 'schema',
            ],
            [
                DbVersionInfo::KEY_MODULE => 'Module_One',
                DbVersionInfo::KEY_CURRENT => '2',
                DbVersionInfo::KEY_REQUIRED => '1',
                DbVersionInfo::KEY_TYPE => 'data',
            ]
        ];
        $this->assertEquals($expectedErrors, $this->dbVersionInfo->getDbVersionErrors());
    }

    /**
     * Test is DB schema up to date for module with no schema
     */
    public function testIsDbSchemaUpToDateException()
    {
        $this->assertTrue($this->dbVersionInfo->isSchemaUpToDate('Module_No_Schema'));
    }

    /**
     * Test is DB Data up to date for module with no schema
     */
    public function testIsDbDataUpToDateException()
    {
        $this->assertTrue($this->dbVersionInfo->isDataUpToDate('Module_No_Schema'));
    }
}
