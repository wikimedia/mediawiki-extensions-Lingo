<?php
/**
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2017, Stephan Gambke
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

use Lingo\BasicBackend;
use PHPUnit\Framework\MockObject\MockObject;

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

		$title = $this->getMockBuilder( 'Title' )
			->getMock();

		$wikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$lingoParser = $this->getMockBuilder( 'Lingo\LingoParser' )
			->getMock();

		$testObject = $this->getMockBuilder( 'Lingo\BasicBackend' )
			->setMethods( [ 'getLingoParser' ] )
			->getMock();

		// Assert that the wikipage is tested against the wgexLingoPage, i.e.
		// that $wikipage->getTitle()->getText() === $page is tested

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$title->expects( $this->once() )
			->method( 'getText' )
			->willReturn( 'SomePage' );

		// Assert that purgeGlossaryFromCache is called
		$lingoParser->expects( $this->once() )
			->method( 'purgeGlossaryFromCache' );

		$testObject->expects( $this->once() )
			->method( 'getLingoParser' )
			->willReturn( $lingoParser );

		$this->assertTrue( $testObject->purgeCache( $wikiPage ) );
	}

	/**
	 * @covers ::useCache
	 */
	public function testUseCache() {
		$backend = new BasicBackend();
		$this->assertTrue( $backend->useCache() );
	}

	/**
	 * @covers ::next
	 * @dataProvider provideForTestNext
	 */
	public function testNext( $lingoPageText, $expectedResults ) {
		$backend = $this->getTestObject( $lingoPageText );
		foreach ( $expectedResults as $expected ) {
			$this->assertEquals( $expected, $backend->next() );
		}
	}

	public function testNext_LingoPageIsInterwiki() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', 'someInterwiki' );
		$backend->getMessageLog()->expects( $this->once() )
			->method( 'addError' )
			->willReturn( null );

		$this->assertNull( $backend->next() );
	}

	public function testNext_LingoPageWasJustEdited() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'submit' );
		$this->assertEquals( [ 'JST', 'Just saved text', null, null ], $backend->next() );
	}

	public function testNext_LingoPageDoesNotExist() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', '', null, false );
		$backend->getMessageLog()->expects( $this->once() )
			->method( 'addWarning' )
			->willReturn( null );

		$this->assertEquals( null, $backend->next() );
	}

	public function testNext_LingoPageNotAccessible() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', '', false, null );
		$this->assertEquals( null, $backend->next() );
	}

	public function testNext_LingoPageIsNotATextPage() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', '', false, 'This is not a TextContent object' );
		$backend->getMessageLog()->expects( $this->once() )
			->method( 'addError' )
			->willReturn( null );

		$this->assertEquals( null, $backend->next() );
	}

	public function testNext_ApprovedRevsEnabledButNotInstalled() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', '', false, false, ';SAT:Some approved text' );
		$backend->getMessageLog()->expects( $this->once() )
			->method( 'addWarning' )
			->willReturn( null );

		$GLOBALS[ 'wgexLingoEnableApprovedRevs' ] = true;

		$this->assertEquals( [ 'SOT', 'Some old text', null, null ], $backend->next() );
	}

	public function testNext_ApprovedRevsEnabledAndInstalled() {
		$backend = $this->getTestObject( ';SOT:Some old text', 'view', '', false, false, ';SAT:Some approved text' );

		$GLOBALS[ 'wgexLingoEnableApprovedRevs' ] = true;
		define( 'APPROVED_REVS_VERSION', '42' );

		$this->assertEquals( [ 'SAT', 'Some approved text', null, null ], $backend->next() );
	}

	/**
	 * @return array
	 */
	public function provideForTestNext() {
		return [

			// Empty page
			[
				'',
				[ null ]
			],

			// Simple entries
			[
<<<'TESTTEXT'
;CIP:Common image point
;CMP:Common midpoint
TESTTEXT
			,
				[
					[ 'CMP', 'Common midpoint', null, null ],
					[ 'CIP', 'Common image point', null, null ],
				],
			],

			// Simple entries with line break
			[
<<<'TESTTEXT'
;CIP
:Common image point
;CMP
:Common midpoint
TESTTEXT
			,
				[
					[ 'CMP', 'Common midpoint', null, null ],
					[ 'CIP', 'Common image point', null, null ],
				],
			],

			// Two terms having the same definition
			[
<<<'TESTTEXT'
;CIP
;CMP
:Common midpoint
TESTTEXT
			,
				[
					[ 'CMP', 'Common midpoint', null, null ],
					[ 'CIP', 'Common midpoint', null, null ],
				],
			],

			// One term having two definitions
			[
<<<'TESTTEXT'
;CIP
:Common image point
:Common midpoint
TESTTEXT
			,
				[
					[ 'CIP', 'Common image point', null, null ],
					[ 'CIP', 'Common midpoint', null, null ],
				],
			],

			// Two terms sharing two definitions
			[
<<<'TESTTEXT'
;CIP
;CMP
:Common image point
:Common midpoint
TESTTEXT
			,
				[
					[ 'CMP', 'Common image point', null, null ],
					[ 'CMP', 'Common midpoint', null, null ],
					[ 'CIP', 'Common image point', null, null ],
					[ 'CIP', 'Common midpoint', null, null ],
				],
			],

			// Mixed entries and noise
			[
<<<'TESTTEXT'
;CIP:Common image point
; CMP : Common midpoint

;DIMO
;DMO
:Dip move-out

== headline ==
Sed ut perspiciatis unde; omnis iste natus error: sit voluptatem accusantium...

;NMO:Normal move-out
TESTTEXT
			,
				[
					[ 'NMO', 'Normal move-out', null, null ],
					[ 'DMO', 'Dip move-out', null, null ],
					[ 'DIMO', 'Dip move-out', null, null ],
					[ 'CMP', 'Common midpoint', null, null ],
					[ 'CIP', 'Common image point', null, null ],
				],
			],

		];
	}

	/**
	 * @return MockObject
	 */
	protected function getTestObject( $lingoPageText = '', $action = 'view', $interwiki = '', $lingoPageRevision = false, $lingoPageContent = false, $lingoApprovedText = '' ) {
		$messageLog = $this->getMockBuilder( 'Lingo\MessageLog' )
			->getMock();

		$backend = $this->getMockBuilder( 'Lingo\BasicBackend' )
			->disableOriginalConstructor()
			->setMethods( [
				'getLatestRevisionFromTitle',
				'getApprovedRevisionFromTitle',
				'getTitleFromText',
			] )
			->getMock();

		$reflected = new \ReflectionClass( '\Lingo\BasicBackend' );
		$constructor = $reflected->getConstructor();
		$constructor->invokeArgs( $backend, [ &$messageLog ] );

		$GLOBALS[ 'wgLingoPageName' ] = 'SomePage';

		$lingoPageTitle = $this->getMockBuilder( 'Title' )
			->getMock();
		$lingoPageTitle->expects( $this->once() )
			->method( 'getInterwiki' )
			->willReturn( $interwiki );
		$lingoPageTitle->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( 'Foom' );

		$backend->expects( $this->any() )
			->method( 'getTitleFromText' )
			->willReturn( $lingoPageTitle );

		$request = $this->getMockBuilder( 'FauxRequest' )
			->getMock();
		$request->expects( $this->any() )
			->method( 'getVal' )
			->willReturnMap( [
				[ 'action', 'view', $action ], // action = submit
				[ 'title', null, $lingoPageTitle ], // title = $lingoPageTitle
				[ 'wpTextbox1', null, ';JST:Just saved text' ]
			] );

		$GLOBALS[ 'wgRequest' ] = $request;

		unset( $GLOBALS[ 'wgexLingoEnableApprovedRevs' ] );

		$backend->expects( $this->any() )
			->method( 'getLatestRevisionFromTitle' )
			->willReturn( $this->getRevisionMock( $lingoPageText, $lingoPageRevision, $lingoPageContent ) );

		$backend->expects( $this->any() )
			->method( 'getApprovedRevisionFromTitle' )
			->willReturn( $this->getRevisionMock( $lingoApprovedText ) );

		return $backend;
	}

	/**
	 * @param $lingoPageText
	 * @param $lingoPageRevision
	 * @param $lingoPageContent
	 * @return MockObject
	 */
	protected function getRevisionMock( $lingoPageText, $lingoPageRevision = false, $lingoPageContent = false ) {
		if ( $lingoPageRevision === false ) {

			if ( $lingoPageContent === false ) {
				$lingoPageContent = $this->getMockBuilder( 'TextContent' )
					->disableOriginalConstructor()
					->getMock();

				// FIXME: getNativeData() is deprecated for MW 1.33+.
				$lingoPageContent->expects( $this->any() )
					->method( 'getNativeData' )
					->willReturn( $lingoPageText );

				$lingoPageContent->expects( $this->any() )
					->method( 'getText' )
					->willReturn( $lingoPageText );
			}

			$lingoPageRevision = $this->getMockBuilder( 'Revision' )
				->disableOriginalConstructor()
				->getMock();

			$lingoPageRevision->expects( $this->any() )
				->method( 'getContent' )
				->willReturn( $lingoPageContent );

			return $lingoPageRevision;
		}
		return $lingoPageRevision;
	}

}
