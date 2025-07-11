<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */

namespace Magento\Backend\Block\Widget\Form\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Form fieldset default renderer
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Fieldset extends \Magento\Backend\Block\Template implements RendererInterface
{
    /**
     * @var AbstractElement
     */
    protected $_element;

    /**
     * @var string
     */
    protected $_template = 'Magento_Backend::widget/form/renderer/fieldset.phtml';

    /**
     * Get element
     *
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Render the element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->_element = $element;
        return $this->toHtml();
    }
}
