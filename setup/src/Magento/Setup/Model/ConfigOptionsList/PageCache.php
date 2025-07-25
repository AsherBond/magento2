<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */

namespace Magento\Setup\Model\ConfigOptionsList;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\SelectConfigOption;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Setup\Validator\RedisConnectionValidator;

/**
 * Deployment configuration options for full page cache
 */
class PageCache implements ConfigOptionsListInterface
{
    public const INPUT_VALUE_PAGE_CACHE_REDIS = 'redis';
    public const CONFIG_VALUE_PAGE_CACHE_REDIS = \Magento\Framework\Cache\Backend\Redis::class;
    public const INPUT_VALUE_PAGE_CACHE_VALKEY = 'valkey';
    public const CONFIG_VALUE_PAGE_CACHE_VALKEY = \Magento\Framework\Cache\Backend\Valkey::class;

    public const INPUT_KEY_PAGE_CACHE_BACKEND = 'page-cache';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER = 'page-cache-redis-server';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE = 'page-cache-redis-db';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT = 'page-cache-redis-port';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD = 'page-cache-redis-password';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESS_DATA = 'page-cache-redis-compress-data';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESSION_LIB = 'page-cache-redis-compression-lib';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER = 'page-cache-valkey-server';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE = 'page-cache-valkey-db';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT = 'page-cache-valkey-port';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD = 'page-cache-valkey-password';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESS_DATA = 'page-cache-valkey-compress-data';
    public const INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESSION_LIB = 'page-cache-valkey-compression-lib';
    public const INPUT_KEY_PAGE_CACHE_ID_PREFIX = 'page-cache-id-prefix';

    public const CONFIG_PATH_PAGE_CACHE_BACKEND = 'cache/frontend/page_cache/backend';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER = 'cache/frontend/page_cache/backend_options/server';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE = 'cache/frontend/page_cache/backend_options/database';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_PORT = 'cache/frontend/page_cache/backend_options/port';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD = 'cache/frontend/page_cache/backend_options/password';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA =
        'cache/frontend/page_cache/backend_options/compress_data';
    public const CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB =
        'cache/frontend/page_cache/backend_options/compression_lib';
    public const CONFIG_PATH_PAGE_CACHE_ID_PREFIX = 'cache/frontend/page_cache/id_prefix';

    /**
     * @var array
     */
    private $defaultConfigValues = [
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER => '127.0.0.1',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE => '1',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT => '6379',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD => '',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESS_DATA => '0',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESSION_LIB => '',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER => '127.0.0.1',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE => '1',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT => '6379',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD => '',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESS_DATA => '0',
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESSION_LIB => '',
    ];

    /**
     * @var array
     */
    private $validPageCacheOptions = [
        self::INPUT_VALUE_PAGE_CACHE_REDIS,
        self::INPUT_VALUE_PAGE_CACHE_VALKEY
    ];

    /**
     * @var array
     */
    private $inputKeyToConfigPathMap = [
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER => self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE => self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT => self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD => self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESS_DATA => self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESSION_LIB =>
            self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB,
    ];

    /**
     * @var array
     */
    private $inputKeyToValkeyConfigPathMap = [
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER => self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE => self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT => self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD => self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESS_DATA => self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA,
        self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESSION_LIB =>
            self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB,
    ];

    /**
     * @var RedisConnectionValidator
     */
    private $redisValidator;

    /**
     * Construct the PageCache ConfigOptionsList
     *
     * @param RedisConnectionValidator $redisValidator
     */
    public function __construct(RedisConnectionValidator $redisValidator)
    {
        $this->redisValidator = $redisValidator;
    }

