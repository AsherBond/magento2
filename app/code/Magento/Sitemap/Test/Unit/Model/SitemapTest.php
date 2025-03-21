<?php
/**
 * Copyright 2012 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sitemap\Test\Unit\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write as DirectoryWrite;
use Magento\Framework\Filesystem\File\Write;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\ZendEscaper;
use Magento\Sitemap\Helper\Data;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory;
use Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory;
use Magento\Sitemap\Model\ResourceModel\Cms\PageFactory;
use Magento\Sitemap\Model\ResourceModel\Sitemap as SitemapResource;
use Magento\Sitemap\Model\Sitemap;
use Magento\Sitemap\Model\SitemapConfigReaderInterface;
use Magento\Sitemap\Model\SitemapItem;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SitemapTest extends TestCase
{
    /**
     * @var SitemapResource|MockObject
     */
    private $resourceMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @var DirectoryWrite|MockObject
     */
    private $directoryMock;

    /**
     * @var Write|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryWrite|MockObject
     */
    private $tmpDirectoryMock;

    /**
     * @var Write|MockObject
     */
    private $tmpFileMock;

    /**
     * @var DirectoryWrite|MockObject
     */
    private $mediaDirectoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var ItemProviderInterface|MockObject
     */
    private $itemProviderMock;

    /**
     * @var ConfigReaderInterface|MockObject
     */
    private $configReaderMock;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $resourceMethods = [
            '_construct',
            'beginTransaction',
            'rollBack',
            'save',
            'addCommitCallback',
            'commit',
            '__wakeup',
        ];
        $this->resourceMock = $this->getMockBuilder(SitemapResource::class)
            ->onlyMethods($resourceMethods)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock->method('addCommitCallback')
            ->willReturnSelf();

        $this->fileMock = $this->createMock(Write::class);
        $this->directoryMock = $this->createMock(DirectoryWrite::class);
        $this->directoryMock->method('openFile')
            ->willReturn($this->fileMock);
        $this->tmpFileMock = $this->createMock(Write::class);
        $this->tmpDirectoryMock = $this->createMock(DirectoryWrite::class);
        $this->tmpDirectoryMock->method('openFile')
            ->willReturn($this->tmpFileMock);
        $this->mediaDirectoryMock = $this->createMock(DirectoryWrite::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->filesystemMock->method('getDirectoryWrite')
            ->willReturnMap(
                [
                    [DirectoryList::PUB, $this->directoryMock],
                    [DirectoryList::SYS_TMP, $this->tmpDirectoryMock],
                    [DirectoryList::MEDIA, $this->mediaDirectoryMock],
                ]
            );

        $this->configReaderMock = $this->createMock(SitemapConfigReaderInterface::class);
        $this->itemProviderMock = $this->createMock(ItemProviderInterface::class);

        $this->store = $this->createPartialMock(Store::class, ['isFrontUrlSecure', 'getBaseUrl']);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')
            ->willReturn($this->store);
    }

    /**
     * Check not allowed sitemap path validation
     */
    public function testNotAllowedPath()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please define a correct path.');
        $model = $this->getModelMock();
        $model->setSitemapPath('../');
        $model->beforeSave();
    }

    /**
     * Check not exists sitemap path validation
     */
    public function testPathNotExists()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please create the specified folder "/" before saving the sitemap.');
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->willReturn(false);

        $model = $this->getModelMock();
        $model->beforeSave();
    }

    /**
     * Check not writable sitemap path validation
     */
    public function testPathNotWritable()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please make sure that "/" is writable by the web-server.');
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->willReturn(true);

        $this->directoryMock->expects($this->once())
            ->method('isWritable')
            ->willReturn(false);

        $model = $this->getModelMock();
        $model->beforeSave();
    }

    /**
     * Check invalid chars in sitemap filename validation
     * No spaces or other characters are allowed.
     */
    public function testFilenameInvalidChars()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Please use only letters (a-z or A-Z), numbers (0-9) or underscores (_) in the filename.'
        );
        $this->directoryMock->expects($this->once())
            ->method('isExist')
            ->willReturn(true);

        $this->directoryMock->expects($this->once())
            ->method('isWritable')
            ->willReturn(true);

        $model = $this->getModelMock();
        $model->setSitemapFilename('*sitemap?.xml');
        $model->beforeSave();
    }

    /**
     * Data provider for sitemaps
     *
     * 1) Limit set to 50000 urls and 10M per sitemap file (single file)
     * 2) Limit set to 1 url and 10M per sitemap file (multiple files, 1 record per file)
     * 3) Limit set to 50000 urls and 264 bytes per sitemap file (multiple files, 1 record per file)
     *
     * @static
     * @return array
     */
    public static function sitemapDataProvider()
    {
        $expectedSingleFile = ['/sitemap-1-1.xml' => __DIR__ . '/_files/sitemap-single.xml'];

        $expectedMultiFile = [
            '/sitemap-1-1.xml' => __DIR__ . '/_files/sitemap-1-1.xml',
            '/sitemap-1-2.xml' => __DIR__ . '/_files/sitemap-1-2.xml',
            '/sitemap-1-3.xml' => __DIR__ . '/_files/sitemap-1-3.xml',
            '/sitemap-1-4.xml' => __DIR__ . '/_files/sitemap-1-4.xml',
            '/sitemap.xml' => __DIR__ . '/_files/sitemap-index.xml',
        ];

        return [
            [50000, 10485760, $expectedSingleFile, 6],
            [1, 10485760, $expectedMultiFile, 18],
            [50000, 264, $expectedMultiFile, 18],
        ];
    }

    /**
     * Check generation of sitemaps
     *
     * @param int $maxLines
     * @param int $maxFileSize
     * @param array $expectedFile
     * @param int $expectedWrites
     * @dataProvider sitemapDataProvider
     */
    public function testGenerateXml($maxLines, $maxFileSize, $expectedFile, $expectedWrites)
    {
        $actualData = [];
        $model = $this->prepareSitemapModelMock(
            $actualData,
            $maxLines,
            $maxFileSize,
            $expectedFile,
            $expectedWrites,
            null
        );
        $model->generateXml();

        $this->assertCount(count($expectedFile), $actualData, 'Number of generated files is incorrect');
        foreach ($expectedFile as $expectedFileName => $expectedFilePath) {
            $this->assertArrayHasKey(
                $expectedFileName,
                $actualData,
                sprintf('File %s was not generated', $expectedFileName)
            );
            $this->assertXmlStringEqualsXmlFile($expectedFilePath, $actualData[$expectedFileName]);
        }
    }

    /**
     * Data provider for robots.txt
     *
     * @static
     * @return array
     */
    public static function robotsDataProvider()
    {
        $expectedSingleFile = ['/sitemap-1-1.xml' => __DIR__ . '/_files/sitemap-single.xml'];

        $expectedMultiFile = [
            '/sitemap-1-1.xml' => __DIR__ . '/_files/sitemap-1-1.xml',
            '/sitemap-1-2.xml' => __DIR__ . '/_files/sitemap-1-2.xml',
            '/sitemap-1-3.xml' => __DIR__ . '/_files/sitemap-1-3.xml',
            '/sitemap-1-4.xml' => __DIR__ . '/_files/sitemap-1-4.xml',
            '/sitemap.xml' => __DIR__ . '/_files/sitemap-index.xml',
        ];

        return [
            [
                50000,
                10485760,
                $expectedSingleFile,
                6,
                [
                    'robotsStart' => '',
                    'robotsFinish' => 'Sitemap: http://store.com/sitemap.xml',
                    'pushToRobots' => 1
                ],
            ], // empty robots file
            [
                50000,
                10485760,
                $expectedSingleFile,
                6,
                [
                    'robotsStart' => "User-agent: *",
                    'robotsFinish' => "User-agent: *" . PHP_EOL . 'Sitemap: http://store.com/sitemap.xml',
                    'pushToRobots' => 1
                ]
            ], // not empty robots file EOL
            [
                1,
                10485760,
                $expectedMultiFile,
                18,
                [
                    'robotsStart' => "User-agent: *\r\n",
                    'robotsFinish' => "User-agent: *\r\n\r\nSitemap: http://store.com/sitemap.xml",
                    'pushToRobots' => 1
                ]
            ], // not empty robots file WIN
            [
                50000,
                264,
                $expectedMultiFile,
                18,
                [
                    'robotsStart' => "User-agent: *\n",
                    'robotsFinish' => "User-agent: *\n\nSitemap: http://store.com/sitemap.xml",
                    'pushToRobots' => 1
                ]
            ], // not empty robots file UNIX
            [
                50000,
                10485760,
                $expectedSingleFile,
                6,
                ['robotsStart' => '', 'robotsFinish' => '', 'pushToRobots' => 0]
            ] // empty robots file
        ];
    }

    /**
     * Check pushing of sitemaps to robots.txt
     *
     * @param int $maxLines
     * @param int $maxFileSize
     * @param array $expectedFile
     * @param int $expectedWrites
     * @param array $robotsInfo
     * @dataProvider robotsDataProvider
     */
    public function testAddSitemapToRobotsTxt($maxLines, $maxFileSize, $expectedFile, $expectedWrites, $robotsInfo)
    {
        $actualData = [];
        $model = $this->prepareSitemapModelMock(
            $actualData,
            $maxLines,
            $maxFileSize,
            $expectedFile,
            $expectedWrites,
            $robotsInfo
        );
        $model->generateXml();
    }

    /**
     * Prepare mock of Sitemap model
     *
     * @param array $actualData
     * @param int $maxLines
     * @param int $maxFileSize
     * @param array $expectedFile
     * @param int $expectedWrites
     * @param array $robotsInfo
     * @return Sitemap|MockObject
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareSitemapModelMock(
        &$actualData,
        $maxLines,
        $maxFileSize,
        $expectedFile,
        $expectedWrites,
        $robotsInfo
    ) {
        // Check that all $expectedWrites lines were written
        $actualData = [];
        $currentFile = '';
        $streamWriteCallback = function ($str) use (&$actualData, &$currentFile) {
            if (!array_key_exists($currentFile, $actualData)) {
                $actualData[$currentFile] = '';
            }
            $actualData[$currentFile] .= $str;
        };
        // Check that all expected lines were written
        $this->tmpFileMock->expects($this->exactly($expectedWrites))
            ->method('write')
            ->willReturnCallback($streamWriteCallback);

        $checkFileCallback = function ($file) use (&$currentFile) {
            $currentFile = $file;
        };
        // Check that all expected file descriptors were created
        $this->tmpDirectoryMock->expects($this->exactly(count($expectedFile)))
            ->method('openFile')
            ->willReturnCallback($checkFileCallback);

        // Check that all file descriptors were closed
        $this->tmpFileMock->expects($this->exactly(count($expectedFile)))
            ->method('close');

        if (count($expectedFile) == 1) {
            $this->tmpDirectoryMock->expects($this->once())
                ->method('renameFile')
                ->with('/sitemap-1-1.xml', '/sitemap.xml', $this->directoryMock)
                ->willReturn(true);
        }

        // Check robots txt
        $robotsStart = '';
        if (isset($robotsInfo['robotsStart'])) {
            $robotsStart = $robotsInfo['robotsStart'];
        }
        $robotsFinish = 'Sitemap: http://store.com/sitemap.xml';
        if (isset($robotsInfo['robotsFinish'])) {
            $robotsFinish = $robotsInfo['robotsFinish'];
        }
        $this->directoryMock->method('readFile')
            ->willReturn($robotsStart);

        $this->directoryMock->method('writeFile')
            ->with(
                $this->equalTo('robots.txt'),
                $this->equalTo($robotsFinish)
            );

        // Mock helper methods
        $pushToRobots = 0;
        if (isset($robotsInfo['pushToRobots'])) {
            $pushToRobots = (int)$robotsInfo['pushToRobots'];
        }
        $this->configReaderMock->method('getMaximumLinesNumber')
            ->willReturn($maxLines);

        $this->configReaderMock->method('getMaximumFileSize')
            ->willReturn($maxFileSize);

        $this->configReaderMock->method('getEnableSubmissionRobots')
            ->willReturn($pushToRobots);

        $model = $this->getModelMock(true);

        $this->store->expects($this->atLeastOnce())
            ->method('isFrontUrlSecure')
            ->willReturn(false);

        $this->store->expects($this->atLeastOnce())
            ->method('getBaseUrl')
            ->with($this->isType('string'), false)
            ->willReturn('http://store.com/');

        return $model;
    }

    /**
     * Get model mock object
     *
     * @param bool $mockBeforeSave
     * @return Sitemap|MockObject
     */
    protected function getModelMock($mockBeforeSave = false)
    {
        $addMethods = [
            '_getFileObject',
            '_afterSave',
            '_getCategoryItemsCollection',
            '_getProductItemsCollection',
            '_getPageItemsCollection'
        ];
        $methods = [
            '_construct',
            '_getResource',
            '_getBaseDir',
            '_getCurrentDateTime',
            '_getDocumentRoot'
        ];
        if ($mockBeforeSave) {
            $methods[] = 'beforeSave';
        }

        $storeBaseMediaUrl = 'http://store.com/media/catalog/product/cache/c9e0b0ef589f3508e5ba515cde53c5ff/';

        $this->itemProviderMock->method('getItems')
            ->willReturn(
                [
                    new SitemapItem('category.html', '1.0', 'daily', '2012-12-21 00:00:00'),
                    new SitemapItem('/category/sub-category.html', '1.0', 'daily', '2012-12-21 00:00:00'),
                    new SitemapItem('product.html', '0.5', 'monthly', '2012-12-21 00:00:00'),
                    new SitemapItem(
                        'product2.html',
                        '0.5',
                        'monthly',
                        '2012-12-21 00:00:00',
                        new DataObject(
                            [
                                'collection' => [
                                    new DataObject(
                                        [
                                            'url' => $storeBaseMediaUrl . 'i/m/image1.png',
                                            'caption' => 'Copyright © caption &trade; & > title < "'
                                        ]
                                    ),
                                    new DataObject(
                                        ['url' => $storeBaseMediaUrl . 'i/m/image_no_caption.png', 'caption' => null]
                                    ),
                                ],
                                'thumbnail' => $storeBaseMediaUrl . 't/h/thumbnail.jpg',
                                'title' => 'Product & > title < "',
                            ]
                        )
                    )
                ]
            );

        /** @var Sitemap $model */
        $model = $this->getMockBuilder(Sitemap::class)
            ->addMethods($addMethods)
            ->onlyMethods($methods)
            ->setConstructorArgs($this->getModelConstructorArgs())
            ->getMock();

        $model->method('_getResource')
            ->willReturn($this->resourceMock);

        $model->method('_getCurrentDateTime')
            ->willReturn('2012-12-21T00:00:00-08:00');

        $model->method('_getBaseDir')
            ->willReturn('');

        $model->method('_getDocumentRoot')
            ->willReturn('/project');

        $model->setSitemapFilename('sitemap.xml');
        $model->setStoreId(1);
        $model->setSitemapPath('/');

        return $model;
    }

    /**
     * @return array
     */
    private function getModelConstructorArgs()
    {
        $categoryFactory = $this->createMock(CategoryFactory::class);
        $productFactory = $this->createMock(ProductFactory::class);
        $cmsFactory = $this->createMock(PageFactory::class);
        $helperMockSitemap = $this->createMock(Data::class);
        $request = $this->createMock(Http::class);

        $objectManager = new ObjectManager($this);
        $escaper = $objectManager->getObject(Escaper::class);
        $this->setPrivatePropertyValue($escaper, 'escaper', $objectManager->getObject(ZendEscaper::class));
        $this->setPrivatePropertyValue($escaper, 'translateInline', $this->createMock(InlineInterface::class));
        $constructArguments = $objectManager->getConstructArguments(
            Sitemap::class,
            [
                'categoryFactory' => $categoryFactory,
                'productFactory' => $productFactory,
                'cmsFactory' => $cmsFactory,
                'storeManager' => $this->storeManagerMock,
                'sitemapData' => $helperMockSitemap,
                'filesystem' => $this->filesystemMock,
                'itemProvider' => $this->itemProviderMock,
                'configReader' => $this->configReaderMock,
                'escaper' => $escaper,
                'request' => $request,
            ]
        );
        $constructArguments['resource'] = null;
        return $constructArguments;
    }

    /**
     * Check site URL getter
     *
     * @param string $storeBaseUrl
     * @param string $documentRoot
     * @param string $baseDir
     * @param string $sitemapPath
     * @param string $sitemapFileName
     * @param string $result
     * @dataProvider siteUrlDataProvider
     */
    public function testGetSitemapUrl($storeBaseUrl, $documentRoot, $baseDir, $sitemapPath, $sitemapFileName, $result)
    {
        /** @var Sitemap $model */
        $model = $this->getMockBuilder(Sitemap::class)
            ->onlyMethods(
                [
                    '_getStoreBaseUrl',
                    '_getDocumentRoot',
                    '_getBaseDir',
                    '_construct',
                ]
            )
            ->setConstructorArgs($this->getModelConstructorArgs())
            ->getMock();

        $model->method('_getStoreBaseUrl')
            ->willReturn($storeBaseUrl);

        $model->method('_getDocumentRoot')
            ->willReturn($documentRoot);

        $model->method('_getBaseDir')
            ->willReturn($baseDir);

        $this->assertEquals($result, $model->getSitemapUrl($sitemapPath, $sitemapFileName));
    }

    /**
     * Data provider for Check site URL getter
     *
     * @static
     * @return array
     */
    public static function siteUrlDataProvider()
    {
        return [
            [
                'http://store.com',
                'c:\\http\\mage2\\',
                'c:\\http\\mage2\\',
                '/',
                'sitemap.xml',
                'http://store.com/sitemap.xml',
            ],
            [
                'http://store.com/store2',
                'c:\\http\\mage2\\',
                'c:\\http\\mage2\\',
                '/sitemaps/store2',
                'sitemap.xml',
                'http://store.com/sitemaps/store2/sitemap.xml'
            ],
            [
                'http://store.com/builds/regression/ee/',
                '/var/www/html',
                '/opt/builds/regression/ee',
                '/',
                'sitemap.xml',
                'http://store.com/builds/regression/ee/sitemap.xml'
            ],
            [
                'http://store.com/store2',
                'c:\\http\\mage2\\',
                'c:\\http\\mage2\\store2',
                '/sitemaps/store2',
                'sitemap.xml',
                'http://store.com/store2/sitemaps/store2/sitemap.xml'
            ],
            [
                'http://store2.store.com',
                'c:\\http\\mage2\\',
                'c:\\http\\mage2\\',
                '/sitemaps/store2',
                'sitemap.xml',
                'http://store2.store.com/sitemaps/store2/sitemap.xml'
            ],
            [
                'http://store.com',
                '/var/www/store/',
                '/var/www/store/',
                '/',
                'sitemap.xml',
                'http://store.com/sitemap.xml'
            ],
            [
                'http://store.com/store2',
                '/var/www/store/',
                '/var/www/store/store2/',
                '/sitemaps/store2',
                'sitemap.xml',
                'http://store.com/store2/sitemaps/store2/sitemap.xml'
            ]
        ];
    }

    /**
     * Check site URL getter
     *
     * @param string $storeBaseUrl
     * @param string $baseDir
     * @param string $documentRoot
     * @dataProvider getDocumentRootFromBaseDirUrlDataProvider
     */
    public function testGetDocumentRootFromBaseDir(
        string $storeBaseUrl,
        string $baseDir,
        ?string $documentRoot
    ) {
        $this->store->setCode('store');
        $this->store->method('getBaseUrl')->willReturn($storeBaseUrl);
        $this->directoryMock->method('getAbsolutePath')->willReturn($baseDir);
        /** @var Sitemap $model */
        $model = $this->getMockBuilder(Sitemap::class)
            ->onlyMethods(['_construct'])
            ->setConstructorArgs($this->getModelConstructorArgs())
            ->getMock();

        $method = new \ReflectionMethod($model, 'getDocumentRootFromBaseDir');
        $method->setAccessible(true);
        $this->assertSame($documentRoot, $method->invoke($model));
    }

    /**
     * Provides test cases for document root testing
     *
     * @return array
     */
    public static function getDocumentRootFromBaseDirUrlDataProvider(): array
    {
        return [
            [
                'http://magento.com/',
                '/var/www',
                '/var/www',
            ],
            [
                'http://magento.com/usa',
                '/var/www/usa',
                '/var/www',
            ],
            [
                'http://magento.com/usa/tx',
                '/var/www/usa/tx',
                '/var/www',
            ],
            'symlink <document root>/usa/txt -> /var/www/html' => [
                'http://magento.com/usa/tx',
                '/var/www/html',
                null,
            ],
        ];
    }

    /**
     * @param mixed $object
     * @param string $attributeName
     * @param string $value
     */
    private function setPrivatePropertyValue($object, $attributeName, $value): void
    {
        $attribute = new \ReflectionProperty($object, $attributeName);
        if ($attribute->isPublic()) {
            $object->$attributeName = $value;
        } else {
            $attribute->setAccessible(true);
            $attribute->setValue($object, $value);
            $attribute->setAccessible(false);
        }
    }
}
