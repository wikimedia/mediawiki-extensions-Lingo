<?php

/**
 * File holding the LingoMessageLog class.
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
 *
 * @file
 * @ingroup Lingo
 */

/**
 * This class holds messages (errors, warnings, notices) for Lingo
 *
 * Contains a static function to initiate the parsing.
 *
 * @ingroup Lingo
 */
class LingoMessageLog {

	private $mMessages = array();
	private $mParser = null;

	const MESSAGE_ERROR = 1;
	const MESSAGE_WARNING = 2;
	const MESSAGE_NOTICE = 3;

	function addMessage( $message, $severity = self::MESSAGE_NOTICE ) {
		$this->mMessages[] = array($message, $severity);

		// log errors and warnings in debug log
		if ( $severity == self::MESSAGE_WARNING ||
			$severity == self::MESSAGE_ERROR ) {
			wfDebug( $message );
		}
	}

	function addError( $message ) {
		$this->mMessages[] = array($message, self::MESSAGE_ERROR);
		wfDebug( "Error: $message\n" );
	}

	function addWarning( $message ) {
		$this->mMessages[] = array($message, self::MESSAGE_WARNING);
		wfDebug( "Warning: $message\n" );
	}

	function addNotice( $message ) {
		$this->mMessages[] = array($message, self::MESSAGE_NOTICE);
	}

	function getMessagesFormatted( $severity = self::MESSAGE_WARNING, $header = null ) {
		global $wgTitle, $wgUser;

		$ret = '';

		foreach ( $this->mMessages as $message ) {
			if ( $message[1] <= $severity ) {
				$ret .= '* ' . $message[0] . "\n";
			}
		}

		if ( $ret != '' ) {
			if ( !$this->mParser ) {
				$parser = new Parser();
			}

			if ( $header == null ) {
				$header = '';
			} elseif ( $header != '' ) {
				$header = Html::rawElement( 'div', array('class' => 'heading'), $header );
			}

			$ret = Html::rawElement( 'div', array('class' => 'messages'),
					$header . "\n" .
					$ret
			);

			$ret = $parser->parse( $ret, $wgTitle, ParserOptions::newFromUser( $wgUser ) );
		} else {
			$ret = null;
		}

		return $ret;
	}

}

