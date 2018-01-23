<?php
/**
 * Object cache functions
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCache;

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'add_action' ) ) {
    // Compatibility with WP Multi Network.
    add_action( 'switch_network', 'wp_cache_switch_to_network', 1, 1 );
}

/**
 * Adds a group or list of groups to the global cache groups.
 *
 * @since 1.0.0
 * @see ObjectCache::addGlobalGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_global_groups( $groups ) {
    $groups = (array) $groups;

    ObjectCache::getInstance()->addGlobalGroups( $groups );
}

/**
 * Adds a group or list of groups to the network cache groups.
 *
 * @since 1.0.0
 * @see ObjectCache::addNetworkGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_network_groups( $groups ) {
    $groups = (array) $groups;

    ObjectCache::getInstance()->addNetworkGroups( $groups );
}

/**
 * Adds a group or list of groups to the non-persistent cache groups.
 *
 * @since 1.0.0
 * @see ObjectCache::addNonPersistentGroups()
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
    $groups = (array) $groups;

    ObjectCache::getInstance()->addNonPersistentGroups( $groups );
}

/**
 * Switches the internal site ID.
 *
 * @since 1.0.0
 * @see ObjectCache::switchSiteContext()
 *
 * @param int $site_id Site ID.
 */
function wp_cache_switch_to_site( $site_id ) {
    ObjectCache::getInstance()->switchSiteContext( (int) $site_id );
}

/**
 * Switches the internal network ID.
 *
 * @since 1.0.0
 * @see ObjectCache::switchNetworkContext()
 *
 * @param int $network_id Network ID.
 */
function wp_cache_switch_to_network( $network_id ) {
    ObjectCache::getInstance()->switchNetworkContext( (int) $network_id );
}

/**
 * Initializes the object cache.
 *
 * @since 1.0.0
 * @see ObjectCache::init()
 */
function wp_cache_init() {
    ObjectCache::getInstance()->init( (int) get_current_blog_id(), (int) get_current_network_id() );
}

/**
 * Obtains a value from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::get()
 *
 * @param string $key    The key of this item in the cache.
 * @param string $group  Optional. The group of this item in the cache. Default empty string.
 * @param bool   $force  Optional. Whether to force an update of the non-persistent cache from the persistent cache. Default false.
 * @param bool   &$found Optional. Whether the key was found in the cache (passed by reference). Disambiguates a return of false,
 *                       a storable value. Default null.
 * @return mixed The value of the item from the cache, or false in case of cache miss.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    $found = (bool) $found;

    return ObjectCache::getInstance()->get( $key, $group, $force, $found );
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
    return ObjectCache::getInstance()->set( $key, $value, $group, $expiration );
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

    return ObjectCache::getInstance()->add( $key, $value, $group, $expiration );
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
    return ObjectCache::getInstance()->replace( $key, $value, $group, $expiration );
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
    return ObjectCache::getInstance()->increment( $key, $offset, $group );
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
    return ObjectCache::getInstance()->decrement( $key, $offset, $group );
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
    return ObjectCache::getInstance()->delete( $key, $group );
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
    return ObjectCache::getInstance()->flush();
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
 * @param string $key   The key of the item to delete.
 * @param string $group Optional. The group of the item to delete. Default empty string.
 * @return bool True if the value is present, false otherwise.
 */
function wp_cache_has( $key, $group = '' ) {
    return ObjectCache::getInstance()->has( $key, $group );
}

/**
 * Obtains multiple values from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::getMultiple()
 *
 * @param array        $keys   The list of keys for the items in the cache.
 * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
 *                             If an array, it corresponds with the $keys array. Default empty string.
 * @return array List of key => value pairs. For cache misses, false will be used as value.
 */
function wp_cache_get_multi( $keys, $groups = '' ) {
    return ObjectCache::getInstance()->getMultiple( $keys, $groups );
}

/**
 * Stores multiple values in the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::setMultiple()
 *
 * @param array        $keys       The list of key => value pairs to store.
 * @param string|array $groups     A group or a list of groups. If a string, it is used for all keys.
 *                                 If an array, it corresponds with the $keys array. Default empty string.
 * @param int          $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
 * @return bool True on success, false on failure.
 */
function wp_cache_set_multi( $values, $groups = '', $expiration = 0 ) {
    return ObjectCache::getInstance()->setMultiple( $values, $groups, $expiration );
}

/**
 * Deletes multiple values from the cache.
 *
 * @since 1.0.0
 * @see ObjectCache::deleteMultiple()
 *
 * @param array        $keys   The list of keys for the items in the cache to delete.
 * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
 *                             If an array, it corresponds with the $keys array. Default empty string.
 * @return bool True on success, false on failure.
 */
function wp_cache_delete_multi( $keys, $groups = '' ) {
    return ObjectCache::getInstance()->deleteMultiple( $keys, $groups );
}

/**
 * Builds the full internal cache key for a given key and group.
 *
 * @since 1.0.0
 * @see ObjectCache::buildKey()
 *
 * @param string $key   A cache key.
 * @param string $group A cache group.
 * @return string The full cache key to use with cache implementations.
 */
function wp_cache_get_key( $key, $group = '' ) {
    return ObjectCache::getInstance()->buildKey( $key, $group );
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
}
