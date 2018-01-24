<?php
/**
 * Class WpPsrCacheKeyGen
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\CacheKeyGen;

/**
 * WordPress PSR cache key generator class.
 *
 * @since 1.0.0
 */
class WpPsrCacheKeyGen implements WpCacheKeyGen
{

    /** @var array List of global cache groups. */
    protected $globalGroups = array();

    /** @var array List of network cache groups. */
    protected $networkGroups = array();

    /** @var int Current site ID. */
    protected $siteId;

    /** @var int Current network ID. */
    protected $networkId;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param int $siteId    Initial site ID.
     * @param int $networkId Initial network ID.
     */
    public function __construct(int $siteId, int $networkId)
    {
        $this->switchSiteContext($siteId);
        $this->switchNetworkContext($networkId);
    }

    /**
     * Generate the full cache key for a given key and group.
     *
     * @since 1.0.0
     *
     * @param string $key   A cache key.
     * @param string $group A cache group.
     * @return string The full cache key to use with cache implementations.
     */
    public function generate(string $key, string $group): string
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

        return $this->sanitize($key);
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
     * Sanitize a cache key by replacing unsupported characters.
     *
     * @since 1.0.0
     *
     * @param string $key A cache key.
     * @return string The sanitized cache key.
     */
    protected function sanitize(string $key): string
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
}
