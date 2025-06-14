<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
namespace Magento\CatalogSearch\Model\ResourceModel;

/**
 * CatalogSearch Index Engine Interface
 *
 * @api
 * @since 100.0.2
 */
interface EngineInterface
{
    /**
     * Field prefix constant
     *
     * @deprecated mysql search engine has been removed
     * @see \Magento\Framework\Search\EngineResolverInterface
     */
    const FIELD_PREFIX = 'attr_';

    /**
     * Scope identifier constant
     *
     * @deprecated since using engine resolver
     * @see \Magento\Framework\Search\EngineResolverInterface
     */
    const SCOPE_IDENTIFIER = 'scope';

    /**
     * Configuration path by which current indexer handler stored
     *
     * @deprecated since using engine resolver
     * @see \Magento\Framework\Search\EngineResolverInterface
     */
    const CONFIG_ENGINE_PATH = 'catalog/search/engine';

    /**
     * Retrieve allowed visibility values for current engine
     *
     * @return array
     */
    public function getAllowedVisibility();

    /**
     * Define if current search engine supports advanced index
     *
     * @return bool
     */
    public function allowAdvancedIndex();

    /**
     * Prepare attribute value to store in index
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param mixed $value
     * @return mixed
     */
    public function processAttributeValue($attribute, $value);

    /**
     * Prepare index array as a string glued by separator
     *
     * @param array $index
     * @param string $separator
     * @return array
     */
    public function prepareEntityIndex($index, $separator = ' ');
}
