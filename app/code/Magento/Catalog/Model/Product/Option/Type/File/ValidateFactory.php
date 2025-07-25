<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */

namespace Magento\Catalog\Model\Product\Option\Type\File;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class ValidateFactory. Creates Validator with type "ExistingValidate"
 */
class ValidateFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Main factory method
     *
     * @return ExistingValidate
     */
    public function create()
    {
        return $this->objectManager->create(ExistingValidate::class);
    }
}
