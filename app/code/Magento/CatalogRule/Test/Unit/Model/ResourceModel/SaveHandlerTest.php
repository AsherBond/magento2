<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogRule\Test\Unit\Model\ResourceModel;

use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\CatalogRule\Model\ResourceModel\SaveHandler;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveHandlerTest extends TestCase
{
    /**
     * @var SaveHandler
     */
    protected $subject;

    /**
     * @var MockObject
     */
    protected $resourceMock;

    /**
     * @var MockObject
     */
    protected $metadataMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(Rule::class);
        $this->metadataMock = $this->createMock(MetadataPool::class);
        $this->subject = new SaveHandler(
            $this->resourceMock,
            $this->metadataMock
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $linkedField = 'entity_id';
        $entityId = 100;
        $entityType = RuleInterface::class;

        $customerGroupIds = '1, 2, 3';
        $websiteIds = '4, 5, 6';
        $entityData = [
            $linkedField => $entityId,
            'website_ids' => $websiteIds,
            'customer_group_ids' => $customerGroupIds
        ];

        $metadataMock = $this->createPartialMock(
            EntityMetadata::class,
            ['getLinkField']
        );
        $this->metadataMock->expects($this->once())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getLinkField')->willReturn($linkedField);

        $this->resourceMock
            ->method('bindRuleToEntity')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($entityId, $websiteIds, $customerGroupIds) {
                $websiteIds = explode(',', (string) $websiteIds);
                $customerGroupIds = explode(',', (string)$customerGroupIds);
                if ($arg1== $entityId && $arg2== $websiteIds && $arg3 == 'website') {
                    return $this->resourceMock;
                } elseif ($arg1== $entityId && $arg2== $customerGroupIds && $arg3 == 'customer_group') {
                    return $this->resourceMock;
                }
            });

        $this->assertEquals($entityData, $this->subject->execute($entityType, $entityData));
    }
}
