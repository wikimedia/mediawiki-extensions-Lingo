<?php
/**
 * This file is part of the MediaWiki extension Lingo.
 */

namespace Lingo\Tests\Integration;

use Article;
use Lingo\BasicBackend;
use Lingo\LingoParser;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;

/**
 * Test that section edit links are included or not included correctly
 *
 * @group extensions-lingo
 * @group Database
 *
 * @covers Lingo\LingoParser
 *
 * @ingroup Lingo
 * @ingroup Test
 */
class EditSectionTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'LingoUseNamespaces', [] );
		$this->setGroupPermissions( '*', 'edit', false );
		$this->setGroupPermissions( 'user', 'edit', true );

		// Because this extension registers its hooks via an extension function
		// callback, the hooks are not registered for tests and need to be
		// registered manually
		$parser = LingoParser::getInstance();

		$backend = new BasicBackend;

		$parser->setBackend( $backend );

		$this->setTemporaryHook(
			'ContentAlterParserOutput',
			static function ( $title, $content, $po ) use ( $parser ) {
				if ( $po->hasText() ) {
					$parser->parse( MediaWikiServices::getInstance()->getParser() );
				}
			}
		);
	}

	/**
	 * Render an example page for the given user
	 */
	private function renderExample( User $user ): string {
		$this->insertPage( 'Terminology', ";MW\n:MediaWiki\n" );
		$result = $this->insertPage( 'Example', "Before\n== Lingo test ==\nMW" );

		$ctx = RequestContext::getMain();
		$ctx->setUser( $user );
		$article = Article::newFromTitle( $result['title'], $ctx );
		$article->view();

		$out = $ctx->getOutput()->getHTML();

		$comment = strpos( $out, "<!-- \nNewPP limit report" );
		$out = substr( $out, 0, $comment );
		$out .= '...';
		return $out;
	}

	/**
	 * Logged out user should not be shown edit section elements
	 */
	public function testLoggedOut() {
		$user = User::newFromName( '192.168.1.1', false );
		$out = $this->renderExample( $user );
		// phpcs:disable Generic.Files.LineLength.TooLong
		$expected = <<<END
<div class="mw-content-ltr mw-parser-output" lang="en" dir="ltr"><p>Before
</p>
<h2><span class="mw-headline" id="Lingo_test">Lingo test</span></h2>
<p><a href="javascript:void(0);" class="mw-lingo-term" data-lingo-term-id="f9f315de90492c8259307985379c2a4e">MW</a>
</p><div class="mw-lingo-tooltip" id="f9f315de90492c8259307985379c2a4e"><div class="mw-lingo-definition navigation-not-searchable"><div class="mw-lingo-definition-text">
<p>MediaWiki
</p>
</div></div>
</div>
...
END;
		// phpcs:enable Generic.Files.LineLength.TooLong
		$this->assertSame( $expected, $out );
	}

	/**
	 * Logged in user should be shown edit section elements
	 */
	public function testLoggedIn() {
		$out = $this->renderExample( $this->getTestSysop()->getUser() );
		// phpcs:disable Generic.Files.LineLength.TooLong
		$expected = <<<END
<div class="mw-content-ltr mw-parser-output" lang="en" dir="ltr"><p>Before
</p>
<h2><span class="mw-headline" id="Lingo_test">Lingo test</span><span class="mw-editsection"><span class="mw-editsection-bracket">[</span><a href="/index.php?title=Example&amp;action=edit&amp;section=1" title="Edit section: Lingo test"><span>edit</span></a><span class="mw-editsection-bracket">]</span></span></h2>
<p><a href="javascript:void(0);" class="mw-lingo-term" data-lingo-term-id="f9f315de90492c8259307985379c2a4e">MW</a>
</p><div class="mw-lingo-tooltip" id="f9f315de90492c8259307985379c2a4e"><div class="mw-lingo-definition navigation-not-searchable"><div class="mw-lingo-definition-text">
<p>MediaWiki
</p>
</div></div>
</div>
...
END;
		// phpcs:enable Generic.Files.LineLength.TooLong
		$this->assertSame( $expected, $out );
	}

}
