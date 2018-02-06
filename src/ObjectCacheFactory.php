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
use LeavesAndLove\WpPsrCache\CacheRouter\WpCacheRouter;
use LeavesAndLove\WpPsrCache\CacheRouter\WpPsrCacheRouter;

final class ObjectCacheFactory
{

    /**
     * Return an instance of an object cache.
     *
     * @since 1.0.0
     *
     * @param CacheAdapter  $persistentCache    Adapter for the persistent cache implementation.
     * @param CacheAdapter  $nonPersistentCache Adapter for the non-persistent cache implementation.
     * @param WpCacheKeyGen $keygen             Optional. Key generator. By default a WpPsrCacheKeyGen will
     *                                          be instantiated with the current site and network as context.
     * @param WpCacheRouter $router             Optional. Router to detect which cache to use. By default a
     *                                          WpPsrCacheRouter will be instantiated.
     * @return ObjectCache The object cache instance provided.
     */
    public function create(CacheAdapter $persistentCache, CacheAdapter $nonPersistentCache, WpCacheKeyGen $keygen = null, WpCacheRouter $router = null): ObjectCache
    {
        if (null === $keygen) {
            $keygen = new WpPsrCacheKeyGen(get_current_blog_id(), get_current_network_id());
        }

        if (null === $router) {
            $router = new WpPsrCacheRouter();
        }

        return new ObjectCache($persistentCache, $nonPersistentCache, $keygen, $router);
    }
}
