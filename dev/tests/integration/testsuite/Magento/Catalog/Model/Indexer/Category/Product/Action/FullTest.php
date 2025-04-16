<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Indexer\Category\Product\Action;

use Magento\Catalog\Model\Indexer\Category\Product\Action\Full as OriginObject;
use Magento\Framework\Module\Manager;
use Magento\TestFramework\Catalog\Model\Indexer\Category\Product\Action\Full as PreferenceObject;
use Magento\Framework\Interception\PluginListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

/**
 * Test for Magento\Catalog\Model\Indexer\Category\Product\Action\Full *
 */
class FullTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PreferenceObject
     */
    private $interceptor;

    /**
     * List of plugins
     *
     * @var PluginListInterface
     */
    private $pluginList;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Manager
     */
    private Manager $moduleManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $preferenceObject = $this->objectManager->get(PreferenceObject::class);
        $this->objectManager->addSharedInstance($preferenceObject, OriginObject::class);
        $this->interceptor = $this->objectManager->get(OriginObject::class);
        $this->pluginList = $this->objectManager->get(PluginListInterface::class);
        $this->objectManager->get(Manager::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->objectManager->removeSharedInstance(OriginObject::class);
    }

    /**
     * Test possibility to add object preference
     */
    public function testPreference()
    {
        $interceptorClassName = get_class($this->interceptor);

        // Check interceptor class name
        if ($this->moduleManager->isEnabled('Magento_Staging')) {
            $this->assertEquals(
                '\Magento\Staging\Model\Indexer\Category\Product\Action\Full\Interceptor',
                $interceptorClassName
            );
        } else {
            $this->assertEquals(PreferenceObject::class . '\Interceptor', $interceptorClassName);
        }

        //check that there are no fatal errors
        $this->pluginList->getNext($interceptorClassName, 'execute');
    }
}
