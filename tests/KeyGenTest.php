<?php
/**
 * Tests for the key generator class.
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

namespace LeavesAndLove\WpPsrCache\Tests;

use LeavesAndLove\WpPsrCache\CacheKeyGen\WpPsrCacheKeyGen;
use PHPUnit\Framework\TestCase;

class KeyGenTest extends TestCase
{

    private $keygen;

    private $globalGroups = array( 'users', 'networks', 'global_options' );

    private $networkGroups = array( 'sites', 'network_options' );

    public function setUp()
    {
        $this->keygen = new WpPsrCacheKeyGen(1, 1);
        $this->keygen->addGlobalGroups($this->globalGroups);
        $this->keygen->addNetworkGroups($this->networkGroups);
    }

    public function tearDown()
    {
        $this->keygen->switchSiteContext(1);
        $this->keygen->switchNetworkContext(1);
    }

    /**
     * @dataProvider dataGenerateCommon
     */
    public function testGenerateCommon(string $key, string $group, int $siteId, int $networkId, string $expected)
    {
        $this->keygen->switchSiteContext($siteId);
        $this->keygen->switchNetworkContext($networkId);

        $this->assertSame($expected, $this->keygen->generate($key, $group));
    }

    public function dataGenerateCommon()
    {
        return array(
            array('key1', 'site_options', 1, 1, 'site.1.site_options.key1'),
            array('key2', 'site_options', 2, 1, 'site.2.site_options.key2'),
            array('key3', 'site_options', 1, 2, 'site.1.site_options.key3'),
            array('key4', 'site_options', 2, 2, 'site.2.site_options.key4'),
            array('key5', 'network_options', 1, 1, 'network.1.network_options.key5'),
            array('key6', 'network_options', 2, 1, 'network.1.network_options.key6'),
            array('key7', 'network_options', 1, 2, 'network.2.network_options.key7'),
            array('key8', 'network_options', 2, 2, 'network.2.network_options.key8'),
            array('key9', 'global_options', 1, 1, 'global.global_options.key9'),
            array('key10', 'global_options', 2, 1, 'global.global_options.key10'),
            array('key11', 'global_options', 1, 2, 'global.global_options.key11'),
            array('key12', 'global_options', 2, 2, 'global.global_options.key12'),
        );
    }
}
