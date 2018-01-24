<?php
/**
 * Interface WpCacheRouter
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheRouter;

/**
 * WordPress cache router interface.
 *
 * @since 1.0.0
 */
interface WpCacheRouter
{

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
     * Add cache groups to consider non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are non-persistent.
     */
    public function addNonPersistentGroups(array $groups);
}
