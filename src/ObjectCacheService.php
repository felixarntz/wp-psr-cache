<?php
/**
 * Class ObjectCacheService
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use RuntimeException;

/**
 * WordPress object cache service class.
 *
 * This class provides a simple access point where you can set and get the main
 * object cache instance so that we don't need to use Singletons.
 *
 * The main instance is stored in a global, to be compatible with how the object
 * cache is commonly handled in WordPress.
 *
 * @since 1.0.0
 */
final class ObjectCacheService
{

    /**
     * Set the main object cache instance to provide.
     *
     * @since 1.0.0
     *
     * @global WP_Object_Cache $wp_object_cache Object cache global instance.
     *
     * @param ObjectCache $instance The object cache instance to provide.
     *
     * @throws RuntimeException Thrown if an object cache instance has already been set.
     */
    public static function setInstance(ObjectCache $instance)
    {
        if (isset($GLOBALS['wp_object_cache'])) {
            throw new RuntimeException('Object cache instance already set.');
        }

        $GLOBALS['wp_object_cache'] = $instance;
    }

    /**
     * Get the object cache instance provided.
     *
     * @since 1.0.0
     *
     * @global WP_Object_Cache $wp_object_cache Object cache global instance.
     *
     * @return ObjectCache The object cache instance provided.
     *
     * @throws RuntimeException Thrown if no object cache instance has been set yet.
     */
    public static function getInstance(): ObjectCache
    {
        if (!isset($GLOBALS['wp_object_cache'])) {
            throw new RuntimeException('Object cache instance not set yet.');
        }

        return $GLOBALS['wp_object_cache'];
    }

    /**
     * Load the API functions needed for WordPress integration.
     *
     * @since 1.0.0
     *
     * @codeCoverageIgnore
     */
    public static function loadApi()
    {
        require_once dirname(__DIR__) . '/includes/functions.php';
    }
}
