<?php

/**
 * File holding the Lingo\LingoParser class.
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2016, Stephan Gambke
 * @license   GNU General Public License, version 2 (or any later version)
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

use DOMDocument;
use DOMXPath;
use Parser;

/**
 * This class parses the given text and enriches it with definitions for defined
 * terms.
 *
 * Contains a static function to initiate the parsing.
 *
 * @ingroup Lingo
 */
class LingoParser {

	private $mLingoTree = null;

	/**
	 * @var Backend
	 */
	private $mLingoBackend = null;
	private static $parserSingleton = null;

	// The RegEx to split a chunk of text into words
	public $regex = null;

	/**
	 * Lingo\LingoParser constructor.
	 * @param MessageLog|null $messages
	 */
	public function __construct( MessageLog &$messages = null ) {
		// The RegEx to split a chunk of text into words
		// Words are: placeholders for stripped items, sequences of letters and numbers, single characters that are neither letter nor number
		$this->regex = '/' . preg_quote( Parser::MARKER_PREFIX, '/' ) . '.*?' . preg_quote( Parser::MARKER_SUFFIX, '/' ) . '|[\p{L}\p{N}]+|[^\p{L}\p{N}]/u';
	}

	/**
	 *
	 * @param Parser $parser
	 * @param string $text
	 * @return Boolean
	 */
	public function parse( Parser &$parser, &$text ) {

		// parse if
		if ( $this->shouldParse( $parser ) ) {

			// unstrip strip items of the 'general' group
			// this will be done again by parse when this hook returns, but it should not hurt to do this twice
			// Only problem is with other hook handlers that might not expect strip items to be unstripped already
			$text = $parser->mStripState->unstripGeneral( $text );
			$this->realParse( $parser, $text );
		}

		return true;
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
	private static function getCacheKey() {
		return wfMemcKey( 'ext', 'lingo', 'lingotree', Tree::TREE_VERSION, get_class( self::getInstance()->getBackend() ) );
	}

	/**
	 * @return Backend the backend used by the parser
	 * @throws \MWException
	 */
	public function getBackend() {

		if ( $this->mLingoBackend === null ) {
			throw new \MWException( 'No Lingo backend available!' );
		}

		return $this->mLingoBackend;
	}

	/**
	 * Returns the list of terms in the glossary
	 *
	 * @return Array an array mapping terms (keys) to descriptions (values)
	 */
	public function getLingoArray() {

		// build glossary array only once per request
		if ( !$this->mLingoTree ) {
			$this->buildLingo();
		}

		return $this->mLingoTree->getTermList();
	}

	/**
	 * Returns the list of terms in the glossary as a Lingo\Tree
	 *
	 * @return Tree a Lingo\Tree mapping terms (keys) to descriptions (values)
	 */
	public function getLingoTree() {

		// build glossary array only once per request
		if ( !$this->mLingoTree ) {

			// use cache if enabled
			if ( $this->mLingoBackend->useCache() ) {

				// Try cache first
				global $wgexLingoCacheType;
				$cache = ( $wgexLingoCacheType !== null ) ? wfGetCache( $wgexLingoCacheType ) : wfGetMainCache();
				$cachekey = $this->getCacheKey();
				$cachedLingoTree = $cache->get( $cachekey );

				// cache hit?
				if ( $cachedLingoTree !== false && $cachedLingoTree !== null ) {

					wfDebug( "Cache hit: Got lingo tree from cache.\n" );
					$this->mLingoTree = &$cachedLingoTree;

					wfDebug( "Re-cached lingo tree.\n" );
				} else {

					wfDebug( "Cache miss: Lingo tree not found in cache.\n" );
					$this->mLingoTree =& $this->buildLingo();
					wfDebug( "Cached lingo tree.\n" );
				}

				// Keep for one month
				// Limiting the cache validity will allow to purge stale cache
				// entries inserted by older versions after one month
				$cache->set( $cachekey, $this->mLingoTree, 60 * 60 * 24 * 30 );

			} else {
				wfDebug( "Caching of lingo tree disabled.\n" );
				$this->mLingoTree =& $this->buildLingo();
			}

		}

		return $this->mLingoTree;
	}

	/**
	 * @return Tree
	 */
	protected function &buildLingo() {

		$lingoTree = new Tree();
		$backend = &$this->mLingoBackend;

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
	 *
	 * @param $parser
	 * @param $text
	 * @return Boolean
	 */
	protected function realParse( &$parser, &$text ) {
		global $wgRequest;

		$action = $wgRequest->getVal( 'action', 'view' );

		if ( $text === null ||
			$text === '' ||
			$action === 'edit' ||
			$action === 'ajax' ||
			isset( $_POST[ 'wpPreview' ] )
		) {

			return true;
		}

		// Get array of terms
		$glossary = $this->getLingoTree();

		if ( $glossary == null ) {
			return true;
		}

		// Parse HTML from page
		\MediaWiki\suppressWarnings();

		$doc = new StashingDOMDocument( '1.0', 'utf-8' );
		$doc->loadHTML( '<html><head><meta http-equiv="content-type" content="charset=utf-8"/></head><body>' . $text . '</body></html>' );

		\MediaWiki\restoreWarnings();

		// Find all text in HTML.
		$xpath = new DOMXpath( $doc );
		$elements = $xpath->query(
			"//*[not(ancestor-or-self::*[@class='noglossary'] or ancestor-or-self::a)][text()!=' ']/text()"
		);

		// Iterate all HTML text matches
		$nb = $elements->length;
		$changedDoc = false;

		for ( $pos = 0; $pos < $nb; $pos++ ) {
			$el = $elements->item( $pos );

			if ( strlen( $el->nodeValue ) < $glossary->getMinTermLength() ) {
				continue;
			}

			$matches = array();
			preg_match_all(
				$this->regex,
				$el->nodeValue,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER
			);

			if ( count( $matches ) == 0 || count( $matches[ 0 ] ) == 0 ) {
				continue;
			}

			$lexemes = &$matches[ 0 ];
			$countLexemes = count( $lexemes );
			$parent = &$el->parentNode;
			$index = 0;
			$changedElem = false;

			while ( $index < $countLexemes ) {
				list( $skipped, $used, $definition ) =
					$glossary->findNextTerm( $lexemes, $index, $countLexemes );

				if ( $used > 0 ) { // found a term
					if ( $skipped > 0 ) { // skipped some text, insert it as is
						$parent->insertBefore(
							$doc->createTextNode(
								substr( $el->nodeValue,
									$currLexIndex = $lexemes[ $index ][ 1 ],
									$lexemes[ $index + $skipped ][ 1 ] - $currLexIndex )
							),
							$el
						);
					}

					$parent->insertBefore( $definition->getFullDefinition( $doc ), $el );

					$changedElem = true;
				} else { // did not find term, just use the rest of the text
					// If we found no term now and no term before, there was no
					// term in the whole element. Might as well not change the
					// element at all.
					// Only change element if found term before
					if ( $changedElem ) {
						$parent->insertBefore(
							$doc->createTextNode(
								substr( $el->nodeValue, $lexemes[ $index ][ 1 ] )
							),
							$el
						);
					} else {
						// In principle superfluous, the loop would run out
						// anyway. Might save a bit of time.
						break;
					}
				}

				$index += $used + $skipped;
			}

			if ( $changedElem ) {
				$parent->removeChild( $el );
				$changedDoc = true;
			}
		}

		if ( $changedDoc ) {
			$this->loadModules( $parser );

			// U - Ungreedy, D - dollar matches only end of string, s - dot matches newlines
			$text = preg_replace( '%(^.*<body>)|(</body>.*$)%UDs', '', $doc->saveHTML() );
		}

		return true;
	}

	/**
	 * @param Parser $parser
	 */
	protected function loadModules( &$parser ) {
		global $wgOut, $wgScriptPath;

		$parserOutput = $parser->getOutput();

		// load scripts
		$parserOutput->addModules( 'ext.Lingo.Scripts' );

		if ( !$wgOut->isArticle() ) {
			$wgOut->addModules( 'ext.Lingo.Scripts' );
		}

		// load styles
		$parserOutput->addModuleStyles( 'ext.Lingo.Styles' );

		if ( !$wgOut->isArticle() ) {
			$wgOut->addModuleStyles( 'ext.Lingo.Styles' );
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
		$cache = ( $wgexLingoCacheType !== null ) ? wfGetCache( $wgexLingoCacheType ) : wfGetMainCache();
		$cache->delete( self::getCacheKey() );
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
	 * @param Parser $parser
	 * @return bool
	 */
	protected function shouldParse( Parser &$parser ) {
		global $wgexLingoUseNamespaces;

		$title = $parser->getTitle();
		$namespace = $title->getNamespace();

		return !isset( $parser->mDoubleUnderscores[ 'noglossary' ] ) && // __NOGLOSSARY__ not present and
		(
			!$title || // title not set (i.e. when text is outside the page content) or
			!isset( $wgexLingoUseNamespaces[ $namespace ] ) || // namespace not explicitly forbidden (i.e. not in list of namespaces and set to false) or
			$wgexLingoUseNamespaces[ $namespace ] // namespace explicitly allowed
		);
	}
}

