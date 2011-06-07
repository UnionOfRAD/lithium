<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n;

use lithium\g11n\Locale;
use lithium\action\Request as ActionRequest;
use lithium\console\Request as ConsoleRequest;

class LocaleTest extends \lithium\test\Unit {

	/**
	 * Tests composing of a locale from tags.
	 *
	 * @return void
	 */
	public function testCompose() {
		$data = array(
			'language' => 'en'
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
			'language' => 'en'
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

	public function testLookup() {
		$expected = 'zh_Hans_HK';
		$result = Locale::lookup(
			array('zh_Hans_REVISED', 'zh_Hans_HK', 'zh', 'zh_Hans'),
			'zh_Hans_HK_REVISED'
		);
		$this->assertEqual($expected, $result);

		$expected = 'zh_Hans_HK';
		$result = Locale::lookup(
			array('zh', 'zh_Hans_REVISED', 'zh_Hans_HK', 'zh_Hans'),
			'zh_Hans_HK_REVISED'
		);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromActionRequest() {
		$request = new ActionRequest(array(
			'env' => array('HTTP_ACCEPT_LANGUAGE' => 'da, en-gb;q=0.8, en;q=0.7')
		));
		$expected = 'da';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ActionRequest(array(
			'env' => array('HTTP_ACCEPT_LANGUAGE' => 'en-gb;q=0.8, da, en;q=0.7')
		));
		$expected = 'da';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ActionRequest(array(
			'env' => array('HTTP_ACCEPT_LANGUAGE' => 'en-gb;q=0.8, en;q=0.7')
		));
		$expected = 'en_GB';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ActionRequest(array(
			'env' => array('HTTP_ACCEPT_LANGUAGE' => 'da')
		));
		$expected = 'da';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromConsoleRequestLanguage() {
		$request = new ConsoleRequest(array(
			'env' => array('LANGUAGE' => 'sv_SE:nn_NO:de_DE')
		));
		$expected = 'sv_SE';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromConsoleRequestLcAll() {
		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => 'es_ES@euro')
		));
		$expected = 'es_ES';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => 'en_US.UTF-8')
		));
		$expected = 'en_US';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => 'en_US')
		));
		$expected = 'en_US';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromConsoleRequestLang() {
		$request = new ConsoleRequest(array(
			'env' => array('LANG' => 'es_ES@euro')
		));
		$expected = 'es_ES';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromConsoleRequestPrecedence() {
		$request = new ConsoleRequest(array(
			'env' => array(
				'LANGUAGE' => 'da_DK:ja_JP',
				'LC_ALL' => 'fr_CA',
				'LANG' => 'de_DE'
		)));
		$expected = 'da_DK';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);

		$request = new ConsoleRequest(array(
			'env' => array(
				'LC_ALL' => 'fr_CA',
				'LANG' => 'de_DE'
		)));
		$expected = 'fr_CA';
		$result = Locale::preferred($request);
		$this->assertEqual($expected, $result);
	}

	public function testPreferredFromConsoleRequestEmptyLocales() {
		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => 'C', 'LANG' => null, 'LANGUAGE' => null)
		));
		$result = Locale::preferred($request);
		$this->assertNull($result);

		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => 'POSIX', 'LANG' => null, 'LANGUAGE' => null)
		));
		$result = Locale::preferred($request);
		$this->assertNull($result);

		$request = new ConsoleRequest(array(
			'env' => array('LC_ALL' => '', 'LANG' => null, 'LANGUAGE' => null)
		));
		$result = Locale::preferred($request);
		$this->assertNull($result);
	}

	public function testPreferredAvailableNegotiation() {
		$expected = 'nl_BE';
		$result = Locale::preferred(
			array('nl_NL', 'nl_BE', 'nl', 'en_US', 'en'),
			array('en', 'en_US', 'nl_BE')
		);
		$this->assertEqual($expected, $result);

		$expected = 'da';
		$result = Locale::preferred(
			array('da', 'en_GB', 'en'),
			array('da', 'en_GB', 'en')
		);
		$this->assertEqual($expected, $result);

		$expected = 'da';
		$result = Locale::preferred(
			array('da', 'en_GB', 'en'),
			array('en', 'en_GB', 'da')
		);
		$this->assertEqual($expected, $result);

		$expected = 'en_GB';
		$result = Locale::preferred(
			array('da', 'en_GB', 'en'),
			array('en_GB', 'en')
		);
		$this->assertEqual($expected, $result);

		$expected = 'da';
		$result = Locale::preferred(
			array('da_DK', 'en_GB', 'en'),
			array('da', 'en_GB', 'en')
		);
		$this->assertEqual($expected, $result);

		$expected = 'zh';
		$result = Locale::preferred(
			array('zh_Hans_REVISED', 'zh_Hans_HK', 'zh', 'en'),
			array('zh_Hans_HK_REVISED', 'zh_Hans_HK', 'zh', 'en')
		);
		$this->assertEqual($expected, $result);
	}
}

?>