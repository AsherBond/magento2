<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Analytics\Model\Connector;

/**
 * Introduces family of integration calls.
 * Each implementation represents call to external service.
 *
 * @api
 */
interface CommandInterface
{
    /**
     * Execute call to external service
     *
     * Information about destination and arguments appears from config
     *
     * @return bool
     */
    public function execute();
}
