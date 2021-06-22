<?php

/**
 * File holding the Lingo\MessageLog class.
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
 *
 * @file
 * @ingroup Lingo
 */

namespace Lingo;

/**
 * This class holds messages (errors, warnings, notices) for Lingo
 *
 * Contains a static function to initiate the parsing.
 *
 * @ingroup Lingo
 */
class MessageLog {

	/**
	 * @param string $message
	 */
	public function addError( $message ) {
		wfDebug( "Error: $message\n" );
	}

	/**
	 * @param string $message
	 */
	public function addWarning( $message ) {
		wfDebug( "Warning: $message\n" );
	}

}
