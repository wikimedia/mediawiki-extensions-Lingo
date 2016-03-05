<?php

/**
 * File holding the Extensions\Lingo\LingoElement class.
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

namespace Extensions\Lingo;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use MWException;
use Title;

/**
 * This class represents a term-definition pair.
 * One term may be related to several definitions.
 *
 * @ingroup Lingo
 */
class LingoElement {
	const ELEMENT_TERM = 0;
	const ELEMENT_DEFINITION = 1;
	const ELEMENT_SOURCE = 2;
	const ELEMENT_LINK = 3;
	const ELEMENT_STYLE = 4;

	const ELEMENT_FIELDCOUNT = 5;  // number of fields stored for each element; (last field's index) + 1
	static private $mLinkTemplate = null;
	private $mFullDefinition = null;
	private $mDefinitions = array();
	private $mTerm = null;
	private $mHasBeenDisplayed = false;

	/**
	 * Extensions\Lingo\LingoElement constructor.
	 * @param $term
	 * @param $definition
	 */
	public function __construct( &$term, &$definition = null ) {

		$this->mTerm = $term;

		if ( $definition ) {
			$this->addDefinition( $definition );
		}
	}

	/**
	 * @param $definition
	 */
	public function addDefinition( &$definition ) {
		$this->mDefinitions[] = array_pad( $definition, self::ELEMENT_FIELDCOUNT, null );
	}

	/**
	 * @param DOMDocument $doc
	 * @return DOMNode|DOMText
	 */
	public function getFullDefinition( DOMDocument &$doc ) {

		global $wgexLingoDisplayOnce;

		if ( $wgexLingoDisplayOnce && $this->mHasBeenDisplayed ) {
			return $doc->createTextNode( $this->mTerm );
		}

		$this->buildFullDefinition( $doc );
		$this->mHasBeenDisplayed = true;

		return $this->mFullDefinition->cloneNode( true );
	}

	/**
	 * @param DOMDocument $doc
	 * @return DOMDocument
	 */
	private function buildFullDefinition( DOMDocument &$doc ) {

		// only create if not yet created
		if ( $this->mFullDefinition === null || $this->mFullDefinition->ownerDocument !== $doc ) {

			if ( $this->isSimpleLink() ) {
				$this->mFullDefinition = $this->getFullDefinitionAsLink( $doc );
			} else {
				$this->mFullDefinition = $this->getFullDefinitionAsTooltip( $doc );
			}
		}
	}

	/**
	 * @return bool
	 */
	private function isSimpleLink() {
		return count( $this->mDefinitions ) === 1 &&
			!is_string( $this->mDefinitions[ 0 ][ self::ELEMENT_DEFINITION ] ) &&
			is_string( $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] );
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMElement
	 * @throws MWException
	 */
	protected function getFullDefinitionAsLink( DOMDocument &$doc ) {

		// create Title object for target page
		$target = Title::newFromText( $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] );

		// create link element
		$link = $doc->createElement( 'a', $this->mDefinitions[ 0 ][ self::ELEMENT_TERM ] );

		// set the link target
		$link->setAttribute( 'href', $target->getLinkUrl() );
		$link = $this->addClassAttributeToLink( $target, $link );
		$link = $this->addTitleAttributeToLink( $target, $link );

		return $link;
	}

	/**
	 * @param $target
	 * @param $link
	 */
	protected function &addClassAttributeToLink( $target, &$link ) {

		// TODO: should this be more elaborate? See Linker::linkAttribs
		// Cleanest would probably be to use Linker::link and parse it
		// back into a DOMElement, but we are in a somewhat time-critical
		// part here.
		$classes = '';

		if ( !$target->isKnown() ) {
			$classes .= 'new ';
		}

		if ( $target->isExternal() ) {
			$classes .= 'extiw ';
		}

		// set style
		$classes .= $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ];

		if ( $classes !== '' ) {
			$link->setAttribute( 'class', $classes );
		}

		return $link;
	}

	/**
	 * @param $target
	 * @param $link
	 */
	protected function &addTitleAttributeToLink( $target, &$link ) {

		if ( $target->getPrefixedText() === '' ) {
			// A link like [[#Foo]].  This used to mean an empty title
			// attribute, but that's silly.  Just don't output a title.
		} elseif ( $target->isKnown() ) {
			$link->setAttribute( 'title', $target->getPrefixedText() );
		} else {
			$link->setAttribute( 'title', wfMessage( 'red-link-title', $target->getPrefixedText() )->text() );
		}

		return $link;
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function getFullDefinitionAsTooltip( DOMDocument &$doc ) {

		// Wrap term and definition in <span> tags
		$span = $doc->createElement( 'span' );
		$span->setAttribute( 'class', 'mw-lingo-tooltip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );

		// Wrap term in <span> tag, hidden
		wfSuppressWarnings();
		$spanTerm = $doc->createElement( 'span', htmlentities( $this->mTerm, ENT_COMPAT, 'UTF-8' ) );

		wfRestoreWarnings();
		$spanTerm->setAttribute( 'class', 'mw-lingo-tooltip-abbr' );

		// Wrap definition in a <span> tag
		$spanDefinition = $doc->createElement( 'span' );
		$spanDefinition->setAttribute( 'class', 'mw-lingo-tooltip-tip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );

		foreach ( $this->mDefinitions as $definition ) {
			wfSuppressWarnings();
			$element = $doc->createElement( 'span', htmlentities( $definition[ self::ELEMENT_DEFINITION ], ENT_COMPAT, 'UTF-8' ) );
			$element->setAttribute( 'class', 'mw-lingo-tooltip-definition ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );
			wfRestoreWarnings();
			if ( $definition[ self::ELEMENT_LINK ] ) {
				$linkedTitle = Title::newFromText( $definition[ self::ELEMENT_LINK ] );
				if ( $linkedTitle ) {
					$link = $this->getLinkTemplate( $doc );
					$link->setAttribute( 'href', $linkedTitle->getFullURL() );
					$element->appendChild( $link );
				}
			}
			$spanDefinition->appendChild( $element );
		}

		// insert term and definition
		$span->appendChild( $spanTerm );
		$span->appendChild( $spanDefinition );
		return $span;
	}

	/**
	 * @param DOMDocument $doc
	 * @return DOMNode
	 */
	private function getLinkTemplate( DOMDocument &$doc ) {
		// create template if it does not yet exist
		if ( !self::$mLinkTemplate || ( self::$mLinkTemplate->ownerDocument !== $doc ) ) {
			global $wgScriptPath;

			$linkimage = $doc->createElement( 'img' );
			$linkimage->setAttribute( 'src', $wgScriptPath . '/extensions/Lingo/styles/linkicon.png' );

			self::$mLinkTemplate = $doc->createElement( 'a' );
			self::$mLinkTemplate->appendChild( $linkimage );
		}

		return self::$mLinkTemplate->cloneNode( true );
	}

	/**
	 * @return mixed
	 */
	public function getCurrentKey() {
		return key( $this->mDefinitions );
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getTerm( $key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_TERM ];
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getSource( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_SOURCE ];
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getDefinition( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_DEFINITION ];
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getLink( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_LINK ];
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getStyle( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_STYLE ];
	}

	public function next() {
		next( $this->mDefinitions );
	}

}
