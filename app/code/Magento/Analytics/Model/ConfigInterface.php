<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */

namespace Magento\Analytics\Model;

/**
 * Interface for Analytics Config.
 */
interface ConfigInterface
{
    /**
     * Get config value by key.
     *
     * @param string|null $key
     * @param string|null $default
     * @return array
     */
    public function get($key = null, $default = null);
}
