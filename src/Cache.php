<?php


namespace openWebX\openCache;

use Exception;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;

class Cache {


    /**
     * @var Psr16Adapter|null
     */
    public static ?Psr16Adapter $cache = null;
    /**
     * @var string
     */
    public static string $defaultDriver = 'Files';

    /**
     * @return bool
     */
    public static function init() : bool {
        if (self::$cache === null) {
            try {
                $configOption = (new ConfigurationOption())
                    ->setAutoTmpFallback(true);
                $cacheClass = ucfirst(self::$defaultDriver);
                $cacheDriver = CacheManager::$cacheClass($configOption);
                self::$cache = new Psr16Adapter($cacheDriver);
            } catch (PhpfastcacheDriverCheckException $phpfastcacheDriverCheckException) {
            } catch (PhpfastcacheLogicException $phpfastcacheLogicException) {
            } catch (PhpfastcacheDriverNotFoundException $phpfastcacheDriverNotFoundException) {
            } catch (PhpfastcacheDriverException $phpfastcacheDriverException) {
            } catch (PhpfastcacheInvalidArgumentException $phpfastcacheInvalidArgumentException) {
            } catch (PhpfastcacheInvalidConfigurationException $phpfastcacheInvalidConfigurationException) {
            } catch (ReflectionException $reflectionException) {
            }
        }
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public static function delete($key) : bool {
        if (self::$cache === null) {
            self::init();
        }
        try {
            $ret = self::$cache->delete($key);
        } catch (PhpfastcacheSimpleCacheException $phpfastcacheSimpleCacheException) {
            echo $phpfastcacheSimpleCacheException->getMessage();
            return false;
        }
        return $ret;
    }

    /**
     * @return bool
     */
    public static function cleanup() : bool {
        if (self::$cache === null) {
            self::init();
        }
        try {
            $ret = self::$cache->clear();
        } catch (PhpfastcacheSimpleCacheException $phpfastcacheSimpleCacheException) {
            echo $phpfastcacheSimpleCacheException->getMessage();
            return false;
        }
        return $ret;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public static function get(string $key) {
        if (self::$cache === null) {
            self::init();
        }
        try {
            $key = sha1($key);
            $ret = self::$cache->get($key) !== null ? igbinary_unserialize(self::$cache->get($key)) : null;
        } catch (PhpfastcacheSimpleCacheException $phpfastcacheSimpleCacheException) {
            echo $phpfastcacheSimpleCacheException->getMessage();
            return false;
        }
        return $ret;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     * @throws Exception
     * @throws \InvalidArgumentException|InvalidArgumentException
     */
    public static function set(string $key, $value, ?int $ttl = 3600) : bool
    {
        if (self::$cache === null) {
            self::init();
        }

        try {
            $key = sha1($key);
            if ($ttl === null) {
                $ret = self::$cache->set($key, igbinary_serialize($value));
            } else {
                $ret = self::$cache->set($key, igbinary_serialize($value), $ttl);
            }
        } catch (PhpfastcacheSimpleCacheException $phpfastcacheSimpleCacheException) {
            echo $phpfastcacheSimpleCacheException->getMessage();
            return false;
        }
        return $ret;
    }
}
