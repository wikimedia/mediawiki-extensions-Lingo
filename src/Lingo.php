<?php
/**
 * File containing the Lingo class
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
 * @file
 * @ingroup Lingo
 */

namespace Lingo;

use Hooks;
use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use PPFrame;
use SpecialPage;
use SpecialVersion;
use Title;

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
		$GLOBALS[ 'wgExtensionFunctions' ][] = static function () {
			$parser = LingoParser::getInstance();

			$backend = new $GLOBALS[ 'wgexLingoBackend' ]();

			$parser->setBackend( $backend );

			Hooks::register( 'SimpleMathJaxAttributes', static function ( array &$attributes, string $tex ) {
				$attributes['class'] = ( $attributes['class'] ?? '' ) . " noglossary";
			} );

			Hooks::register( 'ContentAlterParserOutput', static function () use ( $parser ){
				$parser->parse( MediaWikiServices::getInstance()->getParser() );
			} );

			Hooks::register( 'ApiMakeParserOptions', static function ( ParserOptions $popts, Title $title, array $params ) use ( $parser ){
				$parser->setApiParams( $params );
			} );

			Hooks::register( 'GetDoubleUnderscoreIDs', static function ( array &$doubleUnderscoreIDs ) {
				$doubleUnderscoreIDs[] = 'noglossary';
				return true;
			} );

			Hooks::register( 'ParserFirstCallInit', static function ( Parser $parser ) {
				$parser->setHook( 'noglossary', static function ( $input, array $args, Parser $parser, PPFrame $frame ) {
					$output = $parser->recursiveTagParse( $input, $frame );
					return '<span class="noglossary">' . $output . '</span>';
				} );

				return true;
			} );

			Hooks::register( 'SpecialPageBeforeExecute', static function ( SpecialPage $specialPage, $subPage ) {
				if ( $specialPage instanceof SpecialVersion ) {
					foreach ( $GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ] as $index => $description ) {
						if ( $GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ][ $index ][ 'name' ] === 'Lingo' ) {
							$GLOBALS[ 'wgExtensionCredits' ][ 'parserhook' ][ $index ][ 'description' ] = wfMessage( 'lingo-desc', $GLOBALS[ 'wgexLingoPage' ] ?: wfMessage( 'lingo-terminologypagename' )->inContentLanguage()->text() )->text();
						}
					}
				}

				return true;
			} );
		};
	}

}
