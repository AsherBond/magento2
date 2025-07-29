<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Framework\DB\Test\Unit\Logger;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Logger\File;
use Magento\Framework\DB\Logger\QueryIndexAnalyzer;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\Exception\FileSystemException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryIndexAnalyzerTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection $resource;

    /**
     * @var QueryIndexAnalyzer
     */
    private QueryIndexAnalyzer $queryAnalyzer;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resource = $this->createMock(ResourceConnection::class);
        $this->queryAnalyzer = new QueryIndexAnalyzer($this->resource);

        parent::setUp();
    }

    /**
     * @param $sql
     * @param $bind
     * @param $result
     * @param $explainResult
     * @param $expectedResult
     * @return void
     * @dataProvider statsDataProvider
     */
    public function testProcess($sql, $bind, $result, $explainResult, $expectedResult): void
    {
        $statement = $this->createMock(\Zend_Db_Statement_Interface::class);
        $statement->expects($this->any())->method('fetchAll')->willReturn(json_decode($explainResult, true));
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->any())
            ->method('query')
            ->with('EXPLAIN ' . $sql)
            ->willReturn($statement);
        $this->resource->expects($this->any())->method('getConnection')->willReturn($connection);

        if ($expectedResult instanceof \InvalidArgumentException) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedResult->getMessage());
            $this->queryAnalyzer->process($sql, $bind);
        } else {
            $result = $this->queryAnalyzer->process($sql, $bind);
            $this->assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array
     */
    public static function statsDataProvider(): array
    {
        return [
            'no-stats-for-update-query' => [
                "UPDATE `admin_user_session` SET `updated_at` = '2025-07-23 14:42:02' WHERE (id=5)",
                [],
                null,
                '{}',
                new \InvalidArgumentException("Can't process query type")
            ],
            'no-stats-for-insert-query' => [
                "INSERT INTO `magento_logging_event` (`ip`, `x_forwarded_ip`, `event_code`, `time`, `action`, `info`,
                            `status`, `user`, `user_id`, `fullaction`, `error_message`) VALUES
                            (?, ?, ?, '2025-07-23 14:42:02', ?, ?, ?, ?, ?, ?, ?)",
                [],
                null,
                '{}',
                new \InvalidArgumentException("Can't process query type")
            ],
            'no-stats-for-delete-query' => [
                "DELETE FROM `sales_order_grid` WHERE (entity_id IN
                                      (SELECT `magento_sales_order_grid_archive`.`entity_id`
                                       FROM `magento_sales_order_grid_archive`))",
                [],
                null,
                '{}',
                new \InvalidArgumentException("Can't process query type")
            ],
            'small-table-query' => [
                "SELECT `main_table`.* FROM `admin_system_messages` AS `main_table`
                      ORDER BY severity ASC, created_at DESC",
                [],
                null,
                '[{"id":"1","select_type":"SIMPLE","table":"admin_system_messages","partitions":null,"type":"ALL",
                "possible_keys":null,"key":null,"key_len":null,"ref":null,"rows":"1","filtered":"100.00",
                "Extra":"Using filesort"}]',
                new \InvalidArgumentException("Small table")
            ],
            'subselect-with-dependent-query' => [
                "SELECT `main_table`.*, (IF(
                (SELECT count(*)
                    FROM magento_operation
                    WHERE bulk_uuid = main_table.uuid
                ) = 0,
                0,
                (SELECT MAX(status) FROM magento_operation WHERE bulk_uuid = main_table.uuid)
            )) AS `status` FROM `magento_bulk` AS `main_table` WHERE (`user_id` = '1')
                                                               ORDER BY FIELD(status, 2,3,0,4,1), start_time DESC",
                [],
                null,
                '[{"id":"1","select_type":"PRIMARY","table":"main_table","partitions":null,"type":"ref",
                "possible_keys":"MAGENTO_BULK_USER_ID","key":"MAGENTO_BULK_USER_ID","key_len":"5","ref":"const",
                "rows":"1","filtered":"100.00","Extra":"Using filesort"},{"id":"3","select_type":"DEPENDENT SUBQUERY",
                "table":"magento_operation","partitions":null,"type":"ref","possible_keys":
                "MAGENTO_OPERATION_BULK_UUID_ERROR_CODE","key":"MAGENTO_OPERATION_BULK_UUID_ERROR_CODE",
                "key_len":"42","ref":"magento24i2.main_table.uuid","rows":"1","filtered":"100.00","Extra":null},
                {"id":"2","select_type":"DEPENDENT SUBQUERY","table":"magento_operation","partitions":null,
                "type":"ref","possible_keys":"MAGENTO_OPERATION_BULK_UUID_ERROR_CODE","key":
                "MAGENTO_OPERATION_BULK_UUID_ERROR_CODE","key_len":"42","ref":
                "magento24i2.main_table.uuid","rows":"1","filtered":"100.00","Extra":"Using index"}]',
                ['DEPENDENT SUBQUERY', 'FILESORT', 'PARTIAL INDEX USED']
            ],
            'simple-qeury-partial-index' => [
                "SELECT `o`.`product_type`, COUNT(*) FROM `sales_order_item` AS `o` WHERE (o.order_id='67') AND
                 (o.product_id IS NOT NULL) AND ((o.product_type NOT IN
                 ('simple', 'virtual', 'bundle', 'downloadable', 'giftcard', 'configurable', 'grouped')))
                                                                   GROUP BY `o`.`product_type`",
                [],
                null,
                '[{"id":1,"select_type":"SIMPLE","table":"o","partitions":null,"type":"ref","possible_keys":
                "SALES_ORDER_ITEM_ORDER_ID","key":"SALES_ORDER_ITEM_ORDER_ID","key_len":"4","ref":"const",
                "rows":2,"filtered":45,"Extra":"Using where; Using temporary"}]',
                ['PARTIAL INDEX USED']
            ],
            'full-table-scan-no-index' => [
                "SELECT `main_table`.`entity_type_id`, `main_table`.`attribute_code`, `main_table`.`attribute_model`,
                `main_table`.`backend_model`, `main_table`.`backend_type`, `main_table`.`backend_table`,
                `main_table`.`frontend_model`, `main_table`.`frontend_input`, `main_table`.`frontend_label`,
                `main_table`.`frontend_class`, `main_table`.`source_model`, `main_table`.`is_required`,
                `main_table`.`is_user_defined`, `main_table`.`default_value`, `main_table`.`is_unique`,
                `main_table`.`note`,`additional_table`.* FROM `eav_attribute` AS `main_table`
                INNER JOIN `catalog_eav_attribute` AS `additional_table` ON additional_table.attribute_id =
                main_table.attribute_id WHERE (main_table.entity_type_id = 4) AND ((`is_searchable` = '1')
                OR (`is_visible_in_advanced_search` = '1') OR (((`is_filterable` = '1') OR (`is_filterable` = '2')))
                                                               OR (`is_filterable_in_search` = '1'))",
                [],
                null,
                '[{"id":1,"select_type":"SIMPLE","table":"additional_table","partitions":null,"type":"ALL",
                "possible_keys":"PRIMARY","key":null,"key_len":null,"ref":null,"rows":170,"filtered":40.95,"Extra":
                "Using where"},{"id":1,"select_type":"SIMPLE","table":"main_table","partitions":null,"type":"eq_ref",
                "possible_keys":"PRIMARY,EAV_ATTRIBUTE_ENTITY_TYPE_ID_ATTRIBUTE_CODE","key":"PRIMARY",
                "key_len":"2","ref":"magento24i2.additional_table.attribute_id","rows":1,"filtered":58.72,
                "Extra":"Using where"}]',
                ['FULL TABLE SCAN', 'NO INDEX']
            ],
        ];
    }
}
