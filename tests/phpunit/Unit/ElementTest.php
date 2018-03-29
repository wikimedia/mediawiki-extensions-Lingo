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

use Lingo\Element;

/**
 * @group extensions-lingo
 * @group extensions-lingo-unit
 * @group mediawiki-databaseless
 *
 * @coversDefaultClass \Lingo\Element
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class ElementTest extends \PHPUnit\Framework\TestCase {

	/** @var Element */
	protected $element;
	protected $doc;

	protected function setUp() {
		$this->doc = new \DOMDocument();
	}

	/**
	 * @covers ::__construct
	 */
	public function testCanConstruct() {
		$term = 'someTerm';
		$definition = [];
		$element = new Element( $term, $definition );

		$this->assertInstanceOf( '\Lingo\Element', $element );
	}

	/**
	 * Tests
	 * - if $wgexLingoDisplayOnce = false, the first and second occurrence of a term is correctly marked up as tooltip anchor
	 */
	public function testGetFormattedTerm_1() {
		// Setup
		$term = 'someTerm';
		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => 'someDefinition',
			Element::ELEMENT_LINK       => uniqid(), // just some fake page name that does not exist on the wiki
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];
		$element = new Element( $term, $definition );

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		$expectedAttributes = [ 'class' => [ 'mw-lingo-term' ], 'data-lingo-term-id' => '8ade40e10f35a32fbb1e06a4b54751d0' ];

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'span', $term, $expectedAttributes );

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'span', $term, $expectedAttributes );
	}

	/**
	 * Tests
	 * - if $wgexLingoDisplayOnce = true, the first occurrence of a term is correctly marked up as tooltip anchor
	 * - if $wgexLingoDisplayOnce = true, the second occurrence of a term is not marked up
	 */
	public function testGetFormattedTerm_2() {
		// Setup
		$term = 'someTerm';
		$definition = [];
		$element = new Element( $term, $definition );

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = true;

		$expectedAttributes = [ 'class' => 'mw-lingo-term', 'data-lingo-term-id' => '8ade40e10f35a32fbb1e06a4b54751d0' ];

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'span', $term, $expectedAttributes );

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->assertInstanceOf( 'DOMText', $node );
		$this->assertEquals( $term, $node->wholeText );
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has a link, the term is marked up as link
	 * - if the link is not a URL and does not point to an existing page, the term is marked up as "new" link
	 * - if $wgexLingoDisplayOnce = false, the first and second occurrence of of term are marked up as link
	 */
	public function testGetFormattedTerm_3() {
		// Setup
		$term = 'someTerm';
		$title = uniqid();

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => $title, // just some fake page name that does not exist on the wiki
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$element = new Element( $term, $definition );

		$expectedAttributes = [ 'class' => [ 'mw-lingo-term', 'new' ],  'title' => wfMessage( 'red-link-title', $title )->text() ];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'a', $term, $expectedAttributes );

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'a', $term, $expectedAttributes );
	}

	/**
	 * Tests
	 * - if the link is not a URL and points to an existing page, the term is marked up with that title
	 */
	public function testGetFormattedTerm_4() {
		// Setup
		$term = 'someTerm';
		$title = 'Main Page';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => $title, // just some fake page name that does not exist on the wiki
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$element = new Element( $term, $definition );

		$expectedAttributes = [ 'class' => [ 'mw-lingo-term' ],  'title' => $title ];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'a', $term, $expectedAttributes );
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has a link and $wgexLingoDisplayOnce = true, the first occurrence of a term is correctly marked up as link
	 * - if there is only one definition and its text is empty and it has a link and $wgexLingoDisplayOnce = true, the second occurrence of a term is not marked up
	 * - if a style is set in the definition, the link is marked up with that style
	 * - if the link is a valid URL, the term is marked up as external link
	 */
	public function testGetFormattedTerm_5() {
		// Setup
		$term = 'someTerm';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => 'http://foo.com',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => 'some-style',
		];

		$element = new Element( $term, $definition );

		$expectedAttributes = [ 'class' => [ 'mw-lingo-term', 'ext', 'some-style' ], 'title' => $term ];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = true;

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'a', $term, $expectedAttributes );

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->assertInstanceOf( 'DOMText', $node );
		$this->assertEquals( $term, $node->wholeText );
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has an invalid link, the term is marked up as tooltip
	 * - if the term contains HTML-special characters, it is handled without raising an exception
	 */
	public function testGetFormattedTerm_6() {
		// Setup
		$term = 'some&Term';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => 'foo[]bar',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$element = new Element( $term, $definition );

		$expectedAttributes = [ 'class' => 'mw-lingo-term', 'data-lingo-term-id' => 'a8057b0494da505d2f7ac2e96e17083f' ];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'span', $term, $expectedAttributes );
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has an anchor link, the term is marked up as link without title attribute
	 */
	public function testGetFormattedTerm_7() {
		// Setup
		$term = 'some&Term';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => '#someAnchor',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$element = new Element( $term, $definition );

		$expectedAttributes = [ 'class' => 'mw-lingo-term' ];
		$unexpectedAttributes = [ 'title' ];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		// Run
		$node = $element->getFormattedTerm( $this->doc );

		// Check
		$this->checkTermIsDomElement( $node, 'a', $term, $expectedAttributes, $unexpectedAttributes );
	}

	/**
	 * Tests
	 * - correct html is produced
	 * - correct order of definitions
	 * - user-defined class is applied to definition
	 */
	public function testGetFormattedDefinitions_1() {
		// Setup
		$term = 'some&Term';

		$definition1 = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => 'someDefinition1',
			Element::ELEMENT_LINK       => 'someInternalLink1',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$url1 = \Title::newFromText( $definition1[ Element::ELEMENT_LINK ] )->getFullURL();

		$definition2 = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => 'someDefinition2',
			Element::ELEMENT_LINK       => 'someInternalLink2',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => 'some-style-2',
		];

		$url2 = \Title::newFromText( $definition2[ Element::ELEMENT_LINK ] )->getFullURL();

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		$element = new Element( $term, $definition1 );
		$element->addDefinition( $definition2 );
		$node = $element->getFormattedTerm( $this->doc );

		// Run
		$definitions = $element->getFormattedDefinitions();

		$this->assertEquals(
			"<div class='mw-lingo-tooltip' id='a8057b0494da505d2f7ac2e96e17083f'>" .
			"<div class='mw-lingo-definition '>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"someDefinition1\n" .
			"</div>" .
			"<div class='mw-lingo-definition-link'>" .
			"[" . $url1 . " <nowiki/>]" .
			"</div></div>" .
			"<div class='mw-lingo-definition some-style-2'>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"someDefinition2\n" .
			"</div>" .
			"<div class='mw-lingo-definition-link'>" .
			"[" . $url2 . " <nowiki/>]" .
			"</div></div>\n" .
			"</div>",
			$definitions
		);
	}

	/**
	 * Tests
	 * - if there is no link defined, no link is added to the list of definitions
	 * - if there is an invalid link, an error message is attached to the list of definitions and the link is omitted
	 */
	public function testGetFormattedDefinitions_2() {
		// Setup
		$term = 'some&Term';

		$definition1 = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => 'someDefinition1',
			Element::ELEMENT_LINK       => null,
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		// $url1 = \Title::newFromText( $definition1[ Element::ELEMENT_LINK ] )->getFullURL();

		$definition2 = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => 'someDefinition2',
			Element::ELEMENT_LINK       => 'some[]InvalidLink2',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => 'some-style-2',
		];

		// $url2 = \Title::newFromText( $definition2[ Element::ELEMENT_LINK ] )->getFullURL();

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		$element = new Element( $term, $definition1 );
		$element->addDefinition( $definition2 );
		$node = $element->getFormattedTerm( $this->doc );

		// Run
		$definitions = $element->getFormattedDefinitions();

		$this->assertEquals(
			"<div class='mw-lingo-tooltip' id='a8057b0494da505d2f7ac2e96e17083f'>" .
			"<div class='mw-lingo-definition '>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"someDefinition1\n" .
			"</div>" .
			"</div>" .
			"<div class='mw-lingo-definition some-style-2'>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"someDefinition2\n" .
			"</div></div>" .
			"<div class='mw-lingo-definition invalid-link-target'>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"Invalid link target for term \"some&Term\": some[]InvalidLink2\n" .
			"</div></div>\n" .
			"</div>",
			$definitions
		);
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has a link, no definitions are produced
	 */
	public function testGetFormattedDefinitions_3() {
		// Setup
		$term = 'someTerm';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => 'someLink',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		$element = new Element( $term, $definition );
		$node = $element->getFormattedTerm( $this->doc );

		// Run
		$definitions = $element->getFormattedDefinitions();

		$this->assertEquals( '', $definitions );
	}

	/**
	 * Tests
	 * - if there is only one definition and its text is empty and it has an invalid link, the error message shows as tooltip
	 * - class 'invalid-link-target' is correctly applied to error message
	 * - if the term contains HTML-special characters, it is handled without raising an exception
	 */
	public function testGetFormattedDefinitions_4() {
		// Setup
		$term = 'some&Term';

		$definition = [
			Element::ELEMENT_TERM       => $term,
			Element::ELEMENT_DEFINITION => null,
			Element::ELEMENT_LINK       => 'foo[]bar',
			Element::ELEMENT_SOURCE     => null,
			Element::ELEMENT_STYLE      => null,
		];

		$GLOBALS[ 'wgexLingoDisplayOnce' ] = false;

		$element = new Element( $term, $definition );
		$node = $element->getFormattedTerm( $this->doc );

		// Run
		$definitions = $element->getFormattedDefinitions();

		$this->assertEquals(
			"<div class='mw-lingo-tooltip' id='a8057b0494da505d2f7ac2e96e17083f'>" .
			"<div class='mw-lingo-definition invalid-link-target'>" .
			"<div class='mw-lingo-definition-text'>\n" .
			"Invalid link target for term \"some&Term\": foo[]bar\n" .
			"</div></div>\n</div>",
			$definitions
		);
	}

	/**
	 * @param \DOMElement $node
	 * @param string $tagName
	 * @param string $text
	 * @param string[] $expectedAttributes
	 * @param array $unexpectedAttributes
	 */
	protected function checkTermIsDomElement( $node, $tagName, $text, $expectedAttributes = [], $unexpectedAttributes = [] ) {
		$nodeText = $this->doc->saveHTML( $node );

		$this->assertInstanceOf( 'DOMElement', $node );
		$this->assertEquals( $tagName, $node->tagName );
		$this->assertEquals( $text, $node->textContent );

		if ( array_key_exists( 'class', $expectedAttributes ) ) {

			$classes = array_flip( array_filter( explode( ' ', $node->getAttribute( 'class' ) ) ) );

			foreach ( (array)$expectedAttributes[ 'class' ] as $expectedClass ) {
				$this->assertTrue( array_key_exists( $expectedClass, $classes ) );
			}

			unset( $expectedAttributes[ 'class' ] );
		}

		foreach ( $expectedAttributes as $attribute => $value ) {
			$this->assertEquals( $value, $node->getAttribute( $attribute ) );
		}

		foreach ( $unexpectedAttributes as $attribute ) {
			$this->assertFalse( $node->hasAttribute( $attribute ) );
		}
	}
}
