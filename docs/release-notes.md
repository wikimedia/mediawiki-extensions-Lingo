## Release Notes

### Lingo 3.0.0

Released on 06-May-2018

Changes:
* Raised minimum versions:
  * Mediawiki: 1.27
  * PHP: 5.5
* Allow HTML-formatted definitions for glossary entries (for supporting backends)
* Allow external link targets for glossary entries (for supporting backends)
* Disable tooltips for non-JavaScript browsers
* Disable tooltips outside the main content (including all Special pages)
* Rework of layout of HTML elements and associated styles
* Improved test coverage

Fixes:
* [T68915](https://phabricator.wikimedia.org/T68915) Text resulting from tag extensions is properly annotated
* [T180746](https://phabricator.wikimedia.org/T180746), [T147522](https://phabricator.wikimedia.org/T147522) Tooltips are now disabled for the Special namespace
* [T182261](https://phabricator.wikimedia.org/T182261) Section heads on Category pages are properly annotated
* [T123969](https://phabricator.wikimedia.org/T123969) Styles for printing fixed

### Lingo 2.0.3

Released on 24-May-2017

Fixes:
* Fix Fatal error: Call to undefined function Lingo\string()
* Fixed broken Special:Preferences
* Fixed missing link icon

### Lingo 2.0.2

Released on 03-May-2017

Changes:
* Update Readme for changes in customization procedure
* Removed usage of deprecated MediaWiki features
* Fix and add more tests
* Some reworking of BasicBackend and LingoParser
* Use PageContentSave hook for cache purging (instead of ArticleSave)
* Use GetDoubleUnderscoreIDs hook

Fixes:
* Add page position 'bottom' for loading of ext.Lingo.Scripts module
* Replace unsupported function calls in ArticleAnnotationTest
* Fixed noglossary tag hook
* Fixed language of extension description in Special:Version

### Lingo 2.0.1

Released on 13-Mar-2016

Changes:
* Improved test coverage

Fixes:
* Outdated cache entries are not loaded anymore

### Lingo 2.0.0

Released on 09-Mar-2016

Minimum MediaWiki version is now 1.26

Changes:
* Usage of new extension registration mechanism
* Removed usage of deprecated MediaWiki features
* Reworked file structure
* Introduced unit testing (incl. automatic regression testing)

Fixes:
* Fix "Balloc() allocation exceeds list boundary"
  ([Bug:T70710](https://phabricator.wikimedia.org/T70710))
* Explicitly require Lingo styles to be loaded at the page top

### Lingo 1.2.0

Released on 02-Aug-2014

Minimum MediaWiki version is now 1.20

Changes:
* Migrate to JSON i18n
* Remove wfMsg / wfMsgForContent usage
* Unstrip strip items of 'general' category
* Use ExtensionFunctions instead of SpecialVersionExtensionTypes

### Lingo 1.1.0

Released on 09-Mar-2014

Changes:
* new CSS class mw-lingo-tooltip-definition for definitions

Fixes:
* multiple definitions appear on the same line - 2nd attempt

### Lingo 1.0.1

Released on 03-Mar-2014

Fixes:
* multiple definitions appeared on the same line

### Lingo 1.0.0

Released on 02-Mar-2014

From here on this extension will use [Semantic Versioning](http://semver.org).

Changes:
* features introduced during the [Google Summer of Code 2013]
  (https://www.mediawiki.org/wiki/Google_Summer_of_Code_2013) by Yevheniy
  Vlasenko:
** ability to turn off the recognition of glossary terms by using
   `<noglossary></noglossary>` tags
** ability to use multiple definitions per term
** support the [ApprovedRevs]
   (https://www.mediawiki.org/wiki/Extension:ApprovedRevs) extension for the
   Terminology page
* introduce the [qTip2 library](http://qtip2.com) for the tooltips (and abandon
  the home-grown tooltips)
* enable transclusion of other pages on the Terminology page
* support setting per-term CSS styles (only in combination with [Semantic
  Glossary](https://www.mediawiki.org/wiki/Extension:SemanticGlossary)) (patch
  by Nathan Douglas)
* support the [Composer](https://getcomposer.org) dependency manager for PHP
* change the CSS class of elements from *tooltip* to *mw-lingo-tooltip* to avoid
  collisions, e.g. with the [Bootstrap](http://getbootstrap.com) framework

Fixes:
* Mark-up on the Terminology page is now immediately updated when new terms are
  saved

### Lingo 0.4.2

Released on 07-Apr-2013

Fixes:
* bugfix (links with terms broken for MW pre1.20). This re-introduces bug
  "annotation of text between nowiki tags" for MW pre1.20.

### Lingo 0.4.1

Released on 24-Feb-2013

Fixes:
* bugfix (Compatibility with MW pre1.20 broken, hook ParserAfterParse is not
  available)

### Lingo 0.4

Released on 21-Nov-2012

Changes:
* caching of the glossary
* special handling for IE6/IE7 (it's still ugly, but not as ugly as before)
* if for a term only a link and no definition is specified, insert that link
  (only works with [Semantic Glossary]
  (https://www.mediawiki.org/wiki/Extension:SemanticGlossary))
* improved internationalization

Fixes:
* bugfix (annotation of text between nowiki tags)
* bugfix (Non-static method DOMDocument::loadHTML() should not be called
  statically...)

### Lingo 0.3

Released on 03-Dec-2011

Changes:
* Allow use of one definition for more than one term, e.g. to allow for
  grammatical variants

Fixes:
* bugfix (javascript crashes in lingo-produced html)
* bugfix (incorrect relation of terms to definitions)
* bugfix (useless space)
* bugfix (Partial words highlighted in nonlatin scripts)

### Lingo 0.2.1

Released on 11-Oct-2011

Changes:
* improved internationalization

Fixes:
* bugfix (Incorrect handling of terms containing "0")

### Lingo 0.2

Released on 14-Jun-2011

Complete overhaul of extension

Changes:
* can use any characters in a term now (including punctuation, spaces, all UTF-8
  characters, but *excluding* the colon (:) of course)
* provides interface to plug in alternative dictionaries (see
  [Semantic Glossary](https://www.mediawiki.org/wiki/Extension:SemanticGlossary)
  for an example)
* provides a config setting $wgexLingoPage to specify the name of the
  terminology page
* provides a config setting $wgexLingoDisplayOnce to specify that each term
  should be annotated only once per page
* provides a config setting $wgexLingoUseNamespaces to specify what namespaces
  should be used
** to exclude e.g. namespace NS_TALK from marking up you have to set
   `$wgexLingoUseNamespaces[NS_TALK]=false;`
** everything not explicitly set to false will be marked up
* provides internationalization (e.g. for the default name of the Terminology
  page)
* ignores any element (e.g. div or table) with `class='noglossary'` (*not*
  internationalized)
* provides a magic word `__NOGLOSSARY__` (internationalized) to suppress the
  glossary for the respective article

### Lingo 0.1

Released on 26-May-2011

Maintenance taken over by [Stephan Gambke]
(https://www.mediawiki.org/wiki/User:F.trott) and committed to the Wikimedia
repository

### Lingo 0.14b

Released on 09-Jan-2011

Initial release by
[Barry Coughlan](https://www.mediawiki.org/wiki/User:bcoughlan)
