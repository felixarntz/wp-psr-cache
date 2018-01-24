<?php
/**
 * Class CacheAdapterFactory
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use Psr\Cache\CacheItemPoolInterface as Psr6;
use Psr\SimpleCache\CacheInterface as Psr16;
use InvalidArgumentException;

/**
 * PSR-6 cache adapter class.
 *
 * @since 1.0.0
 */
final class CacheAdapterFactory
{

    /** @var array<string> Map interfaces to adapters. */
    const MAPPINGS = [
        Psr6::class  => Psr6Adapter::class,
        Psr16::class => Psr16Adapter::class,
    ];

    /**
     * Create a matching adapter for a given interface.
     *
     * @since 1.0.0
     *
     * @param string|object $cache Cache interface name or implementation to create an adapter for.
     *
     * @return CacheAdapter Adapter that matches the provided interface.
     * @throws InvalidArgumentException If an incompatible cache implementation was provided.
     */
    public function create($cache): CacheAdapter
    {
        foreach (self::MAPPINGS as $interface => $adapter) {
            if ((is_string($cache) && $cache === $interface)
                || (is_object($cache) && $cache instanceof $interface)) {
                return new $adapter;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'Incompatible cache implementation of type "%s" passed to adapter factory.',
                is_object($cache) ? get_class($cache) : gettype($cache)
            )
        );
    }
}
