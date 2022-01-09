<?php
/**
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
 * @since 2.0.1
 * @file
 * @ingroup Lingo
 */

namespace Lingo\Tests\Integration;

use Lingo\LingoParser;
use Lingo\Tests\Util\XmlFileProvider;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use ParserOptions;
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
class ArticleAnnotationTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		$this->setMwGlobals( 'wgexLingoDisplayOnce', false );
	}

	protected function tearDown(): void {
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
	 */
	public function testArticleAnnotation( $file = null, $text = '', $glossaryEntries = null, $expected = '' ) {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->parse( $text, \Title::newFromText( 'Foo' ), ParserOptions::newFromAnon() );

		$backend = $this->getMockForAbstractClass( \Lingo\Backend::class );
		$backend->expects( $this->any() )
			->method( 'next' )
			->will( self::onConsecutiveCalls( ...$glossaryEntries ) );

		$lingoParser = LingoParser::getInstance();
		$lingoParser->setBackend( $backend );

		$lingoParser->parse( $parser );

		$html = $parser->getOutput()->getText();
		$html = preg_replace( '/^<div class="mw-parser-output">(.*)<\/div>$/s', '$1', $html );
		$this->assertEquals( trim( $expected ), $html );
	}

	public function provideData() {
		$xmlFileProvider = new XmlFileProvider( __DIR__ . '/../Fixture/articleAnnotation' );
		$files = $xmlFileProvider->getFiles();

		foreach ( $files as $file ) {
			$xml = simplexml_load_file( $file, "SimpleXMLElement", LIBXML_NOCDATA );
			$json = json_encode( $xml );
			$decoded = json_decode( $json, true );

			if ( array_key_exists( 'term', $decoded[ 'glossary-entry' ] ) ) {
				$decoded[ 'glossary-entry' ] = [ $decoded[ 'glossary-entry' ] ];
			}

			$glossaryEntries = [];
			foreach ( $decoded[ 'glossary-entry' ] as $entry ) {
				$glossaryEntries[] = [
					$entry[ 'term' ],
					$entry[ 'definition' ],
					$entry[ 'link' ] ?? null,
					$entry[ 'style' ] ?? null
				];
			}

			yield [
				substr( $file, strlen( __DIR__ . '/../Fixture/articleAnnotation' ) ),
				trim( $decoded[ 'text' ] ),
				$glossaryEntries,
				trim( $decoded[ 'expected' ] ) . "\n",
			];
		}
	}
}
