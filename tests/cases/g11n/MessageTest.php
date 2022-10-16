<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n;

use lithium\core\Environment;
use lithium\g11n\Message;
use lithium\g11n\Catalog;
use lithium\g11n\catalog\adapter\Memory;

class MessageTest extends \lithium\test\Unit {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::reset();
		Catalog::config([
			'runtime' => ['adapter' => new Memory()]
		]);
		$data = function($n) { return $n === 1 ? 0 : 1; };
		Catalog::write('runtime', 'message.pluralRule', 'root', $data);

		$this->_backup['environment'] = Environment::get('test');
		Environment::set('test', ['locale' => 'en']);
		Environment::set('test');
		Message::cache(false);
	}

	public function tearDown() {
		Catalog::reset();
		Catalog::config($this->_backup['catalogConfig']);

		Environment::set('test', $this->_backup['environment']);
	}

	public function testTranslateBasic() {
		$data = ['catalog' => 'Katalog'];
		Catalog::write('runtime', 'message', 'de', $data);

		$expected = 'Katalog';
		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertEqual($expected, $result);
	}

	public function testTranslatePlural() {
		$data = [
			'house' => ['Haus', 'Häuser']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$expected = 'Haus';
		$result = Message::translate('house', ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => 5]);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateNonIntegerCounts() {
		$data = [
			'house' => ['Haus', 'Häuser']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => 2.31]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => 1.1]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => 0.1]);
		$this->assertEqual($expected, $result);

		$expected = 'Haus';
		$result = Message::translate('house', ['locale' => 'de', 'count' => true]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => false]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => '2']);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => '0']);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateNegativeIntegerCounts() {
		$data = [
			'house' => ['Haus', 'Häuser']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$expected = 'Haus';
		$result = Message::translate('house', ['locale' => 'de', 'count' => -1]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => -2]);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = Message::translate('house', ['locale' => 'de', 'count' => -5]);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateFail() {
		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertNull($result);

		Catalog::reset();
		Catalog::config([
			'runtime' => ['adapter' => new Memory()]
		]);

		$data = [
			'catalog' => ['Katalog', 'Kataloge']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertNull($result);

		$data = 'not a valid pluralization function';
		Catalog::write('runtime', 'message.pluralRule', 'root', $data);

		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertNull($result);
	}

	public function testTranslateScope() {
		$data = [
			'catalog' => 'Katalog'
		];
		Catalog::write('runtime', 'message', 'de', $data, ['scope' => 'test']);

		$data = function($n) { return $n === 1 ? 0 : 1; };
		Catalog::write('runtime', 'message.pluralRule', 'root', $data, [
			'scope' => 'test'
		]);

		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertNull($result);

		$expected = 'Katalog';
		$result = Message::translate('catalog', ['locale' => 'de', 'scope' => 'test']);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateDefault() {
		$result = Message::translate('Here I am', ['locale' => 'de']);
		$this->assertNull($result);

		$result = Message::translate('Here I am', [
			'locale' => 'de', 'default' => 'Here I am'
		]);
		$expected = 'Here I am';
		$this->assertEqual($expected, $result);
	}

	public function testTranslatePlaceholders() {
		$data = [
			'green' => 'grün',
			'No. {:id}' => 'Nr. {:id}',
			'The fish is {:color}.' => 'Der Fisch ist {:color}.',
			'{:count} bike' => ['{:count} Fahrrad', '{:count} Fahrräder']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$expected = 'Der Fisch ist grün.';
		$result = Message::translate('The fish is {:color}.', [
			'locale' => 'de',
			'color' => Message::translate('green', ['locale' => 'de'])
		]);
		$this->assertEqual($expected, $result);

		$expected = '1 Fahrrad';
		$result = Message::translate('{:count} bike', ['locale' => 'de', 'count' => 1]);
		$this->assertEqual($expected, $result);

		$expected = '7 Fahrräder';
		$result = Message::translate('{:count} bike', ['locale' => 'de', 'count' => 7]);
		$this->assertEqual($expected, $result);

		$expected = 'Nr. 8';
		$result = Message::translate('No. {:id}', ['locale' => 'de', 'id' => 8]);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateContext() {
		$data = [
			'fast|speed' => 'rapide',
			'fast|go without food' => 'jeûner'
		];
		Catalog::write('runtime', 'message', 'fr', $data);

		$expected = 'rapide';
		$result = Message::translate('fast', [
			'locale' => 'fr',
			'context' => 'speed'
		]);
		$this->assertEqual($expected, $result);

		$expected = 'jeûner';
		$result = Message::translate('fast', [
			'locale' => 'fr',
			'context' => 'go without food'
		]);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateLocales() {
		$data = [
			'catalog' => 'Katalog'
		];
		Catalog::write('runtime', 'message', 'de', $data);
		$data = [
			'catalog' => 'catalogue'
		];
		Catalog::write('runtime', 'message', 'fr', $data);

		$expected = 'Katalog';
		$result = Message::translate('catalog', ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = 'catalogue';
		$result = Message::translate('catalog', ['locale' => 'fr']);
		$this->assertEqual($expected, $result);
	}

	public function testTranslateNoop() {
		$data = [
			'catalog' => 'Katalog'
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$result = Message::translate('catalog', ['locale' => 'de', 'noop' => true]);
		$this->assertNull($result);
	}

	public function testAliasesBasic() {
		$data = [
			'house' => ['Haus', 'Häuser']
		];
		Catalog::write('runtime', 'message', 'de', $data);

		$filters = Message::aliases();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = 'Haus';
		$result = $t('house', ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = 'Haus';
		$result = $tn('house', 'houses', 1, ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = 'Häuser';
		$result = $tn('house', 'houses', 3, ['locale' => 'de']);
		$this->assertEqual($expected, $result);
	}

	public function testAliasesSymmetry() {
		$data = ['house' => ['Haus', 'Häuser']];
		Catalog::write('runtime', 'message', 'de', $data);

		$filters = Message::aliases();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = Message::translate('house', ['locale' => 'de']);
		$result = $t('house', ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = Message::translate('house', ['locale' => 'de', 'count' => 1]);
		$result = $tn('house', 'houses', 1, ['locale' => 'de']);
		$this->assertEqual($expected, $result);

		$expected = Message::translate('house', ['locale' => 'de', 'count' => 3]);
		$result = $tn('house', 'houses', 3, ['locale' => 'de']);
		$this->assertEqual($expected, $result);
	}

	public function testAliasesAsymmetry() {
		$filters = Message::aliases();
		$t = $filters['t'];
		$tn = $filters['tn'];

		$expected = Message::translate('house', ['locale' => 'de']);
		$result = $t('house', ['locale' => 'de']);
		$this->assertNotEqual($expected, $result);

		$expected = Message::translate('house', ['locale' => 'de', 'count' => 3]);
		$result = $tn('house', 'houses', 3, ['locale' => 'de']);
		$this->assertNotEqual($expected, $result);
	}

	public function testCaching() {
		$data = ['catalog' => 'Katalog'];
		Catalog::write('runtime', 'message', 'de', $data, ['scope' => 'foo']);

		$this->assertEmpty(Message::cache());

		$result = Message::translate('catalog', ['locale' => 'de', 'scope' => 'foo']);
		$this->assertEqual('Katalog', $result);

		$cache = Message::cache();
		$this->assertEqual('Katalog', $cache['foo']['de']['catalog']);

		Message::cache(false);
		$this->assertEmpty(Message::cache());

		Message::cache(['foo' => ['de' => ['catalog' => '<Katalog>']]]);
		$result = Message::translate('catalog', ['locale' => 'de', 'scope' => 'foo']);
		$this->assertEqual('<Katalog>', $result);

		$options = ['locale' => 'de', 'scope' => 'foo', 'count' => 2];
		$this->assertEqual('<Katalog>', Message::translate('catalog', $options));

		Message::cache(false);
		Message::cache(['foo' => ['de' => ['catalog' => ['<Katalog>']]]]);
		$this->assertNull(Message::translate('catalog', $options));
	}
}

?>