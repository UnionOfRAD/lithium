<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use Exception;
use lithium\core\Libraries;
use lithium\tests\mocks\g11n\catalog\adapter\MockGettext;

class GettextTest extends \lithium\test\Unit {

	public $adapter;

	protected $_path;

	public function skip() {
		$path = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
	}

	public function setUp() {
		$this->_path = $path = Libraries::get(true, 'resources') . '/tmp/tests';
		is_dir("{$this->_path}/en/LC_MESSAGES") || mkdir("{$this->_path}/en/LC_MESSAGES", 0755, true);
		is_dir("{$this->_path}/de/LC_MESSAGES") || mkdir("{$this->_path}/de/LC_MESSAGES", 0755, true);
		$this->adapter = new MockGettext(compact('path'));
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testPathMustExist() {
		try {
			new MockGettext(['path' => $this->_path]);
			$result = true;
		} catch (Exception $e) {
			$result = false;
		}
		$this->assert($result);

		try {
			new MockGettext(['path' => "{$this->_path}/i_do_not_exist"]);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);
	}

	public function testReadNonExistent() {
		$result = $this->adapter->read('messageTemplate', 'root', null);
		$this->assertEmpty($result);
	}

	public function testReadPoSingleItem() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid "singular 1"
msgstr "translated 1"
EOD;
		file_put_contents($file, $data);

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1'],
				'flags' => [],
				'translated' => 'translated 1',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadPoSingleItemWithContext() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgctxt "A"
msgid "singular 1"
msgstr "translated 1"
EOD;
		file_put_contents($file, $data);

		$expected = [
			'singular 1|A' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1'],
				'flags' => [],
				'translated' => 'translated 1',
				'occurrences' => [],
				'comments' => [],
				'context' => 'A'
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadPoMultipleItems() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid "singular 1"
msgstr "translated 1"

msgid "singular 2"
msgstr "translated 2"

msgid "context"
msgstr "context (none specified)"

msgctxt "A"
msgid "context"
msgstr "context A"

msgctxt "B"
msgid "context"
msgstr "context B"
EOD;
		file_put_contents($file, $data);

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1'],
				'flags' => [],
				'translated' => 'translated 1',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'singular 2' => [
				'id' => 'singular 2',
				'ids' => ['singular' => 'singular 2'],
				'flags' => [],
				'translated' => 'translated 2',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'context' => [
				'id' => 'context',
				'ids' => ['singular' => 'context'],
				'flags' => [],
				'translated' => 'context (none specified)',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'context|A' => [
				'id' => 'context',
				'ids' => ['singular' => 'context'],
				'flags' => [],
				'translated' => 'context A',
				'occurrences' => [],
				'comments' => [],
				'context' => 'A'
			],
			'context|B' => [
				'id' => 'context',
				'ids' => ['singular' => 'context'],
				'flags' => [],
				'translated' => 'context B',
				'occurrences' => [],
				'comments' => [],
				'context' => 'B'
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadPoPlural() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid "singular 1"
msgid_plural "plural 1"
msgstr[0] "translated 1-0"
msgstr[1] "translated 1-1"
EOD;
		file_put_contents($file, $data);

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => [],
				'translated' => ['translated 1-0', 'translated 1-1'],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);

		$this->assertEqual($expected, $result);
	}

	public function testReadPoWithGnuHeader() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<'EOD'
# SOME DESCRIPTIVE TITLE.
# Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: PACKAGE VERSION\n"
"Report-Msgid-Bugs-To: \n"
"POT-Creation-Date: 2010-02-21 13:23+0100\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=CHARSET\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"

msgid "singular 1"
msgstr "translated 1"
EOD;
		file_put_contents($file, $data);

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1'],
				'flags' => [],
				'translated' => 'translated 1',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadPoIgnoresDummyAndEmptyItems() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
#, fuzzy
msgid ""
msgstr ""

msgid ""
msgstr ""

msgid " "
msgstr ""

msgid ""
msgstr "translated"
EOD;
		file_put_contents($file, $data);

		$result = $this->adapter->read('message', 'de', null);
		$this->assertEmpty($result);
	}

	public function testReadAndWritePoWithFlagsAndComments() {
		$this->adapter->mo = false;

		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$catalog = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1'],
				'flags' => ['fuzzy' => true, 'c-format' => true],
				'translated' => 'translated 1',
				'occurrences' => [
					['file' => 'test.php', 'line' => 1]
				],
				'comments' => [
					'extracted comment',
					'translator comment'
				],
				'context' => null
			]
		];
		$po = <<<EOD
#: test.php:1
#, fuzzy
#, c-format
#. extracted comment
#  translator comment
msgid "singular 1"
msgstr "translated 1"
EOD;
		file_put_contents($file, $po);
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($catalog, $result);

		unlink($file);

		$this->adapter->write('message', 'de', null, $catalog);
		$po = <<<EOD
#: test.php:1
#. extracted comment
#. translator comment
#, fuzzy
#, c-format
msgid "singular 1"
msgstr "translated 1"
EOD;
		$result = file_get_contents($file);
		$this->assertPattern('/' . preg_quote($po, '/') . '/', $result);
	}

