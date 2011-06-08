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

		$page = $wgexLingoPage ? $wgexLingoPage : wfMsg( 'lingo-terminologypagename' );

		parent::__construct( $messages );

		// Get Terminology page
		$title = Title::newFromText( $page );
		if ( $title->getInterwiki() ) {
			$this->getMessageLog()->addError( wfMsgReal( 'lingo-terminologypagenotlocal', array($page) ) );
			return false;
		}

		$rev = Revision::newFromTitle( $title );
		if ( !$rev ) {
			$this->getMessageLog()->addWarning( wfMsgReal( 'lingo-noterminologypage', array($page) ) );
			return false;
		}

		$content = $rev->getText();

		$term = array();
		$this->mArticleLines = explode( "\n", $content );
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

