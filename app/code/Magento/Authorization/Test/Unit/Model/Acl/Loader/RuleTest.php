<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Authorization\Test\Unit\Model\Acl\Loader;

use Magento\Authorization\Model\Acl\Loader\Rule;
use Magento\Framework\Acl;
use Magento\Framework\Acl\Data\CacheInterface;
use Magento\Framework\Acl\RootResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Magento\Authorization\Model\Acl\Loader\Rule
 */
class RuleTest extends TestCase
{
    /**
     * @var Rule
     */
    private $model;

    /**
     * @var RootResource
     */
    private $rootResource;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var CacheInterface|MockObject
     */
    private $aclDataCacheMock;

    /**
     * @var Json|MockObject
     */
    private $serializerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->rootResource = new RootResource('Magento_Backend::all');
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->addMethods(['getTable'])
            ->onlyMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclDataCacheMock = $this->getMockForAbstractClass(CacheInterface::class);
        $this->serializerMock = $this->createPartialMock(
            Json::class,
            ['serialize', 'unserialize']
        );

        $this->serializerMock->method('serialize')
            ->willReturnCallback(
                static function ($value) {
                    return json_encode($value);
                }
            );

        $this->serializerMock->method('unserialize')
            ->willReturnCallback(
                static function ($value) {
                    return json_decode($value, true);
                }
            );

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Rule::class,
            [
                'rootResource' => $this->rootResource,
                'resource' => $this->resourceMock,
                'aclDataCache' => $this->aclDataCacheMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Test populating acl rule from cache.
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testPopulateAclFromCache(): void
    {
        $rules = [
            ['role_id' => 1, 'resource_id' => 'Magento_Backend::all', 'permission' => 'allow'],
            ['role_id' => 2, 'resource_id' => 1, 'permission' => 'allow'],
            ['role_id' => 3, 'resource_id' => 1, 'permission' => 'deny']
        ];
        $this->resourceMock->expects($this->never())->method('getTable');
        $this->resourceMock->expects($this->never())
            ->method('getConnection');

        $this->aclDataCacheMock->expects($this->once())
            ->method('load')
            ->with(Rule::ACL_RULE_CACHE_KEY)
            ->willReturn(
                json_encode($rules)
            );

        $aclMock = $this->createMock(Acl::class);
        $aclMock->method('hasResource')->willReturn(true);
        $aclMock
            ->method('allow')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) {
                if ($arg1 == '1' && $arg2 === null && $arg3 === null) {
                    return null;
                } elseif ($arg1 == '1' && $arg2 == 'Magento_Backend::all' && $arg3 === null) {
                    return null;
                } elseif ($arg1 == '2' && $arg2 == 1 && $arg3 === null) {
                    return null;
                }
            });

        $aclMock
            ->method('deny')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) {
                if ($arg1 == '3' && $arg2 == 1 && is_null($arg3)) {
                    return null;
                }
            });

        $aclMock
            ->method('getResources')
            ->willReturn([
                'Magento_Backend::all',
                'Magento_Backend::admin',
                'Vendor_MyModule::menu',
                'Vendor_MyModule::index'
            ]);

        $this->model->populateAcl($aclMock);
    }
}
