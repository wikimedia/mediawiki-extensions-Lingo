<?php

/**
 * File holding the Lingo\Backend class
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
 * @file
 * @ingroup Lingo
 */

namespace Lingo;

/**
 * The Lingo\Backend class.
 *
 * @ingroup Lingo
 */
abstract class Backend {

	protected $mMessageLog;
	protected $mLingoParser;

	/**
	 * Lingo\Backend constructor.
	 * @param MessageLog|null $messages
	 */
	public function __construct( MessageLog &$messages = null ) {
		$this->mMessageLog = $messages;
	}

	/**
	 * @return MessageLog
	 */
	public function getMessageLog() {
		if ( !$this->mMessageLog ) {
			$this->mMessageLog = new MessageLog();
		}

		return $this->mMessageLog;
	}

	/**
	 * @return LingoParser
	 */
	public function getLingoParser() {
		if ( !$this->mLingoParser ) {
			$this->mLingoParser = LingoParser::getInstance();
		}

		return $this->mLingoParser;
	}

	/**
	 * @param LingoParser $mLingoParser
	 */
	public function setLingoParser( LingoParser $mLingoParser ) {
		$this->mLingoParser = $mLingoParser;
	}

	/**
	 * This function returns true if the backend is cache-enabled.
	 *
	 * Actual caching is done by the parser, but to be cache-enabled the backend
	 * has to call Lingo\LingoParser::purgeCache when necessary.
	 *
	 * @return bool
	 */
	public function useCache() {
		return false;
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition (as wikitext), Link (as URL or Article title), Source (unused).
	 *
	 * If there is no next element the function returns null.
	 *
	 * @return array | null
	 */
	abstract public function next();
}
