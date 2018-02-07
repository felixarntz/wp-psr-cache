<?php
/**
 * Interface CacheSelector
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheSelector;

use LeavesAndLove\WpPsrCache\CacheAdapter\CacheAdapter;

/**
 * Cache selector interface.
 *
 * @since 1.0.0
 */
interface CacheSelector
{

    /**
     * Registers a cache adapter for a set of persistent groups.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter $cache  Cache adapter.
     * @param array        $groups List of groups to use the cache adapter for.
     */
    public function registerPersistentCache(CacheAdapter $cache, array $groups);

    /**
     * Registers a cache adapter for a set of non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter $cache  Cache adapter.
     * @param array        $groups List of groups to use the cache adapter for.
     */
    public function registerNonPersistentCache(CacheAdapter $cache, array $groups);

    /**
     * Selects the persistent cache adapter to use for a given cache group.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return CacheAdapter Cache adapter to use for the group.
     */
    public function selectPersistentCache(string $group): CacheAdapter;

    /**
     * Selects the non-persistent cache adapter to use for a given cache group.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return CacheAdapter Cache adapter to use for the group.
     */
    public function selectNonPersistentCache(string $group): CacheAdapter;

    /**
     * Add cache groups to consider non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are non-persistent.
     */
    public function addNonPersistentGroups(array $groups);

    /**
     * Determine whether a cache group is non-persistent.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return bool True if the group is non-persistent, false otherwise.
     */
    public function isNonPersistentGroup(string $group): bool;

    /**
     * Delete all values from the persistent caches.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function clearPersistent(): bool;

    /**
     * Delete all values from the non-persistent caches.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function clearNonPersistent(): bool;
}
