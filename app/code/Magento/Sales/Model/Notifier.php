<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Sales\Model;

use Magento\Sales\Model\Resource\Order\Status\History\CollectionFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Logger;
use Magento\Framework\Mail\Exception;

/**
 * Class Notifier
 * @package Magento\Sales\Model
 */
class Notifier extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var CollectionFactory
     */
    protected $historyCollectionFactory;

    /**
     * @var \Magento\Framework\Logger
     */
    protected $logger;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @param CollectionFactory $historyCollectionFactory
     * @param Logger $logger
     * @param OrderSender $orderSender
     */
    public function __construct(
        CollectionFactory $historyCollectionFactory,
        Logger $logger,
        OrderSender $orderSender
    ) {
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->logger = $logger;
        $this->orderSender = $orderSender;
    }

    /**
     * Notify user
     *
     * @param Order $order
     * @return bool
     * @throws \Magento\Framework\Mail\Exception
     */
    public function notify(\Magento\Sales\Model\Order $order)
    {
        try {
            $this->orderSender->send($order);
            if (!$order->getEmailSent()) {
                return false;
            }
            $historyItem = $this->historyCollectionFactory->create()->getUnnotifiedForInstance(
                $order,
                \Magento\Sales\Model\Order::HISTORY_ENTITY_NAME
            );
            if ($historyItem) {
                $historyItem->setIsCustomerNotified(1);
                $historyItem->save();
            }
        } catch (Exception $e) {
            $this->logger->logException($e);
            return false;
        }
        return true;
    }
}
