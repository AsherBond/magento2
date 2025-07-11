<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */

/**
 * Adminhtml header notices block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Backend\Block\Page;

/**
 * @api
 * @since 100.0.2
 */
class Notices extends \Magento\Backend\Block\Template
{
    /**
     * Check if noscript notice should be displayed
     *
     * @return boolean
     */
    public function displayNoscriptNotice()
    {
        return $this->_scopeConfig->getValue(
            'web/browser_capabilities/javascript',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if demo store notice should be displayed
     *
     * @return boolean
     */
    public function displayDemoNotice()
    {
        return $this->_scopeConfig->getValue(
            'design/head/demonotice',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
