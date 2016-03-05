<?php

/**
 * File holding the Extensions\Lingo\LingoBackend class
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

namespace Extensions\Lingo;

/**
 * The Extensions\Lingo\LingoBackend class.
 *
 * @ingroup Lingo
 */
abstract class LingoBackend {

	protected $mMessageLog;

	/**
	 * Extensions\Lingo\LingoBackend constructor.
	 * @param LingoMessageLog|null $messages
	 */
	public function __construct( LingoMessageLog &$messages = null ) {

		if ( !$messages ) {
			$this->mMessageLog = new LingoMessageLog();
		} else {
			$this->mMessageLog = $messages;
		}
	}

	/**
	 * @return LingoMessageLog
	 */
	public function getMessageLog() {
		return $this->mMessageLog;
	}

	/**
	 * This function returns true if the backend is cache-enabled.
	 *
	 * Actual caching is done by the parser, but to be cache-enabled the backend
	 * has to call Extensions\Lingo\LingoParser::purgeCache when necessary.
	 *
	 * @return boolean
	 */
	public function useCache() {
		return false;
	}

	/**
	 * This function returns the next element. The element is an array of four
	 * strings: Term, Definition, Link, Source. If there is no next element the
	 * function returns null.
	 *
	 * @return LingoElement | null
	 */
	abstract public function next();
}

