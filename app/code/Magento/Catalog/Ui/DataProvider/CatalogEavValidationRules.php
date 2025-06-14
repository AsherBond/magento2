<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
namespace Magento\Catalog\Ui\DataProvider;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

/**
 * Class build validation rules for catalog EAV attributes
 *
 * @api
 * @since 101.0.0
 */
class CatalogEavValidationRules
{
    /**
     * Build validation rules
     *
     * @param ProductAttributeInterface $attribute
     * @param array $data
     * @return array
     * @since 101.0.0
     */
    public function build(ProductAttributeInterface $attribute, array $data)
    {
        $rules = [];
        if (!empty($data['required'])) {
            $rules['required-entry'] = true;
        }
        if ($attribute->getFrontendInput() === 'price') {
            $rules['validate-zero-or-greater'] = true;
        }

        $validationClasses = $attribute->getFrontendClass()
            ? explode(' ', $attribute->getFrontendClass())
            : [];

        foreach ($validationClasses as $class) {
            if (preg_match('/^maximum-length-(\d+)$/', $class, $matches)) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $rules = array_merge($rules, ['max_text_length' => $matches[1]]);
                continue;
            }
            if (preg_match('/^minimum-length-(\d+)$/', $class, $matches)) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $rules = array_merge($rules, ['min_text_length' => $matches[1]]);
                continue;
            }

            $rules = $this->mapRules($class, $rules);
        }

        return $rules;
    }

    /**
     * Map classes w. rules
     *
     * @param string $class
     * @param array $rules
     * @return array
     * @since 101.0.0
     */
    protected function mapRules($class, array $rules)
    {
        switch ($class) {
            case 'validate-number':
            case 'validate-digits':
            case 'validate-email':
            case 'validate-url':
            case 'validate-trailing-hyphen':
            case 'validate-alpha':
            case 'validate-alphanum':
                $rules = array_merge($rules, [$class => true]);
                break;
        }

        return $rules;
    }
}
