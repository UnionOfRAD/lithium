<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\util;

use stdClass;
use lithium\util\Set;

class SetTest extends \lithium\test\Unit {

	public function testDepthWithEmptyData() {
		$data = [];
		$result = Set::depth($data);
		$this->assertEqual(0, $result);
	}

	public function testDepthOneLevelWithDefaults() {
		$data = [];
		$result = Set::depth($data);
		$this->assertEqual(0, $result);

		$data = ['one', '2', 'three'];
		$result = Set::depth($data);
		$this->assertEqual(1, $result);

		$data = ['1' => '1.1', '2', '3'];
		$result = Set::depth($data);
		$this->assertEqual(1, $result);

		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Set::depth($data, ['all' => false]);
		$this->assertEqual(1, $result);
	}

	public function testDepthTwoLevelsWithDefaults() {
		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => '3.1.1']];
		$result = Set::depth($data);
		$this->assertEqual(2, $result);

		$data = ['1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => [
			'3.1.1' => '3.1.1.1'
		]]];
		$result = Set::depth($data);
		$this->assertEqual($result, 2);

		$data = [
			'1' => ['1.1' => '1.1.1'],
			['2' => [
				'2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]
			]],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => false]);
		$this->assertEqual($result, 2);
	}

	public function testDepthTwoLevelsWithAll() {
		$data = ['1' => '1.1', '2', '3' => ['3.1' => '3.1.1']];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(2, $result);
	}

	public function testDepthThreeLevelsWithAll() {
		$data = [
			'1' => ['1.1' => '1.1.1'], '2', '3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(3, $result);

		$data = [
			'1' => ['1.1' => '1.1.1'],
			['2' => ['2.1' => ['2.1.1' => '2.1.1.1']]],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(4, $result);

		$data = [
			'1' => ['1.1' => '1.1.1'],
			['2' => ['2.1' => ['2.1.1' => ['2.1.1.1']]]],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(5, $result);

		$data = [
			'1' => ['1.1' => '1.1.1'], [
				'2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]
			],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(5, $result);
	}

	public function testDepthFourLevelsWithAll() {
		$data = [
			'1' => ['1.1' => '1.1.1'], [
				'2' => ['2.1' => ['2.1.1' => '2.1.1.1']],
			],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(4, $result);
	}

	public function testDepthFiveLevelsWithAll() {
		$data = [
			'1' => ['1.1' => '1.1.1'], [
				'2' => ['2.1' => ['2.1.1' => ['2.1.1.1']]]
			],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(5, $result);

		$data = ['1' => ['1.1' => '1.1.1'], [
			'2' => ['2.1' => ['2.1.1' => ['2.1.1.1' => '2.1.1.1.1']]]],
			'3' => ['3.1' => ['3.1.1' => '3.1.1.1']]
		];
		$result = Set::depth($data, ['all' => true]);
		$this->assertEqual(5, $result);
	}

	public function testFlattenOneLevel() {
		$data = ['Larry', 'Curly', 'Moe'];
		$result = Set::flatten($data);
		$this->assertEqual($result, $data);

		$data[9] = 'Shemp';
		$result = Set::flatten($data);
		$this->assertEqual($result, $data);
	}

	public function testFlattenTwoLevels() {
		$data = [
			[
				'Post' => ['id' => '1', 'author_id' => '1', 'title' => 'First Post'],
				'Author' => ['id' => '1', 'user' => 'nate', 'password' => 'foo']
			],
			[
				'Post' => [
					'id' => '2',
					'author_id' => '3',
					'title' => 'Second Post',
					'body' => 'Second Post Body'
				],
				'Author' => ['id' => '3', 'user' => 'joel', 'password' => null]
			]
		];

		$expected = [
			'0.Post.id' => '1', '0.Post.author_id' => '1', '0.Post.title' => 'First Post',
			'0.Author.id' => '1', '0.Author.user' => 'nate', '0.Author.password' => 'foo',
			'1.Post.id' => '2', '1.Post.author_id' => '3', '1.Post.title' => 'Second Post',
			'1.Post.body' => 'Second Post Body', '1.Author.id' => '3',
			'1.Author.user' => 'joel', '1.Author.password' => null
		];
		$result = Set::flatten($data);
		$this->assertEqual($expected, $result);

		$result = Set::expand($result);
		$this->assertEqual($data, $result);

		$result = Set::flatten($data[0], ['separator' => '/']);
		$expected = [
			'Post/id' => '1', 'Post/author_id' => '1', 'Post/title' => 'First Post',
			'Author/id' => '1', 'Author/user' => 'nate', 'Author/password' => 'foo'
		];
		$this->assertEqual($expected, $result);

		$result = Set::expand($expected, ['separator' => '/']);
		$this->assertEqual($data[0], $result);
	}

	public function testExpand() {
		$data = [
			'Gallery.Image' => null,
			'Gallery.Image.Tag' => null,
			'Gallery.Image.Tag.Author' => null
		];
		$expected = ['Gallery' => ['Image' => ['Tag' => ['Author' => null]]]];
		$this->assertEqual($expected, Set::expand($data));

		$data = [
			'Gallery.Image.Tag' => null,
			'Gallery.Image' => null,
			'Gallery.Image.Tag.Author' => null
		];
		$expected = ['Gallery' => ['Image' => ['Tag' => ['Author' => null]]]];
		$this->assertEqual($expected, Set::expand($data));

		$data = [
			'Gallery.Image.Tag.Author' => null,
			'Gallery.Image.Tag' => null,
			'Gallery.Image' => null
		];
		$expected = ['Gallery' => ['Image' => ['Tag' => ['Author' => null]]]];
		$this->assertEqual($expected, Set::expand($data));
	}

	public function testFormat() {
		$data = [
			['Person' => [
				'first_name' => 'Nate', 'last_name' => 'Abele',
				'city' => 'Queens', 'state' => 'NY', 'something' => '42'
			]],
			['Person' => [
				'first_name' => 'Joel', 'last_name' => 'Perras',
				'city' => 'Montreal', 'state' => 'Quebec', 'something' => '{0}'
			]],
			['Person' => [
				'first_name' => 'Garrett', 'last_name' => 'Woodworth',
				'city' => 'Venice Beach', 'state' => 'CA', 'something' => '{1}'
			]]
		];

		$result = Set::format($data, '{1}, {0}', ['/Person/first_name', '/Person/last_name']);
		$expected = ['Abele, Nate', 'Perras, Joel', 'Woodworth, Garrett'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{0}, {1}', ['/Person/last_name', '/Person/first_name']);
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{0}, {1}', ['/Person/city', '/Person/state']);
		$expected = ['Queens, NY', 'Montreal, Quebec', 'Venice Beach, CA'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{{0}, {1}}', ['/Person/city', '/Person/state']);
		$expected = ['{Queens, NY}', '{Montreal, Quebec}', '{Venice Beach, CA}'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{{0}, {1}}', [
			'/Person/something', '/Person/something'
		]);
		$expected = ['{42, 42}', '{{0}, {0}}', '{{1}, {1}}'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{%2$d, %1$s}', [
			'/Person/something', '/Person/something'
		]);
		$expected = ['{42, 42}', '{0, {0}}', '{0, {1}}'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '{%1$s, %1$s}', [
			'/Person/something', '/Person/something'
		]);
		$expected = ['{42, 42}', '{{0}, {0}}', '{{1}, {1}}'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '%2$d, %1$s', [
			'/Person/first_name', '/Person/something'
		]);
		$expected = ['42, Nate', '0, Joel', '0, Garrett'];
		$this->assertEqual($expected, $result);

		$result = Set::format($data, '%1$s, %2$d', [
			'/Person/first_name', '/Person/something'
		]);
		$expected = ['Nate, 42', 'Joel, 0', 'Garrett, 0'];
		$this->assertEqual($expected, $result);
	}

	public function testMatchesBasic() {
		$a = [
			['Article' => ['id' => 1, 'title' => 'Article 1']],
			['Article' => ['id' => 2, 'title' => 'Article 2']],
			['Article' => ['id' => 3, 'title' => 'Article 3']]
		];

		$this->assertTrue(Set::matches($a[1]['Article'], ['id=2']));
		$this->assertFalse(Set::matches($a[1]['Article'], ['id>2']));
		$this->assertTrue(Set::matches($a[1]['Article'], ['id>=2']));
		$this->assertFalse(Set::matches($a[1]['Article'], ['id>=3']));
		$this->assertTrue(Set::matches($a[1]['Article'], ['id<=2']));
		$this->assertFalse(Set::matches($a[1]['Article'], ['id<2']));
		$this->assertTrue(Set::matches($a[1]['Article'], ['id>1']));
		$this->assertTrue(Set::matches($a[1]['Article'], ['id>1', 'id<3', 'id!=0']));

		$this->assertTrue(Set::matches([], ['3'], 3));
		$this->assertTrue(Set::matches([], ['5'], 5));

		$this->assertTrue(Set::matches($a[1]['Article'], ['id']));
		$this->assertTrue(Set::matches($a[1]['Article'], ['id', 'title']));
		$this->assertFalse(Set::matches($a[1]['Article'], ['non-existant']));

		$this->assertTrue(Set::matches($a, '/Article[id=2]'));
		$this->assertFalse(Set::matches($a, '/Article[id=4]'));
		$this->assertTrue(Set::matches($a, []));
	}

	public function testMatchesMultipleLevels() {
		$result = [
			'Attachment' => [
				'keep' => []
			],
			'Comment' => [
				'keep' => ['Attachment' => ['fields' => ['attachment']]]
			],
			'User' => ['keep' => []],
			'Article' => [
				'keep' => [
					'Comment' => ['fields' => ['comment', 'published']],
					'User' => ['fields' => ['user']]
				]
			]
		];
		$this->assertTrue(Set::matches($result, '/Article/keep/Comment'));

		$result = Set::matches($result, '/Article/keep/Comment/fields/user');
		$this->assertFalse($result);
	}

	public function testExtractReturnsEmptyArray() {
		$expected = [];
		$result = Set::extract([], '/Post/id');
		$this->assertIdentical($expected, $result);

		$result = Set::extract([
			['Post' => ['name' => 'bob']],
			['Post' => ['name' => 'jim']]
		], '/Post/id');
		$this->assertIdentical($expected, $result);

		$result = Set::extract([], 'Message.flash');
		$this->assertIdentical($expected, $result);
	}

	public function testExtractionOfNotNull() {
		$data = [
			'plugin' => null, 'admin' => false, 'controller' => 'posts',
			'action' => 'index', 1, 'whatever'
		];

		$expected = ['controller' => 'posts', 'action' => 'index', 1, 'whatever'];
		$result = Set::extract($data, '/');
		$this->assertIdentical($expected, $result);
	}

	public function testExtractOfNumericKeys() {
		$data = [1, 'whatever'];

		$expected = [1, 'whatever'];
		$result = Set::extract($data, '/');
		$this->assertIdentical($expected, $result);
	}

	public function testExtract() {
		$a = [
			[
				'Article' => [
					'id' => '1', 'user_id' => '1', 'title' => 'First Article',
					'body' => 'First Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
				],
				'User' => [
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [
					[
						'id' => '1', 'article_id' => '1', 'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					],
					[
						'id' => '2', 'article_id' => '1', 'user_id' => '4',
						'comment' => 'Second Comment for First Article', 'published' => 'Y',
						'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					]
				],
				'Tag' => [
					[
						'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					],
					[
						'id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
					]
				],
				'Deep' => [
					'Nesting' => [
						'test' => [1 => 'foo', 2 => ['and' => ['more' => 'stuff']]]
					]
				]
			],
			[
				'Article' => [
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				],
				'User' => [
					'id' => '2', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [],
				'Tag' => []
			],
			[
				'Article' => [
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				],
				'User' => [
					'id' => '3', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [],
				'Tag' => []
			],
			[
				'Article' => [
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				],
				'User' => [
					'id' => '4', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [],
				'Tag' => []
			],
			[
				'Article' => [
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				],
				'User' => [
					'id' => '5', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [],
				'Tag' => []
			]
		];

		$b = ['Deep' => $a[0]['Deep']];

		$c = [
			['a' => ['I' => ['a' => 1]]],
			['a' => [2]],
			['a' => ['II' => ['a' => 3, 'III' => ['a' => ['foo' => 4]]]]]
		];

		$expected = [['a' => $c[2]['a']]];
		$result = Set::extract($c, '/a/II[a=3]/..');
		$this->assertEqual($expected, $result);

		$expected = [1, 2, 3, 4, 5];
		$result = Set::extract($a, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = [1, 2, 3, 4, 5];
		$result = Set::extract($a, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = [['test' => $a[0]['Deep']['Nesting']['test']]];
		$this->assertEqual(Set::extract($a, '/Deep/Nesting/test'), $expected);
		$this->assertEqual(Set::extract($b, '/Deep/Nesting/test'), $expected);

		$expected = [['test' => $a[0]['Deep']['Nesting']['test']]];
		$result = Set::extract($a, '/Deep/Nesting/test/1/..');
		$this->assertEqual($expected, $result);

		$expected = [['test' => $a[0]['Deep']['Nesting']['test']]];
		$result = Set::extract($a, '/Deep/Nesting/test/2/and/../..');
		$this->assertEqual($expected, $result);

		$expected = [['test' => $a[0]['Deep']['Nesting']['test']]];
		$result = Set::extract($a, '/Deep/Nesting/test/2/../../../Nesting/test/2/..');
		$this->assertEqual($expected, $result);

		$expected = [2];
		$result = Set::extract($a, '/User[2]/id');
		$this->assertEqual($expected, $result);

		$expected = [4, 5];
		$result = Set::extract($a, '/User[id>3]/id');
		$this->assertEqual($expected, $result);

		$expected = [2, 3];
		$result = Set::extract($a, '/User[id>1][id<=3]/id');
		$this->assertEqual($expected, $result);

		$expected = [['I'], ['II']];
		$result = Set::extract($c, '/a/@*');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNonSequentialKeys() {
		$nonSequential = [
			'User' => [
				0  => ['id' => 1],
				2  => ['id' => 2],
				6  => ['id' => 3],
				9  => ['id' => 4],
				3  => ['id' => 5]
			]
		];

		$expected = [1, 2, 3, 4, 5];
		$result = Set::extract($nonSequential, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNoZeroKey() {
		$noZero = [
			'User' => [
				2  => ['id' => 1],
				4  => ['id' => 2],
				6  => ['id' => 3],
				9  => ['id' => 4],
				3  => ['id' => 5]
			]
		];

		$expected = [1, 2, 3, 4, 5];
		$result = Set::extract($noZero, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractSingle() {

		$single = ['User' => ['id' => 4, 'name' => 'Neo']];

		$expected = [4];
		$result = Set::extract($single, '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractHasMany() {
		$tricky = [
			0 => ['User' => ['id' => 1, 'name' => 'John']],
			1 => ['User' => ['id' => 2, 'name' => 'Bob']],
			2 => ['User' => ['id' => 3, 'name' => 'Tony']],
			'User' => ['id' => 4, 'name' => 'Neo']
		];

		$expected = [1, 2, 3, 4];
		$result = Set::extract($tricky, '/User/id');
		$this->assertEqual($expected, $result);

		$expected = [1, 3];
		$result = Set::extract($tricky, '/User[name=/n/]/id');
		$this->assertEqual($expected, $result);

		$expected = [4];
		$result = Set::extract($tricky, '/User[name=/N/]/id');
		$this->assertEqual($expected, $result);

		$expected = [1, 3, 4];
		$result = Set::extract($tricky, '/User[name=/N/i]/id');
		$this->assertEqual($expected, $result);

		$expected = [
			['id', 'name'], ['id', 'name'], ['id', 'name'], ['id', 'name']
		];
		$result = Set::extract($tricky, '/User/@*');
		$this->assertEqual($expected, $result);
	}

	public function testExtractAssociatedHasMany() {
		$common = [
			[
				'Article' => ['id' => 1, 'name' => 'Article 1'],
				'Comment' => [
					['id' => 1, 'user_id' => 5, 'article_id' => 1, 'text' => 'Comment 1'],
					['id' => 2, 'user_id' => 23, 'article_id' => 1, 'text' => 'Comment 2'],
					['id' => 3, 'user_id' => 17, 'article_id' => 1, 'text' => 'Comment 3']
				]
			],
			[
				'Article' => ['id' => 2, 'name' => 'Article 2'],
				'Comment' => [
					[
						'id' => 4,
						'user_id' => 2,
						'article_id' => 2,
						'text' => 'Comment 4',
						'addition' => ''
					],
					[
						'id' => 5,
						'user_id' => 23,
						'article_id' => 2,
						'text' => 'Comment 5',
						'addition' => 'foo'
					]
				]
			],
			[
				'Article' => ['id' => 3, 'name' => 'Article 3'],
				'Comment' => []
			]
		];
		$result = Set::extract($common, '/');
		$this->assertEqual($result, $common);

		$expected = [1];
		$result = Set::extract($common, '/Comment/id[:first]');
		$this->assertEqual($expected, $result);

		$expected = [5];
		$result = Set::extract($common, '/Comment/id[:last]');
		$this->assertEqual($expected, $result);

		$result = Set::extract($common, '/Comment/id');
		$expected = [1, 2, 3, 4, 5];
		$this->assertEqual($expected, $result);

		$expected = [1, 2, 4, 5];
		$result = Set::extract($common, '/Comment[id!=3]/id');
		$this->assertEqual($expected, $result);

		$expected = [$common[0]['Comment'][2]];
		$result = Set::extract($common, '/Comment/2');
		$this->assertEqual($expected, $result);

		$expected = [$common[0]['Comment'][0]];
		$result = Set::extract($common, '/Comment[1]/.[id=1]');
		$this->assertEqual($expected, $result);

		$expected = [$common[1]['Comment'][1]];
		$result = Set::extract($common, '/1/Comment/.[2]');
		$this->assertEqual($expected, $result);

		$expected = [['Comment' => $common[1]['Comment'][0]]];
		$result = Set::extract($common, '/Comment[addition=]');
		$this->assertEqual($expected, $result);

		$expected = [3];
		$result = Set::extract($common, '/Article[:last]/id');
		$this->assertEqual($expected, $result);

		$expected = [];
		$result = Set::extract([], '/User/id');
		$this->assertEqual($expected, $result);
	}

	public function testExtractHabtm() {
		$habtm = [
			[
				'Post' => ['id' => 1, 'title' => 'great post'],
				'Comment' => [
					['id' => 1, 'text' => 'foo', 'User' => ['id' => 1, 'name' => 'bob']],
					['id' => 2, 'text' => 'bar', 'User' => ['id' => 2, 'name' => 'tod']]
				]
			],
			[
				'Post' => ['id' => 2, 'title' => 'fun post'],
				'Comment' => [
					['id' => 3, 'text' => '123', 'User' => ['id' => 3, 'name' => 'dan']],
					['id' => 4, 'text' => '987', 'User' => ['id' => 4, 'name' => 'jim']]
				]
			]
		];

		$result = Set::extract($habtm, '/Comment/User[name=/\w+/]/..');
		$this->assertEqual(count($result), 4);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'tod');
		$this->assertEqual($result[2]['Comment']['User']['name'], 'dan');
		$this->assertEqual($result[3]['Comment']['User']['name'], 'jim');

		$result = Set::extract($habtm, '/Comment/User[name=/[a-z]+/]/..');
		$this->assertEqual(count($result), 4);
		$this->assertEqual($result[0]['Comment']['User']['name'], 'bob');
		$this->assertEqual($result[1]['Comment']['User']['name'], 'tod');
		$this->assertEqual($result[2]['Comment']['User']['name'], 'dan');
		$this->assertEqual($result[3]['Comment']['User']['name'], 'jim');

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
		$tree = [
			[
				'Category' => ['name' => 'Category 1'],
				'children' => [['Category' => ['name' => 'Category 1.1']]]
			],
			[
				'Category' => ['name' => 'Category 2'],
				'children' => [
					['Category' => ['name' => 'Category 2.1']],
					['Category' => ['name' => 'Category 2.2']]
				]
			],
			[
				'Category' => ['name' => 'Category 3'],
				'children' => [['Category' => ['name' => 'Category 3.1']]]
			]
		];

		$expected = [['Category' => $tree[1]['Category']]];
		$result = Set::extract($tree, '/Category[name=Category 2]');
		$this->assertEqual($expected, $result);

		$expected = [[
			'Category' => $tree[1]['Category'], 'children' => $tree[1]['children']
		]];
		$result = Set::extract($tree, '/Category[name=Category 2]/..');
		$this->assertEqual($expected, $result);

		$expected = [
			['children' => $tree[1]['children'][0]],
			['children' => $tree[1]['children'][1]]
		];
		$result = Set::extract($tree, '/Category[name=Category 2]/../children');
		$this->assertEqual($expected, $result);
	}

	public function testExtractOnMixedKeys() {
		$mixedKeys = [
			'User' => [
				0 => ['id' => 4, 'name' => 'Neo'],
				1 => ['id' => 5, 'name' => 'Morpheus'],
				'stringKey' => []
			]
		];

		$expected = ['Neo', 'Morpheus'];
		$result = Set::extract($mixedKeys, '/User/name');
		$this->assertEqual($expected, $result);
	}

	public function testExtractSingleWithNameCondition() {
		$single = [
			['CallType' => ['name' => 'Internal Voice'], 'x' => ['hour' => 7]]
		];

		$expected = [7];
		$result = Set::extract($single, '/CallType[name=Internal Voice]/../x/hour');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithNameCondition() {
		$multiple = [
			['CallType' => ['name' => 'Internal Voice'], 'x' => ['hour' => 7]],
			['CallType' => ['name' => 'Internal Voice'], 'x' => ['hour' => 2]],
			['CallType' => ['name' => 'Internal Voice'], 'x' => ['hour' => 1]]
		];

		$expected = [7, 2, 1];
		$result = Set::extract($multiple, '/CallType[name=Internal Voice]/../x/hour');
		$this->assertEqual($expected, $result);
	}

	public function testExtractWithTypeCondition() {
		$f = [
			[
				'file' => [
					'name' => 'zipfile.zip',
					'type' => 'application/zip',
					'tmp_name' => '/tmp/php178.tmp',
					'error' => 0,
					'size' => '564647'
				]
			],
			[
				'file' => [
					'name' => 'zipfile2.zip',
					'type' => 'application/x-zip-compressed',
					'tmp_name' => '/tmp/php179.tmp',
					'error' => 0,
					'size' => '354784'
				]
			],
			[
				'file' => [
					'name' => 'picture.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/tmp/php180.tmp',
					'error' => 0,
					'size' => '21324'
				]
			]
		];
		$expected = [[
			'name' => 'zipfile2.zip', 'type' => 'application/x-zip-compressed',
			'tmp_name' => '/tmp/php179.tmp', 'error' => 0, 'size' => '354784'
		]];
		$result = Set::extract($f, '/file/.[type=application/x-zip-compressed]');
		$this->assertEqual($expected, $result);

		$expected = [[
			'name' => 'zipfile.zip', 'type' => 'application/zip',
			'tmp_name' => '/tmp/php178.tmp', 'error' => 0, 'size' => '564647'
		]];
		$result = Set::extract($f, '/file/.[type=application/zip]');
		$this->assertEqual($expected, $result);

	}

	public function testIsNumericArrayCheck() {
		$data = ['one'];
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = [1 => 'one'];
		$this->assertFalse(Set::isNumeric($data));

		$data = ['one'];
		$this->assertFalse(Set::isNumeric($data));

		$data = ['one' => 'two'];
		$this->assertFalse(Set::isNumeric($data));

		$data = ['one' => 1];
		$this->assertTrue(Set::isNumeric($data));

		$data = [0];
		$this->assertTrue(Set::isNumeric($data));

		$data = ['one', 'two', 'three', 'four', 'five'];
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five'];
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = ['1' => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five'];
		$this->assertTrue(Set::isNumeric(array_keys($data)));

		$data = ['one', 2 => 'two', 3 => 'three', 4 => 'four', 'a' => 'five'];
		$this->assertFalse(Set::isNumeric(array_keys($data)));

		$data = [];
		$this->assertNull(Set::isNumeric($data));
	}

	public function testCheckKeys() {
		$data = ['Multi' => ['dimensonal' => ['array']]];
		$this->assertTrue(Set::check($data, 'Multi.dimensonal'));
		$this->assertFalse(Set::check($data, 'Multi.dimensonal.array'));

		$data = [
			[
				'Article' => [
					'id' => '1', 'user_id' => '1', 'title' => 'First Article',
					'body' => 'First Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:39:23',
					'updated' => '2007-03-18 10:41:31'
				],
				'User' => [
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23',
					'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [
					[
						'id' => '1', 'article_id' => '1', 'user_id' => '2',
						'comment' => 'First Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:45:23',
						'updated' => '2007-03-18 10:47:31'
					],
					[
						'id' => '2', 'article_id' => '1', 'user_id' => '4',
						'comment' => 'Second Comment for First Article',
						'published' => 'Y', 'created' => '2007-03-18 10:47:23',
						'updated' => '2007-03-18 10:49:31'
					]
				],
				'Tag' => [
					[
						'id' => '1', 'tag' => 'tag1', 'created' => '2007-03-18 12:22:23',
						'updated' => '2007-03-18 12:24:31'
					],
					[
						'id' => '2', 'tag' => 'tag2', 'created' => '2007-03-18 12:24:23',
						'updated' => '2007-03-18 12:26:31'
					]
				]
			],
			[
				'Article' => [
					'id' => '3', 'user_id' => '1', 'title' => 'Third Article',
					'body' => 'Third Article Body', 'published' => 'Y',
					'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
				],
				'User' => [
					'id' => '1', 'user' => 'mariano',
					'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
					'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				],
				'Comment' => [],
				'Tag' => []
			]
		];
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertTrue(Set::check($data, '0.Comment.0.id'));
		$this->assertFalse(Set::check($data, '0.Comment.0.id.0'));
		$this->assertTrue(Set::check($data, '0.Article.user_id'));
		$this->assertFalse(Set::check($data, '0.Article.user_id.a'));
	}

	public function testMerge() {
		$result = Set::merge(['foo'], []);
		$this->assertIdentical($result, ['foo']);

		$result = Set::merge((array) 'foo', (array) 'bar');
		$this->assertIdentical($result, ['foo', 'bar']);

		$result = Set::merge((array) 'foo', ['user' => 'bob', 'no-bar']);
		$this->assertIdentical($result, ['foo', 'user' => 'bob', 'no-bar']);

		$a = ['foo', 'foo2'];
		$b = ['bar', 'bar2'];
		$this->assertIdentical(Set::merge($a, $b), ['foo', 'foo2', 'bar', 'bar2']);

		$a = ['foo' => 'bar', 'bar' => 'foo'];
		$b = ['foo' => 'no-bar', 'bar' => 'no-foo'];
		$this->assertIdentical(Set::merge($a, $b), ['foo' => 'no-bar', 'bar' => 'no-foo']);

		$a = ['users' => ['bob', 'jim']];
		$b = ['users' => ['lisa', 'tina']];
		$this->assertIdentical(
			Set::merge($a, $b), ['users' => ['bob', 'jim', 'lisa', 'tina']]
		);

		$a = ['users' => ['jim', 'bob']];
		$b = ['users' => 'none'];
		$this->assertIdentical(Set::merge($a, $b), ['users' => 'none']);

		$a = ['users' => ['lisa' => ['id' => 5, 'pw' => 'secret']], 'lithium'];
		$b = ['users' => ['lisa' => ['pw' => 'new-pass', 'age' => 23]], 'ice-cream'];
		$this->assertIdentical(
			Set::merge($a, $b),
			[
				'users' => ['lisa' => ['id' => 5, 'pw' => 'new-pass', 'age' => 23]],
				'lithium',
				'ice-cream'
			]
		);

		$c = [
			'users' => [
				'lisa' => ['pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog']
			],
			'chocolate'
		];
		$expected = [
			'users' => [
				'lisa' => [
					'id' => 5, 'pw' => 'you-will-never-guess', 'age' => 25, 'pet' => 'dog'
				]
			],
			'lithium',
			'ice-cream',
			'chocolate'
		];
		$this->assertIdentical($expected, Set::merge(Set::merge($a, $b), $c));

		$this->assertIdentical($expected, Set::merge(Set::merge($a, $b), Set::merge([], $c)));

		$result = Set::merge($a, Set::merge($b, $c));
		$this->assertIdentical($expected, $result);

		$a = ['Tree', 'CounterCache', 'Upload' => [
			'folder' => 'products', 'fields' => [
				'image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id'
			]
		]];
		$b =  [
			'Cacheable' => ['enabled' => false],
			'Limit', 'Bindable', 'Validator', 'Transactional'
		];

		$expected = ['Tree', 'CounterCache', 'Upload' => [
			'folder' => 'products', 'fields' => [
				'image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id'
			]],
			'Cacheable' => ['enabled' => false],
			'Limit',
			'Bindable',
			'Validator',
			'Transactional'
		];
		$this->assertIdentical(Set::merge($a, $b), $expected);

		$expected = ['Tree' => null, 'CounterCache' => null, 'Upload' => [
			'folder' => 'products', 'fields' => [
				'image_1_id', 'image_2_id', 'image_3_id', 'image_4_id', 'image_5_id'
			]],
			'Cacheable' => ['enabled' => false],
			'Limit' => null,
			'Bindable' => null,
			'Validator' => null,
			'Transactional' => null
		];
		$this->assertIdentical(Set::normalize(Set::merge($a, $b)), $expected);
	}

	public function testSort() {
		$a = [
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]],
			['Person' => ['name' => 'Tracy'], 'Friend' => [
				['name' => 'Lindsay']
			]]
		];
		$b = [
			['Person' => ['name' => 'Tracy'],'Friend' => [
				['name' => 'Lindsay']
			]],
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]]
		];
		$a = Set::sort($a, '/Friend/name', 'asc');
		$this->assertIdentical($a, $b);

		$b = [
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]],
			['Person' => ['name' => 'Tracy'], 'Friend' => [
				['name' => 'Lindsay']
			]]
		];
		$a = [
			['Person' => ['name' => 'Tracy'], 'Friend' => [
				['name' => 'Lindsay']
			]],
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]]
		];
		$a = Set::sort($a, '/Friend/name', 'desc');
		$this->assertIdentical($a, $b);

		$a = [
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]],
			['Person' => ['name' => 'Tracy'], 'Friend' => [
				['name' => 'Lindsay']
			]],
			['Person' => ['name' => 'Adam'], 'Friend' => [['name' => 'Bob']]]
		];
		$b = [
			['Person' => ['name' => 'Adam'],'Friend' => [['name' => 'Bob']]],
			['Person' => ['name' => 'Jeff'], 'Friend' => [['name' => 'Nate']]],
			['Person' => ['name' => 'Tracy'], 'Friend' => [
				['name' => 'Lindsay']
			]]
		];
		$a = Set::sort($a, '/Person/name', 'asc');
		$this->assertIdentical($a, $b);

		$a = [[7, 6, 4], [3, 4, 5], [3, 2, 1]];
		$b = [[3, 2, 1], [3, 4, 5], [7, 6, 4]];

		$a = Set::sort($a, '/', 'asc');
		$this->assertIdentical($a, $b);

		$a = [[7, 6, 4], [3, 4, 5], [3, 2, [1, 1, 1]]];
		$b = [[3, 2, [1, 1, 1]], [3, 4, 5], [7, 6, 4]];

		$a = Set::sort($a, '/.', 'asc');
		$this->assertIdentical($a, $b);

		$a = [
			['Person' => ['name' => 'Jeff']],
			['Shirt' => ['color' => 'black']]
		];
		$b = [['Person' => ['name' => 'Jeff']]];
		$a = Set::sort($a, '/Person/name', 'asc');
		$this->assertIdentical($a, $b);
	}

	public function testInsert() {
		$a = ['pages' => ['name' => 'page']];

		$result = Set::insert($a, 'files', ['name' => 'files']);
		$expected = ['pages' => ['name' => 'page'], 'files' => ['name' => 'files']];
		$this->assertIdentical($expected, $result);

		$a = ['pages' => ['name' => 'page']];
		$result = Set::insert($a, 'pages.name', []);
		$expected = ['pages' => ['name' => []]];
		$this->assertIdentical($expected, $result);

		$a = ['pages' => [['name' => 'main'], ['name' => 'about']]];

		$result = Set::insert($a, 'pages.1.vars', ['title' => 'page title']);
		$expected = [
			'pages' => [
				['name' => 'main'],
				['name' => 'about', 'vars' => ['title' => 'page title']]
			]
		];
		$this->assertIdentical($expected, $result);
	}

	public function testRemove() {
		$a = ['pages' => ['name' => 'page'], 'files' => ['name' => 'files']];

		$result = Set::remove($a, 'files', ['name' => 'files']);
		$expected = ['pages' => ['name' => 'page']];
		$this->assertIdentical($expected, $result);

		$a = [
			'pages' => [
				['name' => 'main'],
				['name' => 'about', 'vars' => ['title' => 'page title']]
			]
		];

		$result = Set::remove($a, 'pages.1.vars', ['title' => 'page title']);
		$expected = ['pages' => [['name' => 'main'], ['name' => 'about']]];
		$this->assertIdentical($expected, $result);

		$result = Set::remove($a, 'pages.2.vars', ['title' => 'page title']);
		$expected = $a;
		$this->assertIdentical($expected, $result);
	}

	public function testCheck() {
		$set = ['My Index 1' => [
			'First' => 'The first item'
		]];
		$result = Set::check($set, 'My Index 1.First');
		$this->assertTrue($result);

		$this->assertTrue(Set::check($set, 'My Index 1'));
		$this->assertNotEmpty(Set::check($set, []));

		$set = ['My Index 1' => ['First' => ['Second' => ['Third' => [
			'Fourth' => 'Heavy. Nesting.'
		]]]]];
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second'));
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second.Third'));
		$this->assertTrue(Set::check($set, 'My Index 1.First.Second.Third.Fourth'));
		$this->assertFalse(Set::check($set, 'My Index 1.First.Seconds.Third.Fourth'));
	}

	public function testInsertAndRemoveWithFunkyKeys() {
		$set = Set::insert([], 'Session Test', "test");
		$result = Set::extract($set, '/Session Test');
		$this->assertEqual($result, ['test']);

		$set = Set::remove($set, 'Session Test');
		$this->assertFalse(Set::check($set, 'Session Test'));

		$this->assertNotEmpty($set = Set::insert([], 'Session Test.Test Case', "test"));
		$this->assertTrue(Set::check($set, 'Session Test.Test Case'));
	}

	public function testDiff() {
		$a = [['name' => 'main'], ['name' => 'about']];
		$b = [['name' => 'main'], ['name' => 'about'], ['name' => 'contact']];

		$result = Set::diff($a, $b);
		$expected = [2 => ['name' => 'contact']];
		$this->assertIdentical($expected, $result);

		$result = Set::diff($a, []);
		$expected = $a;
		$this->assertIdentical($expected, $result);

		$result = Set::diff([], $b);
		$expected = $b;
		$this->assertIdentical($expected, $result);

		$b = [['name' => 'me'], ['name' => 'about']];

		$result = Set::diff($a, $b);
		$expected = [['name' => 'main']];
		$this->assertIdentical($expected, $result);
	}

	public function testContains() {
		$a = [
			0 => ['name' => 'main'],
			1 => ['name' => 'about']
		];
		$b = [
			0 => ['name' => 'main'],
			1 => ['name' => 'about'],
			2 => ['name' => 'contact'],
			'a' => 'b'
		];

		$this->assertTrue(Set::contains($a, $a));
		$this->assertFalse(Set::contains($a, $b));
		$this->assertTrue(Set::contains($b, $a));
	}

	public function testCombine() {
		$result = Set::combine([], '/User/id', '/User/Data');
		$this->assertEmpty($result);
		$result = Set::combine('', '/User/id', '/User/Data');
		$this->assertEmpty($result);

		$a = [
			['User' => ['id' => 2, 'group_id' => 1,
				'Data' => ['user' => 'mariano.iglesias','name' => 'Mariano Iglesias']
			]],
			['User' => ['id' => 14, 'group_id' => 2,
				'Data' => ['user' => 'jperras', 'name' => 'Joel Perras']
			]],
			['User' => ['id' => 25, 'group_id' => 1,
				'Data' => ['user' => 'gwoo','name' => 'The Gwoo']
			]]
		];
		$result = Set::combine($a, '/User/id');
		$expected = [2 => null, 14 => null, 25 => null];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/non-existant');
		$expected = [2 => null, 14 => null, 25 => null];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/.');
		$expected = [
			2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
			14 => ['user' => 'jperras', 'name' => 'Joel Perras'],
			25 => ['user' => 'gwoo', 'name' => 'The Gwoo']
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/name/.');
		$expected = [
			2 => 'Mariano Iglesias',
			14 => 'Joel Perras',
			25 => 'The Gwoo'
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/.', '/User/group_id');
		$expected = [
			1 => [
				2 => ['user' => 'mariano.iglesias', 'name' => 'Mariano Iglesias'],
				25 => ['user' => 'gwoo', 'name' => 'The Gwoo']
			],
			2 => [
				14 => ['user' => 'jperras', 'name' => 'Joel Perras']
			]
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, '/User/id', '/User/Data/name/.', '/User/group_id');
		$expected = [
			1 => [
				2 => 'Mariano Iglesias',
				25 => 'The Gwoo'
			],
			2 => [
				14 => 'Joel Perras'
			]
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			'/User/id',
			['{0}: {1}', '/User/Data/user', '/User/Data/name'],
			'/User/group_id'
		);
		$expected = [
			1 => [2 => 'mariano.iglesias: Mariano Iglesias', 25 => 'gwoo: The Gwoo'],
			2 => [14 => 'jperras: Joel Perras']
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			['{0}: {1}', '/User/Data/user', '/User/Data/name'],
			'/User/id'
		);
		$expected = [
			'mariano.iglesias: Mariano Iglesias' => 2,
			'jperras: Joel Perras' => 14,
			'gwoo: The Gwoo' => 25
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine(
			$a,
			['{1}: {0}', '/User/Data/user', '/User/Data/name'],
			'/User/id'
		);
		$expected = [
			'Mariano Iglesias: mariano.iglesias' => 2,
			'Joel Perras: jperras' => 14,
			'The Gwoo: gwoo' => 25
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, [
			'%1$s: %2$d', '/User/Data/user', '/User/id'], '/User/Data/name'
		);
		$expected = [
			'mariano.iglesias: 2' => 'Mariano Iglesias',
			'jperras: 14' => 'Joel Perras',
			'gwoo: 25' => 'The Gwoo'
		];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($a, [
			'%2$d: %1$s', '/User/Data/user', '/User/id'], '/User/Data/name'
		);
		$expected = [
			'2: mariano.iglesias' => 'Mariano Iglesias',
			'14: jperras' => 'Joel Perras',
			'25: gwoo' => 'The Gwoo'
		];
		$this->assertIdentical($expected, $result);

		$b = new stdClass();
		$b->users = [
			['User' => [
				'id' => 2, 'group_id' => 1, 'Data' => [
					'user' => 'mariano.iglesias','name' => 'Mariano Iglesias'
				]
			]],
			['User' => ['id' => 14, 'group_id' => 2, 'Data' => [
				'user' => 'jperras', 'name' => 'Joel Perras'
			]]],
			['User' => ['id' => 25, 'group_id' => 1, 'Data' => [
				'user' => 'gwoo','name' => 'The Gwoo'
			]]]
		];
		$result = Set::combine($b, '/users/User/id');
		$expected = [2 => null, 14 => null, 25 => null];
		$this->assertIdentical($expected, $result);

		$result = Set::combine($b, '/users/User/id', '/users/User/non-existant');
		$expected = [2 => null, 14 => null, 25 => null];
		$this->assertIdentical($expected, $result);
	}

	public function testAppend() {
		$array1 = ['ModelOne' => [
			'id' => 1001, 'field_one' => 'a1.m1.f1', 'field_two' => 'a1.m1.f2'
		]];
		$array2 = ['ModelTwo' => [
			'id' => 1002, 'field_one' => 'a2.m2.f1', 'field_two' => 'a2.m2.f2'
		]];

		$result = Set::append($array1, $array2);

		$this->assertIdentical($result, $array1 + $array2);

		$array3 = ['ModelOne' => [
			'id' => 1003, 'field_one' => 'a3.m1.f1',
			'field_two' => 'a3.m1.f2', 'field_three' => 'a3.m1.f3'
		]];
		$result = Set::append($array1, $array3);

		$expected = ['ModelOne' => [
			'id' => 1001, 'field_one' => 'a1.m1.f1',
			'field_two' => 'a1.m1.f2', 'field_three' => 'a3.m1.f3'
		]];
		$this->assertIdentical($expected, $result);

		$array1 = [
			['ModelOne' => [
				'id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2'
			]],
			['ModelTwo' => [
				'id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2'
			]]
		];
		$array2 = [
			['ModelOne' => [
				'id' => 1001, 'field_one' => 's2.0.m1.f1', 'field_two' => 's2.0.m1.f2'
			]],
			['ModelTwo' => [
				'id' => 1002, 'field_one' => 's2.1.m2.f2', 'field_two' => 's2.1.m2.f2'
			]]
		];

		$result = Set::append($array1, $array2);
		$this->assertIdentical($result, $array1);

		$array3 = [['ModelThree' => [
			'id' => 1003, 'field_one' => 's3.0.m3.f1', 'field_two' => 's3.0.m3.f2'
		]]];

		$result = Set::append($array1, $array3);
		$expected = [
			[
				'ModelOne' => [
					'id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2'
				],
				'ModelThree' => [
					'id' => 1003, 'field_one' => 's3.0.m3.f1', 'field_two' => 's3.0.m3.f2'
				]
			],
			['ModelTwo' => [
				'id' => 1002, 'field_one' => 's1.1.m2.f2', 'field_two' => 's1.1.m2.f2'
			]]
		];
		$this->assertIdentical($expected, $result);

		$result = Set::append($array1, []);
		$this->assertIdentical($result, $array1);

		$result = Set::append($array1, $array2);
		$this->assertIdentical($result, $array1 + $array2);

		$result = Set::append([], ['2']);
		$this->assertIdentical(['2'], $result);

		$array1 = [
			'ModelOne' => [
				'id' => 1001, 'field_one' => 's1.0.m1.f1', 'field_two' => 's1.0.m1.f2'
			],
			'ModelTwo' => [
				'id' => 1002, 'field_one' => 's1.0.m2.f1', 'field_two' => 's1.0.m2.f2'
			]
		];
		$array2 = [
			'ModelTwo' => [
				'field_three' => 's1.0.m2.f3'
			]
		];
		$array3 = [
			'ModelOne' => [
				'field_three' => 's1.0.m1.f3'
			]
		];

		$result = Set::append($array1, $array2, $array3);

		$expected = [
			'ModelOne' => [
				'id' => 1001,
				'field_one' => 's1.0.m1.f1',
				'field_two' => 's1.0.m1.f2',
				'field_three' => 's1.0.m1.f3'
			],
			'ModelTwo' => [
				'id' => 1002,
				'field_one' => 's1.0.m2.f1',
				'field_two' => 's1.0.m2.f2',
				'field_three' => 's1.0.m2.f3'
			]
		];
		$this->assertIdentical($expected, $result);
	}

	public function testStrictKeyCheck() {
		$set = ['a' => 'hi'];
		$this->assertFalse(Set::check($set, 'a.b'));
		$this->assertTrue(Set::check($set, 'a'));
	}

	public function testMixedKeyNormalization() {
		$input = ['"string"' => ['before' => '=>'], 1 => ['before' => '=>']];
		$result = Set::normalize($input);
		$this->assertEqual($input, $result);

		$input = 'Foo,Bar,Baz';
		$result = Set::normalize($input);
		$this->assertEqual(['Foo' => null, 'Bar' => null, 'Baz' => null], $result);

		$input = ['baz' => 'foo', 'bar'];
		$result = Set::normalize($input, false);
		$this->assertEqual(['baz' => 'foo', 'bar' => null], $result);
	}

	public function testSetSlice() {
		$data = ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3'];
		list($kept, $removed) = Set::slice($data, ['key3']);
		$this->assertEqual(['key3' => 'val3'], $removed);
		$this->assertEqual(['key1' => 'val1', 'key2' => 'val2'], $kept);

		$data = ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3'];
		list($kept, $removed) = Set::slice($data, ['key1', 'key3']);
		$this->assertEqual(['key1' => 'val1', 'key3' => 'val3'], $removed);
		$this->assertEqual(['key2' => 'val2'], $kept);

		$data = ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3'];
		list($kept, $removed) = Set::slice($data, 'key2');
		$this->assertEqual(['key2' => 'val2'], $removed);
		$this->assertEqual(['key1' => 'val1', 'key3' => 'val3'], $kept);

		$data = ['key1' => 'val1', 'key2' => 'val2', 'key3' => ['foo' => 'bar']];
		list($kept, $removed) = Set::slice($data, ['key1', 'key3']);
		$this->assertEqual(['key1' => 'val1', 'key3' => ['foo' => 'bar']], $removed);
		$this->assertEqual(['key2' => 'val2'], $kept);
	}
}

?>