<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Indexer\Product\Flat;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Product\Flat\Indexer;
use Magento\Catalog\Model\Indexer\Product\Flat\FlatTableBuilder;
use Magento\Catalog\Model\Indexer\Product\Flat\TableDataInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FlatTableBuilderTest extends TestCase
{
    /**
     * @var Indexer|MockObject
     */
    private $flatIndexerMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var TableDataInterface|MockObject
     */
    private $tableDataMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    /**
     * @var FlatTableBuilder
     */
    private $flatTableBuilder;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->flatIndexerMock = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tableDataMock = $this->getMockBuilder(
            TableDataInterface::class
        )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(
            EntityMetadataInterface::class
        )->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataMock->expects($this->any())->method('getLinkField')->willReturn('entity_id');

        $this->flatTableBuilder = $objectManagerHelper->getObject(
            FlatTableBuilder::class,
            [
                'productIndexerHelper' => $this->flatIndexerMock,
                'resource' => $this->resourceMock,
                'config' => $this->scopeConfigMock,
                'storeManager' => $this->storeManagerMock,
                'tableData' => $this->tableDataMock,
                '_connection' => $this->connectionMock
            ]
        );
        $objectManagerHelper->setBackwardCompatibleProperty(
            $this->flatTableBuilder,
            'metadataPool',
            $this->metadataPoolMock
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuild()
    {
        $storeId = 1;
        $changedIds = [];
        $valueFieldSuffix = '_value';
        $tableDropSuffix = '';
        $fillTmpTables = true;
        $tableName = 'catalog_product_entity';
        $attributeTable = 'catalog_product_entity_int';
        $temporaryTableName = 'catalog_product_entity_int_tmp_indexer';
        $temporaryValueTableName = 'catalog_product_entity_int_tmp_indexer_value';
        $linkField = 'entity_id';
        $statusId = 22;
        $eavCustomField = 'space_weight';
        $eavCustomValueField = $eavCustomField . $valueFieldSuffix;
        $this->flatIndexerMock->expects($this->once())->method('getAttributes')->willReturn([]);
        $this->flatIndexerMock->expects($this->exactly(3))->method('getFlatColumns')
            ->willReturnOnConsecutiveCalls([], [$eavCustomValueField => []], [$eavCustomValueField => []]);
        $this->flatIndexerMock->expects($this->once())->method('getFlatIndexes')->willReturn([]);
        $statusAttributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eavCustomAttributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->flatIndexerMock->expects($this->once())->method('getTablesStructure')
            ->willReturn(
                [
                    'catalog_product_entity' => [$linkField => $statusAttributeMock],
                    'catalog_product_entity_int' => [
                        $linkField => $statusAttributeMock,
                        $eavCustomField => $eavCustomAttributeMock
                    ]
                ]
            );
        $this->flatIndexerMock->expects($this->atLeastOnce())->method('getTable')
                ->willReturnCallback(
                    function ($arg) use ($tableName) {
                        if ($arg == $tableName) {
                            return $tableName;
                        } elseif ($arg == 'catalog_product_website') {
                            return 'catalog_product_website';
                        }
                    }
                );
        $this->flatIndexerMock->expects($this->once())->method('getAttribute')
            ->with('status')
            ->willReturn($statusAttributeMock);
        $backendMock = $this->getMockBuilder(AbstractBackend::class)
            ->disableOriginalConstructor()
            ->getMock();
        $backendMock->expects($this->atLeastOnce())->method('getTable')->willReturn($attributeTable);
        $statusAttributeMock->expects($this->atLeastOnce())->method('getBackend')->willReturn(
            $backendMock
        );
        $eavCustomAttributeMock->expects($this->atLeastOnce())->method('getBackend')->willReturn(
            $backendMock
        );
        $statusAttributeMock->expects($this->atLeastOnce())->method('getId')->willReturn($statusId);
        $tableMock = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->any())->method('newTable')->willReturn($tableMock);
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->atLeastOnce())->method('select')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('from')->with(
            ['et' => 'catalog_product_entity_tmp_indexer'],
            [$linkField, 'type_id', 'attribute_set_id']
        )->willReturnSelf();
        $selectMock->expects($this->atLeastOnce())->method('joinInner')->willReturnSelf();
        $selectMock->expects($this->exactly(3))->method('joinLeft')
            ->willReturnCallback(
                function (
                    $arg1,
                    $arg2,
                    $arg3
                ) use (
                    $selectMock,
                    $attributeTable,
                    $linkField,
                    $storeId,
                    $statusId,
                    $temporaryTableName,
                    $eavCustomField,
                    $temporaryValueTableName,
                    $eavCustomValueField
                ) {
                    if ($arg1 === ['dstatus' => $attributeTable] &&
                        $arg2 ===
                        sprintf(
                            'e.%s = dstatus.%s AND dstatus.store_id = %s AND dstatus.attribute_id = %s',
                            $linkField,
                            $linkField,
                            $storeId,
                            $statusId
                        )
                        && empty($arg3)) {
                            return $selectMock;

                    } elseif ($arg1 === $temporaryTableName &&
                        $arg2 === "e.{$linkField} = {$temporaryTableName}.{$linkField}" &&
                        $arg3 === [$linkField, $eavCustomField]) {
                            return $selectMock;

                    } elseif ($arg1 === $temporaryValueTableName &&
                        $arg2 === "e.{$linkField} = {$temporaryValueTableName}.{$linkField}" &&
                        $arg3 === [$eavCustomValueField]) {
                            return $selectMock;
                    }
                }
            );
        $this->metadataPoolMock->expects($this->atLeastOnce())->method('getMetadata')->with(ProductInterface::class)
            ->willReturn($this->metadataMock);
        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock->expects($this->once())->method('getStore')->with($storeId)->willReturn($storeMock);
        $this->flatTableBuilder->build($storeId, $changedIds, $valueFieldSuffix, $tableDropSuffix, $fillTmpTables);
    }
}
