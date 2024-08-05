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
 * @author Stephan Gambke
 * @since 2.0
 * @file
 * @ingroup Lingo
 */

namespace Lingo\Tests\Integration;

use Lingo\Backend;
use Lingo\LingoParser;
use MediaWikiIntegrationTestCase;

/**
 * @group extensions-lingo
 *
 * @coversDefaultClass \Lingo\LingoParser
 * @covers ::<private>
 * @covers ::<protected>
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class LingoParserTest extends MediaWikiIntegrationTestCase {

	private const DEFAULT_TEST_CONFIG = [
		'mwParserExpectsGetOutput' => 4,
		'mwOutputExpectsGetText' => null,

		'mwParserProperties' => [],

		'namespace' => 0,
		'text' => 'foo',

		'wgexLingoUseNamespaces' => [],
	];

	/**
	 * This will NOT test the execution path in LingoParser::getInstance where the singleton is actually created as that
	 * path is executed during the initialisation of MW. It will test however that the singleton is of the correct class
	 * and that once created subsequent calls to LingoParser::getInstance will return the same object.
	 *
	 * @covers ::getInstance
	 */
	public function testGetInstance() {
		$singleton = LingoParser::getInstance();

		$this->assertInstanceOf(
			\Lingo\LingoParser::class,
			$singleton
		);

		$this->assertEquals( $singleton, LingoParser::getInstance() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::parse
	 * @dataProvider parseProvider
	 */
	public function testParse( array $config ) {
		// Setup
		$config += self::DEFAULT_TEST_CONFIG;

		$mwParser = $this->getParserMock( $config );
		$backend = $this->getBackendMock();

		$parser = new LingoParser();
		$parser->setBackend( $backend );

		$this->overrideConfigValues( [
			'LingoPageName' => 'SomePage',
			'exLingoUseNamespaces' => $config[ 'wgexLingoUseNamespaces' ],
		] );

		// Run
		$parser->parse( $mwParser );
	}

	/**
	 * @return array
	 */
	public static function parseProvider() {
		return [

			// Lingo parser does not start parsing (i.e. accesses parser output) when __NOGLOSSARY__ is set
			[ [
				'mwParserExpectsGetOutput' => 3,
				'mwParserProperties' => [ 'mDoubleUnderscores' => [ 'noglossary' => true ] ],
			] ],

			// Lingo parser does not start parsing (i.e. accesses parser output) when parsed Page is in explicitly forbidden namespace
			[ [
				'mwParserExpectsGetOutput' => 3,
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => false ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is in explicitly allowed namespace
			[ [
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => true ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is not in explicitly forbidden namespace
			[ [
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 101 => false ],
			] ],

			// Not a real test. Just make sure that it does not break right away.
			[ [
				'mwOutputExpectsGetText' => 1,
				'text' => 'foo',
			] ],

		];
	}

	/**
	 * @param array $config
	 * @return \Parser
	 */
	private function getParserMock( array $config ) {
		if ( array_key_exists( 'mwParser', $config ) ) {
			return $config[ 'mwParser' ];
		}

		$mwTitle = $this->getTitleMock( $config );

		$mwParserOutput = $this->createMock( \ParserOutput::class );

		$mwParser = $this->createMock( \Parser::class );

		$mwParserOutput->method( 'hasText' )
			->willReturn( true );

		$mwParserOutput->expects( isset( $config[ 'mwOutputExpectsGetText' ] ) ? $this->exactly( $config[ 'mwOutputExpectsGetText' ] ) : $this->any() )
			->method( 'getText' )
			->willReturn( $config[ 'text' ] );

		$mwParser->method( 'getTitle' )
			->willReturn( $mwTitle );

		$mwParser->expects( $this->exactly( $config['mwParserExpectsGetOutput'] ) )
			->method( 'getOutput' )
			->willReturn( $mwParserOutput );

		foreach ( $config[ 'mwParserProperties' ] as $propValue ) {
			if ( $propValue['noglossary'] ) {
				$mwParserOutput->expects( $this->once() )->method( 'getPageProperties' )
					->willReturn( [ 'noglossary' => '' ] );
			} else {
				$mwParserOutput->method( 'getPageProperties' )
					->willReturn( [] );
			}

		}

		return $mwParser;
	}

	/**
	 * @param array $config
	 *
	 * @return \Title
	 */
	private function getTitleMock( $config ) {
		if ( array_key_exists( 'mwTitle', $config ) ) {
			return $config[ 'mwTitle' ];
		}

		$mwTitle = $this->createMock( \Title::class );

		$mwTitle->method( 'getNamespace' )
			->willReturn( $config[ 'namespace' ] );

		return $mwTitle;
	}

	/**
	 * @return Backend
	 */
	private function getBackendMock() {
		$backend = $this->getMockBuilder( \Lingo\BasicBackend::class )
			->disableOriginalConstructor()
			->onlyMethods( [
				'getLatestRevisionFromTitle',
				'getApprovedRevisionFromTitle',
				'getTitleFromText',
			] )
			->getMock();

		$lingoPageTitle = $this->createMock( \Title::class );
		$lingoPageTitle->method( 'getInterwiki' )
			->willReturn( '' );

		$backend->method( 'getTitleFromText' )
			->willReturn( $lingoPageTitle );

		return $backend;
	}

}
