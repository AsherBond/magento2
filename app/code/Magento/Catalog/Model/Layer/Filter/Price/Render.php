<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Model\Layer\Filter\Price;

use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;

class Render
{
    const XML_PATH_ONE_PRICE_INTERVAL = 'catalog/layered_navigation/one_price_interval';

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DataBuilder
     */
    private $itemDataBuilder;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param ScopeConfigInterface $scopeConfig
     * @param DataBuilder $itemDataBuilder
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        ScopeConfigInterface $scopeConfig,
        DataBuilder $itemDataBuilder
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->scopeConfig = $scopeConfig;
        $this->itemDataBuilder = $itemDataBuilder;
    }

    /**
     * Prepare text of range label
     *
     * @param float|string $fromPrice
     * @param float|string $toPrice
     * @return float|\Magento\Framework\Phrase
     */
    public function renderRangeLabel($fromPrice, $toPrice)
    {
        $formattedFromPrice = $this->priceCurrency->format($fromPrice);
        $priceInterval = $this->scopeConfig->getValue(
            self::XML_PATH_ONE_PRICE_INTERVAL,
            ScopeInterface::SCOPE_STORE
        );
        if ($toPrice === '') {
            return __('%1 and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice && $priceInterval) {
            return $formattedFromPrice;
        } else {
            if ($fromPrice != $toPrice) {
                $toPrice -= .01;
            }

            return __('%1 - %2', $formattedFromPrice, $this->priceCurrency->format($toPrice));
        }
    }

    /**
     * Prepare range data
     *
     * @param int $range
     * @param int[] $dbRanges
     * @return array
     */
    public function renderRangeData($range, $dbRanges)
    {
        if (empty($dbRanges)) {
            return [];
        }

        foreach ($dbRanges as $index => $count) {
            $fromPrice = $index == 1 ? 0 : ($index - 1) * $range;
            $toPrice = $index * $range;
            $this->itemDataBuilder->addItemData(
                $this->renderRangeLabel($fromPrice, $toPrice),
                $fromPrice . '-' . $toPrice,
                $count
            );
        }
        return $this->itemDataBuilder->build();
    }
}
