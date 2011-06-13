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

	static function parse( &$parser, &$text ) {

		if ( !isset( $parser->mDoubleUnderscores['noglossary'] ) ) {
			LingoParser::parse( $parser, $text );
		}

		return true;
	}

	/**
	 * Deferred setting of description in extension credits
	 *
	 * Setting of description in extension credits has to be deferred to the
	 * SpecialVersionExtensionTypes hook as it uses variable $wgexLingoPage (which
	 * might be set only after inclusion of the extension in LocalSettings) and
	 * function wfMsg not available before.
	 *
	 * @return Boolean Always true.
	 */
	static function setCredits() {

		global $wgExtensionCredits, $wgexLingoPage;
		$wgExtensionCredits['parserhook']['lingo']['description'] =
			wfMsg( 'lingo-desc', $wgexLingoPage ? $wgexLingoPage : wfMsgForContent( 'lingo-terminologypagename' ) );

		return true;
	}

}

