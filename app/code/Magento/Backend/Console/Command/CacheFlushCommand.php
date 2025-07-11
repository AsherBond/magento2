<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */

namespace Magento\Backend\Console\Command;

/**
 * Command for flushing cache
 *
 * @api
 * @since 100.0.2
 */
class CacheFlushCommand extends AbstractCacheTypeManageCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('cache:flush');
        $this->setDescription('Flushes cache storage used by cache type(s)');
        parent::configure();
    }

    /**
     * Flushes cache types
     *
     * @param array $cacheTypes
     * @return void
     */
    protected function performAction(array $cacheTypes)
    {
        if ($cacheTypes === [] || in_array('full_page', $cacheTypes)) {
            $this->eventManager->dispatch('adminhtml_cache_flush_all');
        }

        $this->cacheManager->flush($cacheTypes);
    }

    /**
     * @inheritdoc
     */
    protected function getDisplayMessage()
    {
        return 'Flushed cache types:';
    }
}
