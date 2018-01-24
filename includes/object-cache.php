<?php
/**
 * Object cache drop-in
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCacheService;
use LeavesAndLove\WpPsrCache\CacheAdapter\PsrCacheAdapterFactory;

defined( 'ABSPATH' ) || exit;

ObjectCacheService::loadApi();

/**
 * Defines and thus starts the object cache.
 *
 * @since 1.0.0
 */
function wp_psr_start_cache() {
    $factory = new PsrCacheAdapterFactory();

    $persistentCache    = $factory->create( /* Pass the persistent cache instance here. */ );
    $nonPersistentCache = $factory->create( /* Pass the non-persistent cache instance here. */ );

    ObjectCacheService::startInstance( $persistentCache, $nonPersistentCache );
}

wp_psr_start_cache();
