<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Attribute\Backend;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;

/**
 * Consumer for export message.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumerWebsiteAssign
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Flat\Processor
     */
    private $productFlatIndexerProcessor;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    private $productAction;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    private $productPriceIndexerProcessor;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor
     * @param \Magento\Catalog\Model\Product\Action $action
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param EntityManager $entityManager
     */
    public function __construct(
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        \Magento\Catalog\Model\Product\Action $action,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        EntityManager $entityManager
    ) {
        $this->productFlatIndexerProcessor = $productFlatIndexerProcessor;
        $this->productAction = $action;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->entityManager = $entityManager;
    }

    /**
     * Process
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws \Exception
     *
     * @return void
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);
            $this->execute($data);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __($e->getMessage());
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during product attributes update. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during product attributes update. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Update website in products
     *
     * @param array $productIds
     * @param array $websiteRemoveData
     * @param array $websiteAddData
     *
     * @return void
     */
    private function updateWebsiteInProducts($productIds, $websiteRemoveData, $websiteAddData): void
    {
        if ($websiteRemoveData) {
            $this->productAction->updateWebsites($productIds, $websiteRemoveData, 'remove');
        }
        if ($websiteAddData) {
            $this->productAction->updateWebsites($productIds, $websiteAddData, 'add');
        }
    }

    /**
     * Execute
     *
     * @param array $data
     *
     * @return void
     */
    private function execute($data): void
    {
        $this->updateWebsiteInProducts(
            $data['product_ids'],
            $data['attributes']['website_detach'],
            $data['attributes']['website_assign']
        );
        $this->productPriceIndexerProcessor->reindexList($data['product_ids']);
        $this->productFlatIndexerProcessor->reindexList($data['product_ids']);
    }
}
