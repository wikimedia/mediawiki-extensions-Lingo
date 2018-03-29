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

/**
 * Lazy script to invoke the MediaWiki phpunit runner
 *
 *   php mw-phpunit-runner.php [options]
 */

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

print( "\nMediaWiki phpunit runnner ... \n" );

function isReadablePath( $path ) {
	if ( is_readable( $path ) ) {
		return $path;
	}

	throw new RuntimeException( "Expected an accessible {$path} path" );
}

function addArguments( $args ) {
	array_shift( $args );
	return $args;
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

$extDirectory = dirname( getDirectory() );

$config = isReadablePath( "$extDirectory/phpunit.xml.dist" );
$mw = isReadablePath( dirname( dirname( $extDirectory ) ) . "/tests/phpunit/phpunit.php" );

echo "php {$mw} -c {$config} " . implode( ' ', addArguments( $GLOBALS['argv'] ) ) . "\n\n";

passthru( "php {$mw} -c {$config} " . implode( ' ', addArguments( $GLOBALS['argv'] ) ) );
