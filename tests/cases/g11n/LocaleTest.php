<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n;

use lithium\g11n\Locale;
use lithium\action\Request as ActionRequest;
use lithium\console\Request as ConsoleRequest;

class LocaleTest extends \lithium\test\Unit {

	/**
	 * Tests composing of a locale from tags.
	 */
	public function testCompose() {
		$result = Locale::compose(['language' => 'en']);
		$this->assertEqual('en', $result);

		$result = Locale::compose(['language' => 'en', 'territory' => 'US']);
		$this->assertEqual('en_US', $result);

		$result = Locale::compose(['language' => 'EN', 'territory' => 'US']);
		$this->assertEqual('EN_US', $result);

		$result = Locale::compose([
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		]);
		$this->assertEqual('zh_Hans_HK_REVISED', $result);

		$result = Locale::compose([
			'territory' => 'HK',
			'language' => 'zh',
			'script' => 'Hans'
		]);
		$this->assertEqual('zh_Hans_HK', $result);

		$this->assertNull(Locale::compose([]));
	}

	/**
	 * Tests parsing of locales formatted strictly according to
	 * the definition of the unicode locale identifier.
	 */
	public function testDecomposeStrict() {
		$expected =  ['language' => 'en'];
		$this->assertEqual($expected, Locale::decompose('en'));

		$expected =  ['language' => 'en', 'territory' => 'US'];
		$this->assertEqual($expected, Locale::decompose('en_US'));

		$expected =  [
			'language' => 'en',
			'territory' => 'US',
			'variant' => 'POSIX'
		];
		$this->assertEqual($expected, Locale::decompose('en_US_POSIX'));

		$expected =  ['language' => 'kpe', 'territory' => 'GN'];
		$this->assertEqual($expected, Locale::decompose('kpe_GN'));

		$expected =  ['language' => 'zh', 'script' => 'Hans'];
		$this->assertEqual($expected, Locale::decompose('zh_Hans'));

		$expected =  [
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK'
		];
		$this->assertEqual($expected, Locale::decompose('zh_Hans_HK'));

		$expected =  [
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		];
		$this->assertEqual($expected, Locale::decompose('zh_Hans_HK_REVISED'));
	}

	/**
	 * Tests parsing of locales formatted loosely according to
	 * the definition of the unicode locale identifier.
	 */
	public function testDecomposeLoose() {
		$expected =  ['language' => 'en', 'territory' => 'US'];
		$this->assertEqual($expected, Locale::decompose('en-US'));

		$expected =  [
			'language' => 'en',
			'territory' => 'US',
			'variant' => 'posiX'
		];
		$this->assertEqual($expected, Locale::decompose('en_US-posiX'));

		$expected =  ['language' => 'kpe', 'territory' => 'gn'];
		$this->assertEqual($expected, Locale::decompose('kpe_gn'));

		$expected =  [
			'language' => 'ZH',
			'script' => 'HANS',
			'territory' => 'HK',
			'variant' => 'REVISED'
		];
		$this->assertEqual($expected, Locale::decompose('ZH-HANS-HK_REVISED'));
	}

	/**
	 * Tests failing of parsing invalid locales.
	 */
	public function testDecomposeFail()  {
		$this->assertException('/.*/', function() {
			Locale::decompose('deee_DE');
		});

		$this->assertException('/.*/', function() {
			Locale::decompose('ZH-HANS-HK_REVISED_INVALID');
		});
	}

	/**
	 * Tests parsing of locales using shortcut methods.
	 */
	public function testDecomposeUsingShortcutMethods() {
		$this->assertEqual('zh', Locale::language('zh_Hans_HK_REVISED'));
		$this->assertEqual('Hans', Locale::script('zh_Hans_HK_REVISED'));
		$this->assertEqual('HK', Locale::territory('zh_Hans_HK_REVISED'));
		$this->assertEqual('REVISED', Locale::variant('zh_Hans_HK_REVISED'));

		$this->assertNull(Locale::script('zh_HK'));
		$this->assertNull(Locale::territory('zh'));
		$this->assertNull(Locale::variant('zh'));

		$this->assertException('/.*/', function() {
			Locale::notAValidTag('zh_Hans_HK_REVISED');
		});
	}

	/**
	 * Tests if the ouput of `compose()` can be used as the input for `decompose()`
	 * and vice versa.
	 */
	public function testComposeDecomposeCompose() {
		$data = ['language' => 'en'];
		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual('en', $result);

		$data = ['language' => 'en', 'territory' => 'US'];
		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual('en_US', $result);

		$data = [
			'language' => 'zh',
			'script' => 'Hans',
			'territory' => 'HK',
			'variant' => 'REVISED'
		];
		$result = Locale::compose(Locale::decompose(Locale::compose($data)));
		$this->assertEqual('zh_Hans_HK_REVISED', $result);
	}

