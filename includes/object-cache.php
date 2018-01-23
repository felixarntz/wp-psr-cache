<?php
/**
 * Object cache drop-in
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCache;

defined( 'ABSPATH' ) || exit;

ObjectCache::loadApi();

ObjectCache::getInstance()->setPersistentCache( /* Pass the persistent cache instance here. */ );
ObjectCache::getInstance()->setNonPersistentCache( /* Pass the non-persistent cache instance here. */ );
