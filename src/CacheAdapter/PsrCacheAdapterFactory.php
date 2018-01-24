<?php
/**
 * Class PsrCacheAdapterFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheAdapter;

use Psr\Cache\CacheItemPoolInterface as Psr6;
use Psr\SimpleCache\CacheInterface as Psr16;
use InvalidArgumentException;

/**
 * PSR Cache adapter factory class.
 *
 * @since 1.0.0
 */
class PsrCacheAdapterFactory implements CacheAdapterFactory
{

    /**
     * Create a cache adapter for a given cache implementation.
     *
     * @since 1.0.0
     *
     * @param Psr6|Psr16 $cache The cache implementation to wrap in the adapter.
     * @return CacheAdapter The cache adapter that wraps the passed cache implementation.
     *
     * @throws InvalidArgumentException Thrown if the cache implementation is not supported by this factory.
     */
    public function create($cache): CacheAdapter
    {
        if ($cache instanceof Psr6) {
            return new Psr6CacheAdapter($cache);
        }

        if ($cache instanceof Psr16) {
            return new Psr16CacheAdapter($cache);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Incompatible cache implementation of class "%1$s" passed to "$2$s".',
                get_class($cache),
                get_class($this)
            )
        );
    }
}
