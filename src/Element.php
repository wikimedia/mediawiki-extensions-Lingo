<?php

/**
 * File holding the Lingo\Element class.
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
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

	private $formattedTerm = null;
	private $formattedDefinitions = null;

	private $mDefinitions = [];
	private $mTerm = null;

	private $hasBeenDisplayed = false;

	/**
	 * Lingo\Element constructor.
	 *
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
	 * @param array $definition
	 */
	public function addDefinition( &$definition ) {
		$this->mDefinitions[] = array_pad( $definition, self::ELEMENT_FIELDCOUNT, null );
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMNode|DOMText
	 */
	public function getFormattedTerm( DOMDocument &$doc ) {

		global $wgexLingoDisplayOnce;

		if ( $wgexLingoDisplayOnce && $this->hasBeenDisplayed ) {
			return $doc->createTextNode( $this->mTerm );
		}

		$this->hasBeenDisplayed = true;

		$this->buildFormattedTerm( $doc );

		return $this->formattedTerm->cloneNode( true );
	}

	/**
	 * @param DOMDocument $doc
	 */
	private function buildFormattedTerm( DOMDocument &$doc ) {

		// only create if not yet created
		if ( $this->formattedTerm === null || $this->formattedTerm->ownerDocument !== $doc ) {

			if ( $this->isSimpleLink() ) {
				$this->formattedTerm = $this->buildFormattedTermAsLink( $doc );
			} else {
				$this->formattedTerm = $this->buildFormattedTermAsTooltip( $doc );
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
	 * @return DOMElement
	 */
	protected function buildFormattedTermAsLink( DOMDocument &$doc ) {

		// create Title object for target page
		$target = Title::newFromText( $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] );

		if ( !$target instanceof Title ) {
			$errorMessage = wfMessage( 'lingo-invalidlinktarget', $this->mTerm, $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ] )->text();
			$errorDefinition = [ self::ELEMENT_DEFINITION => $errorMessage, self::ELEMENT_STYLE => 'invalid-link-target' ];
			$this->addDefinition( $errorDefinition );
			return $this->buildFormattedTermAsTooltip( $doc );
		}

		// create link element
		$link = $doc->createElement( 'a', $this->mDefinitions[ 0 ][ self::ELEMENT_TERM ] );

		// set the link target
		$link->setAttribute( 'href', $target->getLinkURL() );


		$link = $this->addClassAttributeToLink( $target, $link );
		$link = $this->addTitleAttributeToLink( $target, $link );

		return $link;
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMElement
	 */
	protected function buildFormattedTermAsTooltip( DOMDocument &$doc ) {

		// Wrap term and definition in <span> tags
		$span = $doc->createElement( 'span' );
		$span->setAttribute( 'class', 'mw-lingo-tooltip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] );
		$span->setAttribute( 'data-lingo-term-id', $this->getId() );

		$spanTerm = $this->buildTerm( $doc );

		// insert term and definition
		$span->appendChild( $spanTerm );
		return $span;
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMElement
	 */
	protected function buildTerm( DOMDocument &$doc ) {

		// Wrap term in <span> tag, hidden
		\MediaWiki\suppressWarnings();
		$spanTerm = $doc->createElement( 'span', htmlentities( $this->mTerm, ENT_COMPAT, 'UTF-8' ) );
		\MediaWiki\restoreWarnings();

		$spanTerm->setAttribute( 'class', 'mw-lingo-tooltip-abbr' );

		return $spanTerm;
	}

	/**
	 * @param Title      $target
	 * @param DOMElement $link
	 *
	 * @return DOMElement
	 */
	protected function &addClassAttributeToLink( $target, &$link ) {

		// TODO: should this be more elaborate? See Linker::linkAttribs
		// Cleanest would probably be to use Linker::link and parse it
		// back into a DOMElement, but we are in a somewhat time-critical
		// part here.

		// set style
		$classes = [];

		if ( $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] !== null ) {
			$classes[] = $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ];
		}

		if ( !$target->isKnown() ) {
			$classes[] = 'new';
		}

		if ( $target->isExternal() ) {
			$classes[] = 'extiw';
		}

		if ( count( $classes ) > 0 ) {
			$link->setAttribute( 'class', join( ' ', $classes ) );
		}

		return $link;
	}

	/**
	 * @param Title      $target
	 * @param DOMElement $link
	 *
	 * @return DOMElement
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
	 * @return string[]
	 */
	public function getFormattedDefinitions() {

		if ( $this->formattedDefinitions === null ) {
			$this->buildFormattedDefinitions();
		}

		return $this->formattedDefinitions;
	}

	/**
	 */
	protected function buildFormattedDefinitions() {

		// Wrap definition in a <div> tag
		$divDefinitions = [];
		$divDefinitions[] = '<div class="mw-lingo-tooltip-tip ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] . '" id="' . $this->getId() . '" >';

		foreach ( $this->mDefinitions as $definition ) {

			$divDefinitions[] = '<div class="mw-lingo-tooltip-definition">';

			$divDefinitions[] = '<div class="mw-lingo-tooltip-text ' . $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ] . "\">\n";
			$divDefinitions[] = $definition[ self::ELEMENT_DEFINITION ];
			$divDefinitions[] = "\n" . '</div>';

			if ( $definition[ self::ELEMENT_LINK ] ) {

				if ( wfParseUrl( $definition[ self::ELEMENT_LINK ] ) !== false ) {
					$url = $definition[ self::ELEMENT_LINK ];
				} else {
					$url = Title::newFromText( $definition[ self::ELEMENT_LINK ] )->getFullURL();
				}

				if ( $url !== null ) {
					$divDefinitions[] = '<div class="mw-lingo-tooltip-link">[' . $url . ' <nowiki/>]</div>';
				}
			}

			$divDefinitions[] = '</div>';
		}

		$divDefinitions[] = "\n" . '</div>';

		$this->formattedDefinitions = join( $divDefinitions );
	}

	/**
	 * @return string
	 */
	public function getId() {
		return md5( $this->mTerm );
	}

	/**
	 * @return mixed
	 */
	public function getCurrentKey() {
		return key( $this->mDefinitions );
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getTerm( $key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_TERM ];
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getSource( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_SOURCE ];
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getDefinition( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_DEFINITION ];
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getLink( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_LINK ];
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	public function getStyle( &$key ) {
		return $this->mDefinitions[ $key ][ self::ELEMENT_STYLE ];
	}

	public function next() {
		next( $this->mDefinitions );
	}

}
