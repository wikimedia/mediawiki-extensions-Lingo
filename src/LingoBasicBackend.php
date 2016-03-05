<?php

/**
 * File holding the LingoBackend class
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
 * @file
 * @ingroup Lingo
 */

/**
 * The LingoBasicBackend class.
 *
 * @ingroup Lingo
 */
class LingoBasicBackend extends LingoBackend {

	protected $mArticleLines = array();

	public function __construct( LingoMessageLog &$messages = null ) {

		global $wgexLingoPage, $wgRequest;

		$page = $wgexLingoPage ? $wgexLingoPage : wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text();

		parent::__construct( $messages );

		// Get Terminology page
		$title = Title::newFromText( $page );
		if ( $title->getInterwiki() ) {
			$this->getMessageLog()->addError( wfMessage( 'lingo-terminologypagenotlocal' , $page )->inContentLanguage()->text() );
			return false;
		}

		// FIXME: This is a hack special-casing the submitting of the terminology
		// page itself. In this case the Revision is not up to date when we get
		// here, i.e. $rev->getText() would return outdated Test.
		// This hack takes the text directly out of the data from the web request.
		if ( $wgRequest->getVal( 'action', 'view' ) === 'submit'
				&& Title::newFromText( $wgRequest->getVal( 'title' ) )->getArticleID() === $title->getArticleID() ) {

			$content = $wgRequest->getVal( 'wpTextbox1' );

		} else {
			$rev = $this->getRevision( $title );
			if ( !$rev ) {
				$this->getMessageLog()->addWarning( wfMessage( 'lingo-noterminologypage', $page )->inContentLanguage()->text() );
				return false;
			}

			$content = $rev->getText();

		}

		$parser  = new Parser;
		// expand templates and variables in the text, producing valid, static wikitext
		// have to use a new anonymous user to avoid any leakage as Lingo is caching only one user-independant glossary
		$content = $parser->preprocess( $content, $title, new ParserOptions( new User() ) );

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

		wfProfileIn( __METHOD__ );

		static $term = null;
		static $definitions = array();
		static $ret = array();

		// find next valid line (yes, the assignation is intended)
		while ( ( count( $ret ) == 0 ) && ( $entry = each( $this->mArticleLines ) ) ) {

			if ( empty( $entry[1] ) || ($entry[1][0] !== ';' && $entry[1][0] !== ':')) {
				continue;
			}

			$chunks = explode( ':', $entry[1], 2 );

			// found a new definition?
			if ( count ( $chunks ) == 2 ) {

				// wipe the data if its a totaly new term definition
				if ( !empty( $term ) && count( $definitions ) > 0) {
					$definitions = array();
					$term = null;
				}

				$definitions[] = trim( $chunks[1] );
			}

			// found a new term?
			if (count( $chunks ) >= 1 && strlen( $chunks[0] ) >= 1 ) {
				$term = trim( substr( $chunks[0], 1 ) );
			}

			if ( $term !== null ) {
				foreach ( $definitions as $definition ) {
					$ret[] = array(
						LingoElement::ELEMENT_TERM => $term,
						LingoElement::ELEMENT_DEFINITION => $definition,
						LingoElement::ELEMENT_LINK => null,
						LingoElement::ELEMENT_SOURCE => null
					);
				}
			}
		}

		wfProfileOut( __METHOD__ );

		return array_pop($ret);
	}

	/**
	 * Returns revision of the terms page.
	 *
	 * @param Title $title
	 * @return Revision
	 */
	public function getRevision( $title )
	{
		global $wgexLingoEnableApprovedRevs;

		if ( $wgexLingoEnableApprovedRevs ) {

			if ( defined( 'APPROVED_REVS_VERSION' ) ) {
				$rev_id = ApprovedRevs::getApprovedRevID( $title );
				return Revision::newFromId( $rev_id );
			} else {
				wfDebug( 'Support for ApprovedRevs is enabled in Lingo. But ApprovedRevs was not found.\n' );
			}
		}

		return Revision::newFromTitle( $title );
	}

	/**
	 * Initiates the purging of the cache when the Terminology page was saved or purged.
	 *
	 * @param Page $wikipage
	 * @return Bool
	 */
	public static function purgeCache( &$wikipage ) {

		global $wgexLingoPage;
		$page = $wgexLingoPage ? $wgexLingoPage : wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text();

		if ( !is_null( $wikipage ) && ( $wikipage->getTitle()->getText() === $page ) ) {

			LingoParser::purgeCache();
		}

		return true;
	}

	/**
	 * The basic backend is cache-enabled so this function returns true.
	 *
	 * Actual caching is done by the parser, the backend just calls
	 * LingoParser::purgeCache when necessary.
	 *
	 * @return boolean
	 */
	public function useCache() {
		return true;
	}
}
