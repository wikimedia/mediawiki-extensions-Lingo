<?php

/**
 * Provides hover-over tool tips on articles from words defined on the
 * Terminology page.
 *
 * @file
 * @defgroup Lingo
 * @author Barry Coughlan
 * @copyright 2010 Barry Coughlan
 * @author Stephan Gambke
 * @version 0.2 alpha
 * @licence GNU General Public Licence 2.0 or later
 * @see http://www.mediawiki.org/wiki/Extension:Lingo Documentation
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'LINGO_VERSION', '0.2 alpha' );

// set defaults for settings

// set LingoBasicBackend as the backend to access the glossary
$wgexLingoBackend = 'LingoBasicBackend';

// set default for Terminology page (null = take from i18n)
$wgexLingoPage = null;


// server-local path to this file
$dir = dirname( __FILE__ );

// register message file
$wgExtensionMessagesFiles[ 'Lingo' ] = $dir . '/Lingo.i18n.php';
// $wgExtensionMessagesFiles['LingoAlias'] = $dir . '/Lingo.alias.php';
// register class files with the Autoloader
// $wgAutoloadClasses['LingoSettings'] = $dir . '/LingoSettings.php';
$wgAutoloadClasses[ 'LingoParser' ] = $dir . '/LingoParser.php';
$wgAutoloadClasses[ 'LingoTree' ] = $dir . '/LingoTree.php';
$wgAutoloadClasses[ 'LingoElement' ] = $dir . '/LingoElement.php';
$wgAutoloadClasses[ 'LingoBackend' ] = $dir . '/LingoBackend.php';
$wgAutoloadClasses[ 'LingoBasicBackend' ] = $dir . '/LingoBasicBackend.php';
$wgAutoloadClasses[ 'LingoMessageLog' ] = $dir . '/LingoMessageLog.php';
// $wgAutoloadClasses['SpecialLingoBrowser'] = $dir . '/SpecialLingoBrowser.php';

unset ($dir);

$wgHooks[ 'SpecialVersionExtensionTypes' ][ ] = 'fnLingoSetCredits';
//$wgExtensionFunctions[ ] = 'fnLingoInit';
$wgHooks[ 'ParserAfterTidy' ][ ] = 'LingoParser::parse';

// register resource modules with the Resource Loader
$wgResourceModules[ 'ext.Lingo' ] = array(
	// JavaScript and CSS styles. To combine multiple file, just list them as an array.
	// 'scripts' => 'libs/ext.myExtension.js',
	'styles' => 'skins/Lingo.css',
	// When your module is loaded, these messages will be available to mediaWiki.msg()
	// 'messages' => array( 'myextension-hello-world', 'myextension-goodbye-world' ),

	// If your scripts need code from other modules, list their identifiers as dependencies
	// and ResourceLoader will make sure they're loaded before you.
	// You don't need to manually list 'mediawiki' or 'jquery', which are always loaded.
	// 'dependencies' => array( 'jquery.ui.datepicker' ),

	// ResourceLoader needs to know where your files are; specify your
	// subdir relative to "extensions" or $wgExtensionAssetsPath
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'Lingo'
);

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
function fnLingoSetCredits() {
	
	global $wgExtensionCredits, $wgexLingoPage;
	$wgExtensionCredits[ 'parserhook' ][ ] = array(
		'path' => __FILE__,
		'name' => 'Lingo',
		'author' => array( 'Barry Coughlan', '[http://www.mediawiki.org/wiki/User:F.trott Stephan Gambke]' ),
		'url' => 'http://www.mediawiki.org/wiki/Extension:Lingo',
		'descriptionmsg' => array('lingo-desc', $wgexLingoPage?$wgexLingoPage:wfMsg('lingo-terminologypage')),
		'version' => LINGO_VERSION,
	);

	return true;
}
