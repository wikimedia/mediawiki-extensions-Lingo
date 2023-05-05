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

use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use PPFrame;
use Title;

/**
 * @package Lingo
 * @ingroup Lingo
 */
class Lingo {

	/**
	 * Deferred settings
	 * - registration of _NOGLOSSARY_ magic word
	 *
	 * @since 2.0.2
	 */
	public static function initExtension() {
		$GLOBALS[ 'wgExtensionFunctions' ][] = static function () {
			$parser = LingoParser::getInstance();

			$backend = new $GLOBALS[ 'wgexLingoBackend' ]();

			$parser->setBackend( $backend );

			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
			$hookContainer->register( 'SimpleMathJaxAttributes', static function ( array &$attributes, string $tex ) {
				$attributes['class'] = ( $attributes['class'] ?? '' ) . " noglossary";
			} );

			$hookContainer->register( 'ContentAlterParserOutput', static function ( $title, $content, $po ) use ( $parser ){
				// FIXME, this should use the correct instance of Parser, not a random global one.
				// For that matter, it should not assume that there is a Parser being used at
				// all. It should use the passed in $parserOutput exclusively, and use a fresh
				// parser instance for its own recursive parsing combined with
				// ParserOutput::mergeTrackingMetaDataFrom. Content handlers do not need to
				// use the global instance of the MW parser. For that matter, they do not
				// need to use the MediaWiki parser at all.

				// Only run if the ParserOutput hasText() (i.e. fillParserOutput is set).
				// Otherwise things like SpamBlacklist will break this.
				if ( $po->hasText() ) {
					$parser->parse( MediaWikiServices::getInstance()->getParser() );
				}
			} );

			$hookContainer->register( 'ApiMakeParserOptions', static function ( ParserOptions $popts, Title $title, array $params ) use ( $parser ){
				$parser->setApiParams( $params );
			} );

			$hookContainer->register( 'GetDoubleUnderscoreIDs', static function ( array &$doubleUnderscoreIDs ) {
				$doubleUnderscoreIDs[] = 'noglossary';
			} );

			$hookContainer->register( 'ParserFirstCallInit', static function ( Parser $parser ) {
				$parser->setHook( 'noglossary', static function ( $input, array $args, Parser $parser, PPFrame $frame ) {
					$output = $parser->recursiveTagParse( $input, $frame );
					return '<span class="noglossary">' . $output . '</span>';
				} );
			} );
		};
	}

}
