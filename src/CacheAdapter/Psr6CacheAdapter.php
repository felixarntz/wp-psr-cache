<?php
/**
 * Class Psr6CacheAdapter
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheAdapter;

use Psr\Cache\CacheItemPoolInterface as Psr6;

/**
 * PSR-6 cache adapter class.
 *
 * @since 1.0.0
 */
class Psr6CacheAdapter implements CacheAdapter
{

    /** @var Psr6 PSR-6 cache implementation. */
    protected $cache;

    /**
     * Constructor.
     *
     * Set the cache implementation.
     *
     * @since 1.0.0
     *
     * @param Psr6 $cache PSR-6 cache implementation.
     */
    public function __construct(Psr6 $cache)
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
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
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
        $item = $this->cache->getItem($key);

        $item->set($value);
        if ($expiration > 0) {
            $item->expiresAfter($expiration);
        }

        return $this->cache->save($item);
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
        return $this->cache->deleteItem($key);
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

        foreach ($this->cache->getItems($keys) as $key => $item) {
            $values[$key] = $item->isHit() ? $item->get() : false;
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
        $items = $this->cache->getItems(array_keys($values));

        foreach ($items as $key => $item) {
            $item->set($values[$key]);
            if ($expiration > 0) {
                $item->expiresAfter($expiration);
            }

            $this->cache->saveDeferred($item);
        }

        return $this->cache->commit();
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
        return $this->cache->deleteItems($keys);
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
        $item = $this->cache->getItem($key);

        return $item->isHit();
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
