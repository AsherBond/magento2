<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Sales\ViewModel\Order\Entity;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Helper\SalesEntityCommentValidator;
use Magento\Sales\Model\Order\Creditmemo\Comment as CreditmemoComment;
use Magento\Sales\Model\Order\Invoice\Comment;
use Magento\Sales\Model\Order\Shipment\Comment as ShipmentComment;

/**
 * Check whether entity is allowed to edit.
 */
class EditCommentCheck implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var SalesEntityCommentValidator
     */
    private SalesEntityCommentValidator $helper;

    /**
     * @param UrlInterface $urlBuilder
     * @param SalesEntityCommentValidator $helper
     */
    public function __construct(
        UrlInterface $urlBuilder,
        SalesEntityCommentValidator $helper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
    }

    /**
     * Get edit url of comment
     *
     * @param int $id
     * @return string
     */
    public function getEditUrl($id): string
    {
        return $this->urlBuilder->getUrl('*/*/editComment', ['id' => $id]);
    }

    /**
     * Is sales entity comment allowed to edit
     *
     * @param Comment|CreditmemoComment|ShipmentComment $comment
     * @return bool
     */
    public function isCommentAllowedToEdit(
        CreditmemoComment|Comment|ShipmentComment $comment
    ): bool {
        return $this->helper->isEditCommentAllowed($comment);
    }
}
