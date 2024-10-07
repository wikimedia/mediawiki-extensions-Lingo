<?php

/**
 * File holding the Lingo\LingoParser class.
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
 * @license GPL-2.0-or-later
 *
 * The Lingo extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * The Lingo extension is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Stephan Gambke
 *
 * @file
 * @ingroup Lingo
 */
namespace Lingo;

use BagOStuff;
use DOMDocument;
use DOMXPath;
use ObjectCache;
use Parser;
use Wikimedia\AtEase\AtEase;

/**
 * This class parses the given text and enriches it with definitions for defined
 * terms.
 *
 * Contains a static function to initiate the parsing.
 *
 * @ingroup Lingo
 */
class LingoParser {

	private const WORD_OFFSET = 1;

	private const CACHE_LIFETIME = 60 * 60 * 24 * 30; // 30 days

	/** @var Tree|null */
	private $mLingoTree = null;

	/**
	 * @var Backend|null
	 */
	private $mLingoBackend = null;
	/** @var LingoParser|null */
	private static $parserSingleton = null;

	/** @var array|null Api params passed in from ApiMakeParserOptions Hook */
	private $mApiParams = null;

	/** @var string|null The RegEx to split a chunk of text into words */
	public $regex = null;

	public function __construct() {
		// The RegEx to split a chunk of text into words
		// Words are: placeholders for stripped items, sequences of letters and numbers, single characters that are neither letter nor number
		$this->regex = '/' . preg_quote( Parser::MARKER_PREFIX, '/' ) . '.*?' . preg_quote( Parser::MARKER_SUFFIX, '/' ) . '|[\p{L}\p{N}]+|[^\p{L}\p{N}]/u';
	}

	public function parse( Parser $mwParser ) {
		if ( $this->shouldParse( $mwParser ) ) {
			$this->realParse( $mwParser );
		}
	}

	/**
	 * @return LingoParser
	 * @since 2.0.1
	 */
	public static function getInstance() {
		if ( !self::$parserSingleton ) {
			self::$parserSingleton = new LingoParser();
		}

		return self::$parserSingleton;
	}

	/**
	 * @return string
	 */
	private function getCacheKey() {
		// FIXME: If Lingo ever stores the glossary tree per user, then the cache key also needs to include the user id (see T163608)
		return ObjectCache::getLocalClusterInstance()->makeKey( 'ext', 'lingo', 'lingotree', Tree::TREE_VERSION, get_class( $this->getBackend() ) );
	}

	/**
	 * @return Backend the backend used by the parser
	 * @throws \MWException
	 */
	private function getBackend() {
		if ( $this->mLingoBackend === null ) {
			throw new \MWException( 'No Lingo backend available!' );
		}

		return $this->mLingoBackend;
	}

	/**
	 * Returns the list of terms in the glossary as a Lingo\Tree
	 *
	 * @return Tree a Lingo\Tree mapping terms (keys) to descriptions (values)
	 */
	private function getLingoTree( array $searchTerms ) {
		$useCache = $this->getBackend()->useCache();

		// build glossary array only once per request
		if ( !$this->mLingoTree ) {
			// use cache if enabled
			if ( $useCache ) {
				// Try cache first
				$cache = $this->getCacheInstance();
				$cachekey = $this->getCacheKey();
				$cachedLingoTree = $cache->get( $cachekey );

				// cache hit?
				if ( $cachedLingoTree !== false && $cachedLingoTree !== null ) {
					wfDebug( "Cache hit: Got lingo tree from cache.\n" );
					$this->mLingoTree = &$cachedLingoTree;

					wfDebug( "Re-cached lingo tree.\n" );
				} else {
					wfDebug( "Cache miss: Lingo tree not found in cache.\n" );
					$this->mLingoTree = $this->buildLingo( $searchTerms );

					// Keep for one month
					// Limiting the cache validity will allow to purge stale cache
					// entries inserted by older versions after one month
					$cache->set( $cachekey, $this->mLingoTree, self::CACHE_LIFETIME );

					wfDebug( "Cached lingo tree.\n" );
				}
			} else {
				wfDebug( "Caching of lingo tree disabled.\n" );
				$this->mLingoTree = $this->buildLingo( $searchTerms );
			}
		}

		return $this->mLingoTree;
	}

