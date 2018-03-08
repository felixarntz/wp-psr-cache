<?php
/**
 * Class ObjectCache
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use LeavesAndLove\WpPsrCache\CacheAdapter\CacheAdapter;
use LeavesAndLove\WpPsrCache\CacheKeyGen\CacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheSelector\CacheSelector;

/**
 * WordPress object cache class.
 *
 * @since 1.0.0
 */
final class ObjectCache
{

    const DEFAULT_GROUP = 'default';

    /** @var CacheSelector The selector to detect which cache to use. */
    private $selector;

    /** @var CacheKeyGen The key generator. */
    private $keygen;

    /** @var int Amount of times the cache data was already stored in the cache. */
    private $cacheHits = 0;

    /** @var int Amount of times the cache data was not stored in the cache. */
    private $cacheMisses = 0;

    /**
     * Constructor.
     *
     * Set the cache adapters to use for persistent and non-persistent caches.
     *
     * @since 1.0.0
     *
     * @param CacheSelector $selector Selector to detect which cache to use.
     * @param CacheKeyGen   $keygen   Key generator.
     */
    public function __construct(CacheSelector $selector, CacheKeyGen $keygen)
    {
        $this->selector = $selector;
        $this->keygen   = $keygen;
    }

    /**
     * Obtain a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of this item in the cache.
     * @param string $group  Optional. The group of this item in the cache. Default 'default'.
     * @param bool   $force  Optional. Whether to force an update of the non-persistent cache
     *                       from the persistent cache. Default false.
     * @param bool   &$found Optional. Whether the key was found in the cache (passed by reference).
     *                       Disambiguates a return of false, a storable value. Default false.
     * @return mixed The value of the item from the cache, or false in case of cache miss.
     */
    public function get(string $key, string $group = self::DEFAULT_GROUP, bool $force = false, bool &$found = false)
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        $found = false;

        $nonPersistent = $this->selector->isNonPersistentGroup($group);
        if ($nonPersistent || !$force) {
            if ($nonPersistentCache->has($key)) {
                $this->cacheHits++;
                $found = true;
                return $nonPersistentCache->get($key);
            }

            if ($nonPersistent) {
                $this->cacheMisses++;
                return false;
            }
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if ($persistentCache->has($key)) {
            $this->cacheHits++;
            $found = true;
            $value = $persistentCache->get($key);

            $nonPersistentCache->set($key, $value);

            return $value;
        }

        $this->cacheMisses++;
        return false;
    }

    /**
     * Store a value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return $nonPersistentCache->set($key, $value, $expiration);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if ($persistentCache->set($key, $value, $expiration)) {
            $nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Store a value in the cache if its key is not already set.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function add(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return !$nonPersistentCache->has($key) && $nonPersistentCache->set($key, $value, $expiration);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if (!$persistentCache->has($key) && $persistentCache->set($key, $value, $expiration)) {
            $nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Store a value in the cache if its key is already set.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function replace(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return $nonPersistentCache->has($key) && $nonPersistentCache->set($key, $value, $expiration);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if ($persistentCache->has($key) && $persistentCache->set($key, $value, $expiration)) {
            $nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Increment a numeric value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of the item to increment its value.
     * @param int    $offset Optional. The amount by which to increment the value. Default 1.
     * @param string $group  Optional. The group of the item to increment. Default 'default'.
     * @return int|bool The item's new value on success, false on failure.
     */
    public function increment(string $key, int $offset = 1, string $group = self::DEFAULT_GROUP)
    {
        $value = $this->get($key, $group, false, $found);

        if (!$found) {
            return false;
        }

        $value = is_numeric($value) ? $value + $offset : 0;

        // A value below 0 is not allowed.
        $value = $value >= 0 ? $value : 0;

        if ($this->set($key, $value, $group)) {
            return $value;
        }

        return false;
    }