	public function testReadPoMultiline() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid "An id"
msgstr ""
"This is a translation spanning "
"multiple lines."
EOD;
		file_put_contents($file, $data);

		$expected = [
			'An id' => [
				'id' => 'An id',
				'ids' => [
					'singular' => 'An id'
				],
				'flags' => [],
				'translated' => 'This is a translation spanning multiple lines.',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);

		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid ""
"This is an id spanning "
"multiple lines."
msgstr ""
"This is a translation spanning "
"multiple lines."
EOD;
		file_put_contents($file, $data);

		$expected = [
			'This is an id spanning multiple lines.' => [
				'id' => 'This is an id spanning multiple lines.',
				'ids' => [
					'singular' => 'This is an id spanning multiple lines.'
				],
				'flags' => [],
				'translated' => 'This is a translation spanning multiple lines.',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);

		$data = <<<EOD
msgid ""
"This is an id spanning "
"multiple lines."
msgid_plural ""
"This is a plural id spanning "
"multiple lines."
msgstr[0] ""
"This is a translation spanning "
"multiple lines."
msgstr[1] ""
"This is a plural translation spanning "
"multiple lines."
EOD;
		file_put_contents($file, $data);

		$expected = [
			'This is an id spanning multiple lines.' => [
				'id' => 'This is an id spanning multiple lines.',
				'ids' => [
					'singular' => 'This is an id spanning multiple lines.',
					'plural' => 'This is a plural id spanning multiple lines.'
				],
				'flags' => [],
				'translated' => [
					'This is a translation spanning multiple lines.',
					'This is a plural translation spanning multiple lines.'
				],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadPoLongIdsAndTranslations() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$dummy = str_repeat('X', 10000);
		$data = <<<EOD
msgid "{$dummy}"
msgstr "translated 1"
EOD;
		file_put_contents($file, $data);

		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertTrue(isset($result[$dummy]));

		$data = <<<EOD
msgid "singular 1"
msgstr "{$dummy}"
EOD;
		file_put_contents($file, $data);

		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($result['singular 1']['translated'], $dummy);
	}

	public function testReadMoLittleEndian() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.mo";
		$data = <<<EOD
3hIElQAAAAADAAAAHAAAADQAAAAFAAAATAAAAAAAAABgAAAAEwAAAGEAAAAKAAAAdQAAADUBAACAAAAAHQAAALYBAAAMAAAA1AEA
AAEAAAACAAAAAAAAAAMAAAAAAAAAAHNpbmd1bGFyIDEAcGx1cmFsIDEAc2luZ3VsYXIgMgBQcm9qZWN0LUlkLVZlcnNpb246IApQ
T1QtQ3JlYXRpb24tRGF0ZTogClBPLVJldmlzaW9uLURhdGU6IDIwMTAtMDItMjAgMTc6MTQrMDEwMApMYXN0LVRyYW5zbGF0b3I6
IERhdmlkIFBlcnNzb24gPGRhdmlkcGVyc3NvbkBnbXguZGU+Ckxhbmd1YWdlLVRlYW06IApNSU1FLVZlcnNpb246IDEuMApDb250
ZW50LVR5cGU6IHRleHQvcGxhaW47IGNoYXJzZXQ9VVRGLTgKQ29udGVudC1UcmFuc2Zlci1FbmNvZGluZzogOGJpdApYLVBvZWRp
dC1MYW5ndWFnZTogR2VybWFuClBsdXJhbC1Gb3JtczogbnBsdXJhbHM9MjsgcGx1cmFsPShuICE9IDEpOwoAdHJhbnNsYXRlZCAx
LTAAdHJhbnNsYXRlZCAxLTEAdHJhbnNsYXRlZCAyAA==
EOD;

		file_put_contents($file, base64_decode($data));

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => [],
				'translated' => ['translated 1-0', 'translated 1-1'],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'singular 2' => [
				'id' => 'singular 2',
				'ids' => ['singular' => 'singular 2', 'plural' => null],
				'flags' => [],
				'translated' => 'translated 2',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testReadMoMalformed() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.mo";

		touch($file);

		try {
			$this->adapter->read('message', 'de', null);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);

		file_put_contents($file, '|---10---||---10---|');

		try {
			$this->adapter->read('message', 'de', null);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);

		file_put_contents($file, '|---10---||---10---||---10---|');

		try {
			$this->adapter->read('message', 'de', null);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);
	}

	public function testReadMoWithContext() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.mo";
		$data = <<<EOD
3hIElQAAAAAGAAAAHAAAAEwAAAAAAAAAfAAAAAAAAAB8AAAACQAAAH0AAAAJAAAAhwAAAAcAAACRAAAACgAAAJkAAAAKAAAApAAA
AAAAAACvAAAACQAAALAAAAAJAAAAugAAABgAAADEAAAADAAAAN0AAAAMAAAA6gAAAABBBGNvbnRleHQAQgRjb250ZXh0AGNvbnRl
eHQAc2luZ3VsYXIgMQBzaW5ndWxhciAyAABjb250ZXh0IEEAY29udGV4dCBCAGNvbnRleHQgKG5vbmUgc3BlY2lmaWVkKQB0cmFu
c2xhdGVkIDEAdHJhbnNsYXRlZCAyAA==
EOD;

		file_put_contents($file, base64_decode($data));

		$expected = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => null],
				'flags' => [],
				'translated' => 'translated 1',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'singular 2' => [
				'id' => 'singular 2',
				'ids' => ['singular' => 'singular 2', 'plural' => null],
				'flags' => [],
				'translated' => 'translated 2',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'context' => [
				'id' => 'context',
				'ids' => ['singular' => 'context', 'plural' => null],
				'flags' => [],
				'translated' => 'context (none specified)',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'context|A' => [
				'id' => 'context',
				'ids' => ['singular' => 'context', 'plural' => null],
				'flags' => [],
				'translated' => 'context A',
				'occurrences' => [],
				'comments' => [],
				'context' => 'A'
			],
			'context|B' => [
				'id' => 'context',
				'ids' => ['singular' => 'context', 'plural' => null],
				'flags' => [],
				'translated' => 'context B',
				'occurrences' => [],
				'comments' => [],
				'context' => 'B'
			]
		];
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($expected, $result);
	}

	public function testWriteMessageCompilesPo() {
		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => null],
				'flags' => [],
				'translated' => ['translated 1'],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$this->adapter->write('message', 'de', null, $data);
		$this->assertFileExists("{$this->_path}/de/LC_MESSAGES/default.po");
	}

	public function testWriteMessageTemplateCompilesPot() {
		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => null],
				'flags' => [],
				'translated' => [],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$this->adapter->write('messageTemplate', 'root', null, $data);
		$this->assertFileExists("{$this->_path}/message_default.pot");
	}

