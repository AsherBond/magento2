<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
namespace Magento\AsynchronousOperations\Controller\Adminhtml\Notification;

use Magento\AsynchronousOperations\Model\BulkNotificationManagement;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Bulk Notification Dismiss Controller
 */
class Dismiss extends Action implements HttpPostActionInterface
{
    /**
     * @var BulkNotificationManagement
     */
    private $notificationManagement;

    /**
     * Class constructor.
     *
     * @param Context $context
     * @param BulkNotificationManagement $notificationManagement
     */
    public function __construct(
        Context $context,
        BulkNotificationManagement $notificationManagement
    ) {
        parent::__construct($context);
        $this->notificationManagement = $notificationManagement;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Logging::system_magento_logging_bulk_operations');
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $bulkUuids = [];
        foreach ((array)$this->getRequest()->getParam('uuid', []) as $bulkUuid) {
            $bulkUuids[] = (string)$bulkUuid;
        }

        $isAcknowledged = $this->notificationManagement->acknowledgeBulks($bulkUuids);

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(['']);
        if (!$isAcknowledged) {
            $result->setHttpResponseCode(400);
        }

        return $result;
    }
}
