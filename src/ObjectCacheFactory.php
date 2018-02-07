<?php
/**
 * Class ObjectCacheFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use LeavesAndLove\WpPsrCache\CacheSelector\CacheSelector;
use LeavesAndLove\WpPsrCache\CacheKeyGen\CacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheKeyGen\WpPsrCacheKeyGen;

final class ObjectCacheFactory
{

    /**
     * Create an object cache instance from a selector and keygen.
     *
     * @since 1.0.0
     *
     * @param CacheSelector $selector           Selector to detect which cache to use.
     * @param CacheKeyGen   $keygen             Optional. Key generator. By default a WpPsrCacheKeyGen will
     *                                          be instantiated with the current site and network as context.
     * @return ObjectCache The created object cache instance.
     */
    public function create(CacheSelector $selector, CacheKeyGen $keygen = null): ObjectCache
    {
        if (null === $keygen) {
            $keygen = new WpPsrCacheKeyGen(get_current_blog_id(), get_current_network_id());
        }

        return new ObjectCache($selector, $keygen);
    }
}
