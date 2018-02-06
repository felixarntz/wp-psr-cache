<?php
/**
 * Object cache drop-in
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCacheService;
use LeavesAndLove\WpPsrCache\ObjectCacheFactory;
use LeavesAndLove\WpPsrCache\CacheAdapter\PsrCacheAdapterFactory;

defined( 'ABSPATH' ) || exit;

ObjectCacheService::loadApi();

/**
 * Defines and thus starts the object cache.
 *
 * @since 1.0.0
 */
function wp_psr_start_cache() {
    $cacheFactory   = new ObjectCacheFactory();
    $adapterFactory = new PsrCacheAdapterFactory();

    $persistentCacheAdapter    = $adapterFactory->create( /* Pass the persistent cache instance here. */ );
    $nonPersistentCacheAdapter = $adapterFactory->create( /* Pass the non-persistent cache instance here. */ );

    $cache = $cacheFactory->create( $persistentCacheAdapter, $nonPersistentCacheAdapter );

    ObjectCacheService::setInstance( $cache );
}

wp_psr_start_cache();