    /**
     * @inheritdoc
     */
    public function getOptions()
    {
        return [
            new SelectConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND,
                SelectConfigOption::FRONTEND_WIZARD_SELECT,
                $this->validPageCacheOptions,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND,
                'Default cache handler'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
                'Redis server'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
                'Database number for the cache'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
                'Redis server listen port'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
                'Redis server password'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESS_DATA,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA,
                'Set to 1 to compress the full page cache (use 0 to disable)'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_COMPRESSION_LIB,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB,
                'Compression library to use [snappy,lzf,l4z,zstd,gzip] (leave blank to determine automatically)'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_ID_PREFIX,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_ID_PREFIX,
                'ID prefix for cache keys'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
                'Valkey server'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
                'Database number for the cache'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
                'Valkey server listen port'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
                'Valkey server password'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESS_DATA,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESS_DATA,
                'Set to 1 to compress the full page cache (use 0 to disable)'
            ),
            new TextConfigOption(
                self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_COMPRESSION_LIB,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_COMPRESSION_LIB,
                'Compression library to use [snappy,lzf,l4z,zstd,gzip] (leave blank to determine automatically)'
            )
        ];
    }

    /**
     * @inheritdoc
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);
        if (isset($options[self::INPUT_KEY_PAGE_CACHE_ID_PREFIX])) {
            $configData->set(self::CONFIG_PATH_PAGE_CACHE_ID_PREFIX, $options[self::INPUT_KEY_PAGE_CACHE_ID_PREFIX]);
        } elseif (!$deploymentConfig->get(self::CONFIG_PATH_PAGE_CACHE_ID_PREFIX)) {
            $configData->set(self::CONFIG_PATH_PAGE_CACHE_ID_PREFIX, $this->generateCachePrefix());
        }

        if (isset($options[self::INPUT_KEY_PAGE_CACHE_BACKEND])) {
            if ($options[self::INPUT_KEY_PAGE_CACHE_BACKEND] == self::INPUT_VALUE_PAGE_CACHE_REDIS) {
                $configData->set(self::CONFIG_PATH_PAGE_CACHE_BACKEND, self::CONFIG_VALUE_PAGE_CACHE_REDIS);
                $this->setDefaultRedisConfig($deploymentConfig, $configData);
            } elseif ($options[self::INPUT_KEY_PAGE_CACHE_BACKEND] == self::INPUT_VALUE_PAGE_CACHE_VALKEY) {
                $configData->set(self::CONFIG_PATH_PAGE_CACHE_BACKEND, self::CONFIG_VALUE_PAGE_CACHE_VALKEY);
                $this->setDefaultValkeyConfig($deploymentConfig, $configData);
            } else {
                $configData->set(self::CONFIG_PATH_PAGE_CACHE_BACKEND, $options[self::INPUT_KEY_PAGE_CACHE_BACKEND]);
            }
        }

        $this->applyCacheBackendConfig($options, $configData);

        return $configData;
    }

    /**
     * Applies cache backend configuration to the config data based on the selected Redis or Valkey backend.
     *
     * @param array $options
     * @param ConfigData $configData
     *
     * @return void
     */
    private function applyCacheBackendConfig(array $options, ConfigData $configData): void
    {
        if (isset($options[self::INPUT_KEY_PAGE_CACHE_BACKEND])) {
            $map = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND] === self::INPUT_VALUE_PAGE_CACHE_VALKEY
                ? $this->inputKeyToValkeyConfigPathMap
                : $this->inputKeyToConfigPathMap;

            foreach ($map as $inputKey => $configPath) {
                if (isset($options[$inputKey])) {
                    $configData->set($configPath, $options[$inputKey]);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        $selectedBackend = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND] ?? null;
        $currentBackend = $deploymentConfig->get(PageCache::CONFIG_PATH_PAGE_CACHE_BACKEND);

        if (in_array($selectedBackend, [self::INPUT_VALUE_PAGE_CACHE_REDIS,
            self::INPUT_VALUE_PAGE_CACHE_VALKEY], true)) {
            if (!$this->validateRedisConfig($options, $deploymentConfig)) {
                $errors[] = "Invalid {$selectedBackend} configuration. Could not connect to {$selectedBackend} server.";
            }
        }

        if (!$selectedBackend && in_array($currentBackend, [self::CONFIG_VALUE_PAGE_CACHE_REDIS,
                self::CONFIG_VALUE_PAGE_CACHE_VALKEY], true)) {
            if (!$this->validateRedisConfig($options, $deploymentConfig)) {
                $errors[] = "Invalid {$currentBackend} configuration. Could not connect to {$currentBackend} server.";
            }
        }

        if ($selectedBackend && !in_array($selectedBackend, $this->validPageCacheOptions, true)) {
            $errors[] = "Invalid cache handler '{$selectedBackend}'";
        }

        return $errors;
    }

    /**
     * Validate that Redis connection succeeds for given configuration
     *
     * @param array $options
     * @param DeploymentConfig $deploymentConfig
     * @return bool
     */
    private function validateRedisConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $config = [];
        if ($options[self::INPUT_KEY_PAGE_CACHE_BACKEND] == self::INPUT_VALUE_PAGE_CACHE_VALKEY
            || $options[Cache::INPUT_KEY_CACHE_BACKEND] == Cache::INPUT_VALUE_CACHE_VALKEY) {
            $config['host'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER] ?? $deploymentConfig->get(
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
                $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_SERVER)
            );

              $config['port'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT] ?? $deploymentConfig->get(
                  self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
                  $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PORT)
              );

              $config['db'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE] ?? $deploymentConfig->get(
                  self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
                  $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_DATABASE)
              );

              $config['password'] =
                $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD] ?? $deploymentConfig->get(
                    self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
                    $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_VALKEY_PASSWORD)
                );
        } else {
            $config['host'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER] ?? $deploymentConfig->get(
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_SERVER,
                $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_SERVER)
            );

            $config['port'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT] ?? $deploymentConfig->get(
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PORT,
                $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PORT)
            );

            $config['db'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE] ?? $deploymentConfig->get(
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_DATABASE,
                $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_DATABASE)
            );

            $config['password'] = $options[self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD] ?? $deploymentConfig->get(
                self::CONFIG_PATH_PAGE_CACHE_BACKEND_PASSWORD,
                $this->getDefaultConfigValue(self::INPUT_KEY_PAGE_CACHE_BACKEND_REDIS_PASSWORD)
            );
        }
        return $this->redisValidator->isValidConnection($config);
    }

    /**
     * Set default values for Redis configuration
     *
     * @param DeploymentConfig $deploymentConfig
     * @param ConfigData $configData
     * @return ConfigData
     */
    private function setDefaultRedisConfig(DeploymentConfig $deploymentConfig, ConfigData $configData)
    {
        foreach ($this->inputKeyToConfigPathMap as $inputKey => $configPath) {
            $configData->set($configPath, $deploymentConfig->get($configPath, $this->getDefaultConfigValue($inputKey)));
        }

        return $configData;
    }

    /**
     * Set default values for Redis configuration
     *
     * @param DeploymentConfig $deploymentConfig
     * @param ConfigData $configData
     * @return ConfigData
     */
    private function setDefaultValkeyConfig(DeploymentConfig $deploymentConfig, ConfigData $configData)
    {
        foreach ($this->inputKeyToValkeyConfigPathMap as $inputKey => $configPath) {
            $configData->set($configPath, $deploymentConfig->get($configPath, $this->getDefaultConfigValue($inputKey)));
        }

        return $configData;
    }

    /**
     * Get the default value for input key
     *
     * @param string $inputKey
     * @return string
     */
    private function getDefaultConfigValue($inputKey)
    {
        if (isset($this->defaultConfigValues[$inputKey])) {
            return $this->defaultConfigValues[$inputKey];
        } else {
            return '';
        }
    }

    /**
     * Generate default cache ID prefix based on installation dir
     *
     * @return string
     */
    private function generateCachePrefix(): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return substr(\hash('sha256', dirname(__DIR__, 6)), 0, 3) . '_';
    }
}