    /**
     * Decrement a numeric value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of the item to decrement its value.
     * @param int    $offset Optional. The amount by which to decrement the value. Default 1.
     * @param string $group  Optional. The group of the item to decrement. Default 'default'.
     * @return int|bool The item's new value on success, false on failure.
     */
    public function decrement(string $key, int $offset = 1, string $group = self::DEFAULT_GROUP)
    {
        $value = $this->get($key, $group, false, $found);

        if (!$found) {
            return false;
        }

        $value = is_numeric($value) ? $value - $offset : 0;

        // A value below 0 is not allowed.
        $value = $value >= 0 ? $value : 0;

        if ($this->set($key, $value, $group)) {
            return $value;
        }

        return false;
    }

    /**
     * Delete a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key   The key of the item to delete.
     * @param string $group Optional. The group of the item to delete. Default 'default'.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key, string $group = self::DEFAULT_GROUP): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            // If the item is not in the cache, return true.
            return !$nonPersistentCache->has($key) || $nonPersistentCache->delete($key);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        // If the item is not in the cache, return true.
        if (!$persistentCache->has($key) || $persistentCache->delete($key)) {
            $nonPersistentCache->delete($key);

            return true;
        }

        return false;
    }

    /**
     * Delete all values from the cache.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function flush(): bool
    {
        if ($this->selector->clearPersistent()) {
            $this->selector->clearNonPersistent();

            return true;
        }

        return false;
    }

    /**
     * Determine whether a value is present in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key   The key of the item in the cache.
     * @param string $group Optional. The group of the item in the cache. Default 'default'.
     * @return bool True if the value is present, false otherwise.
     */
    public function has(string $key, string $group = self::DEFAULT_GROUP): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return $nonPersistentCache->has($key);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        return $persistentCache->has($key);
    }

    /**
     * Obtain multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array  $keys  The list of keys for the items in the cache.
     * @param string $group Optional. The group of the items in the cache. Default 'default'.
     * @param bool   $force Optional. Whether to force an update of the non-persistent cache
     *                      from the persistent cache. Default false.
     * @return array List of key => value pairs. For cache misses, false will be used as value.
     */
    public function getMultiple(array $keys, string $group = self::DEFAULT_GROUP, bool $force = false): array
    {
        $group    = $this->parseDefaultGroup($group);
        $fullKeys = $this->buildKeys($keys, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            $values = array_combine($keys, $nonPersistentCache->getMultiple($fullKeys));

            $this->checkMultipleHitsAndMisses($values);
            return $values;
        }

        if (!$force) {
            $values = $nonPersistentCache->getMultiple($fullKeys);
            $needed = array();
            foreach ($values as $fullKey => $value) {
                if (false !== $value) {
                    continue;
                }

                $needed[] = $fullKey;
            }
        } else {
            $values = array();
            $needed = $fullKeys;
        }

        if (!empty($needed)) {
            $persistentCache = $this->selector->selectPersistentCache($group);

            // For cache misses in original lookup, check the persistent cache.
            $persistentValues = $persistentCache->getMultiple($needed);

            $values = array_merge($values, $persistentValues);
        }

        $values = array_combine($keys, $values);

        $this->checkMultipleHitsAndMisses($values);
        return $values;
    }

    /**
     * Store multiple values in the cache.
     *
     * @since 1.0.0
     *
     * @param array  $values     The list of key => value pairs to store.
     * @param string $group      Optional. The group of the items to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function setMultiple(array $values, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group      = $this->parseDefaultGroup($group);
        $fullKeys   = $this->buildKeys(array_keys($values), $group);
        $fullValues = array_combine($fullKeys, $values);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return $nonPersistentCache->setMultiple($fullValues, $expiration);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if ($persistentCache->setMultiple($fullValues, $expiration)) {
            $nonPersistentCache->set($fullValues, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Delete multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array  $keys  The list of keys for the items in the cache to delete.
     * @param string $group Optional. The group of the items to delete. Default 'default'.
     * @return bool True on success, false on failure.
     */
    public function deleteMultiple(array $keys, string $group = self::DEFAULT_GROUP): bool
    {
        $group    = $this->parseDefaultGroup($group);
        $fullKeys = $this->buildKeys($keys, $group);

        $nonPersistentCache = $this->selector->selectNonPersistentCache($group);

        if ($this->selector->isNonPersistentGroup($group)) {
            return $nonPersistentCache->deleteMultiple($fullKeys);
        }

        $persistentCache = $this->selector->selectPersistentCache($group);

        if ($persistentCache->deleteMultiple($fullKeys)) {
            $nonPersistentCache->deleteMultiple($fullKeys);

            return true;
        }

        return false;
    }

    /**
     * Get the selector used by the object cache.
     *
     * @since 1.0.0
     *
     * @return CacheSelector Selector instance.
     */
    public function getSelector(): CacheSelector
    {
        return $this->selector;
    }

    /**
     * Get the key generator used by the object cache.
     *
     * @since 1.0.0
     *
     * @return CacheKeyGen Key generator instance.
     */
    public function getKeygen(): CacheKeyGen
    {
        return $this->keygen;
    }

    /**
     * Gets the amount of times the cache data was already stored in the cache.
     *
     * @since 1.0.0
     *
     * @return int Amount of cache hits.
     */
    public function getCacheHits(): int
    {
        return $this->cacheHits;
    }

    /**
     * Gets the amount of times the cache data was not stored in the cache.
     *
     * @since 1.0.0
     *
     * @return int Amount of cache misses.
     */
    public function getCacheMisses(): int
    {
        return $this->cacheMisses;
    }

    /**
     * Magic getter.
     *
     * Allows for backward-compatibility with plugins still doing it wrong.
     *
     * @since 1.0.0
     *
     * @param string $name Property to get.
     * @return mixed Property value.
     */
    public function __get(string $name)
    {
        switch($name) {
            case 'cache_hits':
                return $this->cacheHits;
            case 'cache_misses':
                return $this->cacheMisses;
            case 'global_groups':
                return $this->keygen->getGlobalGroups();
            case 'non_persistent_groups':
                return $this->selector->getNonPersistentGroups();
        }
    }

    /**
     * Magic setter.
     *
     * Allows for backward-compatibility with plugins still doing it wrong.
     *
     * @since 1.0.0
     *
     * @param string $name  Property to set.
     * @param mixed  $value Property value.
     */
    public function __set(string $name, $value)
    {
        switch($name) {
            case 'cache_hits':
                $this->cacheHits = (int) $value;
            case 'cache_misses':
                $this->cacheMisses = (int) $value;
            case 'global_groups':
                $this->keygen->addGlobalGroups((array) $value);
            case 'non_persistent_groups':
                $this->selector->addNonPersistentGroups((array) $value);
        }
    }

    /**
     * Magic isset-er.
     *
     * Allows for backward-compatibility with plugins still doing it wrong.
     *
     * @since 1.0.0
     *
     * @param string $name  Property to check if set.
     * @return bool True if property is set, false otherwise.
     */
    public function __isset(string $name): bool
    {
        switch($name) {
            case 'cache_hits':
            case 'cache_misses':
            case 'global_groups':
            case 'non_persistent_groups':
                return true;
        }

        return false;
    }

    /**
     * Get the default group in case the passed group is empty.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return string The value of $group, or the default group.
     */
    private function parseDefaultGroup(string $group)
    {
        return empty($group) ? self::DEFAULT_GROUP : $group;
    }

    /**
     * Builds full cache keys for given keys and a group.
     *
     * @since 1.0.0
     *
     * @param array  $keys  A list of cache keys.
     * @param string $group The cache group for the keys.
     * @return array The list of full cache keys.
     */
    private function buildKeys(array $keys, string $group): array
    {
        $fullKeys = array();

        foreach ($keys as $key) {
            $fullKeys[] = $this->keygen->generate($key, $group);
        }

        return $fullKeys;
    }

    /**
     * Increases the cache hits and misses by evaluating a result for multiple cache keys.
     *
     * @since 1.0.0
     *
     * @param array $values Array of $key => $value pairs as a cache lookup result.
     */
    private function checkMultipleHitsAndMisses(array $values)
    {
        $foundValues = array_filter($values, function($value) {
            return false !== $value;
        });

        $this->cacheHits   += count($foundValues);
        $this->cacheMisses += count($values) - count($foundValues);
    }
}
