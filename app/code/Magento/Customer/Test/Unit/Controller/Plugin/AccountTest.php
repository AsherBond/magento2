<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Customer\Test\Unit\Controller\Plugin;

use Closure;
use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Controller\Plugin\Account;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    /**
     * @var string
     */
    const EXPECTED_VALUE = 'expected_value';

    /**
     * @var Account
     */
    protected $plugin;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var AccountInterface|MockObject
     */
    protected $actionMock;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $actionFlagMock;

    /**
     * @var ResultInterface|MockObject
     */
    private $resultMock;

    protected function setUp(): void
    {
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->addMethods(['setNoReferer', 'unsNoReferer'])
            ->onlyMethods(['authenticate'])
            ->getMock();

        $this->actionMock = $this->getMockBuilder(AccountInterface::class)
            ->addMethods(['getActionFlag'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getActionName'])
            ->getMock();

        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->getMockForAbstractClass();

        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $action
     * @param array $allowedActions
     * @param boolean $isAllowed
     *
     * @dataProvider beforeExecuteDataProvider
     */
    public function testAroundExecuteInterruptsOriginalCallWhenNotAllowed(
        string $action,
        array $allowedActions,
        bool $isAllowed
    ) {
        /** @var callable|MockObject $proceedMock */
        $proceedMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();

        $closureMock = Closure::fromCallable($proceedMock);

        $this->requestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn($action);

        if ($isAllowed) {
            $proceedMock->expects($this->once())->method('__invoke')->willReturn($this->resultMock);
        } else {
            $proceedMock->expects($this->never())->method('__invoke');
        }

        $plugin = new Account($this->requestMock, $this->sessionMock, $allowedActions);
        $result = $plugin->aroundExecute($this->actionMock, $closureMock);

        if ($isAllowed) {
            $this->assertSame($this->resultMock, $result);
        } else {
            $this->assertNull($result);
        }
    }

    /**
     * @return array
     */
    public static function beforeExecuteDataProvider()
    {
        return [
            [
                'action' => 'TestAction',
                'allowedActions' => ['TestAction'],
                'isAllowed' => true
            ],
            [
                'action' => 'testaction',
                'allowedActions' => ['testaction'],
                'isAllowed' => true
            ],
            [
                'action' => 'wrongaction',
                'allowedActions' => ['testaction'],
                'isAllowed' => false
            ],
            [
                'action' => 'wrongaction',
                'allowedActions' => ['testaction'],
                'isAllowed' => false
            ],
            [
                'action' => 'wrongaction',
                'allowedActions' => [],
                'isAllowed' => false
            ],
        ];
    }
}
