<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Backend\Controller\Adminhtml\Dashboard;

class CustomersNewest extends AjaxBlock
{
    /**
     * Gets latest customers list
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(\Magento\Backend\Block\Dashboard\Tab\Customers\Newest::class)
            ->toHtml();
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($output);
    }
}
