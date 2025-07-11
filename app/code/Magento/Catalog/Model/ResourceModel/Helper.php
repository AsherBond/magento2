<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\ResourceModel;

/**
 * Eav Mysql resource helper model
 */
class Helper extends \Magento\Eav\Model\ResourceModel\Helper
{
    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string $modulePrefix
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resource, $modulePrefix = 'Magento_Catalog')
    {
        parent::__construct($resource, $modulePrefix);
    }
}
