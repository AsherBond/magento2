<?php
/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Checkout\Controller\Express;

/**
 * Interface \Magento\Checkout\Controller\Express\RedirectLoginInterface
 *
 * @api
 */
interface RedirectLoginInterface
{
    /**
     * Returns a list of action flags [flag_key] => boolean
     * @return array
     */
    public function getActionFlagList();

    /**
     * Returns before_auth_url redirect parameter for customer session
     * @return string|null
     */
    public function getCustomerBeforeAuthUrl();

    /**
     * Returns login url parameter for redirect
     * @return string|null
     */
    public function getLoginUrl();

    /**
     * Returns action name which requires redirect
     * @return string|null
     */
    public function getRedirectActionName();

    /**
     * Retrieve response object
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function getResponse();
}
