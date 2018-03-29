<?php
/**
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2016, Stephan Gambke, mwjames
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
 * @author mwjames
 * @since 2.0
 * @file
 * @ingroup Lingo
 */

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available for the test environment' );
}

function registerAutoloaderPath( $identifier, $path ) {
	print( "\nUsing the {$identifier} vendor autoloader ...\n\n" );
	return require $path;
}

/**
 * @return string
 */
function getDirectory() {
	$directory = $GLOBALS[ 'argv' ][ 0 ];

	if ( $directory[ 0 ] !== DIRECTORY_SEPARATOR ) {
		$directory = $_SERVER[ 'PWD' ] . DIRECTORY_SEPARATOR . $directory;
	}

	$directory = dirname( $directory );

	return $directory;
}

function runTestAutoLoader( $autoLoader = null ) {
	$directory = getDirectory();

	$mwVendorPath = $directory . '/../../vendor/autoload.php';
	$localVendorPath = $directory . '/../vendor/autoload.php';

	if ( is_readable( $localVendorPath ) ) {
		$autoLoader = registerAutoloaderPath( 'local', $localVendorPath );
	} elseif ( is_readable( $mwVendorPath ) ) {
		$autoLoader = registerAutoloaderPath( 'MediaWiki', $mwVendorPath );
	}

	if ( !$autoLoader instanceof \Composer\Autoload\ClassLoader ) {
		return false;
	}

	return true;
}

if ( !runTestAutoLoader() ) {
	die( 'Required test class loader was not accessible' );
}
