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
 * @author Stephan Gambke
 * @since 2.0
 * @file
 * @ingroup Lingo
 */

namespace Lingo\Tests\Unit;

use Lingo\LingoParser;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @coversDefaultClass \Lingo\LingoParser
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class LingoParserTest extends \PHPUnit\Framework\TestCase {

	private static $defaultParserConfig = [
		'mwParserExpectsGetOutput' => null,
		'mwParserExpectsGetTitle' => null,
		'mwTitleExpectsGetNamespace' => null,
		'mwOutputExpectsGetText' => null,

		'mwParserProperties' => [],

		'namespace' => 0,
		'text' => null,

		'wgexLingoUseNamespaces' => [],
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
	 * Tests
	 *
	 *
	 * @dataProvider parseProvider
	 */
	public function testParse( $config ) {
		// Setup
		$mwParser = $this->getParserMock( $config );
		$parser = new LingoParser();

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
			// trivial case where $wgParser being unset should at least not raise any exceptions
			[ [ 'mwParser' => null ] ],

			// Lingo parser does not start parsing (i.e. accesses parser output) when __NOGLOSSARY__ is set
			[ [
				'mwParserExpectsGetOutput' => $this->never(),
				'mwParserProperties' => [ 'mDoubleUnderscores' => [ 'noglossary' => true ] ],
			] ],

			// Lingo parser does not start parsing (i.e. accesses parser output) when parsed Page is unknown
			[ [
				'mwParserExpectsGetOutput' => $this->never(),
				'mwTitle' => null
			] ],

			// Lingo parser does not start parsing (i.e. accesses parser output) when parsed Page is in explicitly forbidden namespace
			[ [
				'mwParserExpectsGetOutput' => $this->never(),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => false ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is in explicitly allowed namespace
			[ [
				'mwParserExpectsGetOutput' => $this->once(),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 100 => true ],
			] ],

			// Lingo parser starts parsing (i.e. accesses parser output) when parsed Page is not in explicitly forbidden namespace
			[ [
				'mwParserExpectsGetOutput' => $this->once(),
				'namespace' => 100,
				'wgexLingoUseNamespaces' => [ 101 => false ],
			] ],

			// parser output returns null text
			// TODO
			[ [

			] ],

		];
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getParserMock( $config = [] ) {
		$config += self::$defaultParserConfig;

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

		$mwParserOutput->expects( $config[ 'mwOutputExpectsGetText' ] ?: $this->any() )
			->method( 'getText' )
			->willReturn( $config[ 'text' ] );

		$mwParser->expects( $config[ 'mwParserExpectsGetTitle' ] ?: $this->any() )
			->method( 'getTitle' )
			->willReturn( $mwTitle );

		$mwParser->expects( $config[ 'mwParserExpectsGetOutput' ] ?: $this->any() )
			->method( 'getOutput' )
			->willReturn( $mwParserOutput );

		foreach ( $config[ 'mwParserProperties' ] as $propName => $propValue ) {
			$mwParser->$propName = $propValue;
		}

		$GLOBALS[ 'wgexLingoUseNamespaces' ] = $config[ 'wgexLingoUseNamespaces' ];

		return $mwParser;
	}

	/**
	 * @param $config
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
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

}
