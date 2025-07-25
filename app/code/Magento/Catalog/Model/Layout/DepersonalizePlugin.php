<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Layout;

use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;

/**
 * Depersonalize customer data.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class DepersonalizePlugin
{
    /**
     * @var DepersonalizeChecker
     */
    private $depersonalizeChecker;

    /**
     * @var CatalogSession
     */
    private $catalogSession;

    /**
     * @param DepersonalizeChecker $depersonalizeChecker
     * @param CatalogSession $catalogSession
     */
    public function __construct(
        DepersonalizeChecker $depersonalizeChecker,
        CatalogSession $catalogSession
    ) {
        $this->depersonalizeChecker = $depersonalizeChecker;
        $this->catalogSession = $catalogSession;
    }

    /**
     * Change sensitive customer data if the depersonalization is needed.
     *
     * @param LayoutInterface $subject
     * @return void
     */
    public function afterGenerateElements(LayoutInterface $subject)
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($subject)) {
            $this->catalogSession->clearStorage();
        }
    }
}
