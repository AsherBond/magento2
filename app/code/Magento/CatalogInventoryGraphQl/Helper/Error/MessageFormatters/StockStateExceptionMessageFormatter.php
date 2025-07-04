<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryGraphQl\Helper\Error\MessageFormatters;

use Magento\CatalogInventory\Model\StockStateException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Helper\Error\ExceptionMessageFormatterInterface;

/**
 * Check if an internally-thrown exception is from an item stock status issue and re-throw with the message intact if so
 */
class StockStateExceptionMessageFormatter implements ExceptionMessageFormatterInterface
{
    /**
     * If the thrown exception was from an item stock status issue, allow the message to go through
     *
     * @param LocalizedException $e
     * @param string $messagePrefix
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     *
     * @return GraphQlInputException|null
     */
    public function getFormatted(
        LocalizedException $e,
        string $messagePrefix,
        Field $field,
        ContextInterface $context,
        ResolveInfo $info
    ): ?GraphQlInputException {
        if ($e instanceof StockStateException) {
            return new GraphQlInputException(__("$messagePrefix: %message", ['message' => $e->getMessage()]), $e);
        }
        return null;
    }
}
