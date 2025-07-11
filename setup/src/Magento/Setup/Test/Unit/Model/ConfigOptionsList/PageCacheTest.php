<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Setup\Test\Unit\Model\ConfigOptionsList;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\Option\SelectConfigOption;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Setup\Model\ConfigOptionsList\PageCache;
use Magento\Setup\Validator\RedisConnectionValidator;
use PHPUnit\Framework\TestCase;

class PageCacheTest extends TestCase
{
    /**
     * @var PageCache
     */
    private $configList;

    /**
     * @var RedisConnectionValidator
     */
    private $validatorMock;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfigMock;

    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(RedisConnectionValidator::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);

        $this->configList = new PageCache($this->validatorMock);
    }

    /**
     * testGetOptions
     */
    public function testGetOptions()
    {
        $options = $this->configList->getOptions();
        $this->assertCount(14, $options);

        $this->assertArrayHasKey(0, $options);
        $this->assertInstanceOf(SelectConfigOption::class, $options[0]);
        $this->assertEquals('page-cache', $options[0]->getName());

        $this->assertArrayHasKey(1, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[1]);
        $this->assertEquals('page-cache-redis-server', $options[1]->getName());

        $this->assertArrayHasKey(2, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[2]);
        $this->assertEquals('page-cache-redis-db', $options[2]->getName());

        $this->assertArrayHasKey(3, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[3]);
        $this->assertEquals('page-cache-redis-port', $options[3]->getName());

        $this->assertArrayHasKey(4, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[4]);
        $this->assertEquals('page-cache-redis-password', $options[4]->getName());

        $this->assertArrayHasKey(5, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[5]);
        $this->assertEquals('page-cache-redis-compress-data', $options[5]->getName());

        $this->assertArrayHasKey(6, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[6]);
        $this->assertEquals('page-cache-redis-compression-lib', $options[6]->getName());

        $this->assertArrayHasKey(7, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[7]);
        $this->assertEquals('page-cache-id-prefix', $options[7]->getName());

        $this->assertArrayHasKey(8, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[8]);
        $this->assertEquals('page-cache-valkey-server', $options[8]->getName());

        $this->assertArrayHasKey(9, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[9]);
        $this->assertEquals('page-cache-valkey-db', $options[9]->getName());

        $this->assertArrayHasKey(10, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[10]);
        $this->assertEquals('page-cache-valkey-port', $options[10]->getName());

        $this->assertArrayHasKey(11, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[11]);
        $this->assertEquals('page-cache-valkey-password', $options[11]->getName());

        $this->assertArrayHasKey(12, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[12]);
        $this->assertEquals('page-cache-valkey-compress-data', $options[12]->getName());

        $this->assertArrayHasKey(13, $options);
        $this->assertInstanceOf(TextConfigOption::class, $options[13]);
        $this->assertEquals('page-cache-valkey-compression-lib', $options[13]->getName());
    }

    /**
     * testCreateConfigWithRedis
     */
    public function testCreateConfigWithRedis()
    {
        $this->deploymentConfigMock->method('get')->willReturn('');

        $expectedConfigData = [
            'cache' => [
                'frontend' => [
                    'page_cache' => [
                        'backend' => \Magento\Framework\Cache\Backend\Redis::class,
                        'backend_options' => [
                            'server' => '',
                            'port' => '',
                            'database' => '',
                            'compress_data' => '',
                            'password' => '',
                            'compression_lib' => '',
                        ],
                        'id_prefix' => $this->expectedIdPrefix(),
                    ]
                ]
            ]
        ];

        $configData = $this->configList->createConfig(['page-cache' => 'redis'], $this->deploymentConfigMock);

        $this->assertEquals($expectedConfigData, $configData->getData());
    }

    /**
     * testCreateConfigWithRedisConfiguration
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testCreateConfigWithRedisConfiguration()
    {
        $this->deploymentConfigMock->method('get')
            ->willReturnCallback(function ($arg1, $arg2 = null) {
                if ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_ID_PREFIX) {
                    return 'XXX_';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER && $arg2 == '127.0.0.1') {
                    return '127.0.0.1';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE && $arg2 == '1') {
                    return '1';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT && $arg2 == '6379') {
                    return '6379';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD && $arg2 == '') {
                    return '';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA && $arg2 == '0') {
                    return '0';
                } elseif ($arg1 == PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB && $arg2 == '') {
                    return '';
                }
            });

        $expectedConfigData = [
            'cache' => [
                'frontend' => [
                    'page_cache' => [
                        'backend' => \Magento\Framework\Cache\Backend\Redis::class,
                        'backend_options' => [
                            'server' => 'foo.bar',
                            'port' => '9000',
                            'database' => '6',
                            'password' => '',
                            'compress_data' => '1',
                            'compression_lib' => 'gzip',
                        ],
                    ]
                ]
            ]
        ];

        $options = [
            'page-cache' => 'redis',
            'page-cache-redis-server' => 'foo.bar',
            'page-cache-redis-port' => '9000',
            'page-cache-redis-db' => '6',
            'page-cache-redis-compress-data' => '1',
            'page-cache-redis-compression-lib' => 'gzip',
        ];

        $configData = $this->configList->createConfig($options, $this->deploymentConfigMock);

        $this->assertEquals($expectedConfigData, $configData->getData());
    }

    /**
     * testCreateConfigWithRedis
     */
    public function testCreateConfigWithFileCache()
    {
        $this->deploymentConfigMock->method('get')->willReturn('');

        $expectedConfigData = [
            'cache' => [
                'frontend' => [
                    'page_cache' => [
                        'id_prefix' => $this->expectedIdPrefix(),
                    ]
                ]
            ]
        ];

        $configData = $this->configList->createConfig([], $this->deploymentConfigMock);

        $this->assertEquals($expectedConfigData, $configData->getData());
    }

    /**
     * testCreateConfigCacheRedis
     */
    public function testCreateConfigWithIdPrefix()
    {
        $this->deploymentConfigMock->method('get')->willReturn('');

        $explicitPrefix = 'XXX_';
        $expectedConfigData = [
            'cache' => [
                'frontend' => [
                    'page_cache' => [
                        'id_prefix' => $explicitPrefix,
                    ]
                ]
            ]
        ];

        $configData = $this->configList->createConfig(
            ['page-cache-id-prefix' => $explicitPrefix],
            $this->deploymentConfigMock
        );

        $this->assertEquals($expectedConfigData, $configData->getData());
    }

    /**
     * testValidationWithValidData
     */
    public function testValidationWithValidData()
    {
        $this->validatorMock->expects($this->once())
            ->method('isValidConnection')
            ->willReturn(true);

        $options = [
            'page-cache' => 'redis',
            'page-cache-redis-db' => '2',
            'cache-backend' => 'redis'
        ];

        $errors = $this->configList->validate($options, $this->deploymentConfigMock);

        $this->assertEmpty($errors);
    }

    /**
     * testValidationWithInvalidData
     */
    public function testValidationWithInvalidData()
    {
        $options = [
            'page-cache' => 'foobar'
        ];

        $errors = $this->configList->validate($options, $this->deploymentConfigMock);

        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid cache handler \'foobar\'', $errors[0]);
    }

    /**
     * The default ID prefix, based on installation directory
     *
     * @return string
     */
    private function expectedIdPrefix(): string
    {
        return substr(\hash('sha256', dirname(__DIR__, 8)), 0, 3) . '_';
    }
}
