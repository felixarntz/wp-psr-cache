<?php
/**
 * Interface CacheKeyGen
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheKeyGen;

/**
 * Cache key generator interface.
 *
 * @since 1.0.0
 */
interface CacheKeyGen
{

    /**
     * Generate the full cache key for a given key and group.
     *
     * @since 1.0.0
     *
     * @param string $key   A cache key.
     * @param string $group A cache group.
     * @return string The full cache key to use with cache implementations.
     */
    public function generate(string $key, string $group): string;
}
