<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
namespace Magento\Analytics\Model\Connector;

use Laminas\Http\Request;
use Magento\Analytics\Model\AnalyticsToken;
use Magento\Analytics\Model\Connector\Http\ResponseResolver;
use Magento\Analytics\Model\IntegrationManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * SignUp merchant for Free Tier project
 */
class SignUpCommand implements CommandInterface
{
    /**
     * @var string
     */
    private $signUpUrlPath = 'analytics/url/signup';

    /**
     * @var AnalyticsToken
     */
    private $analyticsToken;

    /**
     * @var IntegrationManager
     */
    private $integrationManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Http\ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseResolver
     */
    private $responseResolver;

    /**
     * SignUpCommand constructor.
     *
     * @param AnalyticsToken $analyticsToken
     * @param IntegrationManager $integrationManager
     * @param ScopeConfigInterface $config
     * @param Http\ClientInterface $httpClient
     * @param LoggerInterface $logger
     * @param ResponseResolver $responseResolver
     */
    public function __construct(
        AnalyticsToken $analyticsToken,
        IntegrationManager $integrationManager,
        ScopeConfigInterface $config,
        Http\ClientInterface $httpClient,
        LoggerInterface $logger,
        ResponseResolver $responseResolver
    ) {
        $this->analyticsToken = $analyticsToken;
        $this->integrationManager = $integrationManager;
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->responseResolver = $responseResolver;
    }

    /**
     * Executes signUp command
     *
     * During this call Magento generates or retrieves access token for the integration user
     * In case successful generation Magento activates user and sends access token to MA
     * As the response, Magento receives a token to MA
     * Magento stores this token in System Configuration
     *
     * This method returns true in case of success
     *
     * @return bool
     */
    public function execute()
    {
        $result = false;
        $integrationToken = $this->integrationManager->generateToken();
        if ($integrationToken) {
            $this->integrationManager->activateIntegration();
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                $this->config->getValue($this->signUpUrlPath),
                [
                    "token" => $integrationToken->getData('token'),
                    "url" => $this->config->getValue(Store::XML_PATH_SECURE_BASE_URL),
                ]
            );

            $result = $this->responseResolver->getResult($response);
            if (!$result) {
                $this->logger->warning(
                    sprintf(
                        'Subscription for MBI service has been failed. An error occurred during token exchange: %s.'
                        . ' Content-Type: %s',
                        !empty($response->getBody()) ? $response->getBody() : 'Response body is empty',
                        $response->getHeaders()->has('Content-Type') ?
                            $response->getHeaders()->get('Content-Type')->getFieldValue() :
                            ''
                    )
                );
            }
        }

        return (bool)$result;
    }
}
