<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\UrlRewrite\Test\Constraint;

use Magento\UrlRewrite\Test\Fixture\UrlRewrite;
use Magento\UrlRewrite\Test\Page\Adminhtml\UrlrewriteIndex;
use Mtf\Constraint\AbstractConstraint;

/**
 * Class AssertUrlRewriteNotInGrid
 * Assert that url rewrite category not in grid
 */
class AssertUrlRewriteNotInGrid extends AbstractConstraint
{
    /**
     * Constraint severeness
     *
     * @var string
     */
    protected $severeness = 'low';

    /**
     * Assert that url rewrite not in grid
     *
     * @param UrlrewriteIndex $urlRewriteIndex
     * @param UrlRewrite $productRedirect
     * @return void
     */
    public function processAssert(UrlrewriteIndex $urlRewriteIndex, UrlRewrite $productRedirect)
    {
        $urlRewriteIndex->open();
        $filter = ['request_path' => $productRedirect->getRequestPath()];
        \PHPUnit_Framework_Assert::assertFalse(
            $urlRewriteIndex->getUrlRedirectGrid()->isRowVisible($filter),
            'URL Redirect with request path \'' . $productRedirect->getRequestPath() . '\' is present in grid.'
        );
    }

    /**
     * URL rewrite category not present in grid
     *
     * @return string
     */
    public function toString()
    {
        return 'URL Redirect is not present in grid.';
    }
}
