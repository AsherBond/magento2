<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */

namespace Magento\QuoteGraphQl\Model;

class ErrorMapper
{
    /**
     * Error message codes
     */
    private const ERROR_CART_NOT_FOUND = 'CART_NOT_FOUND';
    private const ERROR_CART_NOT_ACTIVE = 'CART_NOT_ACTIVE';
    private const ERROR_GUEST_EMAIL_MISSING = 'GUEST_EMAIL_MISSING';
    private const ERROR_UNABLE_TO_PLACE_ORDER = 'UNABLE_TO_PLACE_ORDER';
    private const ERROR_UNDEFINED = 'UNDEFINED';

    /**
     * Error message codes ids
     */
    private const ERROR_CART_NOT_FOUND_ID = 1001;
    private const ERROR_CART_NOT_ACTIVE_ID = 1002;
    private const ERROR_GUEST_EMAIL_MISSING_ID = 1003;
    private const ERROR_UNABLE_TO_PLACE_ORDER_ID = 1004;
    private const ERROR_UNDEFINED_ID = 1005;

    /**
     * List of error messages and codes ids.
     */
    private const MESSAGE_IDS = [
        'Could not find a cart with ID' => self::ERROR_CART_NOT_FOUND_ID,
        'The cart isn\'t active' => self::ERROR_CART_NOT_ACTIVE_ID,
        'Guest email for cart is missing' => self::ERROR_GUEST_EMAIL_MISSING_ID,
        'A server error stopped your order from being placed. Please try to place your order again' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
        'Some addresses can\'t be used due to the configurations for specific countries' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
        'The shipping method is missing. Select the shipping method and try again' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
        'Please check the billing address information' => self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
        'Enter a valid payment method and try again' => self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
        'Some of the products are out of stock' => self::ERROR_UNABLE_TO_PLACE_ORDER_ID,
    ];

    /**
     * List of error message ids and codes.
     */
    private const MESSAGE_CODE_IDS = [
            self::ERROR_CART_NOT_FOUND_ID => self::ERROR_CART_NOT_FOUND,
            self::ERROR_CART_NOT_ACTIVE_ID => self::ERROR_CART_NOT_ACTIVE,
            self::ERROR_GUEST_EMAIL_MISSING_ID => self::ERROR_GUEST_EMAIL_MISSING,
            self::ERROR_UNABLE_TO_PLACE_ORDER_ID => self::ERROR_UNABLE_TO_PLACE_ORDER,
            self::ERROR_UNDEFINED_ID => self::ERROR_UNDEFINED
    ];

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        'Could not find a cart with ID' => self::ERROR_CART_NOT_FOUND,
        'The cart isn\'t active' => self::ERROR_CART_NOT_ACTIVE,
        'Guest email for cart is missing' => self::ERROR_GUEST_EMAIL_MISSING,
        'A server error stopped your order from being placed. Please try to place your order again' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER,
        'Some addresses can\'t be used due to the configurations for specific countries' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER,
        'The shipping method is missing. Select the shipping method and try again' =>
            self::ERROR_UNABLE_TO_PLACE_ORDER,
        'Please check the billing address information' => self::ERROR_UNABLE_TO_PLACE_ORDER,
        'Enter a valid payment method and try again' => self::ERROR_UNABLE_TO_PLACE_ORDER,
        'Some of the products are out of stock' => self::ERROR_UNABLE_TO_PLACE_ORDER,
    ];

    /**
     * @param string $message
     * @return int
     */
    public static function getErrorMessageId(string $message): int
    {
        $code = self::ERROR_UNDEFINED_ID;

        $matchedCodes = array_filter(
            self::MESSAGE_IDS,
            function ($key) use ($message) {
                return str_contains($message, $key);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($matchedCodes)) {
            $code = current($matchedCodes);
        }

        return $code;
    }

    /**
     * @param int $errorId
     * @return string
     */
    public static function getMessageCode(int $errorId): string
    {
        return self::MESSAGE_CODE_IDS[$errorId] ?? self::ERROR_UNDEFINED;
    }
}
