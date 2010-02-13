<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\util;

use \lithium\util\Set;

class SetTest extends \lithium\test\Unit {

	public function testDepthWithEmptyData() {
		$data = array();
		$result = Set::depth($data);
		$this->assertEqual($result, 0);
	}

	public function testDepthOneLevelWithDefaults() {
		$data = array();
		$result = Set::depth($data);
		$this->assertEqual($result, 0);

		$data = array('one', '2', 'three');
		$result = Set::depth($data);
		$this->assertEqual($result, 1);

		$data = array('1' => '1.1', '2', '3');
		$result = Set::depth($data);
		$this->assertEqual($result, 1);

		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::depth($data, false, 0);
		$this->assertEqual($result, 1);
 	}

	public function testDepthTwoLevelsWithDefaults() {
		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::depth($data);
		$this->assertEqual($result, 2);

		$data = array('1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array(
			'3.1.1' => '3.1.1.1'
		)));
		$result = Set::depth($data);
		$this->assertEqual($result, 2);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array(
				'2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1'))
			)),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, false, 0);
		$this->assertEqual($result, 2);
	}

	public function testDepthTwoLevelsWithAll() {
		$data = array('1' => '1.1', '2', '3' => array('3.1' => '3.1.1'));
		$result = Set::depth($data, true);
		$this->assertEqual($result, 2);
	}


	public function testDepthThreeLevelsWithAll() {
		$data = array(
			'1' => array('1.1' => '1.1.1'), '2', '3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 3);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => '2.1.1.1'))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 4);

		$data = array(
			'1' => array('1.1' => '1.1.1'),
			array('2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 5);

		$data = array('1' => array('1.1' => '1.1.1'), array(
			'2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 5);
	}

	public function testDepthFourLevelsWithAll() {
		$data = array('1' => array('1.1' => '1.1.1'), array(
			'2' => array('2.1' => array('2.1.1' => '2.1.1.1'))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 4);
	}

	public function testDepthFiveLevelsWithAll() {

		$data = array('1' => array('1.1' => '1.1.1'), array(
			'2' => array('2.1' => array('2.1.1' => array('2.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 5);

		$data = array('1' => array('1.1' => '1.1.1'), array(
			'2' => array('2.1' => array('2.1.1' => array('2.1.1.1' => '2.1.1.1.1')))),
			'3' => array('3.1' => array('3.1.1' => '3.1.1.1'))
		);
		$result = Set::depth($data, true);
		$this->assertEqual($result, 5);
	}

	public function testFlattenOneLevel() {
		$data = array('Larry', 'Curly', 'Moe');
		$result = Set::flatten($data);
		$this->assertEqual($result, $data);

		$data[9] = 'Shemp';
		$result = Set::flatten($data);
		$this->assertEqual($result, $data);
	}

	public function testFlattenTwoLevels() {
		$data = array(
			array(
				'Post' => array('id' => '1', 'author_id' => '1', 'title' => 'First Post'),
				'Author' => array('id' => '1', 'user' => 'nate', 'password' => 'foo'),
			),
			array(
				'Post' => array(
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body'
				),
				'Author' => array('id' => '3', 'user' => 'joel', 'password' => null),
			)
		);

		$expected = array(
			'0.Post.id' => '1', '0.Post.author_id' => '1', '0.Post.title' => 'First Post',
			'0.Author.id' => '1', '0.Author.user' => 'nate', '0.Author.password' => 'foo',
			'1.Post.id' => '2', '1.Post.author_id' => '3', '1.Post.title' => 'Second Post',
			'1.Post.body' => 'Second Post Body', '1.Author.id' => '3',
			'1.Author.user' => 'joel', '1.Author.password' => null
		);
		$result = Set::flatten($data);
		$this->assertEqual($expected, $result);

		$result = Set::flatten(array('Post' => $data[0]['Post']), '/');
		$expected = array('Post/id' => '1', 'Post/author_id' => '1', 'Post/title' => 'First Post');
		$this->assertEqual($expected, $result);
	}

	public function testFormat() {
		$data = array(
			array('Person' => array(
				'first_name' => 'Nate', 'last_name' => 'Abele',
				'city' => 'Queens', 'state' => 'NY', 'something' => '42'
			)),
			array('Person' => array(
				'first_name' => 'Joel', 'last_name' => 'Perras',
				'city' => 'Montreal', 'state' => 'Quebec', 'something' => '{0}'
			)),
			array('Person' => array(
				'first_name' => 'Garrett', 'last_name' => 'Woodworth',
				'city' => 'Venice Beach', 'state' => 'CA', 'something' => '{1}'
			))
		);

		$result = Set::format($data, '{1}, {0}', array('/Person/first_name', '/Person/last_name'));
		$expected = array('Abele, Nate', 'Perras, Joel', 'Woodworth, Garrett');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{0}, {1}', array('/Person/last_name', '/Person/first_name'));
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{0}, {1}', array('/Person/city', '/Person/state'));
		$expected = array('Queens, NY', 'Montreal, Quebec', 'Venice Beach, CA');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{{0}, {1}}', array('/Person/city', '/Person/state'));
		$expected = array('{Queens, NY}', '{Montreal, Quebec}', '{Venice Beach, CA}');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{{0}, {1}}', array(
			'/Person/something', '/Person/something'
		));
		$expected = array('{42, 42}', '{{0}, {0}}', '{{1}, {1}}');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{%2$d, %1$s}', array(
			'/Person/something', '/Person/something'
		));
		$expected = array('{42, 42}', '{0, {0}}', '{0, {1}}');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{%1$s, %1$s}', array(
			'/Person/something', '/Person/something'
		));
		$expected = array('{42, 42}', '{{0}, {0}}', '{{1}, {1}}');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '%2$d, %1$s', array(
			'/Person/first_name', '/Person/something'
		));
		$expected = array('42, Nate', '0, Joel', '0, Garrett');
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '%1$s, %2$d', array(
			'/Person/first_name', '/Person/something'
		));
		$expected = array('Nate, 42', 'Joel, 0', 'Garrett, 0');
		$this->assertEqual($expected, $result);
	}

	public function testMatchesBasic() {
		$a = array(
			array('Article' => array('id' => 1, 'title' => 'Article 1')),
			array('Article' => array('id' => 2, 'title' => 'Article 2')),
			array('Article' => array('id' => 3, 'title' => 'Article 3'))
		);

		$this->assertTrue(Set::matches(array('id=2'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('id>2'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>=2'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('id>=3'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id<=2'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('id<2'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>1'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id>1', 'id<3', 'id!=0'), $a[1]['Article']));

		$this->assertTrue(Set::matches(array('3'), null, 3));
		$this->assertTrue(Set::matches(array('5'), null, 5));

		$this->assertTrue(Set::matches(array('id'), $a[1]['Article']));
		$this->assertTrue(Set::matches(array('id', 'title'), $a[1]['Article']));
		$this->assertFalse(Set::matches(array('non-existant'), $a[1]['Article']));

		$this->assertTrue(Set::matches('/Article[id=2]', $a));
		$this->assertFalse(Set::matches('/Article[id=4]', $a));
		$this->assertTrue(Set::matches(array(), $a));
	}

	public function testMatchesMultipleLevels() {
		$result = array(
			'Attachment' => array(
				'keep' => array()
			),
			'Comment' => array(
				'keep' => array('Attachment' =>  array('fields' => array('attachment')))
			),
			'User' => array('keep' => array()),
			'Article' => array(
				'keep' => array(
					'Comment' =>  array('fields' => array('comment', 'published')),
					'User' => array('fields' => array('user')),
				)
			)
		);
		$result = Set::matches($result, '/Article/keep/Comment');
		$this->assertTrue($result);

		$result = Set::matches($result, '/Article/keep/Comment/fields/user');
		$this->assertFalse($result);
	}

	public function testExtractReturnsEmptyArray() {
		$expected = array();
		$result = Set::extract(array(), '/Post/id');
		$this->assertIdentical($expected, $result);

		$result = Set::extract('/Post/id', array());
		$this->assertIdentical($expected, $result);

		$result = Set::extract(array(
			array('Post' => array('name' => 'bob')),
			array('Post' => array('name' => 'jim'))
		), '/Post/id');
		$this->assertIdentical($expected, $result);

		$result = Set::extract(array(), 'Message.flash');
		$this->assertIdentical($expected, $result);
	}

	public function testExtractionOfNotNull() {
		$data = array(
			'plugin' => null, 'admin' => false, 'controller' => 'posts',
			'action' => 'index', 1, 'whatever'
		);

		$expected = array('controller' => 'posts', 'action' => 'index', 1, 'whatever');
		$result = Set::extract($data, '/');
		$this->assertIdentical($expected, $result);
	}

	public function testExtractOfNumericKeys() {
		$data = array(1, 'whatever');

		$expected = array(1, 'whatever');
		$result = Set::extract($data, '/');
		$this->assertIdentical($expected, $result);
	}

	public function testExtract() {
		$a = array(
			array(
				'Article' => array(
					'id' => '1', 'user_id' => '1', 'title' => 'First Article',
					'body' => 'First Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => '1', 'article_id' => '1', 'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '2', 'article_id' => '1', 'user_id' => '4',
						'comment' => 'Second Comment for First Article', 'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					),
				),
				'Tag' => array(
					array(
						'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
					)
				),
				'Deep' => array(
					'Nesting' => array(
						'test' => array(1 => 'foo', 2 => array('and' => array('more' => 'stuff')))
					)
				)
			),
			array(
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '2', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '3', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '4', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			),
			array(
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '5', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);

		$b = array('Deep' => $a[0]['Deep']);

		$c = array(
			array('a' => array('I' => array('a' => 1))),
			array('a' => array(2)),
			array('a' => array('II' => array('a' => 3, 'III' => array('a' => array('foo' => 4))))),
		);

		$expected = array(array('a' => $c[2]['a']));
		$result = Set::extract('/a/II[a=3]/..', $c);
		$this->assertEqual($expected, $result);

		$expected = array(1, 2, 3, 4, 5);
		$result = Set::extract($a, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = array(1, 2, 3, 4, 5);
		$result = Set::extract($a, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = array(
			array('id' => 1), array('id' => 2), array('id' => 3), array('id' => 4), array('id' => 5)
		);
		$result = Set::extract($a, '/User/id', array('flatten' => false));
		$this->assertEqual($expected, $result);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$this->assertEqual(Set::extract($a, '/Deep/Nesting/test'), $expected);
		$this->assertEqual(Set::extract($b, '/Deep/Nesting/test'), $expected);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$result = Set::extract($a, '/Deep/Nesting/test/1/..');
		$this->assertEqual($expected, $result);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$result = Set::extract($a, '/Deep/Nesting/test/2/and/../..');
		$this->assertEqual($expected, $result);

		$expected = array(array('test' => $a[0]['Deep']['Nesting']['test']));
		$result = Set::extract($a, '/Deep/Nesting/test/2/../../../Nesting/test/2/..');
		$this->assertEqual($expected, $result);

		$expected = array(2);
		$result = Set::extract($a, '/User[2]/id');
		$this->assertEqual($expected, $result);

		$expected = array(4, 5);
		$result = Set::extract($a, '/User[id>3]/id');
		$this->assertEqual($expected, $result);

		$expected = array(2, 3);
		$result = Set::extract($a, '/User[id>1][id<=3]/id');
		$this->assertEqual($expected, $result);

		$expected = array(array('I'), array('II'));
		$result = Set::extract($c, '/a/@*');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNonSequentialKeys() {
		$nonSequential = array(
			'User' => array(
				0  => array('id' => 1),
				2  => array('id' => 2),
				6  => array('id' => 3),
				9  => array('id' => 4),
				3  => array('id' => 5),
			),
		);

		$expected = array(1, 2, 3, 4, 5);
		$result = Set::extract($nonSequential, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNoZeroKey() {
		$noZero = array(
			'User' => array(
				2  => array('id' => 1),
				4  => array('id' => 2),
				6  => array('id' => 3),
				9  => array('id' => 4),
				3  => array('id' => 5),
			),
		);

		$expected = array(1, 2, 3, 4, 5);
		$result = Set::extract($noZero, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractSingle() {

		$single = array('User' => array('id' => 4, 'name' => 'Neo'));

		$expected = array(4);
		$result = Set::extract($single, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractHasMany() {
		$tricky = array(
			0 => array('User' => array('id' => 1, 'name' => 'John')),
			1 => array('User' => array('id' => 2, 'name' => 'Bob')),
			2 => array('User' => array('id' => 3, 'name' => 'Tony')),
			'User' => array('id' => 4, 'name' => 'Neo')
		);

		$expected = array(1, 2, 3, 4);
		$result = Set::extract($tricky, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = array(1, 3);
		$result = Set::extract($tricky, '/User[name=/n/]/id');
		$this->assertEqual($expected, $result);

		$expected = array(4);
		$result = Set::extract($tricky, '/User[name=/N/]/id');
		$this->assertEqual($expected, $result);

		$expected = array(1, 3, 4);
		$result = Set::extract($tricky, '/User[name=/N/i]/id');
		$this->assertEqual($expected, $result);

		$expected = array(
			array('id', 'name'), array('id', 'name'), array('id', 'name'), array('id', 'name')
		);
		$result = Set::extract($tricky, '/User/@*');
		$this->assertEqual($expected, $result);
	}

	function testExtractAssociatedHasMany() {
		$common = array(
			array(
				'Article' => array('id' => 1, 'name' => 'Article 1'),
				'Comment' => array(
					array('id' => 1, 'user_id' => 5, 'article_id' => 1, 'text' => 'Comment 1'),
					array('id' => 2, 'user_id' => 23, 'article_id' => 1, 'text' => 'Comment 2'),
					array('id' => 3, 'user_id' => 17, 'article_id' => 1, 'text' => 'Comment 3')
				)
			),
			array(
				'Article' => array('id' => 2, 'name' => 'Article 2'),
				'Comment' => array(
					array(
						'id' => 4,
						'user_id' => 2,
						'article_id' => 2,
						'text' => 'Comment 4',
						'addition' => ''
					),
					array(
						'id' => 5,
						'user_id' => 23,
						'article_id' => 2,
						'text' => 'Comment 5',
						'addition' => 'foo'
					),
				),
			),
			array(
				'Article' => array('id' => 3, 'name' => 'Article 3'),
				'Comment' => array()
			)
		);
		$result = Set::extract('/', $common);
		$this->assertEqual($result, $common);

		$expected = array(1);
		$result = Set::extract('/Comment/id[:first]', $common);
		$this->assertEqual($expected, $result);

		$expected = array(5);
		$result = Set::extract('/Comment/id[:last]', $common);
		$this->assertEqual($expected, $result);

		$result = Set::extract($common, '/Comment/id');
		$expected = array(1, 2, 3, 4, 5);
		$this->assertEqual($expected, $result);

		$expected = array(1, 2, 4, 5);
		$result = Set::extract($common, '/Comment[id!=3]/id');
		$this->assertEqual($expected, $result);

		$expected = array($common[0]['Comment'][2]);
		$result = Set::extract($common, '/Comment/2');
		$this->assertEqual($expected, $result);

		$expected = array($common[0]['Comment'][0]);
		$result = Set::extract($common, '/Comment[1]/.[id=1]');
		$this->assertEqual($expected, $result);

		$expected = array($common[1]['Comment'][1]);
		$result = Set::extract($common, '/1/Comment/.[2]');
		$this->assertEqual($expected, $result);

		$expected = array(array('Comment' => $common[1]['Comment'][0]));
		$result = Set::extract('/Comment[addition=]', $common);
		$this->assertEqual($expected, $result);

		$expected = array(3);
		$result = Set::extract('/Article[:last]/id', $common);
		$this->assertEqual($expected, $result);

		$expected = array();
		$result = Set::extract('/User/id', array());
		$this->assertEqual($expected, $result);
	}

	public function testExtractHabtm() {
		$habtm = array(
			array(
				'Post' => array('id' => 1, 'title' => 'great post'),
				'Comment' => array(
					array('id' => 1, 'text' => 'foo', 'User' => array('id' => 1, 'name' => 'bob')),
					array('id' => 2, 'text' => 'bar', 'User' => array('id' => 2, 'name' => 'tod')),
				),
			),
			array(
				'Post' => array('id' => 2, 'title' => 'fun post'),
				'Comment' => array(
					array('id' => 3, 'text' => '123', 'User' => array('id' => 3, 'name' => 'dan')),
					array('id' => 4, 'text' => '987', 'User' => array('id' => 4, 'name' => 'jim'))
				)
			)
		);

		$result = Set::extract($habtm, '/Comment/User[name=/\w+/]/..');
		$this->assertEqual(count($result), 4);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'tod');
		$this->assertEqual($result[2]['Comment']['User']['name'], 'dan');
		$this->assertEqual($result[3]['Comment']['User']['name'], 'dan');

		$result = Set::extract($habtm, '/Comment/User[name=/[a-z]+/]/..');
		$this->assertEqual(count($result), 4);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'tod');
		$this->assertEqual($result[2]['Comment']['User']['name'], 'dan');
		$this->assertEqual($result[3]['Comment']['User']['name'], 'dan');

		$result = Set::extract($habtm, '/Comment/User[name=/bob|dan/]/..');
		$this->assertEqual(count($result), 2);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'dan');

		$result = Set::extract($habtm, '/Comment/User[name=/bob|tod/]/..');
		$this->assertEqual(count($result), 2);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'tod');
	}

	public function testExtractFromTree() {
		$tree = array(
			array(
				'Category' => array('name' => 'Category 1'),
				'children' => array(array('Category' => array('name' => 'Category 1.1')))
			),
			array(
				'Category' => array('name' => 'Category 2'),
				'children' => array(
					array('Category' => array('name' => 'Category 2.1')),
					array('Category' => array('name' => 'Category 2.2'))
				)
			),
			array(
				'Category' => array('name' => 'Category 3'),
				'children' => array(array('Category' => array('name' => 'Category 3.1')))
			)
		);

		$expected = array(array('Category' => $tree[1]['Category']));
		$result = Set::extract($tree, '/Category[name=Category 2]');
		$this->assertEqual($expected, $result);

		$expected = array(array('Category' => $tree[1]['Category'], 'children' => $tree[1]['children']));
		$result = Set::extract($tree, '/Category[name=Category 2]/..');
		$this->assertEqual($expected, $result);

		$expected = array(
			array('children' => $tree[1]['children'][0]),
			array('children' => $tree[1]['children'][1])
		);
		$result = Set::extract($tree, '/Category[name=Category 2]/../children');
		$this->assertEqual($expected, $result);
	}

	public function testExtractOnMixedKeys() {
		$mixedKeys = array(
			'User' => array(
				0 => array('id' => 4, 'name' => 'Neo'),
				1 => array('id' => 5, 'name' => 'Morpheus'),
				'stringKey' => array()
			)
		);

		$expected = array('Neo', 'Morpheus');
		$result = Set::extract($mixedKeys, '/User/name');
		$this->assertEqual($expected, $result);
	}

	public function testExtractSingleWithNameCondition() {
		$single = array(
			array('CallType' => array('name' => 'Internal Voice'), 'x' => array('hour' => 7))
		);

		$expected = array(7);
		$result = Set::extract($single, '/CallType[name=Internal Voice]/../x/hour');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNameCondition() {
		$multiple = array(
			array('CallType' => array('name' => 'Internal Voice'), 'x' => array('hour' => 7)),
			array('CallType' => array('name' => 'Internal Voice'), 'x' => array('hour' => 2)),
			array('CallType' => array('name' => 'Internal Voice'), 'x' => array('hour' => 1))
		);

		$expected = array(7, 2, 1);
		$result = Set::extract($multiple, '/CallType[name=Internal Voice]/../x/hour');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithTypeCondition() {
		$f = array(
			array(
				'file' => array(
					'name' => 'zipfile.zip',
					'type' => 'application/zip',
					'tmp_name' => '/tmp/php178.tmp',
					'error' => 0,
					'size' => '564647'
				)
			),
			array(
				'file' => array(
					'name' => 'zipfile2.zip',
					'type' => 'application/x-zip-compressed',
					'tmp_name' => '/tmp/php179.tmp',
					'error' => 0,
					'size' => '354784'
				)
			),
			array(
				'file' => array(
					'name' => 'picture.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/tmp/php180.tmp',
					'error' => 0,
					'size' => '21324'
				)
			)
		);
		$expected = array(array(
			'name' => 'zipfile2.zip', 'type' => 'application/x-zip-compressed',
			'tmp_name' => '/tmp/php179.tmp', 'error' => 0, 'size' => '354784'
		));
		$result = Set::extract($f, '/file/.[type=application/x-zip-compressed]');
		$this->assertEqual($expected, $result);

		$expected = array(array(
			'name' => 'zipfile.zip', 'type' => 'application/zip',
			'tmp_name' => '/tmp/php178.tmp', 'error' => 0, 'size' => '564647'
		));
		$result = Set::extract($f, '/file/.[type=application/zip]');
		$this->assertEqual($expected, $result);

	}

	public function testIsNumericArrayCheck() {
		$data = array('one');
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = array(1 => 'one');
		$this->assertFalse(Set::isNumeric($data));

		$data = array('one');
		$this->assertFalse(Set::isNumeric($data));

		$data = array('one' => 'two');
		$this->assertFalse(Set::isNumeric($data));

		$data = array('one' => 1);
		$this->assertTrue(Set::isNumeric($data));

		$data = array(0);
		$this->assertTrue(Set::isNumeric($data));

		$data = array('one', 'two', 'three', 'four', 'five');
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = array(1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = array('1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five');
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = array('one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five');
		$this->assertFalse(Set::isNumeric(array_keys($data)));
	}

	public function testCheckKeys() {
		$data = array('Multi' => array('dimensonal' => array('array')));
		$this->assertTrue(Set::check($data, 'Multi.dimensonal'));
		$this->assertFalse(Set::check($data, 'Multi.dimensonal.array'));

		$data = array(
			array(
				'Article' => array(
					'id' => '1', 'user_id' => '1', 'title' => 'First Article',
					'body' => 'First Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(
					array(
						'id' => '1', 'article_id' => '1', 'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					),
					array(
						'id' => '2', 'article_id' => '1', 'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					),
				),
				'Tag' => array(
					array(
						'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					),
					array(
						'id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
					)
				)
			),
			array(
				'Article' => array(
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				),
				'User' => array(
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Comment' => array(),
				'Tag' => array()
			)
		);
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertTrue(Set::check($data, '0.Comment.0.id'));
		$this->assertFalse(Set::check($data, '0.Comment.0.id.0'));
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertFalse(Set::check($data, '0.Article.user_id.a'));
	}

	public function testMerge() {
		$result = Set::merge(array('foo'));
		$this->assertIdentical($result, array('foo'));

		$result = Set::merge('foo');
		$this->assertIdentical($result, array('foo'));

		$result = Set::merge('foo', 'bar');
		$this->assertIdentical($result, array('foo', 'bar'));

		$result = Set::merge('foo', array('user' => 'bob', 'no-bar'));
		$this->assertIdentical($result, array('foo', 'user' => 'bob', 'no-bar'));

		$a = array('foo', 'foo2');
		$b = array('bar', 'bar2');
		$this->assertIdentical(Set::merge($a, $b), array('foo', 'foo2', 'bar', 'bar2'));

		$a = array('foo' => 'bar', 'bar' => 'foo');
		$b = array('foo' => 'no-bar', 'bar' => 'no-foo');
		$this->assertIdentical(Set::merge($a, $b), array('foo' => 'no-bar', 'bar' => 'no-foo'));

		$a = array('users' => array('bob', 'jim'));
		$b = array('users' => array('lisa', 'tina'));
		$this->assertIdentical(Set::merge($a, $b), array('users' => array('bob', 'jim', 'lisa', 'tina')));

		$a = array('users' => array('jim', 'bob'));
		$b = array('users' => 'none');
		$this->assertIdentical(Set::merge($a, $b), array('users' => 'none'));

		$a = array('users' => array('lisa' => array('id' => 5, 'pw' => 'secret')), 'lithium');
		$b = array('users' => array('lisa' => array('pw' => 'new-pass', 'age' => 23)), 'ice-cream');
		$this->assertIdentical(
			Set::merge($a, $b),
			array(
				'users' => array('lisa' => array('id' => 5, 'pw' => 'new-pass', 'age' => 23)),
				'lithium',
				'ice-cream'
			)
		);

		$c = array(
			'users' => array(
				'lisa' => array('pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog')
			),
			'chocolate'
		);
		$expected = array(
			'users' => array(
				'lisa' => array(
					'id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog'
				)
			),
			'lithium',
			'ice-cream',
			'chocolate'
		);
		$this->assertIdentical($expected, Set::merge(Set::merge($a, $b), $c));

		$this->assertIdentical($expected, Set::merge(Set::merge($a, $b), Set::merge(array(), $c)));

		$result = Set::merge($a, Set::merge($b, $c));
		$this->assertIdentical($expected, $result);

		$a = array('Tree', 'CounterCache',
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')));
		$b =  array('Cacheable' => array('enabled' => false),
				'Limit',
				'Bindable',
				'Validator',
				'Transactional');

		$expected = array('Tree', 'CounterCache',
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')),
				'Cacheable' => array('enabled' => false),
				'Limit',
				'Bindable',
				'Validator',
				'Transactional');

		$this->assertIdentical(Set::merge($a, $b), $expected);

		$expected = array('Tree' => null, 'CounterCache' => null,
				'Upload' => array('folder' => 'products',
					'fields' => array('image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id')),
				'Cacheable' => array('enabled' => false),
				'Limit' => null,
				'Bindable' => null,
				'Validator' => null,
				'Transactional' => null);

		$this->assertIdentical(Set::normalize(Set::merge($a, $b)), $expected);
	}

	public function testSort() {
		$a = array(
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay')))
		);
		$b = array(
			array('Person' => array('name' => 'Tracy'),'Friend' => array(array('name' => 'Lindsay'))),
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate')))
		);
		$a = Set::sort($a, '/Friend/name', 'asc');
		$this->assertIdentical($a, $b);

		$b = array(
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			array('Person' => array('name' => 'Tracy'), 'Friend' => array(array('name' => 'Lindsay')))
		);
		$a = array(
			array('Person' => array('name' => 'Tracy'), 'Friend' => array(array('name' => 'Lindsay'))),
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate')))
		);
		$a = Set::sort($a, '/Friend/name', 'desc');
		$this->assertIdentical($a, $b);

		$a = array(
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			array('Person' => array('name' => 'Tracy'), 'Friend' => array(array('name' => 'Lindsay'))),
			array('Person' => array('name' => 'Adam'), 'Friend' => array(array('name' => 'Bob')))
		);
		$b = array(
			array('Person' => array('name' => 'Adam'),'Friend' => array(array('name' => 'Bob'))),
			array('Person' => array('name' => 'Jeff'), 'Friend' => array(array('name' => 'Nate'))),
			array('Person' => array('name' => 'Tracy'), 'Friend' => array(array('name' => 'Lindsay')))
		);
		$a = Set::sort($a, '/Person/name', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(array(7, 6, 4), array(3, 4, 5), array(3, 2, 1));
		$b = array(array(3, 2, 1), array(3, 4, 5), array(7, 6, 4));

		$a = Set::sort($a, '/', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(array(7, 6, 4), array(3, 4, 5), array(3, 2, array(1, 1, 1)));
		$b = array(array(3, 2, array(1, 1, 1)), array(3, 4, 5), array(7, 6, 4));

		$a = Set::sort($a, '/.', 'asc');
		$this->assertIdentical($a, $b);

		$a = array(
			array('Person' => array('name' => 'Jeff')),
			array('Shirt' => array('color' => 'black'))
		);
		$b = array(array('Person' => array('name' => 'Jeff')));
		$a = Set::sort($a, '/Person/name', 'asc');
		$this->assertIdentical($a, $b);
	}

	public function testInsert() {
		$a = array('pages' => array('name' => 'page'));

		$result = Set::insert($a, 'files', array('name' => 'files'));
		$expected = array('pages' => array('name' => 'page'), 'files' => array('name' => 'files'));
		$this->assertIdentical($expected, $result);

		$a = array('pages' => array('name' => 'page'));
		$result = Set::insert($a, 'pages.name', array());
		$expected = array('pages' => array('name' => array()));
		$this->assertIdentical($expected, $result);

		$a = array('pages' => array(array('name' => 'main'), array('name' => 'about')));

		$result = Set::insert($a, 'pages.1.vars', array('title' => 'page title'));
		$expected = array(
			'pages' => array(
				array('name' => 'main'),
				array('name' => 'about', 'vars' => array('title' => 'page title'))
			)
		);
		$this->assertIdentical($expected, $result);
	}

	public function testRemove() {
		$a = array('pages' => array('name' => 'page'), 'files' => array('name' => 'files'));

		$result = Set::remove($a, 'files', array('name' => 'files'));
		$expected = array('pages' => array('name' => 'page'));
		$this->assertIdentical($expected, $result);

		$a = array(
			'pages' => array(
				array('name' => 'main'),
				array('name' => 'about', 'vars' => array('title' => 'page title'))
			)
		);

		$result = Set::remove($a, 'pages.1.vars', array('title' => 'page title'));
		$expected = array('pages' => array(array('name' => 'main'), array('name' => 'about')));
		$this->assertIdentical($expected, $result);

		$result = Set::remove($a, 'pages.2.vars', array('title' => 'page title'));
		$expected = $a;
		$this->assertIdentical($expected, $result);
	}

	public function testCheck() {
		$set = array(
			'My Index 1' => array('First' => 'The first item')
		);
		$this->assertTrue(Set::check($set, 'My Index 1.First'));
		$this->assertTrue(Set::check($set, 'My Index 1'));
		$this->assertTrue(Set::check($set, array()));

		$set = array('My Index 1' => array('First' => array('Second' => array('Third' => array(
			'Fourth' => 'Heavy. Nesting.'
		)))));
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second'));
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second.Third'));
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second.Third.Fourth'));
		$this->assertFalse(Set::check($set, 'My Index 1.First.Seconds.Third.Fourth'));
	}

	public function testInsertAndRemoveWithFunkyKeys() {
		$set = Set::insert(array(), 'Session Test', "test");
		$result = Set::extract($set, '/Session Test');
		$this->assertEqual($result, array('test'));

		$set = Set::remove($set, 'Session Test');
		$this->assertFalse(Set::check($set, 'Session Test'));

		$this->assertTrue($set = Set::insert(array(), 'Session Test.Test Case', "test"));
		$this->assertTrue(Set::check($set, 'Session Test.Test Case'));
	}

	public function testDiff() {
		$a = array(array('name' => 'main'), array('name' => 'about'));
		$b = array(array('name' => 'main'), array('name' => 'about'), array('name' => 'contact'));

		$result = Set::diff($a, $b);
		$expected = array(2 => array('name' => 'contact'));
		$this->assertIdentical($expected, $result);

		$result = Set::diff($a, array());
		$expected = $a;
		$this->assertIdentical($expected, $result);

		$result = Set::diff(array(), $b);
		$expected = $b;
		$this->assertIdentical($expected, $result);

		$b = array(array('name' => 'me'), array('name' => 'about'));

		$result = Set::diff($a, $b);
		$expected = array(array('name' => 'main'));
		$this->assertIdentical($expected, $result);
	}

	public function testContains() {
		$a = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about')
		);
		$b = array(
			0 => array('name' => 'main'),
			1 => array('name' => 'about'),
			2 => array('name' => 'contact'),
			'a' => 'b'
		);

		$this->assertTrue(Set::contains($a, $a));
		$this->assertFalse(Set::contains($a, $b));
		$this->assertTrue(Set::contains($b, $a));
	}

	public function testCombine() {
		$result = Set::combine(array(), '/User/id', '/User/Data');
		$this->assertFalse($result);
		$result = Set::combine('', '/User/id', '/User/Data');
		$this->assertFalse($result);

		$a = array(
			array('User' => array('id' => 2, 'group_id' => 1,
				'Data' => array('user' => 'mariano.iglesias','name' => 'Mariano Iglesias'))),
			array('User' => array('id' => 14, 'group_id' => 2,
				'Data' => array('user' => 'jperras', 'name' => 'Joel Perras'))),
			array('User' => array('id' => 25, 'group_id' => 1,
				'Data' => array('user' => 'gwoo','name' => 'The Gwoo'))));
		$result = Set::combine($a, '/User/id');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/non-existant');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/.');
		$expected = array(
			2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
			14 => array('user' => 'jperras', 'name' => 'Joel Perras'),
			25 => array('user' => 'gwoo', 'name' => 'The Gwoo'));
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/name/.');
		$expected = array(
			2 => 'Mariano Iglesias',
			14 => 'Joel Perras',
			25 => 'The Gwoo');
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/.', '/User/group_id');
		$expected = array(
			1 => array(
				2 => array('user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'),
				25 => array('user' => 'gwoo', 'name' => 'The Gwoo')),
			2 => array(
				14 => array('user' => 'jperras', 'name' => 'Joel Perras')));
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/name/.', '/User/group_id');
		$expected = array(
			1 => array(
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'),
			2 => array(
				14 => 'Joel Perras'));
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			'/User/id',
			array('{0}: {1}', '/User/Data/user', '/User/Data/name'),
			'/User/group_id'
		);
		$expected = array(
			1 => array(2 => 'mariano.iglesias: Mariano Iglesias', 25 => 'gwoo: The Gwoo'),
			2 => array(14 => 'jperras: Joel Perras')
		);
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			array('{0}: {1}', '/User/Data/user', '/User/Data/name'),
			'/User/id'
		);
		$expected = array(
			'mariano.iglesias: Mariano Iglesias' => 2,
			'jperras: Joel Perras' => 14,
			'gwoo: The Gwoo' => 25
		);
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			array('{1}: {0}', '/User/Data/user', '/User/Data/name'),
			'/User/id'
		);
		$expected = array(
			'Mariano Iglesias: mariano.iglesias' => 2,
			'Joel Perras: jperras' => 14,
			'The Gwoo: gwoo' => 25
		);
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, array(
			'%1$s: %2$d', '/User/Data/user', '/User/id'), '/User/Data/name'
		);
		$expected = array(
			'mariano.iglesias: 2' => 'Mariano Iglesias',
			'jperras: 14' => 'Joel Perras',
			'gwoo: 25' => 'The Gwoo'
		);
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, array(
			'%2$d: %1$s', '/User/Data/user', '/User/id'), '/User/Data/name'
		);
		$expected = array(
			'2: mariano.iglesias' => 'Mariano Iglesias',
			'14: jperras' => 'Joel Perras',
			'25: gwoo' => 'The Gwoo'
		);
		$this->assertIdentical($expected, $result);

		$b = new \stdClass();
		$b->users = array(
			array('User' => array(
				'id' => 2, 'group_id' => 1, 'Data' => array(
					'user' => 'mariano.iglesias','name' => 'Mariano Iglesias'
				)
			)),
			array('User' => array('id' => 14, 'group_id' => 2, 'Data' => array(
				'user' => 'jperras', 'name' => 'Joel Perras'
			))),
			array('User' => array('id' => 25, 'group_id' => 1, 'Data' => array(
				'user' => 'gwoo','name' => 'The Gwoo'
			)))
		);
		$result = Set::combine($b, '/users/User/id');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($expected, $result);

		$result = Set::combine($b, '/users/User/id', '/users/User/non-existant');
		$expected = array(2 => null, 14 => null, 25 => null);
		$this->assertIdentical($expected, $result);
	}




	public function testBlend() {
		$array1 = array('ModelOne' => array(
			'id' => 1001, 'field_one' => 'a1.m1.f1', 'field_two' => 'a1.m1.f2'
		));
		$array2 = array('ModelTwo' => array(
			'id' => 1002, 'field_one' => 'a2.m2.f1', 'field_two' => 'a2.m2.f2'
		));

		$result = Set::blend($array1, $array2);

		$this->assertIdentical($result, $array1 + $array2);

		$array3 = array('ModelOne' => array(
			'id' => 1003, 'field_one' => 'a3.m1.f1',
			'field_two' => 'a3.m1.f2', 'field_three' => 'a3.m1.f3'
		));
		$result = Set::blend($array1, $array3);

		$expected = array('ModelOne' => array(
			'id' => 1001, 'field_one' => 'a1.m1.f1',
			'field_two' => 'a1.m1.f2', 'field_three' => 'a3.m1.f3'
		));
		$this->assertIdentical($expected, $result);


		$array1 = array(
			array('ModelOne' => array(
				'id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2'
			)),
			array('ModelTwo' => array(
				'id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2'
			))
		);
		$array2 = array(
			array('ModelOne' => array(
				'id' => 1001, 'field_one' => 's2.0.m1.f1', 'field_two' => 's2.0.m1.f2'
			)),
			array('ModelTwo' => array(
				'id' => 1002, 'field_one' => 's2.1.m2.f2', 'field_two' => 's2.1.m2.f2'
			))
		);

		$result = Set::blend($array1, $array2);
		$this->assertIdentical($result, $array1);

		$array3 = array(array('ModelThree' => array(
			'id' => 1003, 'field_one' => 's3.0.m3.f1', 'field_two' => 's3.0.m3.f2'
		)));

		$result = Set::blend($array1, $array3);
		$expected = array(
			array(
				'ModelOne' => array(
					'id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2'
				),
				'ModelThree' => array(
					'id' => 1003, 'field_one' => 's3.0.m3.f1', 'field_two' => 's3.0.m3.f2'
				)
			),
			array('ModelTwo' => array(
				'id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2'
			))
		);
		$this->assertIdentical($expected, $result);

		$result = Set::blend($array1, null);
		$this->assertIdentical($result, $array1);

		$result = Set::blend($array1, $array2);
		$this->assertIdentical($result, $array1 + $array2);
	}

	public function testStrictKeyCheck() {
		$set = array('a' => 'hi');
		$this->assertFalse(Set::check($set, 'a.b'));
	}

	public function testMixedKeyNormalization() {
		$input = array('"string"' => array('before' => '=>'), 1 => array('before' => '=>'));
		$result = Set::normalize($input);
		$this->assertEqual($input, $result);
	}

	public function testToArrayNullAndFalse() {
		$result = Set::to('array', null);
		$this->assertEqual(null, $result);

		$result = Set::to('array', false);
		$this->assertEqual(false, $result);
	}

	public function testToArrayFromObject() {
		$expected = array('User' => array('psword'=> 'whatever', 'Icon' => array('id' => 851)));
		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new \stdClass;
		$class->User->Icon->id = 851;
		$result = Set::to('array', $class);
		$this->assertIdentical($expected, $result);

		$expected = array(
			'User' => array(
				'psword'=> 'whatever', 'Icon' => array('id' => 851),
				'Profile' => array('name' => 'Some Name', 'address' => 'Some Address')
			)
		);
		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new \stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new \stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';

		$result = Set::to('array', $class);
		$this->assertIdentical($expected, $result);

		$expected = array('User' => array(
			'psword'=> 'whatever',
			'Icon' => array('id'=> 851),
			'Profile' => array('name' => 'Some Name', 'address' => 'Some Address'),
			'Comment' => array(
				array(
					'id' => 1, 'article_id' => 1, 'user_id' => 1,
					'comment' => 'First Comment for First Article', 'published' => 'Y',
					'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
				),
				array(
					'id' => 2, 'article_id' => 1, 'user_id' => 2,
					'comment' => 'Second Comment for First Article', 'published' => 'Y',
					'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
				)
			)
		));

		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new \stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new \stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';
		$class->User->Comment = new \stdClass;
		$class->User->Comment->{'0'} = new \stdClass;
		$class->User->Comment->{'0'}->id = 1;
		$class->User->Comment->{'0'}->article_id = 1;
		$class->User->Comment->{'0'}->user_id = 1;
		$class->User->Comment->{'0'}->comment = 'First Comment for First Article';
		$class->User->Comment->{'0'}->published = 'Y';
		$class->User->Comment->{'0'}->created = '2007-03-18 10:47:23';
		$class->User->Comment->{'0'}->updated = '2007-03-18 10:49:31';
		$class->User->Comment->{'1'} = new \stdClass;
		$class->User->Comment->{'1'}->id = 2;
		$class->User->Comment->{'1'}->article_id = 1;
		$class->User->Comment->{'1'}->user_id = 2;
		$class->User->Comment->{'1'}->comment = 'Second Comment for First Article';
		$class->User->Comment->{'1'}->published = 'Y';
		$class->User->Comment->{'1'}->created = '2007-03-18 10:47:23';
		$class->User->Comment->{'1'}->updated = '2007-03-18 10:49:31';

		$result = Set::to('array', $class);
		$this->assertIdentical($expected, $result);

		$expected = array('User' => array(
			'psword'=> 'whatever',
			'Icon' => array('id'=> 851),
			'Profile' => array('name' => 'Some Name', 'address' => 'Some Address'),
			'Comment' => array(
				array(
					'id' => 1, 'article_id' => 1, 'user_id' => 1,
					'comment' => 'First Comment for First Article', 'published' => 'Y',
					'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
				),
				array(
					'id' => 2, 'article_id' => 1, 'user_id' => 2,
					'comment' => 'Second Comment for First Article', 'published' => 'Y',
					'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'
				)
			)
		));

		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->psword = 'whatever';
		$class->User->Icon = new \stdClass;
		$class->User->Icon->id = 851;
		$class->User->Profile = new \stdClass;
		$class->User->Profile->name = 'Some Name';
		$class->User->Profile->address = 'Some Address';
		$class->User->Comment = array();
		$comment = new \stdClass;
		$comment->id = 1;
		$comment->article_id = 1;
		$comment->user_id = 1;
		$comment->comment = 'First Comment for First Article';
		$comment->published = 'Y';
		$comment->created = '2007-03-18 10:47:23';
		$comment->updated = '2007-03-18 10:49:31';
		$comment2 = new \stdClass;
		$comment2->id = 2;
		$comment2->article_id = 1;
		$comment2->user_id = 2;
		$comment2->comment = 'Second Comment for First Article';
		$comment2->published = 'Y';
		$comment2->created = '2007-03-18 10:47:23';
		$comment2->updated = '2007-03-18 10:49:31';
		$class->User->Comment =  array($comment, $comment2);
		$result = Set::to('array', $class);
		$this->assertIdentical($expected, $result);

		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->id = 100;
		$class->someString = 'this is some string';
		$class->Profile = new \stdClass;
		$class->Profile->name = 'Joe Mamma';

		$result = Set::to('array', $class);
		$expected = array(
			'User' => array('id' => '100'),
			'someString' => 'this is some string',
			'Profile' => array('name' => 'Joe Mamma')
		);
		$this->assertEqual($expected, $result);

		$class = new \stdClass;
		$class->User = new \stdClass;
		$class->User->id = 100;
		$class->User->_name_ = 'User';
		$class->Profile = new \stdClass;
		$class->Profile->name = 'Joe Mamma';
		$class->Profile->_name_ = 'Profile';

		$result = Set::to('array', $class);
		$expected = array(
			'User' => array('id' => '100'),
			'Profile' => array('name' => 'Joe Mamma')
		);
		$this->assertEqual($expected, $result);
	}

	public function testAssociativeArrayToObject() {
		$data =array(
			'Post' => array(
				'id' => '1', 'author_id' => '1', 'title' => 'First Post',
				'body' => 'First Post Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Author' => array(
				'id' => '1', 'user' => 'mariano',
				'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
				'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31',
				'test' => 'working'
			),
		);
		$result = Set::to('object', $data);
		$expected = new \stdClass;
		$expected->_name_ = 'Post';
		$expected->id = '1';
		$expected->author_id = '1';
		$expected->title = 'First Post';
		$expected->body = 'First Post Body';
		$expected->published = 'Y';
		$expected->created = "2007-03-18 10:39:23";
		$expected->updated = "2007-03-18 10:41:31";

		$expected->Author = new \stdClass;
		$expected->Author->id = '1';
		$expected->Author->user = 'mariano';
		$expected->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected->Author->created = "2007-03-17 01:16:23";
		$expected->Author->updated = "2007-03-17 01:18:31";
		$expected->Author->test = "working";
		$expected->Author->_name_ = 'Author';
		$this->assertEqual($expected, $result);
	}

	public function testNestedArrayToObject() {
		$data = array(
			array(
				'Post' => array(
					'id' => '1', 'author_id' => '1', 'title' => 'First Post',
					'body' => 'First Post Body', 'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				),
				'Author' => array(
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31',
					'test' => 'working'
				),
			),
			array(
				'Post' => array(
					'id' => '2', 'author_id' => '3', 'title' => 'Second Post',
					'body' => 'Second Post Body', 'published' => 'Y',
					'created' => '2007-03-18 10:41:23',
					'updated' => '2007-03-18 10:43:31'
				),
				'Author' => array(
					'id' => '3', 'user' => 'joel',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:20:23',
					'updated' => '2007-03-17 01:22:31',
					'test' => 'working'
				),
			)
		);
		$result = Set::to('object', $data);

		$expected = new \stdClass;
		$expected->_name_ = 'Post';
		$expected->id = '1';
		$expected->author_id = '1';
		$expected->title = 'First Post';
		$expected->body = 'First Post Body';
		$expected->published = 'Y';
		$expected->created = "2007-03-18 10:39:23";
		$expected->updated = "2007-03-18 10:41:31";

		$expected->Author = new \stdClass;
		$expected->Author->id = '1';
		$expected->Author->user = 'mariano';
		$expected->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected->Author->created = "2007-03-17 01:16:23";
		$expected->Author->updated = "2007-03-17 01:18:31";
		$expected->Author->test = "working";
		$expected->Author->_name_ = 'Author';

		$expected2 = new \stdClass;
		$expected2->_name_ = 'Post';
		$expected2->id = '2';
		$expected2->author_id = '3';
		$expected2->title = 'Second Post';
		$expected2->body = 'Second Post Body';
		$expected2->published = 'Y';
		$expected2->created = "2007-03-18 10:41:23";
		$expected2->updated = "2007-03-18 10:43:31";

		$expected2->Author = new \stdClass;
		$expected2->Author->id = '3';
		$expected2->Author->user = 'joel';
		$expected2->Author->password = '5f4dcc3b5aa765d61d8327deb882cf99';
		$expected2->Author->created = "2007-03-17 01:20:23";
		$expected2->Author->updated = "2007-03-17 01:22:31";
		$expected2->Author->test = "working";
		$expected2->Author->_name_ = 'Author';

		$test = array();
		$test[0] = $expected;
		$test[1] = $expected2;

		$this->assertEqual($test, $result);
	}

	public function testDeepNestedArrayToObject() {
		$data = array(
			'User' => array(
				'id' => 1,
				'email' => 'user@example.com',
				'first_name' => 'John',
				'last_name' => 'Smith',
			),
			'Piece' => array(
				array(
					'id' => 1,
					'title' => 'Moonlight Sonata',
					'composer' => 'Ludwig van Beethoven',
					'PiecesUser' => array(
						'id' => 1,
						'created' => '2008-01-01 00:00:00',
						'modified' => '2008-01-01 00:00:00',
						'piece_id' => 1,
						'user_id' => 2,
					)
				),
				array(
					'id' => 2,
					'title' => 'Moonlight Sonata 2',
					'composer' => 'Ludwig van Beethoven',
					'PiecesUser' => array(
						'id' => 2,
						'created' => '2008-01-01 00:00:00',
						'modified' => '2008-01-01 00:00:00',
						'piece_id' => 2,
						'user_id' => 2,
					)
				)
			)
		);

		$result = Set::to('object', $data);

		$expected = new \stdClass();
		$expected->_name_ = 'User';
		$expected->id = 1;
		$expected->email = 'user@example.com';
		$expected->first_name = 'John';
		$expected->last_name = 'Smith';

		$piece = new \stdClass();
		$piece->id = 1;
		$piece->title = 'Moonlight Sonata';
		$piece->composer = 'Ludwig van Beethoven';

		$piece->PiecesUser = new \stdClass();
		$piece->PiecesUser->id = 1;
		$piece->PiecesUser->created = '2008-01-01 00:00:00';
		$piece->PiecesUser->modified = '2008-01-01 00:00:00';
		$piece->PiecesUser->piece_id = 1;
		$piece->PiecesUser->user_id = 2;
		$piece->PiecesUser->_name_ = 'PiecesUser';

		$piece->_name_ = 'Piece';


		$piece2 = new \stdClass();
		$piece2->id = 2;
		$piece2->title = 'Moonlight Sonata 2';
		$piece2->composer = 'Ludwig van Beethoven';

		$piece2->PiecesUser = new \stdClass();
		$piece2->PiecesUser->id = 2;
		$piece2->PiecesUser->created = '2008-01-01 00:00:00';
		$piece2->PiecesUser->modified = '2008-01-01 00:00:00';
		$piece2->PiecesUser->piece_id = 2;
		$piece2->PiecesUser->user_id = 2;
		$piece2->PiecesUser->_name_ = 'PiecesUser';

		$piece2->_name_ = 'Piece';

		$expected->Piece = array($piece, $piece2);

		$this->assertEqual($expected, $result);
	}

	public function testToObjectNullValue() {
		$expected = null;
		$result = Set::to('object', null);
		$this->assertEqual($expected, $result);
	}

	public function testToObjectWithTypicalArray() {
		$expected = array(
			'Post' => array('id'=> 1, 'title' => 'First Post'),
			'Comment' => array(
				array('id'=> 1, 'title' => 'First Comment'),
				array('id'=> 2, 'title' => 'Second Comment')
			),
			'Tag' => array(
				array('id'=> 1, 'title' => 'First Tag'),
				array('id'=> 2, 'title' => 'Second Tag')
			),
		);
		$map = Set::to('object', $expected);
		$this->assertIdentical($map->title, $expected['Post']['title']);
		foreach ($map->Comment as $comment) {
			$ids[] = $comment->id;
		}
		$this->assertIdentical($ids, array(1, 2));

		$expected = array('User' => array('psword'=> 'whatever', 'Icon' => array('id' => 851)));
		$map = Set::to('object', $expected);
		$result = Set::to('array', $map);
		$this->assertIdentical($expected, $result);
	}

	public function testToObjectWithPredifinedName() {
		$data = array(
			'User' => array(
				'id' => 1,
				'email' => 'user@example.com',
				'first_name' => 'John',
				'last_name' => 'Smith',
				'_name_' => 'FooUser',
			),
			'Piece' => array(
				array(
					'id' => 1,
					'title' => 'Moonlight Sonata',
					'composer' => 'Ludwig van Beethoven',
					'_name_' => 'FooPiece',
					'PiecesUser' => array(
						'id' => 1,
						'created' => '2008-01-01 00:00:00',
						'modified' => '2008-01-01 00:00:00',
						'piece_id' => 1,
						'user_id' => 2,
						'_name_' => 'FooPiecesUser',
					)
				),
				array(
					'id' => 2,
					'title' => 'Moonlight Sonata 2',
					'composer' => 'Ludwig van Beethoven',
					'_name_' => 'FooPiece',
					'PiecesUser' => array(
						'id' => 2,
						'created' => '2008-01-01 00:00:00',
						'modified' => '2008-01-01 00:00:00',
						'piece_id' => 2,
						'user_id' => 2,
						'_name_' => 'FooPiecesUser',
					)
				)
			)
		);

		$result = Set::to('object', $data);

		$expected = new \stdClass();
		$expected->_name_ = 'FooUser';
		$expected->id = 1;
		$expected->email = 'user@example.com';
		$expected->first_name = 'John';
		$expected->last_name = 'Smith';

		$piece = new \stdClass();
		$piece->id = 1;
		$piece->title = 'Moonlight Sonata';
		$piece->composer = 'Ludwig van Beethoven';
		$piece->_name_ = 'FooPiece';
		$piece->PiecesUser = new \stdClass();
		$piece->PiecesUser->id = 1;
		$piece->PiecesUser->created = '2008-01-01 00:00:00';
		$piece->PiecesUser->modified = '2008-01-01 00:00:00';
		$piece->PiecesUser->piece_id = 1;
		$piece->PiecesUser->user_id = 2;
		$piece->PiecesUser->_name_ = 'FooPiecesUser';

		$piece2 = new \stdClass();
		$piece2->id = 2;
		$piece2->title = 'Moonlight Sonata 2';
		$piece2->composer = 'Ludwig van Beethoven';
		$piece2->_name_ = 'FooPiece';
		$piece2->PiecesUser = new \stdClass();
		$piece2->PiecesUser->id = 2;
		$piece2->PiecesUser->created = '2008-01-01 00:00:00';
		$piece2->PiecesUser->modified = '2008-01-01 00:00:00';
		$piece2->PiecesUser->piece_id = 2;
		$piece2->PiecesUser->user_id = 2;
		$piece2->PiecesUser->_name_ = 'FooPiecesUser';

		$expected->Piece = array($piece, $piece2);

		$this->assertEqual($expected, $result);
	}

	public function testComplexArrayToObjectBackToArray() {
		$expected = array(
			array(
				"IndexedPage" => array(
					"id" => 1,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'headers' => array(
							'Date' => "Wed, 14 Nov 2007 15:51:42 GMT",
							'Server' => "Apache",
							'Expires' => "Thu, 19 Nov 1981 08:52:00 GMT",
							'Cache-Control' => "private",
							'Pragma' => "no-cache",
							'Content-Type' => "text/html; charset=UTF-8",
							'X-Original-Transfer-Encoding' => "chunked",
							'Content-Length' => "50210",
					),
					'meta' => array(
						'keywords' => array('testing','tests'),
						'description'=>'describe me',
					),
					'get_vars' => '',
					'post_vars' => array(),
					'cookies' => array('PHPSESSID' => "dde9896ad24595998161ffaf9e0dbe2d"),
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				)
			),
			array(
				"IndexedPage" => array(
					"id" => 2,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'headers' => array(
						'Date' => "Wed, 14 Nov 2007 15:51:42 GMT",
						'Server' => "Apache",
						'Expires' => "Thu, 19 Nov 1981 08:52:00 GMT",
						'Cache-Control' => "private",
						'Pragma' => "no-cache",
						'Content-Type' => "text/html; charset=UTF-8",
						'X-Original-Transfer-Encoding' => "chunked",
						'Content-Length' => "50210",
					),
					'meta' => array(
						'keywords' => array('testing','tests'),
						'description'=>'describe me',
					),
					'get_vars' => '',
					'post_vars' => array(),
					'cookies' => array('PHPSESSID' => "dde9896ad24595998161ffaf9e0dbe2d"),
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				),
			)
		);

		$mapped = Set::to('object', $expected);
		$ids = array();

		foreach ($mapped as $object)	 {
			$ids[] = $object->id;
		}
		$this->assertEqual($ids, array(1, 2));
		$this->assertEqual(
			get_object_vars($mapped[0]->headers),
			$expected[0]['IndexedPage']['headers']
		);

		$result = Set::to('array', $mapped);
		$this->assertIdentical($expected, $result);

		$data = array(
			array(
				"IndexedPage" => array(
					"id" => 1,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'get_vars' => '',
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				)
			),
			array(
				"IndexedPage" => array(
					"id" => 2,
					"url" => 'http://blah.com/',
					'hash' => '68a9f053b19526d08e36c6a9ad150737933816a5',
					'get_vars' => '',
					'redirect' => '',
					'created' => "1195055503",
					'updated' => "1195055503",
				),
			)
		);
		$mapped = Set::to('object', $data);

		$expected = new \stdClass();
		$expected->_name_ = 'IndexedPage';
		$expected->id = 2;
		$expected->url = 'http://blah.com/';
		$expected->hash = '68a9f053b19526d08e36c6a9ad150737933816a5';
		$expected->get_vars = '';
		$expected->redirect = '';
		$expected->created = "1195055503";
		$expected->updated = "1195055503";
		$this->assertEqual($mapped[1], $expected);

		$ids = array();

		foreach ($mapped as $object)	 {
			$ids[] = $object->id;
		}
		$this->assertEqual($ids, array(1, 2));
	}

	public function testMixedArrayToObjectAndBackToArrayNotFlattened() {
		$expected = array(
			'Array1' => array(
				'Array1Data1' => 'Array1Data1 value 1', 'Array1Data2' => 'Array1Data2 value 2'
			),
			'Array2' => array(
				array(
					'Array2Data1' => 1, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 2, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 3, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 4, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 5, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				)
			),
			'Array3' => array(
				array(
					'Array3Data1' => 1, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 2, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 3, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 4, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 5, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				)
			)
		);
		$map = Set::to('object', $expected, array('flatten' => false));
		$this->assertEqual($map->Array1->Array1Data1, $expected['Array1']['Array1Data1']);
		$this->assertEqual($map->Array2[0]->Array2Data1, $expected['Array2'][0]['Array2Data1']);

		$result = Set::to('array', $map);
		$this->assertEqual($expected, $result);
	}

	public function testToObjectAndToArrayWithSomeRandomArraysNotFlattened() {
		$expected = array(
			'Array1' => array(
				'Array1Data1' => 'Array1Data1 value 1', 'Array1Data2' => 'Array1Data2 value 2',
				'Array1Data3' => 'Array1Data3 value 3','Array1Data4' => 'Array1Data4 value 4',
				'Array1Data5' => 'Array1Data5 value 5', 'Array1Data6' => 'Array1Data6 value 6',
				'Array1Data7' => 'Array1Data7 value 7', 'Array1Data8' => 'Array1Data8 value 8'
			),
			'string' => 1,
			'another' => 'string',
			'some' => 'thing else',
			'Array2' => array(
				array(
					'Array2Data1' => 1, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 2, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 3, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 4, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				),
				array(
					'Array2Data1' => 5, 'Array2Data2' => 'Array2Data2 value 2',
					'Array2Data3' => 'Array2Data3 value 2', 'Array2Data4' => 'Array2Data4 value 4'
				)
			),
			'Array3' => array(
				array(
					'Array3Data1' => 1, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 2, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 3, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 4, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				),
				array(
					'Array3Data1' => 5, 'Array3Data2' => 'Array3Data2 value 2',
					'Array3Data3' => 'Array3Data3 value 2', 'Array3Data4' => 'Array3Data4 value 4'
				)
			)
		);
		$map = Set::to('object', $expected, array('flatten' => false));
		$result = Set::to('array', $map);
		$this->assertIdentical($expected, $result);
	}
}

?>