<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
namespace Magento\TestModuleAsyncStomp\Model;

class WildCardHandler
{
    /**
     * @param AsyncTestData $simpleDataItem
     */
    public function methodOne(AsyncTestData $simpleDataItem)
    {
        file_put_contents(
            $simpleDataItem->getTextFilePath(),
            'WildCardHandler::methodOne - stomp.wildcard.queue.one - ' . $simpleDataItem->getValue() . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * @param AsyncTestData $simpleDataItem
     */
    public function methodTwo(AsyncTestData $simpleDataItem)
    {
        file_put_contents(
            $simpleDataItem->getTextFilePath(),
            'WildCardHandler::methodTwo - stomp.wildcard.queue.two - ' . $simpleDataItem->getValue() . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * @param AsyncTestData $simpleDataItem
     */
    public function methodThree(AsyncTestData $simpleDataItem)
    {
        file_put_contents(
            $simpleDataItem->getTextFilePath(),
            'WildCardHandler::methodThree - stomp.wildcard.queue.three - ' . $simpleDataItem->getValue() . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * @param AsyncTestData $simpleDataItem
     */
    public function methodFour(AsyncTestData $simpleDataItem)
    {
        file_put_contents(
            $simpleDataItem->getTextFilePath(),
            'WildCardHandler::methodFour - stomp.wildcard.queue.four - ' . $simpleDataItem->getValue() . PHP_EOL,
            FILE_APPEND
        );
    }
}
