<?php
/**
 * Class Psr16CacheAdapter
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheAdapter;

use Psr\SimpleCache\CacheInterface as Psr16;

/**
 * PSR-16 cache adapter class.
 *
 * @since 1.0.0
 */
class Psr16CacheAdapter implements CacheAdapter
{

    /** @var Psr16 PSR-16 cache implementation. */
    protected $cache;

    /**
     * Constructor.
     *
     * Set the cache implementation.
     *
     * @since 1.0.0
     *
     * @param Psr16 $cache PSR-16 cache implementation.
     */
    public function __construct(Psr16 $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Obtain a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key The unique key of this item in the cache.
     * @return mixed The value of the item from the cache, or false in case of cache miss.
     */
    public function get(string $key)
    {
        return $this->cache->get($key, false);
    }

    /**
     * Store a value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param int    $expiration Optional. When the value should expire. Must be passed
     *                           in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, int $expiration = 0): bool
    {
        $expiration = 0 === $expiration ? null : $expiration;

        return $this->cache->set($key, $value, $expiration);
    }

    /**
     * Delete a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key The unique cache key of the item to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Obtain multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array $keys The list of unique keys for the items in the cache.
     * @return array List of key => value pairs. For cache misses, false will be used as value.
     */
    public function getMultiple(array $keys): array
    {
        $values = array();

        foreach ($this->cache->getMultiple($keys, false) as $key => $value) {
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Store multiple values in the cache.
     *
     * @since 1.0.0
     *
     * @param array $values     The list of key => value pairs to store.
     * @param int   $expiration Optional. When the values should expire. Must be passed
     *                          in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function setMultiple(array $values, int $expiration = 0): bool
    {
        $expiration = 0 === $expiration ? null : $expiration;

        return $this->cache->setMultiple($values, $expiration);
    }

    /**
     * Delete multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array $keys The list of unique keys for the items in the cache to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteMultiple(array $keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    /**
     * Determine whether a value is present in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key The unique key of this item.
     * @return bool True if the value is present, false otherwise.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Delete all values from the cache.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }
}
