<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\GiftMessage\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Gift Message Observer Model
 */
class MultishippingEventCreateOrdersObserver implements ObserverInterface
{
    /**
     * Set gift message to order from address in multiple addresses checkout.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getEvent()->getOrder()->setGiftMessageId($observer->getEvent()->getAddress()->getGiftMessageId());

        return $this;
    }
}
