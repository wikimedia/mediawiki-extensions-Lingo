<?php

/**
 * File holding the LingoHooks class
 *
 * @author Stephan Gambke
 * @file
 * @ingroup Lingo
 */
if ( !defined( 'LINGO_VERSION' ) ) {
	die( 'This file is part of the Lingo extension, it is not a valid entry point.' );
}

/**
 * The LingoHooks class.
 *
 * @ingroup Lingo
 */
class LingoHooks {

	static function setup( &$parser ) {
		# Set a function hook associating the "noglossary" with fnLingoNoGlossary
		MagicWord::getDoubleUnderscoreArray()->add( 'nolingo' );
		return true;
	}

	static function setMagicWords( &$magicWords, $langCode ) {
		# Add the magic word
		$magicWords['nolingo'] = array( 0, '__nolingo__' );
		return true;
	}

	static function parse( &$parser, &$text ) {

		if ( !isset( $parser->mDoubleUnderscores['nolingo'] ) ) {
			LingoParser::parse( $parser, $text );
		}

		return true;
	}

	/**
	 * Deferred setting of extension credits
	 *
	 * Setting of extension credits has to be deferred to the
	 * SpecialVersionExtensionTypes hook as it uses variable $wgexLingoPage (which
	 * might be set only after inclusion of the extension in LocalSettings) and
	 * function wfMsg not available before.
	 *
	 * @return Boolean Always true.
	 */
	static function setCredits() {

		global $wgExtensionCredits, $wgexLingoPage;
		$wgExtensionCredits['parserhook'][] = array(
			'path' => __FILE__,
			'name' => 'Lingo',
			'author' => array( 'Barry Coughlan', '[http://www.mediawiki.org/wiki/User:F.trott Stephan Gambke]' ),
			'url' => 'http://www.mediawiki.org/wiki/Extension:Lingo',
			'descriptionmsg' => array( 'lingo-desc', $wgexLingoPage ? $wgexLingoPage : wfMsgForContent( 'lingo-terminologypagename' ) ),
			'version' => LINGO_VERSION,
		);

		return true;
	}

}

