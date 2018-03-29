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
	 * @param string $term
	 * @param string[] $definition
	 */
	public function __construct( &$term, &$definition ) {
		$this->mTerm = $term;
		$this->addDefinition( $definition );
	}

	/**
	 * @param array $definition
	 */
	public function addDefinition( &$definition ) {
		$this->mDefinitions[] = $definition + array_fill( 0, self::ELEMENT_FIELDCOUNT, null );
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMElement|DOMText
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
		$linkTarget = $this->mDefinitions[ 0 ][ self::ELEMENT_LINK ];
		$descriptor = $this->getDescriptorFromLinkTarget( $linkTarget );

		if ( $descriptor === null ) {
			$this->mDefinitions = [];
			$this->addErrorMessageForInvalidLink( $linkTarget );
			return $this->buildFormattedTermAsTooltip( $doc );
		}

		// create link element
		$link = $doc->createElement( 'a', htmlentities( $this->mDefinitions[ 0 ][ self::ELEMENT_TERM ] ) );

		// set the link target
		$link->setAttribute( 'href', $descriptor[ 'url' ] );
		$link->setAttribute( 'class', implode( ' ', $this->getClassesForLink( $descriptor ) ) );

		$title = $this->getTitleForLink( $descriptor );
		if ( $title !== null ) {
			$link->setAttribute( 'title', $title );
		}

		return $link;
	}

	/**
	 * @param DOMDocument $doc
	 *
	 * @return DOMElement
	 */
	protected function buildFormattedTermAsTooltip( DOMDocument &$doc ) {
		// Wrap term and definition in <span> tags
		$span = $doc->createElement( 'span', htmlentities( $this->mTerm ) );
		$span->setAttribute( 'class', 'mw-lingo-term' );
		$span->setAttribute( 'data-lingo-term-id', $this->getId() );

		return $span;
	}

	/**
	 * @param $descriptor
	 *
	 * @return string[]
	 */
	protected function getClassesForLink( $descriptor ) {
		// TODO: should this be more elaborate?
		// Cleanest would probably be to use Linker::link and parse it
		// back into a DOMElement, but we are in a somewhat time-critical
		// part here.

		// set style
		$classes = [ 'mw-lingo-term' ];

		$classes[] = $this->mDefinitions[ 0 ][ self::ELEMENT_STYLE ];

		if ( array_key_exists( 'title', $descriptor ) && $descriptor[ 'title' ] instanceof Title ) {

			if ( !$descriptor['title']->isKnown() ) {
				$classes[] = 'new';
			}

			if ( $descriptor['title']->isExternal() ) {
				$classes[] = 'extiw';
			}

		} else {
			$classes[] = 'ext';
		}

		return array_filter( $classes );
	}

	/**
	 * @param Title $target
	 * @param DOMElement $link
	 *
	 * @return string
	 */
	protected function getTitleForLink( $descriptor ) {
		/** @var \Title $target */
		$target = $descriptor[ 'title' ];

		if ( is_string( $target ) ) {
			return $target;
		}

		if ( $target->getPrefixedText() === '' ) {
			return null;
		}

		if ( $target->isKnown() ) {
			return $target->getPrefixedText();
		}

		return wfMessage( 'red-link-title', $target->getPrefixedText() )->text();
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
		if ( $this->isSimpleLink() ) {
			$this->formattedDefinitions = '';
			return;
		}

		$divDefinitions = "<div class='mw-lingo-tooltip' id='{$this->getId()}'>";

		$definition = reset( $this->mDefinitions );
		while ( $definition !== false ) {

			$text = $definition[ self::ELEMENT_DEFINITION ];
			$link = $definition[ self::ELEMENT_LINK ];
			$style = $definition[ self::ELEMENT_STYLE ];

			$divDefinitions .= "<div class='mw-lingo-definition {$style}'><div class='mw-lingo-definition-text'>\n{$text}\n</div>";

			if ( $link !== null ) {

				$descriptor = $this->getDescriptorFromLinkTarget( $link );

				if ( $descriptor === null ) {
					$this->addErrorMessageForInvalidLink( $link );
				} else {
					$divDefinitions .= "<div class='mw-lingo-definition-link'>[{$descriptor[ 'url' ]} <nowiki/>]</div>";
				}
			}

			$divDefinitions .= "</div>";

			$definition = next( $this->mDefinitions );
		}

		$divDefinitions .= "\n</div>";

		$this->formattedDefinitions = $divDefinitions;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return md5( $this->mTerm );
	}

	/**
	 * @param string $linkTarget
	 *
	 * @return string[]
	 */
	protected function getDescriptorFromLinkTarget( $linkTarget ) {
		if ( $this->isValidLinkTarget( $linkTarget ) ) {
			return [ 'url' => $linkTarget, 'title' => $this->mTerm ];
		}

		$title = Title::newFromText( $linkTarget );

		if ( $title !== null ) {
			return [ 'url' => $title->getFullURL(), 'title' => $title ];
		}

		return null;
	}

	/**
	 * @param string $linkTarget
	 *
	 * @return bool
	 */
	protected function isValidLinkTarget( $linkTarget ) {
		return wfParseUrl( $linkTarget ) !== false;
	}

	/**
	 * @param $link
	 */
	protected function addErrorMessageForInvalidLink( $link ) {
		$errorMessage = wfMessage( 'lingo-invalidlinktarget', $this->mTerm, $link )->text();
		$errorDefinition = [ self::ELEMENT_DEFINITION => $errorMessage, self::ELEMENT_STYLE => 'invalid-link-target' ];

		$this->addDefinition( $errorDefinition );
	}

}
