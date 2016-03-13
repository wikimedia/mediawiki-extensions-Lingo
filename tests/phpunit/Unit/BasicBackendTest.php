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

use Lingo\BasicBackend;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @coversDefaultClass \Lingo\BasicBackend
 * @covers ::<private>
 * @covers ::<protected>
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class BasicBackendTest extends BackendTest {

	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Lingo\BasicBackend',
			new \Lingo\BasicBackend()
		);
	}

	/**
	 * @covers ::purgeCache
	 */
	public function testPurgeCache() {

		$GLOBALS[ 'wgexLingoPage' ] = 'SomePage';

		$title = $this->getMock( 'Title' );

		$wikiPage = $this->getMockBuilder( 'WikiPage')
			->disableOriginalConstructor()
			->getMock();

		$lingoParser = $this->getMock( 'Lingo\LingoParser');

		$testObject = $this->getMockBuilder( 'Lingo\BasicBackend' )
			->setMethods( array( 'getLingoParser') )
			->getMock();


		// Assert that the wikipage is tested against the wgexLingoPage:
		// $wikipage->getTitle()->getText() === $page

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$title->expects( $this->once() )
			-> method( 'getText' )
			-> willReturn( 'SomePage' );

		// Assert that purgeGlossaryFromCache is called
		$lingoParser->expects( $this->once() )
			->method( 'purgeGlossaryFromCache' );


		$testObject->expects( $this->once() )
			-> method( 'getLingoParser' )
			-> willReturn( $lingoParser );

		$this->assertTrue( $testObject->purgeCache( $wikiPage ) );
	}

	/**
	 * @covers ::useCache
	 */
	public function testUseCache() {
		$backend = new BasicBackend();
		$this->assertTrue( $backend->useCache() );
	}

}
