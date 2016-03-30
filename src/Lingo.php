<?php
/**
 * File containing the Lingo class
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
 * @file
 * @ingroup Lingo
 */

namespace Lingo;
use MagicWord;

/**
 * Class Lingo
 *
 * @package Lingo
 * @ingroup Lingo
 */
class Lingo {

	/**
	 * Deferred settings
	 * - registration of _NOGLOSSARY_ magic word
	 * - extension description shown on Special:Version
	 *
	 * @since 2.0.2
	 */
	public static function initExtension() {

		$GLOBALS[ 'wgExtensionFunctions' ][] = function () {

			$parser = LingoParser::getInstance();

			$backend = new $GLOBALS[ 'wgexLingoBackend' ]();

			$parser->setBackend( $backend );

			\Hooks::register( 'ParserAfterParse', array( $parser, 'parse' ) );

			\Hooks::register( 'ParserFirstCallInit', function ( \Parser $parser ) {

				$parser->setHook( 'noglossary', function ( $input, array $args, Parser $parser, PPFrame $frame ) {
					$output = $parser->recursiveTagParse( $input, $frame );
					return '<span class="noglossary">' . $output . '</span>';
				} );

				return true;
			} );

			MagicWord::$mDoubleUnderscoreIDs[] = 'noglossary';

			foreach ( $GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ] as $index => $description ) {

				if ( $GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ][ $index ][ 'name' ] === 'Lingo' ) {

					$lingoPageName = $GLOBALS[ 'wgexLingoPage' ] ? $GLOBALS[ 'wgexLingoPage' ] : wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text();
					$GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ][ $index ][ 'description' ] = wfMessage( 'lingo-desc', $lingoPageName )->text();

					break;
				}

			}
		};
	}


}
