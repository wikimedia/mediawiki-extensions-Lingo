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

namespace Lingo\Tests\Integration;

use Lingo\Backend;
use MediaWikiIntegrationTestCase;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @coversDefaultClass \Lingo\Backend
 * @covers ::<private>
 * @covers ::<protected>
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class BackendTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::getMessageLog
	 */
	public function testGetMessageLog_withLogGivenToConstructor() {
		$log = $this->createMock( \Lingo\MessageLog::class );

		$stub = $this->getMockForAbstractClass( Backend::class );

		$reflected = new \ReflectionClass( \Lingo\Backend::class );
		$constructor = $reflected->getConstructor();
		$constructor->invokeArgs( $stub, [ &$log ] );

		$this->assertEquals( $log, $stub->getMessageLog() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::getMessageLog
	 */
	public function testGetMessageLog_withoutLogGivenToConstructor() {
		$stub = $this->getMockForAbstractClass( Backend::class );

		$reflected = new \ReflectionClass( \Lingo\Backend::class );
		$constructor = $reflected->getConstructor();
		$constructor->invoke( $stub );

		$this->assertInstanceOf( \Lingo\MessageLog::class, $stub->getMessageLog() );
	}

	/**
	 * @covers ::useCache
	 */
	public function testUseCache() {
		$stub = $this->getMockForAbstractClass( \Lingo\Backend::class );

		$this->assertFalse( $stub->useCache() );
	}

	/**
	 * @covers ::setLingoParser
	 * @covers ::getLingoParser
	 */
	public function testSetGetLingoParser() {
		$stub = $this->getMockForAbstractClass( \Lingo\Backend::class );
		$parserMock = $this->createMock( \Lingo\LingoParser::class );

		$stub->setLingoParser( $parserMock );
		$this->assertEquals( $parserMock, $stub->getLingoParser() );
	}

	/**
	 * @covers ::setLingoParser
	 * @covers ::getLingoParser
	 */
	public function testGetLingoParser_withoutParserGiven() {
		$stub = $this->getMockForAbstractClass( \Lingo\Backend::class );
		$this->assertInstanceOf( \Lingo\LingoParser::class, $stub->getLingoParser() );
	}
}
