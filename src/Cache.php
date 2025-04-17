<?php
declare(strict_types=1);

namespace openWebX\openCache;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Helper\Psr16Adapter;
use Phpfastcache\Exceptions\{
    PhpfastcacheDriverCheckException,
    PhpfastcacheDriverException,
    PhpfastcacheDriverNotFoundException,
    PhpfastcacheInvalidArgumentException,
    PhpfastcacheInvalidConfigurationException,
    PhpfastcacheLogicException,
    PhpfastcacheSimpleCacheException
};
use Psr\SimpleCache\InvalidArgumentException as PsSimpleCacheException;
use RuntimeException;
use ReflectionException;

final class Cache
{
    private static ?Psr16Adapter $cache = null;
    private static string     $defaultDriver = 'Files';

    // Prevent instantiation or cloning
    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    /**
     * Initialize the PSR‑16 adapter once.
     *
     * @throws RuntimeException on failure
     */
    public static function init(): void
    {
        if (self::$cache !== null) {
            return;
        }

        try {
            $config   = (new ConfigurationOption())->setAutoTmpFallback(true);
            $driver   = ucfirst(self::$defaultDriver);
            $instance = CacheManager::$driver(config: $config);

            self::$cache = new Psr16Adapter(driver: $instance);
        } catch (
        PhpfastcacheDriverCheckException |
        PhpfastcacheLogicException |
        PhpfastcacheDriverNotFoundException |
        PhpfastcacheDriverException |
        PhpfastcacheInvalidArgumentException |
        PhpfastcacheInvalidConfigurationException |
        ReflectionException $e
        ) {
            throw new RuntimeException(
                'Failed to initialize cache: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * @return Psr16Adapter
     */
    private static function getAdapter(): Psr16Adapter
    {
        if (self::$cache === null) {
            self::init();
        }
        return self::$cache;
    }

    /**
     * Delete a key.
     */
    public static function delete(string $key): bool
    {
        $cache = self::getAdapter();
        $hash  = sha1($key);

        try {
            return $cache->delete($hash);
        } catch (PhpfastcacheSimpleCacheException $e) {
            error_log(sprintf(
                '[%s] delete("%s") failed: %s',
                $e::class,
                $key,
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Clear all entries.
     */
    public static function clear(): bool
    {
        try {
            return self::getAdapter()->clear();
        } catch (PhpfastcacheSimpleCacheException $e) {
            error_log(sprintf(
                '[%s] clear() failed: %s',
                $e::class,
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Fetch and unserialize a value.
     *
     * @return mixed|null  Returns null on miss or error.
     */
    public static function get(string $key): mixed
    {
        $cache = self::getAdapter();
        $hash  = sha1($key);

        try {
            $raw = $cache->get($hash);
            return $raw !== null
                ? igbinary_unserialize($raw)
                : null;
        } catch (PhpfastcacheSimpleCacheException $e) {
            error_log(sprintf(
                '[%s] get("%s") failed: %s',
                $e::class,
                $key,
                $e->getMessage()
            ));
            return null;
        }
    }

    /**
     * Serialize and store a value with an optional TTL.
     */
    public static function set(string $key, mixed $value, ?int $ttl = 3600): bool
    {
        $cache   = self::getAdapter();
        $hash    = sha1($key);
        $payload = igbinary_serialize($value);

        try {
            return $cache->set(
                key:   $hash,
                value: $payload,
                ttl:   $ttl
            );
        } catch (PhpfastcacheSimpleCacheException $e) {
            error_log(sprintf(
                '[%s] set("%s") failed: %s',
                $e::class,
                $key,
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Change the default driver (and reset).
     */
    public static function configureDriver(string $driver): void
    {
        self::$defaultDriver = $driver;
        self::$cache         = null;
    }
}
