<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Product\Option;

class UrlBuilder
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_frontendUrlBuilder;

    /**
     * @param \Magento\Framework\UrlInterface $frontendUrlBuilder
     */
    public function __construct(\Magento\Framework\UrlInterface $frontendUrlBuilder)
    {
        $this->_frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * @param string|null $route
     * @param array|null $params
     * @return string
     */
    public function getUrl($route, $params)
    {
        return $this->_frontendUrlBuilder->getUrl($route, $params);
    }
}
