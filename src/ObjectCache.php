<?php
/**
 * Class ObjectCache
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache;

use Psr\Cache\CacheItemPoolInterface as Psr6;
use Psr\SimpleCache\CacheInterface as Psr16;
use Exception;
use InvalidArgumentException;
use BadMethodCallException;
use RuntimeException;

/**
 * WordPress object cache class.
 *
 * @since 1.0.0
 */
final class ObjectCache
{

    const DEFAULT_GROUP = 'default';

    /** @var CacheAdapter The persistent cache instance. */
    protected $persistentCache;

    /** @var CacheAdapter The non-persistent cache instance. */
    protected $nonPersistentCache;

    /** @var array List of global cache groups. */
    protected $globalGroups = array();

    /** @var array List of network cache groups. */
    protected $networkGroups = array();

    /** @var array List of non-persistent cache groups. */
    protected $nonPersistentGroups = array();

    /** @var int Current site ID. */
    protected $siteId;

    /** @var int Current network ID. */
    protected $networkId;

    /** @var ObjectCache The main object cache instance. */
    protected static $instance;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param Psr6|Psr16 $persistentCache    Optional. Persistent cache implementation. Default none.
     * @param Psr6|Psr16 $nonPersistentCache Optional. Non-persistent cache implementation. Default none.
     */
    public function __construct($persistentCache = null, $nonPersistentCache = null)
    {
        if ($persistentCache) {
            $this->setPersistentCache($persistentCache);
        }
        if ($nonPersistentCache) {
            $this->setNonPersistentCache($nonPersistentCache);
        }
    }

