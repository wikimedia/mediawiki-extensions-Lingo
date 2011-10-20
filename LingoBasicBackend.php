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

	public function __construct( LingoMessageLog &$messages = null ) {

		global $wgexLingoPage;

		$page = $wgexLingoPage ? $wgexLingoPage : wfMsgForContent( 'lingo-terminologypagename' );

		parent::__construct( $messages );

		// Get Terminology page
		$title = Title::newFromText( $page );
		if ( $title->getInterwiki() ) {
			$this->getMessageLog()->addError( wfMsgForContent( 'lingo-terminologypagenotlocal' , $page ) );
			return false;
		}

		$rev = Revision::newFromTitle( $title );
		if ( !$rev ) {
			$this->getMessageLog()->addWarning( wfMsgForContent( 'lingo-noterminologypage' , $page ) );
			return false;
		}

		$content = $rev->getText();

		$this->mArticleLines = array_reverse(explode( "\n", $content ));
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition, Link, Source. For the LingoBasicBackend Link
	 * and Source are set to null. If there is no next element the function
	 * returns null.
	 *
	 * @return Array the next element or null
	 */
	public function next() {

		$ret = null;
		static $term = null;
		static $definition = null;

		// find next valid line (yes, the assignation is intended)
		while ( ( $ret == null ) && ( $entry = each( $this->mArticleLines ) ) ) {

			if ( empty( $entry[1] ) || ($entry[1][0] !== ';' && $entry[1][0] !== ':')) {
				continue;
			}

			$chunks = explode( ':', $entry[1], 2 );

			// found a new term?
			if ( $chunks[0] ) {
				$term = trim( substr( $chunks[0], 1 ) );
			}
			
			// found a new definition?
			if ( count ( $chunks ) == 2 ) {
				$definition = trim( $chunks[1] );
			}

			if ( $term !== null ) {
				$ret = array(
					LingoElement::ELEMENT_TERM => $term,
					LingoElement::ELEMENT_DEFINITION => $definition,
					LingoElement::ELEMENT_LINK => null,
					LingoElement::ELEMENT_SOURCE => null
				);
			}

		}

		return $ret;
	}

}

