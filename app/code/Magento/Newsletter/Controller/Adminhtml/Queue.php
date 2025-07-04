<?php
/**
 * Copyright 2011 Adobe
 * All Rights Reserved.
 */

/**
 * Newsletter queue controller
 */
namespace Magento\Newsletter\Controller\Adminhtml;

abstract class Queue extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Newsletter::queue';

    /**
     * Checks the acl permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return ($this->_authorization->isAllowed(self::ADMIN_RESOURCE) &&
            $this->_authorization->isAllowed('Magento_Newsletter::template'));
    }
}