	/**
	 * @return Tree
	 */
	private function buildLingo( array $searchTerms ) {
		$lingoTree = new Tree();
		$backend = $this->mLingoBackend;
		$backend->setSearchTerms( $searchTerms );

		// assemble the result array
		while ( $elementData = $backend->next() ) {
			$lingoTree->addTerm( $elementData[ Element::ELEMENT_TERM ], $elementData );
		}

		return $lingoTree;
	}

	/**
	 * Parses the given text and enriches applicable terms
	 *
	 * This method currently only recognizes terms consisting of max one word
	 */
	private function realParse( Parser $parser ): void {
		// Parse text identical to options used in includes/api/ApiParse.php
		$params = $this->mApiParams;
		$text = $params === null ? $parser->getOutput()->getText() : $parser->getOutput()->getText( [
			'allowTOC' => !$params['disabletoc'],
			'enableSectionEditLinks' => !$params['disableeditsection'],
			'wrapperDivClass' => $params['wrapoutputclass'],
			'deduplicateStyles' => !$params['disablestylededuplication'],
		] );

		if ( $text === null || $text === '' ) {
			return;
		}
		// Parse HTML from page

		// Suppress warnings during DOMDocument loading
		AtEase::suppressWarnings();
		$doc = new DOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( '<html><head><meta http-equiv="content-type" content="charset=utf-8"/></head><body>' . $text . '</body></html>' );
		AtEase::restoreWarnings();

		// Find all text in HTML.
		$xpath = new DOMXPath( $doc );
		$textElements = $xpath->query(
			"//*[not(ancestor-or-self::*[@class='noglossary'] or ancestor-or-self::a)][text()!=' ']/text()"
		);

		// Collect all terms from text elements
		$allTermsInText = [];

		// Iterate all HTML text matches to collect terms
		foreach ( $textElements as $textElement ) {
			$matches = [];
			preg_match_all(
				$this->regex,
				$textElement->nodeValue,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER
			);

			if ( count( $matches ) === 0 || count( $matches[ 0 ] ) === 0 ) {
				continue;
			}

			$termsInText = array_map( static fn ( $match ) => $match[0], $matches[0] );
			$allTermsInText = array_merge( $allTermsInText, $termsInText );
		}

		// Remove duplicates and empty strings
		$allTermsInText = array_unique(
			array_filter(
				array_map( 'trim', $allTermsInText ),
				static fn ( $item ) => !empty( $item )
			)
		);

		// Get array of terms once
		$glossary = $this->getLingoTree( $allTermsInText );

		if ( $glossary == null || count( $glossary->getTermList() ) === 0 ) {
			return;
		}

		// Now iterate again to process each text element using the built glossary
		$definitions = [];

		foreach ( $textElements as $textElement ) {
			$matches = [];
			preg_match_all(
				$this->regex,
				$textElement->nodeValue,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER
			);

			if ( count( $matches ) === 0 || count( $matches[ 0 ] ) === 0 ) {
				continue;
			}

			if ( strlen( $textElement->nodeValue ) < $glossary->getMinTermLength() ) {
				continue;
			}

			$wordDescriptors = &$matches[ 0 ]; // See __construct() for definition of "word"
			$numberOfWordDescriptors = count( $wordDescriptors );

			$parentNode = &$textElement->parentNode;

			$wordDescriptorIndex = 0;
			$changedElem = false;

			while ( $wordDescriptorIndex < $numberOfWordDescriptors ) {
				/** @var \Lingo\Element $definition */
				[ $skippedWords, $usedWords, $definition ] =
					$glossary->findNextTerm( $wordDescriptors, $wordDescriptorIndex, $numberOfWordDescriptors );

				if ( $usedWords > 0 ) { // found a term
					if ( $skippedWords > 0 ) { // skipped some text, insert it as is
						$start = $wordDescriptors[ $wordDescriptorIndex ][ self::WORD_OFFSET ];
						$length = $wordDescriptors[ $wordDescriptorIndex + $skippedWords ][ self::WORD_OFFSET ] - $start;

						$parentNode->insertBefore(
							$doc->createTextNode(
								substr( $textElement->nodeValue, $start, $length )
							),
							$textElement
						);
					}

					$parentNode->insertBefore( $definition->getFormattedTerm( $doc ), $textElement );

					$definitions[ $definition->getId() ] = $definition->getFormattedDefinitions();

					$changedElem = true;
				} else { // did not find any term, just use the rest of the text
					if ( $changedElem === true ) {
						$start = $wordDescriptors[ $wordDescriptorIndex ][ self::WORD_OFFSET ];

						$parentNode->insertBefore(
							$doc->createTextNode(
								substr( $textElement->nodeValue, $start )
							),
							$textElement
						);
					}
					break;
				}

				$wordDescriptorIndex += $usedWords + $skippedWords;
			}

			if ( $changedElem ) {
				$parentNode->removeChild( $textElement );
			}
		}

		if ( count( $definitions ) > 0 ) {
			$this->loadModules( $parser );

			// U - Ungreedy, D - dollar matches only end of string, s - dot matches newlines
			$text = preg_replace( '%(^.*<body>)|(</body>.*$)%UDs', '', $doc->saveHTML() );
			$text .= $parser->recursiveTagParseFully( implode( $definitions ) );

			$parser->getOutput()->setText( $text );
		}
	}

