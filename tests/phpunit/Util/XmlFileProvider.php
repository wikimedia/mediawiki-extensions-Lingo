<?php
/**
 *
 * @copyright 2011 - 2016, Stephan Gambke, mwjames
 *
 * @license   GNU General Public License, version 2 (or any later version)
 *
 * This file is part of the MediaWiki extension Lingo.
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
 * @since 2.0.1
 * @file
 * @ingroup Lingo
 * @ingroup Test
 */

namespace Lingo\Tests\Util;

use RuntimeException;

/**
 * @group extensions-lingo
 * @group mediawiki-databaseless
 *
 * @since 2.0.1
 * @author mwjames, Stephan Gambke
 * @ingroup Lingo
 * @ingroup Test
 */
class XmlFileProvider {

	protected $path = null;

	/**
	 * @param string $path
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}

	/**
	 * @return string[]
	 */
	public function getFiles() {
		return $this->loadXmlFiles( $this->readDirectory( $this->path ) );
	}

	/**
	 * @param String $path
	 * @return string
	 */
	protected function readDirectory( $path ) {
		$path = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $path );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path} path" );
	}

	/**
	 * @param String $path
	 * @return string[]
	 */
	protected function loadXmlFiles( $path ) {
		$directoryIterator = new \RecursiveDirectoryIterator( $path );
		$iteratorIterator = new \RecursiveIteratorIterator( $directoryIterator );
		$regexIterator = new \RegexIterator( $iteratorIterator, '/^.+\.xml$/i', \RecursiveRegexIterator::GET_MATCH );

		$files = call_user_func_array( 'array_merge', iterator_to_array( $regexIterator ) );

		return $files;
	}

}
