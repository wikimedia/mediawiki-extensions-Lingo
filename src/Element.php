<?php

/**
 * File holding the Lingo\Element class.
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

use DOMElement;
use DOMNode;
use DOMText;
use Title;

/**
 * This class represents a term-definition pair.
 * One term may be related to several definitions.
 *
 * @ingroup Lingo
 */
class Element {
	const ELEMENT_TERM = 0;
	const ELEMENT_DEFINITION = 1;
	const ELEMENT_SOURCE = 2;
	const ELEMENT_LINK = 3;
	const ELEMENT_STYLE = 4;

	const ELEMENT_FIELDCOUNT = 5;  // number of fields stored for each element; (last field's index) + 1

	const LINK_TEMPLATE_ID = 'LingoLink';

	private $mFullDefinition = null;
	private $mDefinitions = array();
	private $mTerm = null;
	private $mHasBeenDisplayed = false;

	/**
	 * Lingo\Element constructor.
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
	 * @param StashingDOMDocument $doc
	 * @return DOMNode|DOMText
	 */
	public function getFullDefinition( StashingDOMDocument &$doc ) {

		global $wgexLingoDisplayOnce;

		if ( $wgexLingoDisplayOnce && $this->mHasBeenDisplayed ) {
			return $doc->createTextNode( $this->mTerm );
		}

		$this->buildFullDefinition( $doc );
		$this->mHasBeenDisplayed = true;

		return $this->mFullDefinition->cloneNode( true );
	}

	/**
	 * @param StashingDOMDocument $doc
	 */
	private function buildFullDefinition( StashingDOMDocument &$doc ) {

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
	 * @param StashingDOMDocument $doc
	 * @return DOMElement
	 * @throws \MWException
	 */
	protected function getFullDefinitionAsLink( StashingDOMDocument &$doc ) {

		// create Title object for target page
		$target = Title::newFromText( $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] );

		if ( !$target instanceof Title ) {
			$errorMessage = wfMessage( 'lingo-invalidlinktarget', $this->mTerm, $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] )->text();
			$errorDefinition = array( self::ELEMENT_DEFINITION => $errorMessage, self::ELEMENT_STYLE => 'invalid-link-target' );
			$this->addDefinition( $errorDefinition );
			return $this->getFullDefinitionAsTooltip( $doc );
		}

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
	 * @return mixed
	 */
	protected function &addClassAttributeToLink( $target, &$link ) {

		// TODO: should this be more elaborate? See Linker::linkAttribs
		// Cleanest would probably be to use Linker::link and parse it
		// back into a DOMElement, but we are in a somewhat time-critical
		// part here.

		// set style
		$classes = string( $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );

		if ( !$target->isKnown() ) {
			$classes .= ' new';
		}

		if ( $target->isExternal() ) {
			$classes .= ' extiw';
		}

		$classes = trim( $classes );

		if ( $classes !== '' ) {
			$link->setAttribute( 'class', $classes );
		}

		return $link;
	}

	/**
	 * @param $target
	 * @param $link
	 * @return mixed
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
	 * @param StashingDOMDocument $doc
	 * @return string
	 * @throws \MWException
	 */
	protected function getFullDefinitionAsTooltip( StashingDOMDocument &$doc ) {

		// Wrap term and definition in <span> tags
		$span = $doc->createElement( 'span' );
		$span->setAttribute( 'class', 'mw-lingo-tooltip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );

		// Wrap term in <span> tag, hidden
		\MediaWiki\suppressWarnings();
		$spanTerm = $doc->createElement( 'span', htmlentities( $this->mTerm, ENT_COMPAT, 'UTF-8' ) );
		\MediaWiki\restoreWarnings();

		$spanTerm->setAttribute( 'class', 'mw-lingo-tooltip-abbr' );

		// Wrap definition in a <span> tag
		$spanDefinition = $doc->createElement( 'span' );
		$spanDefinition->setAttribute( 'class', 'mw-lingo-tooltip-tip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );

		foreach ( $this->mDefinitions as $definition ) {

			\MediaWiki\suppressWarnings();
			$element = $doc->createElement( 'span', htmlentities( $definition[ self::ELEMENT_DEFINITION ], ENT_COMPAT, 'UTF-8' ) );
			$element->setAttribute( 'class', 'mw-lingo-tooltip-definition ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );
			\MediaWiki\restoreWarnings();

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
	 * @param StashingDOMDocument $doc
	 * @return DOMNode
	 */
	private function getLinkTemplate( StashingDOMDocument &$doc ) {

		$mLinkTemplate = $doc->stashGet( self::LINK_TEMPLATE_ID );

		// create template if it does not yet exist
		if ( $mLinkTemplate === null ) {
			global $wgScriptPath;

			$linkimage = $doc->createElement( 'img' );
			$linkimage->setAttribute( 'src', $wgScriptPath . '/extensions/Lingo/styles/linkicon.png' );

			$mLinkTemplate = $doc->createElement( 'a' );
			$mLinkTemplate->appendChild( $linkimage );

			$doc->stashSet( $mLinkTemplate, self::LINK_TEMPLATE_ID );
		}

		return $mLinkTemplate->cloneNode( true );
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
