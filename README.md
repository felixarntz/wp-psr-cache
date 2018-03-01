[![Build Status](https://api.travis-ci.org/felixarntz/wp-psr-cache.png?branch=master)](https://travis-ci.org/felixarntz/wp-psr-cache)
[![All Contributors](https://img.shields.io/badge/all_contributors-4-orange.svg?style=flat-square)](#contributors)
[![Code Climate](https://codeclimate.com/github/felixarntz/wp-psr-cache/badges/gpa.svg)](https://codeclimate.com/github/felixarntz/wp-psr-cache)
[![Test Coverage](https://codeclimate.com/github/felixarntz/wp-psr-cache/badges/coverage.svg)](https://codeclimate.com/github/felixarntz/wp-psr-cache/coverage)
[![Latest Stable Version](https://poser.pugx.org/felixarntz/wp-psr-cache/version)](https://packagist.org/packages/felixarntz/wp-psr-cache)
[![License](https://poser.pugx.org/felixarntz/wp-psr-cache/license)](https://packagist.org/packages/felixarntz/wp-psr-cache)

# WP PSR Cache

Object cache implementation for WordPress that acts as an adapter for PSR-6 and PSR-16 caching libraries.

## What do PSR-6 and PSR-16 mean?

[PSR-6](http://www.php-fig.org/psr/psr-6/) and [PSR-16](http://www.php-fig.org/psr/psr-16/) are standards established by the [PHP-FIG](http://www.php-fig.org/) organization. These standards are commonly used in PHP projects of any kind (WordPress is unfortunately an exception), and since this library acts as an adapter, you can use any compatible caching library of your choice with WordPress now. Popular examples include the [Symfony Cache Component](https://github.com/symfony/cache) or [Stash](https://github.com/tedious/Stash).

## Features

* Any PSR-6 or PSR-16 cache implementation can be used
* Persistent and non-persistent cache implementations can be individually specified
* Support for reading/writing/deleting multiple cache keys at once
* Only checks persistent cache if value not already present in non-persistent cache
* Full multisite support, including site and network switching
* Allows registration of further cache implementations for fine-grained control per cache group

## How to Install

Require this library as a dependency when managing your project with Composer (for example by using [Bedrock](https://github.com/roots/bedrock)). You also have to install an actual [PSR-6](https://packagist.org/providers/psr/cache-implementation) or [PSR-16](https://packagist.org/providers/psr/simple-cache-implementation) cache implementation.

After the installation, you need to move the `includes/object-cache.php` file into your `wp-content` directory. If you prefer, you can also automate that process by adding the following to your project's `composer.json`:

```
	"scripts": {
		"post-install-cmd": [
			"cp -rp web/app/mu-plugins/wp-psr-cache/includes/object-cache.php web/app/object-cache.php"
		]
	}
```

Then, replace the inline comment in the `object-cache.php` file with the actual instantiations of the classes you want to use. You need to provide two implementations, one for the persistent cache and another for the non-persistent cache.

To prevent conflicts with multiple WordPress installations accessing the same cache service, it is recommended to define a unique `WP_CACHE_KEY_SALT` constant in your `wp-config.php` file.

### Example

The following example uses the `symfony/cache` library, so you have to require it in your `composer.json`. It then uses that library's Memcached implementation as persistent cache and its array storage as non-persistent cache.

```php
<?php
/**
 * Object cache drop-in
 *
 * @package LeavesAndLove\WpPsrCache
 * @license GNU General Public License, version 2
 * @link    https://github.com/felixarntz/wp-psr-cache
 */

use LeavesAndLove\WpPsrCache\ObjectCacheService;
use Symfony\Component\Cache\Simple\MemcachedCache;
use Symfony\Component\Cache\Simple\ArrayCache;

defined( 'ABSPATH' ) || exit;

ObjectCacheService::loadApi();

/**
 * Defines and thus starts the object cache.
 *
 * @since 1.0.0
 */
function wp_psr_start_cache() {
	$memcached = new Memcached();
	$memcached->addServer( '127.0.0.1', 11211, 20 );

	wp_cache_start( new MemcachedCache( $memcached ), new ArrayCache() );
}

wp_psr_start_cache();

```

If you prefer to have more granular control and use more than just one persistent and one non-persistent cache, you can register additional cache adapters using the [`LeavesAndLove\WpPsrCache\CacheSelector\CacheSelector`](https://github.com/felixarntz/wp-psr-cache/blob/master/src/CacheSelector/CacheSelector.php) interface. The implementation used by the object cache can easily be retrieved via `wp_object_cache()->getSelector()`.

## Requirements

* PHP >= 7.0

## Contributors

Thanks goes to these wonderful people ([emoji key](https://github.com/kentcdodds/all-contributors#emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore -->
| [<img src="https://avatars1.githubusercontent.com/u/3531426?v=4" width="100px;"/><br /><sub><b>Felix Arntz</b></sub>](https://leaves-and-love.net)<br />[💻](https://github.com/felixarntz/wp-psr-cache/commits?author=felixarntz "Code") [🐛](https://github.com/felixarntz/wp-psr-cache/issues?q=author%3Afelixarntz "Bug reports") [📖](https://github.com/felixarntz/wp-psr-cache/commits?author=felixarntz "Documentation") [💡](#example-felixarntz "Examples") [🤔](#ideas-felixarntz "Ideas, Planning, & Feedback") [⚠️](https://github.com/felixarntz/wp-psr-cache/commits?author=felixarntz "Tests") | [<img src="https://avatars1.githubusercontent.com/u/83631?v=4" width="100px;"/><br /><sub><b>Alain Schlesser</b></sub>](https://www.alainschlesser.com/)<br />[💻](https://github.com/felixarntz/wp-psr-cache/commits?author=schlessera "Code") [🐛](https://github.com/felixarntz/wp-psr-cache/issues?q=author%3Aschlessera "Bug reports") [👀](#review-schlessera "Reviewed Pull Requests") | [<img src="https://avatars2.githubusercontent.com/u/6049306?v=4" width="100px;"/><br /><sub><b>Thorsten Frommen</b></sub>](https://tfrommen.de)<br />[👀](#review-tfrommen "Reviewed Pull Requests") | [<img src="https://avatars0.githubusercontent.com/u/2005352?v=4" width="100px;"/><br /><sub><b>Jip</b></sub>](http://www.jipmoors.nl)<br />[🤔](#ideas-moorscode "Ideas, Planning, & Feedback") |
| :---: | :---: | :---: | :---: |
<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors](https://github.com/kentcdodds/all-contributors) specification. Contributions of any kind welcome!