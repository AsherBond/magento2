<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Config\Source;

/**
 * Catalog products per page on List mode source
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ListPerPage implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $_pagerOptions;

    /**
     * Constructor
     *
     * @param string $options
     */
    public function __construct($options)
    {
        $this->_pagerOptions = $options !== null ? explode(',', $options) : [];
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $output = [];
        foreach ($this->_pagerOptions as $option) {
            $output[] = ['value' => $option, 'label' => $option];
        }
        return $output;
    }
}
