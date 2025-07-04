<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
namespace Magento\Analytics\Model\Connector\Http\Client;

use Laminas\Http\Response;
use Magento\Analytics\Model\Connector\Http\ConverterInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\HTTP\ResponseFactory;

/**
 * A CURL HTTP client.
 *
 * Sends requests via a CURL adapter.
 */
class Curl implements \Magento\Analytics\Model\Connector\Http\ClientInterface
{
    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CurlFactory $curlFactory
     * @param ResponseFactory $responseFactory
     * @param ConverterInterface $converter
     * @param LoggerInterface $logger
     */
    public function __construct(
        CurlFactory $curlFactory,
        ResponseFactory $responseFactory,
        ConverterInterface $converter,
        LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->responseFactory = $responseFactory;
        $this->converter = $converter;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function request($method, $url, array $body = [], array $headers = [], $version = '1.1')
    {
        $response = new Response();
        $response->setCustomStatusCode(Response::STATUS_CODE_CUSTOM);

        try {
            $curl = $this->curlFactory->create();
            $headers = $this->applyContentTypeHeaderFromConverter($headers);

            $curl->write($method, $url, $version, $headers, $this->converter->toBody($body));

            $result = $curl->read();

            if ($curl->getErrno()) {
                $this->logger->critical(
                    new \Exception(
                        sprintf(
                            'MBI service CURL connection error #%s: %s',
                            $curl->getErrno(),
                            $curl->getError()
                        )
                    )
                );

                return $response;
            }

            $response = $this->responseFactory->create($result);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $response;
    }

    /**
     * Apply content type header from converter
     *
     * @param array $headers
     *
     * @return array
     */
    private function applyContentTypeHeaderFromConverter(array $headers)
    {
        $contentTypeHeaderKey = array_search($this->converter->getContentTypeHeader(), $headers);
        if ($contentTypeHeaderKey === false) {
            $headers[] = $this->converter->getContentTypeHeader();
        }

        return $headers;
    }
}
