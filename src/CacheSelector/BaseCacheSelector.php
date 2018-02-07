<?php
/**
 * Class BaseCacheSelector
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheSelector;

use LeavesAndLove\WpPsrCache\CacheAdapter\CacheAdapter;

/**
 * Base cache selector class.
 *
 * @since 1.0.0
 */
class BaseCacheSelector implements CacheSelector
{

    /** @var CacheAdapter The default persistent cache. */
    protected $persistentCache;

    /** @var CacheAdapter The default non-persistent cache. */
    protected $nonPersistentCache;

    /** @var array Array of additional persistent caches, by group. */
    protected $persistentCaches = array();

    /** @var array Array of additional non-persistent caches, by group. */
    protected $nonPersistentCaches = array();

    /** @var array List of non-persistent cache groups. */
    protected $nonPersistentGroups = array();

    /**
     * Constructor.
     *
     * Set the default cache adapters to use for persistent and non-persistent caches.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter $persistentCache    Adapter for the persistent cache implementation.
     * @param CacheAdapter $nonPersistentCache Adapter for the non-persistent cache implementation.
     */
    public function __construct(CacheAdapter $persistentCache, CacheAdapter $nonPersistentCache)
    {
        $this->persistentCache    = $persistentCache;
        $this->nonPersistentCache = $nonPersistentCache;
    }

    /**
     * Registers a cache adapter for a set of persistent groups.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter $cache  Cache adapter.
     * @param array        $groups List of groups to use the cache adapter for.
     */
    public function registerPersistentCache(CacheAdapter $cache, array $groups)
    {
        foreach ($groups as $group) {
            $this->persistentCaches[$group] = $cache;
        }
    }

    /**
     * Registers a cache adapter for a set of non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter $cache  Cache adapter.
     * @param array        $groups List of groups to use the cache adapter for.
     */
    public function registerNonPersistentCache(CacheAdapter $cache, array $groups)
    {
        foreach ($groups as $group) {
            $this->nonPersistentCaches[$group] = $cache;
        }
    }

    /**
     * Selects the persistent cache adapter to use for a given cache group.
     *
     * If no persistent cache adapter is registered for the group specifically,
     * the default persistent cache adapter will be returned.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return CacheAdapter Cache adapter to use for the group.
     */
    public function selectPersistentCache(string $group): CacheAdapter
    {
        if (isset($this->persistentCaches[$group])) {
            return $this->persistentCaches[$group];
        }

        return $this->persistentCache;
    }

    /**
     * Selects the non-persistent cache adapter to use for a given cache group.
     *
     * If no non-persistent cache adapter is registered for the group specifically,
     * the default non-persistent cache adapter will be returned.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return CacheAdapter Cache adapter to use for the group.
     */
    public function selectNonPersistentCache(string $group): CacheAdapter
    {
        if (isset($this->nonPersistentCaches[$group])) {
            return $this->nonPersistentCaches[$group];
        }

        return $this->nonPersistentCache;
    }

    /**
     * Add cache groups to consider non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are non-persistent.
     */
    public function addNonPersistentGroups(array $groups)
    {
        $groups                    = array_fill_keys($groups, true);
        $this->nonPersistentGroups = array_merge($this->nonPersistentGroups, $groups);
    }

    /**
     * Determine whether a cache group is non-persistent.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return bool True if the group is non-persistent, false otherwise.
     */
    public function isNonPersistentGroup(string $group): bool
    {
        return isset($this->nonPersistentGroups[$group]);
    }

    /**
     * Delete all values from the persistent caches.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function clearPersistent(): bool
    {
        $result = $this->persistentCache->clear();

        $caches        = array_filter($this->persistentCaches);
        $clearedCaches = array_filter(array_map(function ($cache) {
            return $cache->clear();
        }, $caches));

        return $result && count($caches) === count($clearedCaches);
    }

    /**
     * Delete all values from the non-persistent caches.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function clearNonPersistent(): bool
    {
        $result = $this->nonPersistentCache->clear();

        $caches        = array_filter($this->nonPersistentCaches);
        $clearedCaches = array_filter(array_map(function ($cache) {
            return $cache->clear();
        }, $caches));

        return $result && count($caches) === count($clearedCaches);
    }
}
