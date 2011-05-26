<?php

/**
 * Provides hover-over tool tips on articles from words defined on the Terminology page.
 * For more info see http://mediawiki.org/wiki/Extension:Lingo
 *
 * @defgroup Lingo
 * @author Barry Coughlan
 * @copyright 2010 Barry Coughlan
 * @version 0.1
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'LINGO_VERSION', '0.1' );

$wgExtensionCredits[ 'parserhook' ][ ] = array(
	'path' => __FILE__,
	'name' => 'Lingo',
	'author' => 'Barry Coughlan',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Lingo',
	'description' => 'Provides hover-over tool tips on articles from words defined on the [[Terminology]] page',
	'version' => '0.1'
);

$wgHooks[ 'OutputPageBeforeHTML' ][ ] = 'lingoHook';

function lingoHook ( &$out, &$text ) {
	global $wgOut, $wgScriptPath;
	$out -> includeJQuery();
	$out -> addHeadItem( 'tooltip.css', '<link rel="stylesheet" type="text/css" href="' . $wgScriptPath . '/extensions/Lingo/tooltip.css"/>' );
	$out -> addHeadItem( 'tooltip.js', '<script type="text/javascript" src="' . $wgScriptPath . '/extensions/Lingo/tooltip.min.js"></script>' );
	return $out;
}

function getLingoArray ( &$content ) {
	$term = array( );
	$c = explode( "\n", $content );

	foreach ( $c as $entry ) {
		if ( empty( $entry ) || $entry[ 0 ] !== ';' )
			continue;

		$terms = explode( ':', $entry, 2 );
		if ( count( $terms ) < 2 )
			continue; //Invalid syntax
			//Add to array
 $term[ trim( substr( $terms[ 0 ], 1 ) ) ] = trim( $terms[ 1 ] );
	}
	return $term;
}

$wgHooks[ 'ParserAfterTidy' ][ ] = 'lingoParser';

function lingoParser ( &$parser, &$text ) {
	global $wgRequest;
	$action = $wgRequest -> getVal( 'action', 'view' );
	if ( $action == "edit" || $action == "ajax" || isset( $_POST[ 'wpPreview' ] ) )
		return false;

	//Get Terminology page
	$rev = Revision::newFromTitle( Title::makeTitle( null, 'Terminology' ) );
	if ( !$rev )
		return false;
	$content = &$rev -> getText();
	if ( empty( $content ) )
		return false;

	//Get array of terms
	$terms = getLingoArray( $content );
	//Get the minimum length abbreviation so we don't bother checking against words shorter than that
	$min = min( array_map( 'strlen', array_keys( $terms ) ) );

	//Parse HTML from page
//	$doc = new DOMDocument();
//	$doc -> loadHTML( $text );
	$doc = @DOMDocument::loadHTML( $text );

	//Find all text in HTML.
	$xpath = new DOMXpath( $doc );
	$elements = $xpath -> query( "//*[text()!=' ']/text()" );

	//Iterate all HTML text matches
	$nb = $elements -> length;
	$changed = false;
	for ( $pos = 0; $pos < $nb; $pos++ ) {
		$el = &$elements -> item( $pos );
		if ( strlen( $el -> nodeValue ) < $min )
			continue;

		//Split node text into words, putting offset and text into $offsets[0] array
		preg_match_all( "/\b[^\b\s\.,;:]+/", $el -> nodeValue, $offsets, PREG_OFFSET_CAPTURE );

		//Search and replace words in reverse order (from end of string backwards),
		//This way we don't mess up the offsets of the words as we iterate
		$len = count( $offsets[ 0 ] );
		for ( $i = $len - 1; $i >= 0; $i-- ) {
			$offset = $offsets[ 0 ][ $i ];
			//Check if word is an abbreviation from the terminologies
			if ( !is_numeric( $offset[ 0 ] ) && isset( $terms[ $offset[ 0 ] ] ) ) { //Word matches, replace with appropriate span tag
				$changed = true;

				$tip = htmlentities( $terms[ $offset[ 0 ] ] );

				$beforeMatchNode = $doc -> createTextNode( substr( $el -> nodeValue, 0, $offset[ 1 ] ) );
				$afterMatchNode = $doc -> createTextNode( substr( $el -> nodeValue, $offset[ 1 ] + strlen( $offset[ 0 ] ), strlen( $el -> nodeValue ) - 1 ) );

				//Wrap abbreviation in <span> tags
				$span = @$doc -> createElement( 'span', $offset[ 0 ] );
				$span -> setAttribute( 'class', "tooltip_abbr" );

				//Wrap definition in <span> tags, hidden
				$spanTip = @$doc -> createElement( 'span', $tip );
				$spanTip -> setAttribute( 'class', "tooltip_hide" );

				$el -> parentNode -> insertBefore( $beforeMatchNode, $el );
				$el -> parentNode -> insertBefore( $span, $el );
				$span -> appendChild( $spanTip );
				$el -> parentNode -> insertBefore( $afterMatchNode, $el );
				$el -> parentNode -> removeChild( $el );
				$el = $beforeMatchNode; //Set new element to the text before the match for next iteration
			}
		}
	}

	if ( $changed ) {

		$body = $xpath -> query( '/html/body' );

		$text = '';
		foreach ( $body -> item( 0 ) -> childNodes as $child ) {
			$text .= $doc -> saveXML( $child );
		}

	}

	return true;
}
