<?php
/**
 * Copyright 2019 Adobe
 * All rights reserved.
 */
declare(strict_types=1);

namespace Magento\Framework\DB\Test\Unit\DB\Statement;

use Magento\Framework\DB\Statement\Parameter;
use Magento\Framework\DB\Statement\Pdo\Mysql;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MysqlTest extends TestCase
{
    /**
     * @var \Zend_Db_Adapter_Abstract|MockObject
     */
    private $adapterMock;

    /**
     * @var \PDO|MockObject
     */
    private $pdoMock;

    /**
     * @var \Zend_Db_Profiler|MockObject
     */
    private $zendDbProfilerMock;

    /**
     * @var \PDOStatement|MockObject
     */
    private $pdoStatementMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->adapterMock = $this->getMockForAbstractClass(
            \Zend_Db_Adapter_Abstract::class,
            [],
            '',
            false,
            true,
            true,
            ['getConnection', 'getProfiler']
        );
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->adapterMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->pdoMock);
        $this->zendDbProfilerMock = $this->createMock(\Zend_Db_Profiler::class);
        $this->adapterMock->expects($this->once())
            ->method('getProfiler')
            ->willReturn($this->zendDbProfilerMock);
        $this->pdoStatementMock = $this->createMock(\PDOStatement::class);
    }

    public function testExecuteWithoutParams()
    {
        $query = 'SET @a=1;';
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->pdoStatementMock);
        $this->pdoStatementMock->expects($this->once())
            ->method('execute');
        (new Mysql($this->adapterMock, $query))->_execute();
    }

    public function testExecuteWhenThrowPDOException()
    {
        $this->expectException(\Zend_Db_Statement_Exception::class);
        $this->expectExceptionMessage('test message, query was:');
        $errorReporting = error_reporting();
        $query = 'SET @a=1;';
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->pdoStatementMock);
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \PDOException('test message'));
        $this->setQueryStringForPdoStmtMock($query);
        $this->assertEquals($errorReporting, error_reporting(), 'Error report level was\'t restored');

        (new Mysql($this->adapterMock, $query))->_execute();
    }

    public function testExecuteWhenParamsAsPrimitives()
    {
        $params = [':param1' => 'value1', ':param2' => 'value2'];
        $query = 'UPDATE `some_table1` SET `col1`=\'val1\' WHERE `param1`=\':param1\' AND `param2`=\':param2\';';
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->pdoStatementMock);
        $this->pdoStatementMock->expects($this->never())
            ->method('bindParam');
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($params);

        (new Mysql($this->adapterMock, $query))->_execute($params);
    }

    /**
     * Test execute method when params are passed as Parameter objects.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testExecuteWhenParamsAsParameterObject()
    {
        $param1 = $this->createMock(Parameter::class);
        $param1Value = 'SomeValue';
        $param1DataType = \PDO::PARAM_STR;
        $param1Length = 9;
        $param1DriverOptions = 'some driver options';
        $param1->expects($this->once())
            ->method('getIsBlob')
            ->willReturn(false);
        $param1->expects($this->once())
            ->method('getDataType')
            ->willReturn($param1DataType);
        $param1->expects($this->once())
            ->method('getLength')
            ->willReturn($param1Length);
        $param1->expects($this->once())
            ->method('getDriverOptions')
            ->willReturn($param1DriverOptions);
        $param1->expects($this->once())
            ->method('getValue')
            ->willReturn($param1Value);
        $params = [
            ':param1' => $param1,
            ':param2' => 'value2',
        ];
        $query = 'UPDATE `some_table1` SET `col1`=\'val1\' WHERE `param1`=\':param1\' AND `param2`=\':param2\';';
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($query)
            ->willReturn($this->pdoStatementMock);
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('bindParam')
            ->willReturnCallback(
                function (
                    $arg1,
                    $arg2,
                    $arg3,
                    $arg4,
                    $arg5
                ) use (
                    $param1Value,
                    $param1DataType,
                    $param1Length,
                    $param1DriverOptions
                ) {
                    if ($arg1 == ':param1' &&
                        $arg2 == $param1Value &&
                        $arg3 == $param1DataType &&
                        $arg4 == $param1Length &&
                        $arg5 == $param1DriverOptions) {
                        return true;
                    } elseif ($arg1 == ':param2' &&
                        $arg2 == 'value2' &&
                        $arg3 == \PDO::PARAM_STR &&
                        $arg4 == 6 &&
                        $arg5 == null) {
                        return true;
                    }
                }
            );
        $this->pdoStatementMock->expects($this->once())
            ->method('execute');

        (new Mysql($this->adapterMock, $query))->_execute($params);
    }

    /**
     * Initialize queryString property.
     *
     * @param string $query
     *
     * @return void
     */
    private function setQueryStringForPdoStmtMock(string $query): void
    {
        /*
         * In PHP 8.1 $queryString is a Typed property, thus it should be initialized before the 1st call.
         * But it's not automatically initialized in case of Mocking, so we do it here.
         */
        $this->pdoStatementMock->queryString = $query;
    }
}
