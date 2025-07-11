<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */

/**
 * Catalog textarea attribute WYSIWYG button
 */
namespace Magento\Catalog\Block\Adminhtml\Helper\Form;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Wysiwyg helper.
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Wysiwyg extends \Magento\Framework\Data\Form\Element\Textarea
{
    /**
     * Adminhtml data
     *
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendData = null;

    /**
     * Catalog data
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager = null;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var SecureHtmlRenderer
     */
    protected $secureRenderer;

    /**
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Backend\Helper\Data $backendData
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Backend\Helper\Data $backendData,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_layout = $layout;
        $this->_moduleManager = $moduleManager;
        $this->_backendData = $backendData;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
    }

    /**
     * Retrieve additional html and put it at the end of element html
     *
     * @return string
     */
    public function getAfterElementHtml()
    {
        $config = $this->_wysiwygConfig->getConfig();
        $config = json_encode($config->getData());

        $html = parent::getAfterElementHtml();
        if ($this->getIsWysiwygEnabled()) {
            $disabled = $this->getDisabled() || $this->getReadonly();
            $html .= $this->_layout->createBlock(
                \Magento\Backend\Block\Widget\Button::class,
                '',
                [
                    'data' => [
                        'label' => __('WYSIWYG Editor'),
                        'type' => 'button',
                        'disabled' => $disabled,
                        'class' => 'action-wysiwyg',
                        'onclick' => 'catalogWysiwygEditor.open(\'' . $this->_backendData->getUrl(
                            'catalog/product/wysiwyg'
                        ) . '\', \'' . $this->getHtmlId() . '\')',
                    ]
                ]
            )->toHtml();
            $scriptString = <<<HTML
require([
    'jquery',
    'mage/adminhtml/wysiwyg/tiny_mce/setup'
], function(jQuery){

var config = $config,
    editor;

editor = new wysiwygSetup(
    '{$this->getHtmlId()}',
    config
);

editor.turnOn();

jQuery('#{$this->getHtmlId()}')
    .addClass('wysiwyg-editor')
    .data(
        'wysiwygEditor',
        editor
    );
});
HTML;
            $html .= /* @noEscape */ $this->secureRenderer->renderTag('script', [], $scriptString, false);
        }

        return $html;
    }

    /**
     * Check whether wysiwyg enabled or not
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsWysiwygEnabled()
    {
        if ($this->_moduleManager->isEnabled('Magento_Cms')) {
            return (bool)($this->_wysiwygConfig->isEnabled() && $this->getEntityAttribute()->getIsWysiwygEnabled());
        }

        return false;
    }
}
