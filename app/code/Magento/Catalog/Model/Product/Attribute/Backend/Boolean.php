<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Product\Attribute\Backend;

use Magento\Catalog\Model\Product\Attribute\Source\Boolean as BooleanSource;

/**
 * Product attribute for enable/disable option
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Boolean extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * Set attribute default value if value empty
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getName();
        if ($object->getData('use_config_' . $attributeCode)) {
            $object->setData($attributeCode, BooleanSource::VALUE_USE_CONFIG);
            return $this;
        }

        return parent::beforeSave($object);
    }
}
