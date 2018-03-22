# Lingo
[![Build Status](https://travis-ci.org/wikimedia/mediawiki-extensions-Lingo.svg?branch=master)](https://travis-ci.org/wikimedia/mediawiki-extensions-Lingo/builds)
[![Code Coverage](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Lingo/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Lingo/?branch=master)
[![Code Quality](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Lingo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wikimedia/mediawiki-extensions-Lingo/?branch=master)
[![Dependency Status](https://www.versioneye.com/php/mediawiki:lingo/badge.png)](https://www.versioneye.com/php/mediawiki:lingo)
[![Latest Stable Version](https://poser.pugx.org/mediawiki/lingo/version.png)](https://packagist.org/packages/mediawiki/lingo)
[![Packagist download count](https://poser.pugx.org/mediawiki/lingo/d/total.png)](https://packagist.org/packages/mediawiki/lingo)

Lingo is a glossary extension to MediaWiki, that lets you define abbreviations
and their definitions on a wiki page. It displays these definitions whenever an
abbreviation is hovered over in an article.

See http://www.mediawiki.org/wiki/Extension:Lingo for online documentation.

## Requirements

- PHP 5.5 or later
- MediaWiki 1.27 or later

## Installation & Activation

The recommended way to install this extension is by using [Composer][composer].

1. Add the following to the MediaWiki `composer.local.json` file
 ```json
 {
 	"require": {
 		"mediawiki/lingo": "~3.0"
 	}
 }
 ```

2.  Run `php composer.phar update mediawiki/lingo` from the MediaWiki
    installation directory.

3. Add the following code to your LocalSettings.php:
 ```php
 wfLoadExtension('Lingo');
 ```

## Updating

Run `php composer.phar update mediawiki/lingo` from the MediaWiki installation
directory.

## Customization

Add the following to `LocalSettings.php` and uncomment/modify as needed:

```php
$wgHooks['SetupAfterCache'][] = function() {

    // specify a different name for the terminology page (Default: 'Terminology' (or localised version). See MediaWiki:Lingo-terminologypagename.)
    //$GLOBALS['wgexLingoPage'] = 'Terminology';

    // specify that each term should be annotated only once per page (Default: false)
    //$GLOBALS['wgexLingoDisplayOnce'] = false;

    // specify what namespaces should or should not be used (Default: Empty, i.e. use all namespaces)
    //$GLOBALS['wgexLingoUseNamespaces'][NS_SPECIAL] = false;

    // set default cache type (Default: null, i.e. use main cache)
    //$GLOBALS['wgexLingoCacheType'] = CACHE_NONE;

    // use ApprovedRevs extension on the Terminology page (Default: false)
    //$GLOBALS['wgexLingoEnableApprovedRevs'] = true;

};
```

## Usage

By default Lingo will mark up any page that is not in a forbidden namespace. To
exclude a page from markup you can include __NOGLOSSARY__ anywhere in the
article. In some cases it may be necessary to exclude portions of a page, e.g.
because Lingo interferes with some JavaScript. This can be achieved by wrapping
the part in an HTML element (e.g. a span or a div) and specify class="noglossary".

### Terminology page

Create the page "Terminology" (no namespace), and insert some entries using
the following syntax:

;FTP:File Transport Protocol
;AAAAA:American Association Against Acronym Abuse
;ACK:Acknowledge
;AFAIK:As Far As I Know
;AWGTHTGTATA:Are We Going To Have To Go Through All This Again
;HTTP:HyperText Transfer Protocol

## Running tests

From the `Lingo` directory run
```bash
php ../../tests/phpunit/phpunit.php  --group extensions-lingo -c phpunit.xml.dist
```

## Reporting bugs

Comments, questions and suggestions should be sent or posted to:
* the Lingo discussion page: https://www.mediawiki.org/wiki/Extension_talk:Lingo
* the maintainer: https://www.mediawiki.org/wiki/Special:EmailUser/F.trott

## Credits

Lingo is a rewrite of Extension:Terminology, written by BarkerJr with
modifications by Benjamin Kahn. It was originally written by Barry Coughlan and
is currently maintained by Stephan Gambke.

## License

[GNU General Public License 2.0][license] or later.

[composer]: https://getcomposer.org/
[license]: https://www.gnu.org/copyleft/gpl.html
