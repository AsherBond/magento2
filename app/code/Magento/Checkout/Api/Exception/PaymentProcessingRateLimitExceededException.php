<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\Checkout\Api\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Thrown when too many payment processing/saving requests have been initiated by a user.
 *
 * @api
 */
class PaymentProcessingRateLimitExceededException extends LocalizedException
{

}
