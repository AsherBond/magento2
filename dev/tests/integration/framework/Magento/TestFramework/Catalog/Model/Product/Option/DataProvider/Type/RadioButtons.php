<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\Catalog\Model\Product\Option\DataProvider\Type;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\TestFramework\Catalog\Model\Product\Option\DataProvider\Type\AbstractSelect;

/**
 * Data provider for custom options from select group with type "Radio Buttons".
 */
class RadioButtons extends AbstractSelect
{
    /**
     * @inheritdoc
     */
    protected static function getType(): string
    {
        return ProductCustomOptionInterface::OPTION_TYPE_RADIO;
    }
}
