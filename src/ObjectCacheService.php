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
 * This class provides a simple access point to the main object cache instance
 * so that we don't need to use Singletons. Still not amazing, but we can't rely
 * on actual service providers in WordPress.
 *
 * @since 1.0.0
 */
final class ObjectCacheService
{

    /** @var ObjectCache The object cache instance provided. */
    private static $instance;

    /**
     * Start the object cache instance to provide.
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
     *
     * @throws RuntimeException Thrown if an object cache instance has already been started.
     */
    public static function startInstance(CacheAdapter $persistentCache, CacheAdapter $nonPersistentCache, WpCacheKeyGen $keygen = null, WpCacheRouter $router = null): ObjectCache
    {
        if (null !== self::$instance) {
            throw new RuntimeException('Object cache instance already started.');
        }

        if (null === $keygen) {
            $keygen = new WpPsrCacheKeyGen(get_current_blog_id(), get_current_network_id());
        }

        if (null === $router) {
            $router = new WpPsrCacheRouter();
        }

        self::$instance = new ObjectCache($persistentCache, $nonPersistentCache, $keygen, $router);

        return self::$instance;
    }

    /**
     * Get the object cache instance provided.
     *
     * @since 1.0.0
     *
     * @return ObjectCache The object cache instance provided.
     *
     * @throws RuntimeException Thrown if no object cache instance has been started yet.
     */
    public static function getInstance(): ObjectCache
    {
        if (null === self::$instance) {
            throw new RuntimeException('Object cache instance not started yet.');
        }

        return self::$instance;
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
