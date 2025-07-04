<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */

namespace Magento\AdvancedPricingImportExport\Controller\Adminhtml\Export;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\ImportExport\Controller\Adminhtml\Export as ExportController;
use Magento\Framework\Controller\ResultFactory;
use Magento\AdvancedPricingImportExport\Model\Export\AdvancedPricing as ExportAdvancedPricing;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\ImportExport\Model\Export\EntityFiltersProviderInterface;

class GetFilter extends ExportController implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param EntityFiltersProviderInterface $filtersProvider
     */
    public function __construct(
        Context $context,
        private readonly EntityFiltersProviderInterface $filtersProvider
    ) {
        parent::__construct($context);
    }

    /**
     * Get grid-filter of entity attributes action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if ($this->getRequest()->isXmlHttpRequest() && $data) {
            try {
                if ($data['entity'] == ExportAdvancedPricing::ENTITY_ADVANCED_PRICING) {
                    $data['entity'] = CatalogProduct::ENTITY;
                }
                /** @var \Magento\Framework\View\Result\Layout $resultLayout */
                $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
                /** @var $attrFilterBlock \Magento\ImportExport\Block\Adminhtml\Export\Filter */
                $attrFilterBlock = $resultLayout->getLayout()->getBlock('export.filter');
                /** @var $export \Magento\ImportExport\Model\Export */
                $export = $this->_objectManager->create(\Magento\ImportExport\Model\Export::class);
                $export->setData($data);
                $attrFilterBlock->prepareCollection($this->filtersProvider->getFilters($export));
                return $resultLayout;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Please correct the data sent.'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }
}
