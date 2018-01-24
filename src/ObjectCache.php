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
use LeavesAndLove\WpPsrCache\CacheKeyGen\WpCacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheRouter\WpCacheRouter;

/**
 * WordPress object cache class.
 *
 * @since 1.0.0
 */
final class ObjectCache
{

    const DEFAULT_GROUP = 'default';

    /** @var CacheAdapter The persistent cache. */
    private $persistentCache;

    /** @var CacheAdapter The non-persistent cache. */
    private $nonPersistentCache;

    /** @var WpCacheKeyGen The key generator. */
    private $keygen;

    /** @var WpCacheRouter The router to detect which cache to use. */
    private $router;

    /**
     * Constructor.
     *
     * Set the cache adapters to use for persistent and non-persistent caches.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter  $persistentCache    Adapter for the persistent cache implementation.
     * @param CacheAdapter  $nonPersistentCache Adapter for the non-persistent cache implementation.
     * @param WpCacheKeyGen $keygen             Key generator.
     * @param WpCacheRouter $router             Router to detect which cache to use.
     */
    public function __construct(CacheAdapter $persistentCache, CacheAdapter $nonPersistentCache, WpCacheKeyGen $keygen, WpCacheRouter $router)
    {
        $this->persistentCache    = $persistentCache;
        $this->nonPersistentCache = $nonPersistentCache;
        $this->keygen             = $keygen;
        $this->router             = $router;
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

        $found = false;

        $nonPersistent = $this->router->isNonPersistentGroup($group);
        if ($nonPersistent || !$force) {
            if ($this->nonPersistentCache->has($key)) {
                $found = true;
                return $this->nonPersistentCache->get($key);
            }

            if ($nonPersistent) {
                return false;
            }
        }

        if ($this->persistentCache->has($key)) {
            $found = true;
            $value = $this->persistentCache->get($key);

            $this->nonPersistentCache->set($key, $value);

            return $value;
        }

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

        if ($this->router->isNonPersistentGroup($group)) {
            return $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if ($this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

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

        if ($this->router->isNonPersistentGroup($group)) {
            return !$this->nonPersistentCache->has($key) && $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if (!$this->persistentCache->has($key) && $this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

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

        if ($this->router->isNonPersistentGroup($group)) {
            return $this->nonPersistentCache->has($key) && $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if ($this->persistentCache->has($key) && $this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

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

        if ($this->router->isNonPersistentGroup($group)) {
            // If the item is not in the cache, return true.
            return !$this->nonPersistentCache->has($key) || $this->nonPersistentCache->delete($key);
        }

        // If the item is not in the cache, return true.
        if (!$this->persistentCache->has($key) || $this->persistentCache->delete($key)) {
            $this->nonPersistentCache->delete($key);

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
        if ($this->persistentCache->clear()) {
            $this->nonPersistentCache->clear();

            return true;
        }

        return false;
    }

    /**
     * Determine whether a value is present in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key   The key of the item to delete.
     * @param string $group Optional. The group of the item to delete. Default 'default'.
     * @return bool True if the value is present, false otherwise.
     */
    public function has(string $key, string $group = self::DEFAULT_GROUP): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->keygen->generate($key, $group);

        if ($this->router->isNonPersistentGroup($group)) {
            return $this->nonPersistentCache->has($key);
        }

        return $this->persistentCache->has($key);
    }

    /**
     * Obtain multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys   The list of keys for the items in the cache.
     * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
     *                             If an array, it corresponds with the $keys array. Default 'default'.
     * @return array List of key => value pairs. For cache misses, false will be used as value.
     */
    public function getMultiple(array $keys, $groups = self::DEFAULT_GROUP): array
    {
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys = $this->buildKeys($keys, $groups);
        $groupMap = array_combine($fullKeys, $groups);

        // Check the non-persistent cache first.
        $values = $this->nonPersistentCache->getMultiple($fullKeys);

        // If only non-persistent groups, bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return array_combine($keys, $values);
        }

        $needed = array();
        foreach ($values as $fullKey => $value) {
            if (false !== $value) {
                continue;
            }

            if ($this->router->isNonPersistentGroup($groupMap[$fullKey])) {
                continue;
            }

            $needed[] = $fullKey;
        }

        if (!empty($needed)) {
            // For cache misses in original lookup, check the persistent cache.
            $persistentValues = $this->persistentCache->getMultiple($needed);

            $values = array_merge($values, $persistentValues);
        }

        return $values;
    }

    /**
     * Store multiple values in the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys       The list of key => value pairs to store.
     * @param string|array $groups     A group or a list of groups. If a string, it is used for all keys.
     *                                 If an array, it corresponds with the $keys array. Default 'default'.
     * @param int          $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function setMultiple(array $values, $groups = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $keys                = array_keys($values);
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys   = $this->buildKeys($keys, $groups);
        $fullValues = array_combine($fullKeys, $values);
        $groupMap   = array_combine($fullKeys, $groups);

        // If only non-persistent groups, set in non-persistent cache and bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return $this->nonPersistentCache->setMultiple($fullValues, $expiration);
        }

        // Split values between persistent and non-persistent groups.
        $nonPersistentNeeded = array();
        $persistentNeeded    = array();
        foreach ($fullValues as $fullKey => $value) {
            if ($this->router->isNonPersistentGroup($groupMap[$fullKey])) {
                $nonPersistentNeeded[$fullKey] = $value;
                continue;
            }

            $persistentNeeded[$fullKey] = $value;
        }

        $result = true;
        if (!empty($persistentNeeded)) {
            $result = $this->persistentCache->setMultiple($persistentNeeded, $expiration);
            if ($result) {
                // If persistent successfully set, they also need to be set in non-persistent cache.
                $nonPersistentNeeded = $fullValues;
            }
        }

        if (!empty($nonPersistentNeeded)) {
            $result = $this->nonPersistentCache->setMultiple($nonPersistentNeeded, $expiration) && $result;
        }

        return $result;
    }

    /**
     * Delete multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys   The list of keys for the items in the cache to delete.
     * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
     *                             If an array, it corresponds with the $keys array. Default 'default'.
     * @return bool True on success, false on failure.
     */
    public function deleteMultiple(array $keys, $groups = self::DEFAULT_GROUP): bool
    {
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys = $this->buildKeys($keys, $groups);
        $groupMap = array_combine($fullKeys, $groups);

        // Delete from the non-persistent cache first.
        $result = $this->nonPersistentCache->deleteMultiple($fullKeys);

        // If only non-persistent groups, bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return $result;
        }

        $needed = array();
        foreach ($fullKeys as $fullKey) {
            if ($this->router->isNonPersistentGroup($groupMap[$fullKey])) {
                continue;
            }

            $needed[] = $fullKey;
        }

        if (!empty($needed)) {
            $result = $this->persistentCache->deleteMultiple($needed) && $result;
        }

        return $result;
    }

    /**
     * Get the key generator used by the object cache.
     *
     * @since 1.0.0
     *
     * @return WpCacheKeyGen Key generator instance.
     */
    public function getKeygen(): WpCacheKeyGen
    {
        return $this->keygen;
    }

    /**
     * Get the router used by the object cache.
     *
     * @since 1.0.0
     *
     * @return WpCacheRouter Router instance.
     */
    public function getRouter(): WpCacheRouter
    {
        return $this->router;
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
     * Parses a group or a list of groups for a list of keys.
     *
     * @since 1.0.0
     *
     * @param string|array $groups A group or a list of groups.
     * @param array        $keys   A list of keys.
     * @return array List of groups, keyed by their key. The length of the list matches the length of $keys.
     */
    private function parseGroupsForKeys($groups, array $keys): array
    {
        if (is_string($groups)) {
            $groups = $this->parseDefaultGroup($groups);

            return array_fill_keys($keys, $groups);
        }

        $groups = array_map(array($this,'parseDefaultGroup'), $groups);

        $diff = count($keys) - count($groups);
        if ($diff > 0) {
            $groups = array_merge($groups, array_fill(0, $diff, self::DEFAULT_GROUP));
        } elseif ($diff < 0) {
            $groups = array_slice($groups, 0, $diff);
        }

        return array_combine($keys, $groups);
    }

    /**
     * Builds full cache keys for given keys and groups.
     *
     * @since 1.0.0
     *
     * @param array $keys   A list of cache keys.
     * @param array $groups A list of cache groups, keyed by their key. The length of the array must match
     *                      the length of $keys.
     * @return array The list of full cache keys.
     */
    private function buildKeys(array $keys, array $groups): array
    {
        $fullKeys = array();

        foreach ($keys as $key) {
            $fullKeys[] = $this->keygen->generate($key, $groups[$key]);
        }

        return $fullKeys;
    }
}