	/**
	 * @param Parser $parser
	 */
	private function loadModules( $parser ) {
		global $wgOut, $wgexLingoWCAGStyle;

		$parserOutput = $parser->getOutput();

		$modules = [ 'ext.Lingo' ];
		$moduleStyles = [ 'ext.Lingo.styles' ];

		if ( $wgexLingoWCAGStyle ) {
			$moduleStyles[] = 'ext.Lingo.WCAG.styles';
		}

		// load scripts
		$parserOutput->addModules( $modules );
		$parserOutput->addModuleStyles( $moduleStyles );

		if ( !$wgOut->isArticle() ) {
			$wgOut->addModules( $modules );
			$wgOut->addModuleStyles( $moduleStyles );
		}
	}

	/**
	 * Purges the lingo tree from the cache.
	 *
	 * @deprecated 2.0.2
	 */
	public static function purgeCache() {
		self::getInstance()->purgeGlossaryFromCache();
	}

	/**
	 * Purges the lingo tree from the cache.
	 *
	 * @since 2.0.2
	 */
	public function purgeGlossaryFromCache() {
		global $wgexLingoCacheType;
		$cache = ( $wgexLingoCacheType !== null )
			? ObjectCache::getInstance( $wgexLingoCacheType )
			: ObjectCache::getLocalClusterInstance();
		$cache->delete( $this->getCacheKey() );
	}

	/**
	 * @since 2.0.1
	 * @param Backend $backend
	 */
	public function setBackend( Backend $backend ) {
		$this->mLingoBackend = $backend;
		$backend->setLingoParser( $this );
	}

	/**
	 * Set parser options from API
	 *
	 * @param array $params
	 */
	public function setApiParams( array $params ) {
		$this->mApiParams = $params;
	}

	private function shouldParse( Parser $parser ): bool {
		global $wgexLingoUseNamespaces;

		if ( !$parser->getOutput() || !$parser->getOutput()->hasText() ) {
			return false;
		}

		if ( isset( $parser->getOutput()->getPageProperties()['noglossary'] ) ) { // __NOGLOSSARY__ found in wikitext
			return false;
		}

		$namespace = $parser->getTitle()->getNamespace();
		return $wgexLingoUseNamespaces[$namespace] ?? true;
	}

	private function getCacheInstance(): BagOStuff {
		global $wgexLingoCacheType;
		return ( $wgexLingoCacheType !== null )
			? ObjectCache::getInstance( $wgexLingoCacheType )
			: ObjectCache::getLocalClusterInstance();
	}
}
