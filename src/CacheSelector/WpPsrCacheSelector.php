<?php
/**
 * Class WpPsrCacheSelector
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheSelector;

/**
 * WordPress PSR cache selector class.
 *
 * @since 1.0.0
 */
class WpPsrCacheSelector implements WpCacheSelector
{

    /** @var array List of non-persistent cache groups. */
    protected $nonPersistentGroups = array();

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
}
