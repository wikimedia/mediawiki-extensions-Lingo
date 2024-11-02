<?php

/**
 * File holding the Lingo\Backend class
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
 * @license GPL-2.0-or-later
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
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use ParserOptions;
use TextContent;
use Title;
use WikiPage;

/**
 * @ingroup Lingo
 */
class BasicBackend extends Backend {

	/** @var string[]|null */
	private $mArticleLines = null;

	/**
	 * @param MessageLog|null &$messages
	 */
	public function __construct( ?MessageLog &$messages = null ) {
		parent::__construct( $messages );

		$this->registerHooks();
	}

	private function registerHooks() {
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$hookContainer->register( 'ArticlePurge', [ $this, 'purgeCache' ] );
		$hookContainer->register( 'PageContentSave', [ $this, 'purgeCache' ] );
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition, Link, Source. For the Lingo\BasicBackend Link
	 * and Source are set to null. If there is no next element the function
	 * returns null.
	 *
	 * @return array|null
	 * @throws \MWException
	 */
	public function next() {
		static $term = null;
		static $definitions = [];
		static $ret = [];

		$this->collectDictionaryLines();

		// loop backwards: accumulate definitions until term found
		while ( ( count( $ret ) === 0 ) && ( $this->mArticleLines ) ) {
			$line = array_pop( $this->mArticleLines );

			if ( $this->isValidGlossaryLine( $line ) ) {
				[ $term, $definitions ] = $this->processNextGlossaryLine( $line, $term, $definitions );

				if ( $term !== null ) {
					$ret = $this->queueDefinitions( $definitions, $term );
				}
			}
		}

		return array_pop( $ret );
	}

	/**
	 * @param string $line
	 * @param string $term
	 * @param string[] $definitions
	 * @return array
	 */
	private function processNextGlossaryLine( $line, $term, $definitions ) {
		$chunks = explode( ':', $line, 2 );

		// found a new definition?
		if ( count( $chunks ) === 2 ) {
			// wipe the data if it's a totally new term definition
			if ( !empty( $term ) && count( $definitions ) > 0 ) {
				$definitions = [];
				$term = null;
			}

			$definitions[] = trim( $chunks[ 1 ] );
		}

		// found a new term?
		if ( strlen( trim( $chunks[ 0 ] ) ) > 1 ) {
			$term = trim( substr( $chunks[ 0 ], 1 ) );
		}

		return [ $term, $definitions ];
	}

	/**
	 * @param string[] $definitions
	 * @param string $term
	 * @return array
	 */
	private function queueDefinitions( $definitions, $term ) {
		$ret = [];

		foreach ( $definitions as $definition ) {
			$ret[] = [
				Element::ELEMENT_TERM       => $term,
				Element::ELEMENT_DEFINITION => $definition,
				Element::ELEMENT_LINK       => null,
				Element::ELEMENT_SOURCE     => null
			];
		}

		return $ret;
	}

	/**
	 * @throws \MWException
	 */
	private function collectDictionaryLines() {
		if ( $this->mArticleLines !== null ) {
			return;
		}

		// Get Terminology page
		$dictionaryPageName = $this->getLingoPageName();
		$dictionaryTitle = $this->getTitleFromText( $dictionaryPageName );

		if ( $dictionaryTitle->getInterwiki() !== '' ) {
			$this->getMessageLog()->addError( wfMessage( 'lingo-terminologypagenotlocal', $dictionaryPageName )->inContentLanguage()->text() );
			return;
		}

		$rawContent = $this->getRawDictionaryContent( $dictionaryTitle );

		// Expand templates and variables in the text, producing valid, static
		// wikitext. Have to use a new anonymous user to avoid any leakage as
		// Lingo is caching only one user-independent glossary
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$content = $parser->preprocess( $rawContent, $dictionaryTitle, ParserOptions::newFromAnon() );

		$this->mArticleLines = explode( "\n", $content );
	}

	/**
	 * @return string
	 */
	private function getLingoPageName() {
		global $wgexLingoPage;
		return $wgexLingoPage ?: wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text();
	}

	/**
	 * @param Title $dictionaryTitle
	 *
	 * @return null|string
	 */
	private function getRawDictionaryContent( Title $dictionaryTitle ) {
		global $wgRequest;

		// This is a hack special-casing the submitting of the terminology page
		// itself. In this case the Revision is not up to date when we get here,
		// i.e. $revision->getText() would return outdated Text. This hack takes the
		// text directly out of the data from the web request.
		if ( $wgRequest->getVal( 'action', 'view' ) === 'submit' &&
			$this->getTitleFromText( $wgRequest->getVal( 'title' ) )->getArticleID() === $dictionaryTitle->getArticleID()
		) {
			return $wgRequest->getVal( 'wpTextbox1' );
		}

		$revision = $this->getRevisionFromTitle( $dictionaryTitle );

		if ( $revision !== null ) {
			$content = $revision->getContent( SlotRecord::MAIN );

			if ( $content === null ) {
				return '';
			}

			if ( $content instanceof TextContent ) {
				return $content->getText();
			}

			$this->getMessageLog()->addError( wfMessage( 'lingo-notatextpage', $dictionaryTitle->getFullText() )->inContentLanguage()->text() );
		} else {
			$this->getMessageLog()->addWarning( wfMessage( 'lingo-noterminologypage', $dictionaryTitle->getFullText() )->inContentLanguage()->text() );
		}

		return '';
	}

	/**
	 * Returns revision of the terms page.
	 *
	 * @param Title $title
	 * @return null|RevisionRecord
	 */
	private function getRevisionFromTitle( Title $title ) {
		global $wgexLingoEnableApprovedRevs;

		if ( $wgexLingoEnableApprovedRevs ) {
			if ( defined( 'APPROVED_REVS_VERSION' ) ) {
				return $this->getApprovedRevisionFromTitle( $title );
			}

			$this->getMessageLog()->addWarning( wfMessage( 'lingo-noapprovedrevs' )->inContentLanguage()->text() );
		}

		return $this->getLatestRevisionFromTitle( $title );
	}

	/**
	 * Initiates the purging of the cache when the Terminology page was saved or purged.
	 */
	public function purgeCache( WikiPage $wikipage ) {
		if ( $wikipage->getTitle()->getText() === $this->getLingoPageName() ) {
			$this->getLingoParser()->purgeGlossaryFromCache();
		}
	}

	/**
	 * The basic backend is cache-enabled so this function returns true.
	 *
	 * Actual caching is done by the parser, the backend just calls
	 * Lingo\LingoParser::purgeCache when necessary.
	 *
	 * @return bool
	 */
	public function useCache() {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @param string $dictionaryPage
	 * @return Title
	 */
	protected function getTitleFromText( $dictionaryPage ) {
		return Title::newFromTextThrow( $dictionaryPage );
	}

	/**
	 * @codeCoverageIgnore
	 * @param Title $title
	 * @return null|RevisionRecord
	 */
	protected function getApprovedRevisionFromTitle( Title $title ) {
		return MediaWikiServices::getInstance()->getRevisionLookup()
			->getRevisionById( ApprovedRevs::getApprovedRevID( $title ) );
	}

	/**
	 * @codeCoverageIgnore
	 * @param Title $title
	 * @return null|RevisionRecord
	 */
	protected function getLatestRevisionFromTitle( Title $title ) {
		return MediaWikiServices::getInstance()->getRevisionLookup()
			->getRevisionByTitle( $title );
	}

	/**
	 * @param string $line
	 * @return bool
	 */
	private function isValidGlossaryLine( $line ) {
		return !empty( $line ) && ( $line[ 0 ] === ';' || $line[ 0 ] === ':' );
	}

}
