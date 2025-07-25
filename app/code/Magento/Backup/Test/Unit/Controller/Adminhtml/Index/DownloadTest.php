<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Backup\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backup\Controller\Adminhtml\Index\Download;
use Magento\Backup\Helper\Data;
use Magento\Backup\Model\Backup;
use Magento\Backup\Model\BackupFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ImportExport\Model\ResourceModel\Helper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Magento\Backup\Controller\Adminhtml\Index\Download
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DownloadTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Download
     */
    protected $downloadController;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var BackupFactory|MockObject
     */
    protected $backupModelFactoryMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupModelMock;

    /**
     * @var Data|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var FileFactory|MockObject
     */
    protected $fileFactoryMock;

    /**
     * @var RawFactory|MockObject
     */
    protected $resultRawFactoryMock;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactoryMock;

    /**
     * @var Raw|MockObject
     */
    protected $resultRawMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Helper
     */
    protected $resourceHelper;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $this->backupModelFactoryMock = $this->getMockBuilder(BackupFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->backupModelMock = $this->getMockBuilder(Backup::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTime', 'getPath'])
            ->onlyMethods(['exists', 'getSize', 'output', 'getFileName'])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fileFactoryMock = $this->getMockBuilder(FileFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRawFactoryMock = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultRedirectFactoryMock = $this->getMockBuilder(
            RedirectFactory::class
        )->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultRawMock = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceHelper = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->context = $this->objectManager->getObject(
            Context::class,
            [
                'objectManager' => $this->objectManagerMock,
                'request' => $this->requestMock,
                'response' => $this->responseMock,
                'resultRedirectFactory' => $this->resultRedirectFactoryMock
            ]
        );

        $objects = [
            [
                Data::class,
                $this->dataHelperMock
            ]
        ];
        $this->objectManager->prepareObjectManager($objects);

        $this->downloadController = $this->objectManager->getObject(
            Download::class,
            [
                'helper' => $this->resourceHelper,
                'context' => $this->context,
                'backupModelFactory' => $this->backupModelFactoryMock,
                'fileFactory' => $this->fileFactoryMock,
                'resultRawFactory' => $this->resultRawFactoryMock,
            ]
        );
    }

    /**
     * @covers \Magento\Backup\Controller\Adminhtml\Index\Download::execute
     */
    public function testExecuteBackupFound()
    {
        $time = 1;
        $type = 'db';
        $filename = 'filename';
        $size = 10;
        $path = 'testpath';
        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('getPath')
            ->willReturn($path);
        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('getFileName')
            ->willReturn($filename);
        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('getTime')
            ->willReturn($time);
        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('exists')
            ->willReturn(true);
        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('getSize')
            ->willReturn($size);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['time', null, $time],
                    ['type', null, $type]
                ]
            );
        $this->backupModelFactoryMock->expects($this->once())
            ->method('create')
            ->with($time, $type)
            ->willReturn($this->backupModelMock);
        $this->dataHelperMock->expects($this->once())
            ->method('generateBackupDownloadName')
            ->with($this->backupModelMock)
            ->willReturn($filename);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(Data::class)
            ->willReturn($this->dataHelperMock);
        $this->fileFactoryMock->expects($this->once())
            ->method('create')->with(
                $filename,
                ['type' => 'filename', 'value' => $path . '/' . $filename],
                DirectoryList::VAR_DIR,
                'application/octet-stream',
                $size
            )
            ->willReturn($this->responseMock);

        $this->assertSame($this->responseMock, $this->downloadController->execute());
    }

    /**
     * @covers \Magento\Backup\Controller\Adminhtml\Index\Download::execute
     * @param int $time
     * @param bool $exists
     * @param int $existsCount
     * @dataProvider executeBackupNotFoundDataProvider
     */
    public function testExecuteBackupNotFound($time, $exists, $existsCount)
    {
        $type = 'db';

        $this->backupModelMock->expects($this->atLeastOnce())
            ->method('getTime')
            ->willReturn($time);
        $this->backupModelMock->expects($this->exactly($existsCount))
            ->method('exists')
            ->willReturn($exists);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['time', null, $time],
                    ['type', null, $type]
                ]
            );
        $this->backupModelFactoryMock->expects($this->once())
            ->method('create')
            ->with($time, $type)
            ->willReturn($this->backupModelMock);
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('backup/*');
        $this->resultRedirectFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirectMock);

        $this->assertSame($this->resultRedirectMock, $this->downloadController->execute());
    }

    /**
     * @return array
     */
    public static function executeBackupNotFoundDataProvider()
    {
        return [
            [1, false, 1],
            [0, true, 0],
            [0, false, 0]
        ];
    }
}
