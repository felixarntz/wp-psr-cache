<?php
/**
 * Object cache functions
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCache;
use LeavesAndLove\WpPsrCache\ObjectCacheService;
use LeavesAndLove\WpPsrCache\ObjectCacheFactory;
use LeavesAndLove\WpPsrCache\CacheSelector\BaseCacheSelectorFactory;
use LeavesAndLove\WpPsrCache\CacheAdapter\PsrCacheAdapterFactory;
use LeavesAndLove\WpPsrCache\CacheKeyGen\WpCacheKeyGen;
use LeavesAndLove\WpPsrCache\CacheSelector\CacheSelector;
use Psr\Cache\CacheItemPoolInterface as Psr6;
use Psr\SimpleCache\CacheInterface as Psr16;

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'add_action' ) ) {
    // Compatibility with WP Multi Network.
    add_action( 'switch_network', 'wp_cache_switch_to_network', 1, 1 );
}

/**
 * Starts the object cache by creating the main object cache instance for
 * given persistent and non-persistent caches.
 *
 * This function must only be called once, from the `object-cache.php` drop-in.
 *
 * @since 1.0.0
 *
 * @param Psr6|Psr16 $persistent_cache     Cache implementation to use for persistent cache.
 * @param Psr6|Psr16 $non_persistent_cache Cache implementation to use for non-persistent cache.
 */
function wp_cache_start( $persistent_cache, $non_persistent_cache ) {
    $cache_factory    = new ObjectCacheFactory();
    $selector_factory = new BaseCacheSelectorFactory();
    $adapter_factory  = new PsrCacheAdapterFactory();

    $persistent_cache_adapter     = $adapter_factory->create( $persistent_cache );
    $non_persistent_cache_adapter = $adapter_factory->create( $non_persistent_cache );

    $selector = $selector_factory->create( $persistent_cache_adapter, $non_persistent_cache_adapter );
    $cache    = $cache_factory->create( $selector );

    ObjectCacheService::setInstance( $cache );
}

/**
 * Adds a group or list of groups to the global cache groups.
 *
 * @since 1.0.0
 * @see WpCacheKeyGen::addGlobalGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_global_groups( $groups ) {
	wp_object_cache()->getKeygen()->addGlobalGroups( (array) $groups );
}

/**
 * Adds a group or list of groups to the network cache groups.
 *
 * @since 1.0.0
 * @see WpCacheKeyGen::addNetworkGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_network_groups( $groups ) {
	wp_object_cache()->getKeygen()->addNetworkGroups( (array) $groups );
}

/**
 * Adds a group or list of groups to the non-persistent cache groups.
 *
 * @since 1.0.0
 * @see CacheSelector::addNonPersistentGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	wp_object_cache()->getSelector()->addNonPersistentGroups( (array) $groups );
}

/**
 * Switches the internal site ID.
 *
 * @since 1.0.0
 * @see WpCacheKeyGen::switchSiteContext()
 *
 * @param int $site_id Site ID.
 */
function wp_cache_switch_to_site( $site_id ) {
    wp_object_cache()->getKeygen()->switchSiteContext( (int) $site_id );
}

/**
 * Switches the internal network ID.
 *
 * @since 1.0.0
 * @see WpCacheKeyGen::switchNetworkContext()
 *
 * @param int $network_id Network ID.
 */
function wp_cache_switch_to_network( $network_id ) {
    wp_object_cache()->getKeygen()->switchNetworkContext( (int) $network_id );
}

/**
 * Obtains a value from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::get()
 *
 * @param string $key    The key of this item in the cache.
 * @param string $group  Optional. The group of this item in the cache. Default empty string.
 * @param bool   $force  Optional. Whether to force an update of the non-persistent cache
 *                       from the persistent cache. Default false.
 * @param bool   &$found Optional. Whether the key was found in the cache (passed by reference).
 *                       Disambiguates a return of false, a storable value. Default null.
 * @return mixed The value of the item from the cache, or false in case of cache miss.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    $found = (bool) $found;

    return wp_object_cache()->get( $key, $group, $force, $found );
}

/**
 * Stores a value in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::set()
 *
 * @param string $key        The key of the item to store.
 * @param mixed  $value      The value of the item to store. Must be serializable.
 * @param string $group      Optional. The group of the item to store. Default empty string.
 * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_set( $key, $value, $group = '', $expiration = 0 ) {
    return wp_object_cache()->set( $key, $value, $group, $expiration );
}

/**
 * Stores a value in the cache if its key is not already set.
 *
 * @since 1.0.0
 * @see ObjectCache::add()
 *
 * @param string $key        The key of the item to store.
 * @param mixed  $value      The value of the item to store. Must be serializable.
 * @param string $group      Optional. The group of the item to store. Default empty string.
 * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_add( $key, $value, $group = '', $expiration = 0 ) {
    if ( wp_suspend_cache_addition() ) {
        return false;
    }

    return wp_object_cache()->add( $key, $value, $group, $expiration );
}

/**
 * Stores a value in the cache if its key is already set.
 *
 * @since 1.0.0
 * @see ObjectCache::replace()
 *
 * @param string $key        The key of the item to store.
 * @param mixed  $value      The value of the item to store. Must be serializable.
 * @param string $group      Optional. The group of the item to store. Default empty string.
 * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_replace( $key, $value, $group = '', $expiration = 0 ) {
    return wp_object_cache()->replace( $key, $value, $group, $expiration );
}

/**
 * Increments a numeric value in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::increment()
 *
 * @param string $key    The key of the item to increment its value.
 * @param int    $offset Optional. The amount by which to increment the value. Default 1.
 * @param string $group  Optional. The group of the item to increment. Default empty string.
 * @return int|bool The item's new value on success, false on failure.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
    return wp_object_cache()->increment( $key, $offset, $group );
}

/**
 * Decrements a numeric value in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::decrement()
 *
 * @param string $key    The key of the item to decrement its value.
 * @param int    $offset Optional. The amount by which to decrement the value. Default 1.
 * @param string $group  Optional. The group of the item to decrement. Default empty string.
 * @return int|bool The item's new value on success, false on failure.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
    return wp_object_cache()->decrement( $key, $offset, $group );
}

/**
 * Deletes a value from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::delete()
 *
 * @param string $key   The key of the item to delete.
 * @param string $group Optional. The group of the item to delete. Default empty string.
 * @return bool True on success, false on failure.
 */
