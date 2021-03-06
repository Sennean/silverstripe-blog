<?php

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class BlogTest extends SapphireTest {
	/**
	 * @var string
	 */
	static $fixture_file = 'blog.yml';

	/**
	 * {@inheritdoc}
	 */
	public function setUp() {
		parent::setUp();

		Config::nest();
		SS_Datetime::set_mock_now('2013-10-10 20:00:00');

		/**
		 * @var Blog $blog
		 */
		$blog = $this->objFromFixture('Blog', 'FirstBlog');

		$blog->publish('Stage', 'Live');
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown() {
		SS_Datetime::clear_mock_now();
		Config::unnest();

		parent::tearDown();
	}

	public function testGetExcludedSiteTreeClassNames() {
		$member = Member::currentUser();

		if($member) {
			$member->logout();
		}

		/**
		 * @var Blog $blog
		 */
		$blog = $this->objFromFixture('Blog', 'FirstBlog');

		Config::inst()->update('BlogPost', 'show_in_sitetree', true);
		$classes = $blog->getExcludedSiteTreeClassNames();

		$this->assertNotContains('BlogPost', $classes, 'BlogPost class should be hidden.');

		Config::inst()->update('BlogPost', 'show_in_sitetree', false);
		$classes = $blog->getExcludedSiteTreeClassNames();

		$this->assertContains('BlogPost', $classes, 'BlogPost class should be hidden.');
	}

	public function testGetArchivedBlogPosts() {
		$member = Member::currentUser();

		if($member) {
			$member->logout();
		}

		/**
		 * @var Blog $blog
		 */
		$blog = $this->objFromFixture('Blog', 'FirstBlog');

		$archive = $blog->getArchivedBlogPosts(2013);

		$this->assertEquals(2, $archive->count(), 'Incorrect Yearly Archive count for 2013');
		$this->assertEquals('First Post', $archive->first()->Title, 'Incorrect First Blog post');
		$this->assertEquals('Second Post', $archive->last()->Title, 'Incorrect Last Blog post');

		$archive = $blog->getArchivedBlogPosts(2013, 10);

		$this->assertEquals(1, $archive->count(), 'Incorrect monthly archive count.');

		$archive = $blog->getArchivedBlogPosts(2013, 10, 01);

		$this->assertEquals(1, $archive->count(), 'Incorrect daily archive count.');
	}

	public function testArchiveLinks() {
		/**
		 * @var Blog $blog
		 */
		$blog = $this->objFromFixture('Blog', 'FirstBlog');

		$link = Controller::join_links($blog->Link('archive'), '2013', '10', '01');

		$this->assertEquals(200, $this->getStatusOf($link), 'HTTP Status should be 200');

		$link = Controller::join_links($blog->Link('archive'), '2013', '10');

		$this->assertEquals(200, $this->getStatusOf($link), 'HTTP Status should be 200');

		$link = Controller::join_links($blog->Link('archive'), '2013');

		$this->assertEquals(200, $this->getStatusOf($link), 'HTTP Status should be 200');

		$link = Controller::join_links($blog->Link('archive'), '2011', '10', '01');

		$this->assertEquals(200, $this->getStatusOf($link), 'HTTP Status should be 200');

		$link = Controller::join_links($blog->Link('archive'));

		$this->assertEquals(404, $this->getStatusOf($link), 'HTTP Status should be 404');

		$link = Controller::join_links($blog->Link('archive'), 'invalid-year');

		$this->assertEquals(404, $this->getStatusOf($link), 'HTTP Status should be 404');

		$link = Controller::join_links($blog->Link('archive'), '2013', '99');

		$this->assertEquals(404, $this->getStatusOf($link), 'HTTP Status should be 404');

		$link = Controller::join_links($blog->Link('archive'), '2013', '10', '99');

		$this->assertEquals(404, $this->getStatusOf($link), 'HTTP Status should be 404');
	}

	/**
	 * @param string $link
	 *
	 * @return int
	 */
	protected function getStatusOf($link) {
		return Director::test($link)->getStatusCode();
	}

	public function testRoles() {
		/**
		 * @var Blog $firstBlog
		 */
		$firstBlog = $this->objFromFixture('Blog', 'FirstBlog');

		/**
		 * @var Blog $fourthBlog
		 */
		$fourthBlog = $this->objFromFixture('Blog', 'FourthBlog');

		/**
		 * @var BlogPost $postA
		 */
		$postA = $this->objFromFixture('BlogPost', 'PostA');

		/**
		 * @var BlogPost $postB
		 */
		$postB = $this->objFromFixture('BlogPost', 'PostB');

		/**
		 * @var BlogPost $postC
		 */
		$postC = $this->objFromFixture('BlogPost', 'PostC');

		/**
		 * @var Member $editor
		 */
		$editor = $this->objFromFixture('Member', 'BlogEditor');

		/**
		 * @var Member $writer
		 */
		$writer = $this->objFromFixture('Member', 'Writer');

		/**
		 * @var Member $contributor
		 */
		$contributor = $this->objFromFixture('Member', 'Contributor');

		/**
		 * @var Member $visitor
		 */
		$visitor = $this->objFromFixture('Member', 'Visitor');

		$this->assertEquals('Editor', $fourthBlog->RoleOf($editor));
		$this->assertEquals('Contributor', $fourthBlog->RoleOf($contributor));
		$this->assertEquals('Writer', $fourthBlog->RoleOf($writer));
		$this->assertEmpty($fourthBlog->RoleOf($visitor));
		$this->assertEquals('Author', $postA->RoleOf($writer));
		$this->assertEquals('Author', $postA->RoleOf($contributor));
		$this->assertEquals('Editor', $postA->RoleOf($editor));
		$this->assertEmpty($postA->RoleOf($visitor));

		$this->assertTrue($fourthBlog->canEdit($editor));
		$this->assertFalse($firstBlog->canEdit($editor));
		$this->assertTrue($fourthBlog->canAddChildren($editor));
		$this->assertFalse($firstBlog->canAddChildren($editor));
		$this->assertTrue($postA->canEdit($editor));
		$this->assertTrue($postB->canEdit($editor));
		$this->assertTrue($postC->canEdit($editor));
		$this->assertTrue($postA->canPublish($editor));
		$this->assertTrue($postB->canPublish($editor));
		$this->assertTrue($postC->canPublish($editor));

		$this->assertFalse($fourthBlog->canEdit($writer));
		$this->assertFalse($firstBlog->canEdit($writer));
		$this->assertTrue($fourthBlog->canAddChildren($writer));
		$this->assertFalse($firstBlog->canAddChildren($writer));
		$this->assertTrue($postA->canEdit($writer));
		$this->assertFalse($postB->canEdit($writer));
		$this->assertTrue($postC->canEdit($writer));
		$this->assertTrue($postA->canPublish($writer));
		$this->assertFalse($postB->canPublish($writer));
		$this->assertTrue($postC->canPublish($writer));

		$this->assertFalse($fourthBlog->canEdit($contributor));
		$this->assertFalse($firstBlog->canEdit($contributor));
		$this->assertTrue($fourthBlog->canAddChildren($contributor));
		$this->assertFalse($firstBlog->canAddChildren($contributor));
		$this->assertTrue($postA->canEdit($contributor));
		$this->assertFalse($postB->canEdit($contributor));
		$this->assertTrue($postC->canEdit($contributor));
		$this->assertFalse($postA->canPublish($contributor));
		$this->assertFalse($postB->canPublish($contributor));
		$this->assertFalse($postC->canPublish($contributor));

		$this->assertFalse($fourthBlog->canEdit($visitor));
		$this->assertFalse($firstBlog->canEdit($visitor));
		$this->assertFalse($fourthBlog->canAddChildren($visitor));
		$this->assertFalse($firstBlog->canAddChildren($visitor));
		$this->assertFalse($postA->canEdit($visitor));
		$this->assertFalse($postB->canEdit($visitor));
		$this->assertFalse($postC->canEdit($visitor));
		$this->assertFalse($postA->canPublish($visitor));
		$this->assertFalse($postB->canPublish($visitor));
		$this->assertFalse($postC->canPublish($visitor));
	}
}
