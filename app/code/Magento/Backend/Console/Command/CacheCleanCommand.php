<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */

namespace Magento\Backend\Console\Command;

/**
 * Command for cleaning cache
 *
 * @api
 * @since 100.0.2
 */
class CacheCleanCommand extends AbstractCacheTypeManageCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('cache:clean');
        $this->setDescription('Cleans cache type(s)');
        parent::configure();
    }

    /**
     * Cleans cache types
     *
     * @param array $cacheTypes
     * @return void
     */
    protected function performAction(array $cacheTypes)
    {
        if ($cacheTypes === [] || in_array('full_page', $cacheTypes)) {
            $this->eventManager->dispatch('adminhtml_cache_flush_system');
        }

        $this->cacheManager->clean($cacheTypes);
    }

    /**
     * @inheritdoc
     */
    protected function getDisplayMessage()
    {
        return 'Cleaned cache types:';
    }
}