function wp_cache_delete( $key, $group = '' ) {
    return wp_object_cache()->delete( $key, $group );
}

/**
 * Deletes all values from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::flush()
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_flush() {
    return wp_object_cache()->flush();
}

/**
 * Initializes the object cache.
 *
 * @since 1.0.0
 */
function wp_cache_init() {
    // This ensures an exception is thrown if no object cache has been set before this point.
    ObjectCacheService::getInstance();
}

/**
 * Closes the cache.
 *
 * @since 1.0.0
 *
 * @return bool True on success, false on failure.
 */
function wp_cache_close() {
    return true;
}

/**
 * Determines whether a value is present in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::has()
 *
 * @param string $key   The key of the item in the cache.
 * @param string $group Optional. The group of the item in the cache. Default empty string.
 * @return bool True if the value is present, false otherwise.
 */
function wp_cache_has( $key, $group = '' ) {
    return wp_object_cache()->has( $key, $group );
}

/**
 * Obtains multiple values from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::getMultiple()
 *
 * @param array  $keys  The list of keys for the items in the cache.
 * @param string $group Optional. The group of the items in the cache. Default empty string.
 * @param bool   $force Optional. Whether to force an update of the non-persistent cache
 *                      from the persistent cache. Default false.
 * @return array List of key => value pairs. For cache misses, false will be used as value.
 */
function wp_cache_get_multi( $keys, $group = '', $force = false ) {
    return wp_object_cache()->getMultiple( $keys, $group, $force );
}

/**
 * Stores multiple values in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::setMultiple()
 *
 * @param array  $keys       The list of key => value pairs to store.
 * @param string $group      Optional. The group of the items to store. Default empty string.
 * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_set_multi( $values, $group = '', $expiration = 0 ) {
    return wp_object_cache()->setMultiple( $values, $group, $expiration );
}

/**
 * Deletes multiple values from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::deleteMultiple()
 *
 * @param array  $keys  The list of keys for the items in the cache to delete.
 * @param string $group Optional. The group of the items to delete. Default empty string.
 * @return bool True on success, false on failure.
 */
function wp_cache_delete_multi( $keys, $group = '' ) {
    return wp_object_cache()->deleteMultiple( $keys, $group );
}

/**
 * Builds the full internal cache key for a given key and group.
 *
 * @since 1.0.0
 * @see WpCacheKeyGen::generate()
 *
 * @param string $key   A cache key.
 * @param string $group A cache group.
 * @return string The full cache key to use with cache implementations.
 */
function wp_cache_get_key( $key, $group = '' ) {
    return wp_object_cache()->getKeygen()->generate( $key, $group );
}

/**
 * Gets the main object cache instance.
 *
 * @since 1.0.0
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @return ObjectCache Main object cache instance.
 */
function wp_object_cache() {
    return $GLOBALS['wp_object_cache'];
}

/**
 * Switches the internal site ID.
 *
 * This function exists for compatibility, but is actually incorrectly named.
 * It will not trigger a deprecated notice, but it's still outdated.
 *
 * @since 1.0.0
 * @deprecated 1.0.0 Use wp_cache_switch_to_site()
 * @see wp_cache_switch_to_site()
 *
 * @param int $blog_id Site ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
    wp_cache_switch_to_site( $blog_id );

    // When `wp_cache_switch_to_blog()` is called right after multisite initialization, it must set the network.
    $keygen = wp_object_cache()->getKeygen();
    if ( 0 === $keygen->getNetworkContext() ) {
        if ( ! empty( $GLOBALS['current_blog'] ) && (int) $blog_id === (int) $GLOBALS['current_blog']->id ) {
            $site = $GLOBALS['current_blog'];
        } else {
            $site = get_site( $blog_id );
        }

        $keygen->switchNetworkContext( $site->network_id );
    }
}
