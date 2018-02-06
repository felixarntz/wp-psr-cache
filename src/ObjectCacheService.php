<?php
/**
 * Class ObjectCacheService
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
use RuntimeException;
use BadMethodCallException;

/**
 * WordPress object cache service class.
 *
 * This class provides a simple access point where you can set and get the main
 * object cache instance so that we don't need to use Singletons.
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
     * Proxy to call methods of the object cache instance provided statically for easy access.
     *
     * @since 1.0.0
     *
     * @param string $method    Method to call.
     * @param array  $arguments Arguments to pass to the method.
     * @return mixed Results of the method called.
     *
     * @throws BadMethodCallException Thrown when the method does not exist on the object cache instance.
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $instance = self::getInstance();

        if (!method_exists($instance, $method)) {
            throw new BadMethodCallException(
                sprintf(
                    'The method "%1$s" does not exist on the "%2$s" instance.',
                    $method,
                    get_class($instance)
                )
            );
        }

        return $instance->$method(...$arguments);
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
