<?php

/**
 * File holding the LingoBackend class
 *
 * @author Stephan Gambke
 * @file
 * @ingroup Lingo
 */
if ( !defined( 'LINGO_VERSION' ) ) {
	die( 'This file is part of the Lingo extension, it is not a valid entry point.' );
}

/**
 * The LingoBasicBackend class.
 *
 * @ingroup Lingo
 */
class LingoBasicBackend extends LingoBackend {

	protected $mArticleLines = array();

	public function __construct( LingoMessageLog &$messages ) {

		parent::__construct( $messages );

		// Get Terminology page
		$rev = Revision::newFromTitle( Title::makeTitle( null, 'Terminology' ) );

		if ( !$rev ) {
			$messages->addWarning( '[[Terminology]] does not exist.' );
			return false;
		}

		$content = $rev->getText();

		$term = array();
		$this->mArticleLines = explode( "\n", $content );
	}

	/**
	 *
	 * @return Boolean true, if a next element is available
	 */
	public function next() {

		$ret = null;

		// find next valid line (yes, the assignation is intended)
		while ( ( $ret == null ) && ( $entry = each( $this->mArticleLines ) ) ) {

			if ( empty( $entry[1] ) || $entry[1][0] !== ';' ) {
				continue;
			}

			$terms = explode( ':', $entry[1], 2 );

			if ( count( $terms ) < 2 ) {
				continue; // Invalid syntax
			}

			$ret = array(
				LingoElement::ELEMENT_TERM => trim( substr( $terms[0], 1 ) ),
				LingoElement::ELEMENT_DEFINITION => trim( $terms[1] ),
				LingoElement::ELEMENT_LINK => null,
				LingoElement::ELEMENT_SOURCE => null
			);
		}

		return $ret;
	}

}

