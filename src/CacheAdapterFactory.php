<?php
/**
 * Interface CacheAdapterFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use InvalidArgumentException;

/**
 * Cache adapter factory interface.
 *
 * @since 1.0.0
 */
interface CacheAdapterFactory
{

    /**
     * Create a cache adapter for a given cache implementation.
     *
     * @since 1.0.0
     *
     * @param object $cache The cache implementation to wrap in the adapter.
     * @return CacheAdapter The cache adapter that wraps the passed cache implementation.
     *
     * @throws InvalidArgumentException Thrown if the cache implementation is not supported by this factory.
     */
    public function create($cache): CacheAdapter;
}
