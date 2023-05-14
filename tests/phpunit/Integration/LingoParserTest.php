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

namespace Lingo\Tests\Unit;

use Lingo\LingoParser;
use PHPUnit\Framework\MockObject\MockObject;
use MediaWikiIntegrationTestCase;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @coversDefaultClass \Lingo\LingoParser
 * @covers ::<private>
 * @covers ::<protected>
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class LingoParserTest extends MediaWikiIntegrationTestCase {

	private static $defaultTestConfig = [
		'mwParserExpectsGetOutput' => null,
		'mwParserExpectsGetTitle' => null,
		'mwTitleExpectsGetNamespace' => null,
		'mwOutputExpectsGetText' => null,

		'mwParserProperties' => [],

		'namespace' => 0,
		'text' => 'foo',

		'wgexLingoUseNamespaces' => [],
		'wgexLingoBackend' => 'Lingo\\BasicBackend',
	];

	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\Lingo\LingoParser',
			new \Lingo\LingoParser()
		);
	}

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
			'\Lingo\LingoParser',
			$singleton
		);

		$this->assertEquals( $singleton, LingoParser::getInstance() );
	}

	/**
	 * Tests
	 *
	 *
	 * @covers ::parse
	 * @dataProvider parseProvider
	 */
	public function testParse( $config ) {
		// Setup
		$config += self::$defaultTestConfig;

		$mwParser = $this->getParserMock( $config );
		$backend = $this->getBackendMock();

		$parser = new LingoParser();
		$parser->setBackend( $backend );

		$GLOBALS[ 'wgLingoPageName' ] = 'SomePage';
		$GLOBALS[ 'wgexLingoUseNamespaces' ] = $config[ 'wgexLingoUseNamespaces' ];

		// Run
		$ret = $parser->parse( $mwParser );

		// Check
		$this->assertTrue( $ret );

		// Teardown
	}

	/**
	 * @return array
	 */
	public function parseProvider() {
		return [

			// Lingo parser does not start parsing (i.e. accesses parser output) when __NOGLOSSARY__ is set
			[ [
				'mwParserExpectsGetOutput' => $this->never(),
				'mwParserProperties' => [ 'mDoubleUnderscores' => [ 'noglossary' => true ] ],
			] ],

			// Lingo parser does not start parsing (i.e. accesses parser output) when parsed Page is in explicitly forbidden namespace
			[ [
				'mwParserExpectsGetOutput' => $this->exactly( 2 ),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => false ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is in explicitly allowed namespace
			[ [
				'mwParserExpectsGetOutput' => $this->exactly( 3 ),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => true ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is not in explicitly forbidden namespace
			[ [
				'mwParserExpectsGetOutput' => $this->exactly( 3 ),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 101 => false ],
			] ],

			// Not a real test. Just make sure that it does not break right away.
			[ [
				'text' => 'foo',
			] ],

		];
	}

	/**
	 * @return MockObject
	 */
	protected function getParserMock( $config = [] ) {
		if ( array_key_exists( 'mwParser', $config ) ) {
			return $config[ 'mwParser' ];
		}

		$mwTitle = $this->getTitleMock( $config );

		$mwParserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$mwParser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$mwParserOutput->expects( $this->any() )
			->method( 'hasText' )
			->willReturn( true );

		$mwParserOutput->expects( $config[ 'mwOutputExpectsGetText' ] ?? $this->any() )
			->method( 'getText' )
			->willReturn( $config[ 'text' ] );

		$mwParser->expects( $config[ 'mwParserExpectsGetTitle' ] ?? $this->any() )
			->method( 'getTitle' )
			->willReturn( $mwTitle );

		$mwParser->expects( $config[ 'mwParserExpectsGetOutput' ] ?? $this->exactly( 3 ) )
			->method( 'getOutput' )
			->willReturn( $mwParserOutput );

		foreach ( $config[ 'mwParserProperties' ] as $propName => $propValue ) {
			$mwParser->$propName = $propValue;
		}

		return $mwParser;
	}

	/**
	 * @param $config
	 *
	 * @return MockObject
	 */
	protected function getTitleMock( $config ) {
		if ( array_key_exists( 'mwTitle', $config ) ) {
			return $config[ 'mwTitle' ];
		}

		$mwTitle = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$mwTitle->expects( $config[ 'mwTitleExpectsGetNamespace' ] ?: $this->any() )
			->method( 'getNamespace' )
			->willReturn( $config[ 'namespace' ] );

		return $mwTitle;
	}

	/**
	 * @return MockObject
	 */
	protected function getBackendMock() {
		$backend = $this->getMockBuilder( 'Lingo\BasicBackend' )
			->disableOriginalConstructor()
			->setMethods( [
				'getLatestRevisionFromTitle',
				'getApprovedRevisionFromTitle',
				'getTitleFromText',
			] )
			->getMock();

		$lingoPageTitle = $this->getMockBuilder( 'Title' )
		  ->disableOriginalConstructor()
			->getMock();
		$lingoPageTitle->expects( $this->any() )
			->method( 'getInterwiki' )
			->willReturn( '' );
		$lingoPageTitle->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( 'Foom' );

		$backend->expects( $this->any() )
			->method( 'getTitleFromText' )
			->willReturn( $lingoPageTitle );

		return $backend;
	}

}
