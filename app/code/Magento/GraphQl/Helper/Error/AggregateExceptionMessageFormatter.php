<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Helper\Error;

use GraphQL\Error\ClientAware;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;

/**
 * Class for formatting internally-thrown errors if they match allowed exception types or using a default message if not
 */
class AggregateExceptionMessageFormatter
{
    /**
     * @var ExceptionMessageFormatterInterface[]
     */
    private array $messageFormatters;

    /**
     * @param ExceptionMessageFormatterInterface[] $messageFormatters
     */
    public function __construct(array $messageFormatters)
    {
        $this->messageFormatters = $messageFormatters;
    }

    /**
     * Format a thrown exception message if it matches one of the supplied formatters, otherwise use a default message
     *
     * @param LocalizedException $e
     * @param Phrase $defaultMessage
     * @param string $messagePrefix
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     *
     * @return ClientAware
     */
    public function getFormatted(
        LocalizedException $e,
        Phrase $defaultMessage,
        string $messagePrefix,
        Field $field,
        ContextInterface $context,
        ResolveInfo $info
    ): ClientAware {
        foreach ($this->messageFormatters as $formatter) {
            if ($formatted = $formatter->getFormatted($e, $messagePrefix, $field, $context, $info)) {
                return $formatted;
            }
        }

        $messageText = $e->getCode() ? __($e->getMessage()) : $defaultMessage;
        $messageWithPrefix = empty($messagePrefix)
            ? $messageText
            : __("$messagePrefix: %message", ['message' => $messageText]);

        return new GraphQlInputException($messageWithPrefix, $e, $e->getCode());
    }
}
