<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Paypal\Controller\Adminhtml\Express;

use Magento\Framework\App\RequestInterface;
use Magento\Paypal\Model\Api\Nvp;
use Magento\Paypal\Model\Api\Type\Factory as ApiFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\OrderValidatorInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Validation\CanInvoice;
use Magento\Sales\Model\ValidatorResultInterface;

/**
 * Makes a test of the payment authorization for Paypal Express when payment action is order.
 *
 * @magentoAppArea adminhtml
 */
class AuthorizationTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resource = 'Magento_Paypal::authorization';
        $this->uri = 'backend/paypal/express/authorization';

        parent::setUp();
        $this->createSharedInstances();
    }

    /**
     * Negative scenario for controller calls.
     *
     * @return void
     */
    public function testNoOrderPassed()
    {
        $this->dispatch('backend/paypal/express/authorization');
        $this->assertRedirect($this->stringContains('backend/sales/order/index'));
    }

    /**
     * Test of authorization of full order amount.
     *
     * @magentoConfigFixture current_store payment/paypal_express/active 1
     * @magentoConfigFixture current_store payment/paypal_express/payment_action Order
     * @magentoDataFixture Magento/Paypal/_files/order_express_payment_action_order.php
     * @magentoDbIsolation enabled
     * @return void
     */
    public function testAuthorization()
    {
        /** @var Order $order */
        $order = $this->_objectManager->create(Order::class);
        $order->loadByIncrementId('100000001');

        $orderValidator = $this->_objectManager->create(OrderValidatorInterface::class);

        /** @var ValidatorResultInterface $validationResult */
        $validationResult = $orderValidator->validate($order, [CanInvoice::class]);
        $validationMessages = $validationResult->getMessages();

        $this->assertCount(1, $validationMessages);
        $this->assertEquals(
            'An invoice cannot be created when none of authorization transactions available.',
            $validationMessages[0]
        );

        /** @var RequestInterface $request */
        $request = $this->getRequest();
        $request->setParam('order_id', $order->getId());

        $this->dispatch('backend/paypal/express/authorization');

        $orderRepository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($order->getId());

        /** @var Payment $payment */
        $payment = $order->getPayment();

        /** @var ValidatorResultInterface $validationResult */
        $validationResult = $orderValidator->validate($order, [CanInvoice::class]);

        $this->assertInstanceOf(
            Transaction::class,
            $payment->getAuthorizationTransaction()
        );
        $this->assertEquals($order->getBaseGrandTotal(), $payment->getAmountAuthorized());
        $this->assertEmpty($validationResult->getMessages());
        $this->assertRedirect($this->stringContains('backend/sales/order/view/order_id/' . $order->getId()));
    }

    /**
     * Creates required shared instances.
     */
    private function createSharedInstances()
    {
        $nvpMock = $this->getMockBuilder(Nvp::class)
            ->onlyMethods(['call'])
            ->disableOriginalConstructor()
            ->getMock();

        $nvpMock->method('call')
            ->willReturn([]);

        $apiFactoryMock = $this->getMockBuilder(ApiFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $apiFactoryMock->method('create')
            ->with(Nvp::class)
            ->willReturn($nvpMock);

        $this->_objectManager->addSharedInstance($apiFactoryMock, ApiFactory::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->_objectManager->removeSharedInstance(ApiFactory::class);
        parent::tearDown();
    }
}