	/**
	 * Tests cascading of locales.
	 */
	public function testCascade() {
		$this->assertEqual(['root'], Locale::cascade('root'));
		$this->assertEqual(['en', 'root'], Locale::cascade('en'));
		$this->assertEqual(['en_US', 'en', 'root'], Locale::cascade('en_US'));

		$expected = ['zh_HK_REVISED', 'zh_HK', 'zh', 'root'];
		$this->assertEqual($expected, Locale::cascade('zh_HK_REVISED'));

		$expected = ['zh_Hans_HK', 'zh_Hans', 'zh', 'root'];
		$this->assertEqual($expected, Locale::cascade('zh_Hans_HK'));

		$expected = ['zh_Hans_HK_REVISED', 'zh_Hans_HK', 'zh_Hans', 'zh', 'root'];
		$this->assertEqual($expected, Locale::cascade('zh_Hans_HK_REVISED'));
	}

	/**
	 * Tests formatting of locale.
	 */
	public function testCanonicalize() {
		$this->assertEqual('en_US', Locale::canonicalize('en-US'));
		$this->assertEqual('en_US_POSIX', Locale::canonicalize('en_US-posiX'));
		$this->assertEqual('kpe_GN', Locale::canonicalize('kpe_gn'));
		$this->assertEqual('zh_Hans_HK_REVISED', Locale::canonicalize('ZH-HANS-HK_REVISED'));
	}

	public function testLookup() {
		$result = Locale::lookup(
			['zh_Hans_REVISED', 'zh_Hans_HK', 'zh', 'zh_Hans'], 'zh_Hans_HK_REVISED'
		);
		$this->assertEqual('zh_Hans_HK', $result);

		$result = Locale::lookup(
			['zh', 'zh_Hans_REVISED', 'zh_Hans_HK', 'zh_Hans'],
			'zh_Hans_HK_REVISED'
		);
		$this->assertEqual('zh_Hans_HK', $result);

		$result = Locale::lookup(
			['en', 'en_UK', 'en_US', 'es', 'es_AR'],
			'en'
		);
		$this->assertEqual('en', $result);

		$result = Locale::lookup(
			['en', 'en_UK', 'en_US', 'es', 'es_AR'],
			'en_UK'
		);
		$this->assertEqual('en_UK', $result);

		$result = Locale::lookup(
			['en', 'en_UK', 'en_US', 'es', 'es_AR'],
			'es_ES'
		);
		$this->assertEqual('es', $result);

		$result = Locale::lookup(
			['en', 'en_UK', 'en_US', 'es_AR'],
			'es_ES'
		);
		$this->assertEqual('es_AR', $result);
	}

	public function testPreferredFromActionRequest() {
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => 'da, en-gb;q=0.8, en;q=0.7']
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('da', $result);

		$request = new ActionRequest(['env' => [
			'HTTP_ACCEPT_LANGUAGE' => 'en-gb;q=0.8, da, en;q=0.7'
		]]);
		$result = Locale::preferred($request);
		$this->assertEqual('da', $result);

		$request = new ActionRequest(['env' => [
			'HTTP_ACCEPT_LANGUAGE' => 'en-gb;q=0.8, en;q=0.7'
		]]);
		$result = Locale::preferred($request);
		$this->assertEqual('en_GB', $result);

		$request = new ActionRequest(['env' => [
			'HTTP_ACCEPT_LANGUAGE' => 'fr;q=1, en-gb;q=0.8, en;q=0.7'
		]]);
		$result = Locale::preferred($request);

