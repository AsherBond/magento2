<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Bundle\Api;

/**
 * Option manager for bundle products
 *
 * @api
 * @since 100.0.2
 */
interface ProductOptionManagementInterface
{
    /**
     * Add new option for bundle product
     *
     * @param \Magento\Bundle\Api\Data\OptionInterface $option
     * @return int
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function save(\Magento\Bundle\Api\Data\OptionInterface $option);
}
