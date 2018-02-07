<?php
/**
 * Class ObjectCacheFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use LeavesAndLove\WpPsrCache\CacheAdapter\CacheAdapter;
use LeavesAndLove\WpPsrCache\CacheKeyGen\WpCacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheKeyGen\WpPsrCacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheSelector\WpCacheSelector;
use LeavesAndLove\WpPsrCache\CacheSelector\WpPsrCacheSelector;

final class ObjectCacheFactory
{

    /**
     * Return an instance of an object cache.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter    $persistentCache    Adapter for the persistent cache implementation.
     * @param CacheAdapter    $nonPersistentCache Adapter for the non-persistent cache implementation.
     * @param WpCacheKeyGen   $keygen             Optional. Key generator. By default a WpPsrCacheKeyGen will
     *                                            be instantiated with the current site and network as context.
     * @param WpCacheSelector $selector           Optional. Selector to detect which cache to use. By default a
     *                                            WpPsrCacheSelector will be instantiated.
     * @return ObjectCache The object cache instance provided.
     */
    public function create(CacheAdapter $persistentCache, CacheAdapter $nonPersistentCache, WpCacheKeyGen $keygen = null, WpCacheSelector $selector = null): ObjectCache
    {
        if (null === $keygen) {
            $keygen = new WpPsrCacheKeyGen(get_current_blog_id(), get_current_network_id());
        }

        if (null === $selector) {
            $selector = new WpPsrCacheSelector();
        }

        return new ObjectCache($persistentCache, $nonPersistentCache, $keygen, $selector);
    }
}