		$request = new ActionRequest(['env' => [
			'HTTP_ACCEPT_LANGUAGE' => 'fr,en,it,es;q=0.7'
		]]);
		$result = Locale::preferred($request, ['en', 'fr', 'it']);
		$this->assertEqual('fr', $result);

		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => 'da']
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('da', $result);
	}

	public function testPreferredFromConsoleRequestLcAll() {
		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => 'es_ES@euro'],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('es_ES', $result);

		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => 'en_US.UTF-8'],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('en_US', $result);

		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => 'en_US'],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('en_US', $result);
	}

	public function testPreferredFromConsoleRequestLanguage() {
		$request = new ConsoleRequest([
			'env' => ['LANGUAGE' => 'sv_SE:nn_NO:de_DE'],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('sv_SE', $result);
	}

	public function testPreferredFromConsoleRequestLang() {
		$request = new ConsoleRequest([
			'env' => [
				'LANG' => 'es_ES@euro',
				'LANGUAGE' => null,
				'LC_ALL' => null
			],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('es_ES', $result);
	}

	public function testPreferredFromConsoleRequestPrecedence() {
		$request = new ConsoleRequest([
			'env' => [
				'LANGUAGE' => 'da_DK:ja_JP',
				'LC_ALL' => 'fr_CA',
				'LANG' => 'de_DE'
			],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('da_DK', $result);

		$request = new ConsoleRequest([
			'env' => [
				'LC_ALL' => 'fr_CA',
				'LANG' => 'de_DE'
			],
			'globals' => false
		]);
		$result = Locale::preferred($request);
		$this->assertEqual('fr_CA', $result);
	}

	public function testPreferredFromConsoleRequestEmptyLocales() {
		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => 'C', 'LANG' => null, 'LANGUAGE' => null],
			'globals' => false
		]);
		$this->assertNull(Locale::preferred($request));

		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => 'POSIX', 'LANG' => null, 'LANGUAGE' => null],
			'globals' => false
		]);
		$this->assertNull(Locale::preferred($request));

		$request = new ConsoleRequest([
			'env' => ['LC_ALL' => '', 'LANG' => null, 'LANGUAGE' => null],
			'globals' => false
		]);
		$this->assertNull(Locale::preferred($request));
	}

	public function testPreferredAvailableNegotiation() {
		$result = Locale::preferred(
			['nl_NL', 'nl_BE', 'nl', 'en_US', 'en'],
			['en', 'en_US', 'nl_BE']
		);
		$this->assertEqual('nl_BE', $result);

		$result = Locale::preferred(
			['da', 'en_GB', 'en'],
			['da', 'en_GB', 'en']
		);
		$this->assertEqual('da', $result);

		$result = Locale::preferred(
			['da', 'en_GB', 'en'],
			['en', 'en_GB', 'da']
		);
		$this->assertEqual('da', $result);

		$result = Locale::preferred(
			['da', 'en_GB', 'en'],
			['en_GB', 'en']
		);
		$this->assertEqual('en_GB', $result);

		$result = Locale::preferred(
			['da_DK', 'en_GB', 'en'],
			['da', 'en_GB', 'en']
		);
		$this->assertEqual('da', $result);

		$result = Locale::preferred(
			['zh_Hans_REVISED', 'zh_Hans_HK', 'zh', 'en'],
			['zh_Hans_HK_REVISED', 'zh_Hans_HK', 'zh', 'en']
		);
		$this->assertEqual('zh', $result);

		$result = Locale::preferred(
			['es_ES', 'en'],
			['da', 'en_GB', 'en', 'es_AR']
		);
		$this->assertEqual('es_AR', $result);
	}

	/**
	 * When the Accept-Language contains `*;q=0.01` it's been seen as `q` and
	 * raises an exception.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/386
	 */
	public function testPreferredStarWithQ() {
		$available = ['fr', 'de'];

		$yandex = 'ru, uk;q=0.8, be;q=0.8, en;q=0.7, *;q=0.01';
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => $yandex]
		]);
		$result = Locale::preferred($request, $available);
		$this->assertNull($result);

		$exabot = 'en;q=0.9,*;q=0.8';
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => $exabot]
		]);
		$result = Locale::preferred($request, $available);
		$this->assertNull($result);
	}

	/**
	 * When the Accept-Language is empty, it should return `null`.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/386
	 */
	public function testPreferredEmpty() {
		$available = ['fr', 'de'];

		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => '']
		]);
		$result = Locale::preferred($request, $available);
		$this->assertNull($result);
	}

	/**
	 * A random Firefox 4 sent us this Accept-Language header which is
	 * malformed.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/386
	 */
	public function testPreferredMalformedContainingChrome() {
		$available = ['fr', 'de'];

		$randomFirefox4 = 'de-DE,de;q=0.7,chrome://global/locale/intl.properties;q=0.3';
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => $randomFirefox4]
		]);
		$result = Locale::preferred($request, $available);
		$this->assertIdentical('de', $result);
	}

	/**
	 * A very strange Accept-Language which might be coming from a proxy, this
	 * rule `x-ns…` must be ignored.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/386
	 */
	public function testPreferredMalformedSquid() {
		$available = ['fr', 'de'];

		$squid = 'fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3,x-ns14sRVhG$uNxh';
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => $squid]
		]);
		$result = Locale::preferred($request, $available);
		$this->assertIdentical('fr', $result);
	}

	/**
	 * An Accept-Language coming from a Chrome user which contains an invalid
	 * item `es-419` causing `preferred` to fail with an exception while it
	 * should ignored.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/386
	 */
	public function testPreferredMalformedSpanish() {
		$available = ['fr', 'de'];

		$chrome = 'es-419,es;q=0.8';
		$request = new ActionRequest([
			'env' => ['HTTP_ACCEPT_LANGUAGE' => $chrome]
		]);
		$result = Locale::preferred($request, $available);
		$this->assertNull($result);
	}

	public function testRespondsToParentCall() {
		$this->assertTrue(Locale::respondsTo('invokeMethod'));
		$this->assertFalse(Locale::respondsTo('fooBarBaz'));
	}

	public function testRespondsToMagic() {
		$this->assertTrue(Locale::respondsTo('language'));
		$this->assertTrue(Locale::respondsTo('territory'));
		$this->assertFalse(Locale::respondsTo('foobar'));
	}

}

?>