    /**
     * Set the persistent cache instance.
     *
     * @since 1.0.0
     *
     * @param Psr6|Psr16 $cache PSR-6 or PSR-16 compatible cache implementation.
     *
     * @throws InvalidArgumentException Thrown when the cache implementation is compatible with neither PSR-6 nor PSR-16.
     */
    public function setPersistentCache($cache)
    {
        if ($cache instanceof Psr6) {
            $this->persistentCache = new Psr6Adapter($cache);
        } elseif ($cache instanceof Psr16) {
            $this->persistentCache = new Psr16Adapter($cache);
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'Incompatible cache implementation of class "%s" passed for persistent cache.',
                    get_class($cache)
                )
            );
        }
    }

    /**
     * Set the non-persistent cache instance.
     *
     * @since 1.0.0
     *
     * @param Psr6|Psr16 $cache PSR-6 or PSR-16 compatible cache implementation.
     *
     * @throws InvalidArgumentException Thrown when the cache implementation is compatible with neither PSR-6 nor PSR-16.
     */
    public function setNonPersistentCache($cache)
    {
        if ($cache instanceof Psr6) {
            $this->nonPersistentCache = new Psr6Adapter($cache);
        } elseif ($cache instanceof Psr16) {
            $this->nonPersistentCache = new Psr16Adapter($cache);
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'Incompatible cache implementation of class "%s" passed for non-persistent cache.',
                    get_class($cache)
                )
            );
        }
    }

    /**
     * Add cache groups to consider global groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are global.
     */
    public function addGlobalGroups(array $groups)
    {
        $groups             = array_fill_keys($groups, true);
        $this->globalGroups = array_merge($this->globalGroups, $groups);
    }

    /**
     * Add cache groups to consider network groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are network-specific.
     */
    public function addNetworkGroups(array $groups)
    {
        $groups              = array_fill_keys($groups, true);
        $this->networkGroups = array_merge($this->networkGroups, $groups);
    }

    /**
     * Add cache groups to consider non-persistent groups.
     *
     * @since 1.0.0
     *
     * @param array $groups The list of groups that are non-persistent.
     */
    public function addNonPersistentGroups(array $groups)
    {
        $groups                    = array_fill_keys($groups, true);
        $this->nonPersistentGroups = array_merge($this->nonPersistentGroups, $groups);
    }

    /**
     * Switch the site context.
     *
     * @since 1.0.0
     *
     * @param int $siteId Site ID to switch the context to.
     */
    public function switchSiteContext(int $siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * Switch the network context.
     *
     * @since 1.0.0
     *
     * @param int $networkId Network ID to switch the context to.
     */
    public function switchNetworkContext(int $networkId)
    {
        $this->networkId = $networkId;
    }

    /**
     * Initialize the object cache.
     *
     * @since 1.0.0
     *
     * @throws RuntimeException Thrown when a required cache implementation has not been provided.
     */
    public function init(int $siteId, int $networkId)
    {
        $this->switchSiteContext($siteId);
        $this->switchNetworkContext($networkId);

        if (!$this->persistentCache) {
            throw new RuntimeException('Persistent cache implementation not provided.');
        }
        if (!$this->nonPersistentCache) {
            throw new RuntimeException('Non-persistent cache implementation not provided.');
        }
    }

    /**
     * Obtain a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of this item in the cache.
     * @param string $group  Optional. The group of this item in the cache. Default 'default'.
     * @param bool   $force  Optional. Whether to force an update of the non-persistent cache from the persistent cache. Default false.
     * @param bool   &$found Optional. Whether the key was found in the cache (passed by reference). Disambiguates a return of false,
     *                       a storable value. Default false.
     * @return mixed The value of the item from the cache, or false in case of cache miss.
     */
    public function get(string $key, string $group = self::DEFAULT_GROUP, bool $force = false, bool &$found = false)
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        $found = false;

        $nonPersistent = $this->isNonPersistentGroup($group);
        if ($nonPersistent || !$force) {
            if ($this->nonPersistentCache->has($key)) {
                $found = true;
                return $this->nonPersistentCache->get($key);
            }

            if ($nonPersistent) {
                return false;
            }
        }

        if ($this->persistentCache->has($key)) {
            $found = true;
            $value = $this->persistentCache->get($key);

            $this->nonPersistentCache->set($key, $value);

            return $value;
        }

        return false;
    }

    /**
     * Store a value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        if ($this->isNonPersistentGroup($group)) {
            return $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if ($this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Store a value in the cache if its key is not already set.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function add(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        if ($this->isNonPersistentGroup($group)) {
            if ($this->nonPersistentCache->has($key)) {
                return false;
            }

            return $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if ($this->persistentCache->has($key)) {
            return false;
        }

        if ($this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Store a value in the cache if its key is already set.
     *
     * @since 1.0.0
     *
     * @param string $key        The key of the item to store.
     * @param mixed  $value      The value of the item to store. Must be serializable.
     * @param string $group      Optional. The group of the item to store. Default 'default'.
     * @param int    $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function replace(string $key, $value, string $group = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        if ($this->isNonPersistentGroup($group)) {
            if (!$this->nonPersistentCache->has($key)) {
                return false;
            }

            return $this->nonPersistentCache->set($key, $value, $expiration);
        }

        if (!$this->persistentCache->has($key)) {
            return false;
        }

        if ($this->persistentCache->set($key, $value, $expiration)) {
            $this->nonPersistentCache->set($key, $value, $expiration);

            return true;
        }

        return false;
    }

    /**
     * Increment a numeric value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of the item to increment its value.
     * @param int    $offset Optional. The amount by which to increment the value. Default 1.
     * @param string $group  Optional. The group of the item to increment. Default 'default'.
     * @return int|bool The item's new value on success, false on failure.
     */
    public function increment(string $key, int $offset = 1, string $group = self::DEFAULT_GROUP)
    {
        $value = $this->get($key, $group, false, $found);

        if (!$found) {
            return false;
        }

        $value = is_numeric($value) ? $value + $offset : 0;

        // A value below 0 is not allowed.
        $value = $value >= 0 ? $value : 0;

        if ($this->set($key, $value, $group)) {
            return $value;
        }

        return false;
    }

    /**
     * Decrement a numeric value in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key    The key of the item to decrement its value.
     * @param int    $offset Optional. The amount by which to decrement the value. Default 1.
     * @param string $group  Optional. The group of the item to decrement. Default 'default'.
     * @return int|bool The item's new value on success, false on failure.
     */
    public function decrement(string $key, int $offset = 1, string $group = self::DEFAULT_GROUP)
    {
        $value = $this->get($key, $group, false, $found);

        if (!$found) {
            return false;
        }

        $value = is_numeric($value) ? $value - $offset : 0;

        // A value below 0 is not allowed.
        $value = $value >= 0 ? $value : 0;

        if ($this->set($key, $value, $group)) {
            return $value;
        }

        return false;
    }

    /**
     * Delete a value from the cache.
     *
     * @since 1.0.0
     *
     * @param string $key   The key of the item to delete.
     * @param string $group Optional. The group of the item to delete. Default 'default'.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key, string $group = self::DEFAULT_GROUP): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        if ($this->isNonPersistentGroup($group)) {
            // If the item is not in the cache, return true.
            if (!$this->nonPersistentCache->has($key)) {
                return true;
            }

            return $this->nonPersistentCache->delete($key);
        }

        // If the item is not in the cache, return true.
        if (!$this->persistentCache->has($key)) {
            return true;
        }

        if ($this->persistentCache->delete($key)) {
            $this->nonPersistentCache->delete($key);

            return true;
        }

        return false;
    }

    /**
     * Delete all values from the cache.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function flush(): bool
    {
        if ($this->persistentCache->clear()) {
            $this->nonPersistentCache->clear();

            return true;
        }

        return false;
    }

    /**
     * Determine whether a value is present in the cache.
     *
     * @since 1.0.0
     *
     * @param string $key   The key of the item to delete.
     * @param string $group Optional. The group of the item to delete. Default 'default'.
     * @return bool True if the value is present, false otherwise.
     */
    public function has(string $key, string $group = self::DEFAULT_GROUP): bool
    {
        $group = $this->parseDefaultGroup($group);
        $key   = $this->buildKey($key, $group);

        if ($this->isNonPersistentGroup($group)) {
            return $this->nonPersistentCache->has($key);
        }

        return $this->persistentCache->has($key);
    }

    /**
     * Obtain multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys   The list of keys for the items in the cache.
     * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
     *                             If an array, it corresponds with the $keys array. Default 'default'.
     * @return array List of key => value pairs. For cache misses, false will be used as value.
     */
    public function getMultiple(array $keys, $groups = self::DEFAULT_GROUP): array
    {
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys = $this->buildKeys($keys, $groups);
        $groupMap = array_combine($fullKeys, $groups);

        // Check the non-persistent cache first.
        $values = $this->nonPersistentCache->getMultiple($fullKeys);

        // If only non-persistent groups, bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return array_combine($keys, $values);
        }

        $needed = array();
        foreach ($values as $fullKey => $value ) {
            if (false !== $value) {
                continue;
            }

            if ($this->isNonPersistentGroup($groupMap[$fullKey])) {
                continue;
            }

            $needed[] = $fullKey;
        }

        if (!empty($needed)) {
            // For cache misses in original lookup, check the persistent cache.
            $persistentValues = $this->persistentCache->getMultiple($needed);

            $values = array_merge($values, $persistentValues);
        }

        return $values;
    }

    /**
     * Store multiple values in the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys       The list of key => value pairs to store.
     * @param string|array $groups     A group or a list of groups. If a string, it is used for all keys.
     *                                 If an array, it corresponds with the $keys array. Default 'default'.
     * @param int          $expiration Optional. When to expire the value, passed in seconds. Default 0 (no expiration).
     * @return bool True on success, false on failure.
     */
    public function setMultiple(array $values, $groups = self::DEFAULT_GROUP, int $expiration = 0): bool
    {
        $keys                = array_keys($values);
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys   = $this->buildKeys($keys, $groups);
        $fullValues = array_combine($fullKeys, $values);
        $groupMap   = array_combine($fullKeys, $groups);

        // If only non-persistent groups, set in non-persistent cache and bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return $this->nonPersistentCache->setMultiple($fullValues, $expiration);
        }

        // Split values between persistent and non-persistent groups.
        $nonPersistentNeeded = array();
        $persistentNeeded    = array();
        foreach ($fullValues as $fullKey => $value) {
            if ($this->isNonPersistentGroup($groupMap[$fullKey])) {
                $nonPersistentNeeded[$fullKey] = $value;
                continue;
            }

            $persistentNeeded[$fullKey] = $value;
        }

        $result = true;
        if (!empty($persistentNeeded)) {
            $result = $this->persistentCache->setMultiple($persistentNeeded, $expiration);
            if ($result) {
                // If persistent successfully set, they also need to be set in non-persistent cache.
                $nonPersistentNeeded = $fullValues;
            }
        }

        if (!empty($nonPersistentNeeded)) {
            $result = $this->nonPersistentCache->setMultiple($nonPersistentNeeded, $expiration) && $result;
        }

        return $result;
    }

    /**
     * Delete multiple values from the cache.
     *
     * @since 1.0.0
     *
     * @param array        $keys   The list of keys for the items in the cache to delete.
     * @param string|array $groups A group or a list of groups. If a string, it is used for all keys.
     *                             If an array, it corresponds with the $keys array. Default 'default'.
     * @return bool True on success, false on failure.
     */
    public function deleteMultiple(array $keys, $groups = self::DEFAULT_GROUP): bool
    {
        $groups              = $this->parseGroupsForKeys($groups, $keys);
        $nonPersistentGroups = array_filter($groups, array($this,'isNonPersistentGroup'));

        $fullKeys = $this->buildKeys($keys, $groups);
        $groupMap = array_combine($fullKeys, $groups);

        // Delete from the non-persistent cache first.
        $result = $this->nonPersistentCache->deleteMultiple($fullKeys);

        // If only non-persistent groups, bail.
        if (count($groups) === count($nonPersistentGroups)) {
            return $result;
        }

        $needed = array();
        foreach ($fullKeys as $fullKey ) {
            if ($this->isNonPersistentGroup($groupMap[$fullKey])) {
                continue;
            }

            $needed[] = $fullKey;
        }

        if (!empty($needed)) {
            $result = $this->persistentCache->deleteMultiple($needed) && $result;
        }

        return $result;
    }

    /**
     * Build the full cache key for a given key and group.
     *
     * @since 1.0.0
     *
     * @param string $key   A cache key.
     * @param string $group A cache group.
     * @return string The full cache key to use with cache implementations.
     */
    public function buildKey(string $key, string $group): string
    {
        switch (true) {
            case isset($this->globalGroups[$group]):
                $key = 'global.' . $group . '.' . $key;
                break;
            case isset($this->networkGroups[$group]):
                $key = 'network.' . $this->networkId . '.' . $group . '.' . $key;
                break;
            default:
                $key = 'site.' . $this->siteId . '.' . $group . '.' . $key;
        }

        return $this->sanitizeKey($key);
    }

    /**
     * Sanitize a cache key by replacing unsupported characters.
     *
     * @since 1.0.0
     *
     * @param string $key A cache key.
     * @return string The sanitized cache key.
     */
    protected function sanitizeKey(string $key): string
    {
        // The following characters are not supported in PSR-6/PSR-16.
        $replacements = array(
            '{'  => '',
            '}'  => '',
            '('  => '',
            ')'  => '',
            '/'  => '',
            '\\' => '',
            '@'  => '',
            ':'  => '.',
            ' '  => '', // This is not explicitly forbidden, but causes issues easily.
        );

        return str_replace(array_keys($replacements), array_values($replacements), $key);
    }

    /**
     * Determine whether a cache group is non-persistent.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return bool True if the group is non-persistent, false otherwise.
     */
    protected function isNonPersistentGroup(string $group): bool
    {
        return isset($this->nonPersistentGroups[$group]);
    }

    /**
     * Get the default group in case the passed group is empty.
     *
     * @since 1.0.0
     *
     * @param string $group A cache group.
     * @return string The value of $group, or the default group.
     */
    protected function parseDefaultGroup(string $group)
    {
        return empty($group) ? self::DEFAULT_GROUP : $group;
    }

    /**
     * Parses a group or a list of groups for a list of keys.
     *
     * @since 1.0.0
     *
     * @param string|array $groups A group or a list of groups.
     * @param array        $keys   A list of keys.
     * @return array List of groups, keyed by their key. The length of the list matches the length of $keys.
     */
    protected function parseGroupsForKeys($groups, array $keys): array
    {
        if (is_string($groups)) {
            $groups = $this->parseDefaultGroup($groups);

            return array_fill_keys($keys, $groups);
        }

        $groups = array_map(array($this,'parseDefaultGroup'), $groups);

        $diff = count($keys) - count($groups);
        if ($diff > 0) {
            $groups = array_merge($groups, array_fill(0, $diff, self::DEFAULT_GROUP));
        } elseif ($diff < 0) {
            $groups = array_slice($groups, 0, $diff);
        }

        return array_combine($keys, $groups);
    }

    /**
     * Builds full cache keys for given keys and groups.
     *
     * @since 1.0.0
     *
     * @param array $keys   A list of cache keys.
     * @param array $groups A list of cache groups, keyed by their key. The length of the array must match
     *                      the length of $keys.
     * @return array The list of full cache keys.
     */
    protected function buildKeys(array $keys, array $groups): array
    {
        $fullKeys = array();

        foreach ($keys as $key) {
            $fullKeys[] = $this->buildKey($key, $groups[$key]);
        }

        return $fullKeys;
    }

    /**
     * Load the API functions needed for WordPress integration.
     *
     * @since 1.0.0
     */
    public static function loadApi()
    {
        require_once dirname( __DIR__ ) . '/includes/functions.php';
    }

    /**
     * Get the main instance.
     *
     * This is a workaround because the static facade doesn't work here.
     * See https://stackoverflow.com/questions/31039380/callstatic-does-not-call-if-there-exist-a-non-static-function
     *
     * @since 1.0.0
     *
     * @return static The main object cache instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
