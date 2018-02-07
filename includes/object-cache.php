<?php
/**
 * Object cache drop-in
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCacheService;

defined( 'ABSPATH' ) || exit;

ObjectCacheService::loadApi();

/**
 * Defines and thus starts the object cache.
 *
 * @since 1.0.0
 */
function wp_psr_start_cache() {
    wp_cache_start( /* Pass the persistent cache instance as first parameter and the non-persistent cache instance as second parameter. */ );
}

wp_psr_start_cache();
