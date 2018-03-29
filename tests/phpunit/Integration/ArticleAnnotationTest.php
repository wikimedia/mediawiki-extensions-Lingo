<?php
/**
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
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
 * @since 2.0.1
 * @file
 * @ingroup Lingo
 */

namespace Lingo\Tests\Integration;

use Lingo\LingoParser;
use Lingo\Tests\Util\XmlFileProvider;

use Parser;
use ParserOptions;
use PHPUnit\Framework\TestCase;

use PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls;
use ReflectionClass;

/**
 * @group extensions-lingo
 * @group extensions-lingo-integration
 * @group mediawiki-databaseless
 *
 * @coversNothing
 *
 * @ingroup Lingo
 * @ingroup Test
 * @since 2.0.1
 * @author Stephan Gambke
 */
class ArticleAnnotationTest extends TestCase {

	public function setup() {
		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;
	}

	public function tearDown() {
		// reset LingoParser singleton
		$lingoParser = LingoParser::getInstance();
		$reflection = new ReflectionClass( $lingoParser );
		$instance = $reflection->getProperty( 'parserSingleton' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
		$instance->setAccessible( false );
	}

	/**
	 * @dataProvider provideData
	 * @param $text
	 * @param $glossaryEntries
	 * @param $expected
	 */
	public function testArticleAnnotation( $file = null, $text = '', $glossaryEntries = null, $expected = '' ) {
		$parser = new Parser();
		$parser->parse( $text, \Title::newFromText( 'Foo' ), new ParserOptions() );

		$backend = $this->getMockForAbstractClass( '\Lingo\Backend' );
		$backend->expects( $this->any() )
			->method( 'next' )
			->will( new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls( $glossaryEntries ) );

		$lingoParser = LingoParser::getInstance();
		$lingoParser->setBackend( $backend );

		$lingoParser->parse( $parser );

		$this->assertEquals( trim( $expected ), trim( $parser->getOutput()->getText() ) );
	}

	public function provideData() {
		$data = [];

		$xmlFileProvider = new XmlFileProvider( __DIR__ . '/../Fixture/articleAnnotation' );
		$files = $xmlFileProvider->getFiles();

		foreach ( $files as $file ) {

			$xml = simplexml_load_file( $file, "SimpleXMLElement", LIBXML_NOCDATA );
			$json = json_encode( $xml );
			$decoded = json_decode( $json, true );

			// suppress warnings for non-existant array keys
			\MediaWiki\suppressWarnings();

			$testCase = [
				0 => substr( $file, strlen( __DIR__ . '/../Fixture/articleAnnotation' ) ),
				1 => trim( $decoded[ 'text' ] ),
				2 => [],
				3 => trim( $decoded[ 'expected' ] ) . "\n",
			];

			if ( array_key_exists( 'term', $decoded[ 'glossary-entry' ] ) ) {
				$decoded[ 'glossary-entry' ] = [ $decoded[ 'glossary-entry' ] ];
			}

			foreach ( $decoded[ 'glossary-entry' ] as $entry ) {
				$testCase[ 2 ][] = [ $entry[ 'term' ], $entry[ 'definition' ], $entry[ 'link' ], $entry[ 'style' ] ];
			}

			\MediaWiki\restoreWarnings();

			$data[] = $testCase;
		}

		return $data;
	}
}
