<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n;

use \lithium\g11n\Locale;

class LocaleTest extends \lithium\test\Unit {

	/**
	 * Tests composing of a locale from tags.
	 *
	 * @return void
	 */
	public function testCompose() {
		$data = array(
			'language' => 'en',
		);
		$expected = 'en';

		$result = Locale::compose($data);
		$this->assertEqual($expected, $result);

		$data = array(
			'language' => 'en',
			'territory' => 'US'
		);
		$expected = 'en_US';

		$result = Locale::compose($data);
		$this->assertEqual($expected, $result);

		$data = array(
			'language' => 'EN',
			'territory' => 'US'
		);
		$expected = 'EN_US';

		$result = Locale::compose($data);
		$this->assertEqual($expected, $result);

		$data = array(
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		);
		$expected = 'zh_Hans_HK_REVISED';

		$result = Locale::compose($data);
		$this->assertEqual($expected, $result);

		$data = array(
			'territory' => 'HK',
			'language' => 'zh',
			'script' => 'Hans'
		);
		$expected = 'zh_Hans_HK';

		$result = Locale::compose($data);
		$this->assertEqual($expected, $result);

		$result = Locale::compose(array());
		$this->assertNull($result);
	}

	/**
	 * Tests parsing of locales formatted strictly according to
	 * the definition of the unicode locale identifier.
	 *
	 * @return void
	 */
	public function testDecomposeStrict() {
		$expected =  array(
			'language' => 'en'
		);
		$this->assertEqual($expected, Locale::decompose('en'));

		$expected =  array(
			'language' => 'en',
			'territory' => 'US'
		);
		$this->assertEqual($expected, Locale::decompose('en_US'));

		$expected =  array(
			'language' => 'en',
			'territory' => 'US',
			'variant' => 'POSIX'
		);
		$this->assertEqual($expected, Locale::decompose('en_US_POSIX'));

		$expected =  array(
			'language' => 'kpe',
			'territory' => 'GN'
		);
		$this->assertEqual($expected, Locale::decompose('kpe_GN'));

		$expected =  array(
			'language' => 'zh',
			'script' => 'Hans'
		);
		$this->assertEqual($expected, Locale::decompose('zh_Hans'));

		$expected =  array(
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK'
		);
		$this->assertEqual($expected, Locale::decompose('zh_Hans_HK'));

		$expected =  array(
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		);
		$this->assertEqual($expected, Locale::decompose('zh_Hans_HK_REVISED'));
	}

	/**
	 * Tests parsing of locales formatted loosely according to
	 * the definition of the unicode locale identifier.
	 *
	 * @return void
	 */
	public function testDecomposeLoose() {
		$expected =  array(
			'language' => 'en',
			'territory' => 'US'
		);
		$this->assertEqual($expected, Locale::decompose('en-US'));

		$expected =  array(
			'language' => 'en',
			'territory' => 'US',
			'variant' => 'posiX'
		);
		$this->assertEqual($expected, Locale::decompose('en_US-posiX'));

		$expected =  array(
			'language' => 'kpe',
			'territory' => 'gn'
		);
		$this->assertEqual($expected, Locale::decompose('kpe_gn'));

		$expected =  array(
			'language' => 'ZH',
			'script' => 'HANS',
			'territory' => 'HK',
			'variant' => 'REVISED'
		);
		$this->assertEqual($expected, Locale::decompose('ZH-HANS-HK_REVISED'));
	}

	/**
	 * Tests failing of parsing invalid locales.
	 *
	 * @return void
	 */
	public function testDecomposeFail()  {
		$this->expectException();
		try {
			Locale::decompose('deee_DE');
			$this->assert(false);
		} catch (Exception $e) {
			$this->assert(true);
		}

		$this->expectException();
		try {
			Locale::decompose('ZH-HANS-HK_REVISED_INVALID');
			$this->assert(false);
		} catch (Exception $e) {
			$this->assert(true);
		}
	}

	/**
	 * Tests parsing of locales using shortcut methods.
	 *
	 * @return void
	 */
	public function testDecomposeUsingShortcutMethods() {
		$this->assertEqual('zh', Locale::language('zh_Hans_HK_REVISED'));
		$this->assertEqual('Hans', Locale::script('zh_Hans_HK_REVISED'));
		$this->assertEqual('HK', Locale::territory('zh_Hans_HK_REVISED'));
		$this->assertEqual('REVISED', Locale::variant('zh_Hans_HK_REVISED'));

		$this->assertNull(Locale::script('zh_HK'));
		$this->assertNull(Locale::territory('zh'));
		$this->assertNull(Locale::variant('zh'));

		$this->expectException();
		try {
			Locale::notAValidTag('zh_Hans_HK_REVISED');
			$this->assert(false);
		} catch (Exception $e) {
			$this->assert(true);
		}
	}

	/**
	 * Tests if the ouput of `compose()` can be used as the input for `decompose()`
	 * and vice versa.
	 *
	 * @return void
	 */
	public function testComposeDecomposeCompose() {
		$data = array(
			'language' => 'en',
		);
		$expected = 'en';

		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual($expected, $result);

		$data = array(
			'language' => 'en',
			'territory' => 'US'
		);
		$expected = 'en_US';

		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual($expected, $result);

		$data = array(
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		);
		$expected = 'zh_Hans_HK_REVISED';

		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests cascading of locales.
	 *
	 * @return void
	 */
	public function testCascade() {
		$expected = array('root');
		$this->assertEqual($expected, Locale::cascade('root'));

		$expected = array('en', 'root');
		$this->assertEqual($expected, Locale::cascade('en'));

		$expected = array('en_US', 'en', 'root');
		$this->assertEqual($expected, Locale::cascade('en_US'));

		$expected = array('zh_HK_REVISED', 'zh_HK', 'zh', 'root');
		$this->assertEqual($expected, Locale::cascade('zh_HK_REVISED'));

		$expected = array('zh_Hans_HK', 'zh_Hans', 'zh', 'root');
		$this->assertEqual($expected, Locale::cascade('zh_Hans_HK'));

		$expected = array('zh_Hans_HK_REVISED', 'zh_Hans_HK', 'zh_Hans', 'zh', 'root');
		$this->assertEqual($expected, Locale::cascade('zh_Hans_HK_REVISED'));
	}

	/**
	 * Tests formatting of locale.
	 *
	 * @return void
	 */
	public function testCanonicalize() {
		$this->assertEqual('en_US', Locale::canonicalize('en-US'));
		$this->assertEqual('en_US_POSIX', Locale::canonicalize('en_US-posiX'));
		$this->assertEqual('kpe_GN', Locale::canonicalize('kpe_gn'));
		$this->assertEqual('zh_Hans_HK_REVISED', Locale::canonicalize('ZH-HANS-HK_REVISED'));
	}
}

?>