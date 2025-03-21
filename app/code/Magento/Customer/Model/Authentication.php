<?php
/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */
namespace Magento\Customer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Backend\App\ConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;

/**
 * Class Authentication model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Authentication implements AuthenticationInterface
{
    /**
     * Configuration path to customer lockout threshold
     */
    public const LOCKOUT_THRESHOLD_PATH = 'customer/password/lockout_threshold';

    /**
     * Configuration path to customer max login failures number
     */
    public const MAX_FAILURES_PATH = 'customer/password/lockout_failures';

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * Backend configuration interface
     *
     * @var \Magento\Backend\App\ConfigInterface
     */
    protected $backendConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerAuthUpdate
     */
    private $customerAuthUpdate;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerRegistry $customerRegistry
     * @param ConfigInterface $backendConfig
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param Encryptor $encryptor
     * @param CustomerAuthUpdate|null $customerAuthUpdate
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerRegistry $customerRegistry,
        ConfigInterface $backendConfig,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        Encryptor $encryptor,
        ?CustomerAuthUpdate $customerAuthUpdate = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerRegistry = $customerRegistry;
        $this->backendConfig = $backendConfig;
        $this->dateTime = $dateTime;
        $this->encryptor = $encryptor;
        $this->customerAuthUpdate = $customerAuthUpdate ?: ObjectManager::getInstance()->get(CustomerAuthUpdate::class);
    }

    /**
     * @inheritdoc
     */
    public function processAuthenticationFailure($customerId)
    {
        $now = new \DateTime();
        $lockThreshold = $this->getLockThreshold();
        $maxFailures =  $this->getMaxFailures();
        $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);

        if (!($lockThreshold && $maxFailures)) {
            return;
        }
        $failuresNum = (int)$customerSecure->getFailuresNum() + 1;

        $firstFailureDate = $customerSecure->getFirstFailure();
        if ($firstFailureDate) {
            $firstFailureDate = new \DateTime($firstFailureDate);
        }

        $lockThreshInterval = new \DateInterval('PT' . $lockThreshold . 'S');
        $lockExpires = $customerSecure->getLockExpires();
        $lockExpired = ($lockExpires !== null) && ($now > new \DateTime($lockExpires));
        // set first failure date when this is the first failure or the lock is expired
        if (1 === $failuresNum || !$firstFailureDate || $lockExpired) {
            $customerSecure->setFirstFailure($this->dateTime->formatDate($now));
            $failuresNum = 1;
            $customerSecure->setLockExpires(null);
            // otherwise lock customer
        } elseif ($failuresNum >= $maxFailures) {
            $customerSecure->setLockExpires($this->dateTime->formatDate($now->add($lockThreshInterval)));
        }

        $customerSecure->setFailuresNum($failuresNum);
        $this->customerAuthUpdate->saveAuth($customerId);
    }

    /**
     * @inheritdoc
     */
    public function unlock($customerId)
    {
        $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
        $customerSecure->setFailuresNum(0);
        $customerSecure->setFirstFailure(null);
        $customerSecure->setLockExpires(null);
        $this->customerAuthUpdate->saveAuth($customerId);
    }

    /**
     * Get lock threshold
     *
     * @return int
     */
    protected function getLockThreshold()
    {
        return $this->backendConfig->getValue(self::LOCKOUT_THRESHOLD_PATH) * 60;
    }

    /**
     * Get max failures
     *
     * @return int
     */
    protected function getMaxFailures()
    {
        return $this->backendConfig->getValue(self::MAX_FAILURES_PATH);
    }

    /**
     * @inheritdoc
     */
    public function isLocked($customerId)
    {
        $currentCustomer = $this->customerRegistry->retrieve($customerId);
        return $currentCustomer->isCustomerLocked();
    }

    /**
     * @inheritdoc
     */
    public function authenticate($customerId, $password)
    {
        $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
        $hash = $customerSecure->getPasswordHash() ?? '';
        if (!$this->encryptor->validateHash($password, $hash)) {
            $this->processAuthenticationFailure($customerId);
            if ($this->isLocked($customerId)) {
                throw new UserLockedException(__('The account is locked.'));
            }
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
        return true;
    }
}
