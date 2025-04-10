<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\OrderCancellationGraphQl\Plugin\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\OrderCancellationGraphQl\Model\Validator\ValidateOrderCancellationReason;
use Magento\Sales\Model\Order;
use Magento\OrderCancellationGraphQl\Model\CancelOrderGuest as Subject;

/**
 * Plugin for guest cancel order model
 */
class CancelOrderGuest
{
    /**
     * @var ValidateOrderCancellationReason $validateOrderCancellationReason
     */
    private ValidateOrderCancellationReason $validateOrderCancellationReason;

    /**
     * @param ValidateOrderCancellationReason $validateOrderCancellationReason
     */
    public function __construct(
        ValidateOrderCancellationReason $validateOrderCancellationReason
    ) {
        $this->validateOrderCancellationReason = $validateOrderCancellationReason;
    }

    /**
     * Before plugin for reason validation
     *
     * @param Subject $subject
     * @param Order $order
     * @param array $input
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        Subject $subject,
        Order $order,
        array $input
    ) {
        if (!empty($input['reason'])) {
            if ($this->validateOrderCancellationReason->validateReason($order, $input['reason'])) {
                throw new GraphQlInputException(__('Order cancellation reason is invalid.'));
            }
        }

        return [$order, $input];
    }
}
