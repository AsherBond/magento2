<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */

namespace Magento\Catalog\Model\Product\Type\Price;

/**
 * Product type price factory
 *
 * @api
 */
class Factory
{
    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create price model for product of particular type
     *
     * @param string $className
     * @param array $data
     * @return \Magento\Catalog\Model\Product\Type\Price
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($className, array $data = [])
    {
        $price = $this->_objectManager->create($className, $data);

        if (!$price instanceof \Magento\Catalog\Model\Product\Type\Price) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 doesn\'t extends \Magento\Catalog\Model\Product\Type\Price', $className)
            );
        }
        return $price;
    }
}
