<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
namespace Magento\Backend\Block;

/**
 * Base for all admin blocks.
 *
 * Avoid using this class. Will be deprecated
 *
 * Marked as public API because it is actively used now.
 * @api
 * @since 100.0.2
 */
class AbstractBlock extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        $this->_authorization = $context->getAuthorization();
        parent::__construct($context, $data);
    }
}
