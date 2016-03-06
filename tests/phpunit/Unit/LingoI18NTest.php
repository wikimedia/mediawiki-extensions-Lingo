<?php
/**
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
 * @author Stephan Gambke
 * @since 2.0
 * @file
 * @ingroup Lingo
 */

namespace Lingo\Tests\Unit;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class LingoI18NTest extends \PHPUnit_Framework_TestCase {

	public function testMagicWordsLoaded() {

		// load magic words
		require __DIR__ . '/../../../src/Lingo.i18n.magic.php';

		// assert $magicWords was created
		$defined_vars = get_defined_vars();
		$this->assertArrayHasKey( 'magicWords', $defined_vars );

		// validate structure

		$data = json_decode( json_encode( $defined_vars[ 'magicWords' ] ) );

		$retriever = new UriRetriever;
		$schema = $retriever->retrieve( 'file://' . realpath( __DIR__ . '/../Fixture/magicWordsSchema.json' ) );

		$refResolver = new RefResolver( $retriever );
		$refResolver->resolve( $schema, 'file://' . realpath( __DIR__ . '/../Fixture/' ) );

		$validator = new Validator();
		$validator->check( $data, $schema );

		// format error message
		$errors = implode( '', array_map(
			function ( $error ) {
				return "* [{$error[ 'property' ]}] {$error[ 'message' ]}\n";
			},
			$validator->getErrors()
		) );

		// assert structure is valid
		$this->assertTrue( $validator->isValid(), "JSON does not validate. Violations:\n" . $errors );
	}
}
