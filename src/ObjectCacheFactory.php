<?php
/**
 * Class ObjectCacheFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

final class ObjectCacheFactory
{
    /** @var ObjectCache Instance of the object cache to share. */
    private static $instance;

    /**
     * Instantiate an ObjectCacheFactory object.
     *
     * @since 0.1.0
     *
     * @param ObjectCache|null $cache Optional. ObjectCache instance to reuse.
     */
    public function __construct(ObjectCache $cache = null)
    {
        self::$instance = $cache ?? new ObjectCache();
    }

    /**
     * Return an instance of an object cache.
     *
     * @since 0.1.0
     *
     * @return ObjectCache
     */
    public function create(): ObjectCache
    {
        return self::$instance;
    }
}
