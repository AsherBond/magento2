<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
namespace Magento\Backend\Model\Auth;

/**
 * Backend Auth Storage interface
 *
 * @api
 * @since 100.0.2
 */
interface StorageInterface
{
    /**
     * Perform login specific actions
     *
     * @return $this
     * @abstract
     */
    public function processLogin();

    /**
     * Perform logout specific actions
     *
     * @return $this
     * @abstract
     */
    public function processLogout();

    /**
     * Check if user is logged in
     *
     * @return bool
     * @abstract
     */
    public function isLoggedIn();

    /**
     * Prolong storage lifetime
     *
     * @return void
     * @abstract
     */
    public function prolong();
}