	public function testWriteReadPo() {
		$this->adapter->mo = false;

		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => ['fuzzy' => true],
				'translated' => ['translated singular 1', 'translated plural 1'],
				'occurrences' => [
					['file' => 'test.php', 'line' => 1]
				],
				'comments' => [
					'comment 1'
				],
				'context' => null
			],
			'singular 1|A' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => ['fuzzy' => true],
				'translated' => ['translated singular 1A', 'translated plural 1A'],
				'occurrences' => [
					['file' => 'test.php', 'line' => 2]
				],
				'comments' => [
					'comment 1a'
				],
				'context' => 'A'
			],
			'singular 1|B' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => ['fuzzy' => true],
				'translated' => ['translated singular 1B', 'translated plural 1B'],
				'occurrences' => [
					['file' => 'test.php', 'line' => 2]
				],
				'comments' => [
					'comment 1b'
				],
				'context' => 'B'
			]
		];

		$this->adapter->write('message', 'de', null, $data);
		$result = $this->adapter->read('message', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($data, $result);

		$this->adapter->write('messageTemplate', 'root', null, $data);
		$result = $this->adapter->read('messageTemplate', 'root', null);
		unset($result['pluralRule']);
		$this->assertEqual($data, $result);
	}

	public function testWrittenPoHasGnuHeader() {
		$this->adapter->mo = false;

		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => [],
				'translated' => ['translated 1-0', 'translated 1-1'],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			],
			'singular 2' => [
				'id' => 'singular 2',
				'ids' => ['singular' => 'singular 2', 'plural' => null],
				'flags' => [],
				'translated' => ['translated 2'],
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$this->adapter->write('message', 'de', null, $data);
		$result = file_get_contents("{$this->_path}/de/LC_MESSAGES/default.po");

		$expected = 'msgstr ""\n"Project-Id';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Project-Id-Version: PACKAGE VERSION\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"POT-Creation-Date: YEAR-MO-DA HO:MI\+ZONE\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"PO-Revision-Date: YEAR-MO-DA HO:MI\+ZONE\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Language-Team: LANGUAGE <EMAIL@ADDRESS>\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"MIME-Version: 1.0\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Content-Type: text/plain; charset=UTF-8\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Content-Transfer-Encoding: 8bit\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);

		$expected = '"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\\n"\n';
		$this->assertPattern("%{$expected}%", $result);
	}

	public function testReadAndWritePoValidation() {
		$this->adapter->mo = false;
		mkdir("{$this->_path}/de/LC_VALIDATION", 0755, true);

		$file = "{$this->_path}/de/LC_VALIDATION/default.po";
		$catalog = [
			'phone' => [
				'id' => 'phone',
				'ids' => ['singular' => 'phone'],
				'flags' => [],
				'translated' => '/[0-9].*/i',
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$po = <<<EOD
msgid "phone"
msgstr "/[0-9].*/i"
EOD;

		file_put_contents($file, $po);
		$result = $this->adapter->read('validation', 'de', null);
		unset($result['pluralRule']);
		$this->assertEqual($catalog, $result);

		unlink($file);

		$this->adapter->write('validation', 'de', null, $catalog);
		$result = file_get_contents($file);
		$this->assertPattern('/' . preg_quote($po, '/') . '/', $result);
	}

	public function testWrittenPoHasShortFilePaths() {
		$this->adapter->mo = false;

		$data = [
			'singular 1' => [
				'id' => 'singular 1',
				'ids' => ['singular' => 'singular 1', 'plural' => 'plural 1'],
				'flags' => [],
				'translated' => ['translated 1-0', 'translated 1-1'],
				'occurrences' => [
					['file' => Libraries::get(true, 'path') . '/testa.php', 'line' => 22],
					['file' => '/testb.php', 'line' => 23]
				],
				'comments' => [],
				'context' => null
			]
		];
		$this->adapter->write('messageTemplate', 'root', null, $data);
		$result = file_get_contents("{$this->_path}/message_default.pot");

		$expected = '\#: /testa\.php:22';
		$this->assertPattern("={$expected}=", $result);

		$expected = '\#: /testb\.php:23';
		$this->assertPattern("={$expected}=", $result);
	}

	public function testEscapeUnescape() {
		$this->adapter->mo = false;
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";

		$chars = [
			"\0" => '\000',
			"\1" => '\001',
			"\2" => '\002',
			"\3" => '\003',
			"\4" => '\004',
			"\5" => '\005',
			"\6" => '\006',
			"\7" => '\a',
			"\10" => '\b',
			"\11" => '\t',
			"\12" => '\n',
			"\13" => '\v',
			"\14" => '\f',
			"\15" => '\r',
			"\16" => '\016',
			"\17" => '\017',
			"\20" => '\020',
			"\21" => '\021',
			"\22" => '\022',
			"\23" => '\023',
			"\24" => '\024',
			"\25" => '\025',
			"\26" => '\026',
			"\30" => '\030',
			"\31" => '\031',
			"\32" => '\032',
			"\33" => '\033',
			"\34" => '\034',
			"\35" => '\035',
			"\36" => '\036',
			"\37" => '\037',
			'"' => '\"',
			'\\' => '\\\\'
		];

		foreach ($chars as $unescaped => $escaped) {
			$ord = decoct(ord($unescaped));

			$catalog = [
				"this is the{$unescaped}message" => [
					'id' => "this is the{$unescaped}message",
					'ids' => ['singular' => "this is the{$unescaped}message"],
					'flags' => [],
					'translated' => "this is the{$unescaped}translation",
					'occurrences' => [],
					'comments' => [],
					'context' => null
				]
			];
			$po = <<<EOD
msgid "this is the{$escaped}message"
msgstr "this is the{$escaped}translation"
EOD;
			file_put_contents($file, $po);
			$result = $this->adapter->read('message', 'de', null);
			unset($result['pluralRule']);

			$message  = "`{$unescaped}` (ASCII octal {$ord}) was not escaped to `{$escaped}`";
			$message .= "\n{:message}";
			$this->assertEqual($catalog, $result, $message);

			unlink($file);

			$this->adapter->write('message', 'de', null, $catalog);
			$result = file_get_contents($file);
			$message  = "`{$escaped}` was not unescaped to `{$unescaped}` (ASCII octal {$ord})";
			$message .= "\n{:message}";
			$this->assertPattern('/' . preg_quote($po, '/') . '/', $result, $message);

			unlink($file);
		}
	}

	public function testCrLfToLfOnWrite() {
		$this->adapter->mo = false;
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";

		$catalog = [
			"this is the\r\nmessage" => [
				'id' => "this is the\r\nmessage",
				'ids' => ['singular' => "this is the\r\nmessage"],
				'flags' => [],
				'translated' => "this is the\r\ntranslation",
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$po = <<<EOD
msgid "this is the\\nmessage"
msgstr "this is the\\ntranslation"
EOD;

		$this->adapter->write('message', 'de', null, $catalog);
		$result = file_get_contents($file);
		$this->assertPattern('/' . preg_quote($po, '/') . '/', $result);
	}

	public function testFixEscapedSingleQuoteOnWrite() {
		$this->adapter->mo = false;
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";

		$catalog = [
			"this is the\\'message" => [
				'id' => "this is the\\'message",
				'ids' => ['singular' => "this is the\\'message"],
				'flags' => [],
				'translated' => "this is the\\'translation",
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$po = <<<EOD
msgid "this is the'message"
msgstr "this is the'translation"
EOD;

		$this->adapter->write('message', 'de', null, $catalog);
		$result = file_get_contents($file);
		$this->assertPattern('/' . preg_quote($po, '/') . '/', $result);
	}

	public function testFixDoubleEscapedOnWrite() {
		$this->adapter->mo = false;
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";

		$catalog = [
			"this is the\\\\message" => [
				'id' => "this is the\\\\message",
				'ids' => ['singular' => "this is the\\\\message"],
				'flags' => [],
				'translated' => "this is the\\\\translation",
				'occurrences' => [],
				'comments' => [],
				'context' => null
			]
		];
		$po = <<<EOD
msgid "this is the\\\\message"
msgstr "this is the\\\\translation"
EOD;

		$this->adapter->write('message', 'de', null, $catalog);
		$result = file_get_contents($file);
		$this->assertPattern('/' . preg_quote($po, '/') . '/', $result);
	}

	public function testPluralRule() {
		$file = "{$this->_path}/de/LC_MESSAGES/default.po";
		$data = <<<EOD
msgid "singular 1"
msgid_plural "plural 1"
msgstr[0] "translated 1-0"
msgstr[1] "translated 1-1"
EOD;
		file_put_contents($file, $data);

		$result = $this->adapter->read('message', 'de', null);
		$this->assertInternalType('callable', $result['pluralRule']['translated']);
		$this->assertEqual(true, $result['pluralRule']['translated'](3));
		$this->assertEqual(0, $result['pluralRule']['translated'](1));
	}
}

?>