<?php
/**
 * Copyright 2025 Adobe
 * All rights reserved.
 */
declare(strict_types=1);

namespace Magento\Paypal\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Paypal\Controller\Payflow\ReturnUrl as Subject;
use Magento\Paypal\Model\PayflowlinkFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class PayflowSilentPost
{
    /**
     * @var array
     */
    protected array $allowedOrderStates = [
        Order::STATE_PROCESSING,
        Order::STATE_COMPLETE,
        Order::STATE_PAYMENT_REVIEW
    ];

    /**
     * @param RequestInterface $request
     * @param OrderFactory $orderFactory
     * @param PayflowlinkFactory $payflowlinkFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Private readonly RequestInterface $request,
        Private readonly OrderFactory $orderFactory,
        Private readonly PayflowlinkFactory $payflowlinkFactory,
        Private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Process payment if not already done via silent post
     *
     * @param Subject $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(Subject $subject): void
    {
        $data = $this->request->getParams();
        $orderId = (string)$data['INVNUM'];

        if (!$orderId) {
            return;
        }

        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $payment = $order->getPayment();

        if (in_array($order->getState(), $this->allowedOrderStates) || $payment->getLastTransId()
            || trim((string)$data['RESPMSG']) !== 'Approved' || (int)$data['RESULT'] !== 0) {
            return;
        }

        $paymentModel = $this->payflowlinkFactory->create();
        try {
            $paymentModel->process($data);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
