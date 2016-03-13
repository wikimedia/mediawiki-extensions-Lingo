<?php

/**
 * File holding the Lingo\Backend class
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
namespace Lingo;

use ApprovedRevs;
use ContentHandler;
use Hooks;
use Page;
use Parser;
use ParserOptions;
use Revision;
use Title;
use User;
use WikiPage;

/**
 * The Lingo\BasicBackend class.
 *
 * @ingroup Lingo
 */
class BasicBackend extends Backend {

	protected $mArticleLines = array();

	/**
	 * Lingo\BasicBackend constructor.
	 * @param MessageLog|null $messages
	 * @param LingoParser $lingoParser
	 */
	public function __construct( MessageLog &$messages = null ) {

		parent::__construct( $messages );

		$this->registerHooks();

	}

	protected function registerHooks() {
		Hooks::register( 'ArticlePurge', array( $this, 'purgeCache' ) );
		Hooks::register( 'ArticleSave', array( $this, 'purgeCache' ) );
	}

	/**
	 * @return string
	 */
	private function getLingoPage() {
		global $wgexLingoPage;
		return $wgexLingoPage ? $wgexLingoPage : wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text();
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition, Link, Source. For the Lingo\BasicBackend Link
	 * and Source are set to null. If there is no next element the function
	 * returns null.
	 *
	 * @return Array | null
	 */
	public function next() {

		static $term = null;
		static $definitions = array();
		static $ret = array();

		$this->setArticleLines();

		// find next valid line (yes, the assignation is intended)
		while ( ( count( $ret ) == 0 ) && ( $entry = each( $this->mArticleLines ) ) ) {

			if ( empty( $entry[ 1 ] ) || ( $entry[ 1 ][ 0 ] !== ';' && $entry[ 1 ][ 0 ] !== ':' ) ) {
				continue;
			}

			$chunks = explode( ':', $entry[ 1 ], 2 );

			// found a new definition?
			if ( count( $chunks ) == 2 ) {

				// wipe the data if its a totaly new term definition
				if ( !empty( $term ) && count( $definitions ) > 0 ) {
					$definitions = array();
					$term = null;
				}

				$definitions[] = trim( $chunks[ 1 ] );
			}

			// found a new term?
			if ( count( $chunks ) >= 1 && strlen( $chunks[ 0 ] ) >= 1 ) {
				$term = trim( substr( $chunks[ 0 ], 1 ) );
			}

			if ( $term !== null ) {
				foreach ( $definitions as $definition ) {
					$ret[] = array(
						Element::ELEMENT_TERM       => $term,
						Element::ELEMENT_DEFINITION => $definition,
						Element::ELEMENT_LINK       => null,
						Element::ELEMENT_SOURCE     => null
					);
				}
			}
		}

		return array_pop( $ret );
	}

	/**
	 * Returns revision of the terms page.
	 *
	 * @param Title $title
	 * @return Revision
	 */
	public function getRevision( $title ) {
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
	 * @param WikiPage $wikipage
	 * @return Bool
	 */
	public function purgeCache( WikiPage &$wikipage ) {

		$page = $this->getLingoPage();

		if ( !is_null( $wikipage ) && ( $wikipage->getTitle()->getText() === $page ) ) {

			$this->getLingoParser()->purgeGlossaryFromCache();
		}

		return true;
	}

	/**
	 * The basic backend is cache-enabled so this function returns true.
	 *
	 * Actual caching is done by the parser, the backend just calls
	 * Lingo\LingoParser::purgeCache when necessary.
	 *
	 * @return boolean
	 */
	public function useCache() {
		return true;
	}

	/**
	 * @throws \MWException
	 */
	private function setArticleLines() {
		global $wgRequest;

		if ( $this->mArticleLines ) {
			return;
		}

		$page = $this->getLingoPage();

		// Get Terminology page
		$title = Title::newFromText( $page );
		if ( $title->getInterwiki() ) {
			$this->getMessageLog()->addError( wfMessage( 'lingo-terminologypagenotlocal', $page )->inContentLanguage()->text() );
			return;
		}

		// This is a hack special-casing the submitting of the terminology
		// page itself. In this case the Revision is not up to date when we get
		// here, i.e. $rev->getText() would return outdated Text.
		// This hack takes the text directly out of the data from the web request.
		if ( $wgRequest->getVal( 'action', 'view' ) === 'submit'
			&& Title::newFromText( $wgRequest->getVal( 'title' ) )->getArticleID() === $title->getArticleID()
		) {

			$content = $wgRequest->getVal( 'wpTextbox1' );

		} else {
			$rev = $this->getRevision( $title );
			if ( !$rev ) {
				$this->getMessageLog()->addWarning( wfMessage( 'lingo-noterminologypage', $page )->inContentLanguage()->text() );
				return;
			}

			$content = ContentHandler::getContentText( $rev->getContent() );

		}

		$parser = new Parser;
		// expand templates and variables in the text, producing valid, static
		// wikitext have to use a new anonymous user to avoid any leakage as
		// Lingo is caching only one user-independent glossary
		$content = $parser->preprocess( $content, $title, new ParserOptions( new User() ) );

		$this->mArticleLines = array_reverse( explode( "\n", $content ) );
	}
}
