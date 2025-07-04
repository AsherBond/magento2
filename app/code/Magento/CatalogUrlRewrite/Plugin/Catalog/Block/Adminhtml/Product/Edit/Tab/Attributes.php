<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\CatalogUrlRewrite\Plugin\Catalog\Block\Adminhtml\Product\Edit\Tab;

class Attributes
{
    /**
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $subject
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $result
     * @return \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes
     */
    public function afterSetForm(
        \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $subject,
        \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $result
    ) {
        $form = $subject->getForm();
        $field = $form->getElement('url_key');
        if ($field) {
            $field->setRenderer(
                $subject->getLayout()->createBlock(\Magento\CatalogUrlRewrite\Block\UrlKeyRenderer::class)
            );
        }
        return $result;
    }
}
