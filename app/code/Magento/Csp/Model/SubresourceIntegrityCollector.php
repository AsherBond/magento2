<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Csp\Model;

/**
 * Collector of Integrity objects.
 */
class SubresourceIntegrityCollector
{
    /**
     * @var array
     */
    private array $data = [];

    /**
     * Collects given Integrity object.
     *
     * @param SubresourceIntegrity $integrity
     *
     * @return void
     */
    public function collect(SubresourceIntegrity $integrity): void
    {
        $this->data[] = $integrity;
    }

    /**
     * Provides all collected Integrity objects.
     *
     * @return SubresourceIntegrity[]
     */
    public function release(): array
    {
        return $this->data;
    }

    /**
     * Clear all collected data.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }
}
