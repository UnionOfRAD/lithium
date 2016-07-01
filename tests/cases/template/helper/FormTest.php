<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\template\helper;

use Exception;
use lithium\action\Request;
use lithium\net\http\Router;
use lithium\data\entity\Record;
use lithium\data\entity\Document;
use lithium\template\helper\Form;
use lithium\tests\mocks\template\helper\MockFormPost;
use lithium\tests\mocks\template\helper\MockFormRenderer;

class FormTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\template\helper\MockFormPost';
	protected $_model2 = 'lithium\tests\mocks\template\helper\MockFormPostInfo';

	/**
	 * Test object instance.
	 *
	 * @var object
	 */
	public $form = null;

	/**
	 * The rendering context object.
	 *
	 * @var object
	 */
	public $context = null;

	public $base = null;

	/**
	 * Initialize test by creating a new object instance with a default context.
	 */
	public function setUp() {
		Router::connect('/{:controller}/{:action}/{:id}.{:type}', ['id' => null]);
		Router::connect('/{:controller}/{:action}/{:args}');

		$request = new Request();
		$request->params = ['controller' => 'posts', 'action' => 'index'];
		$request->persist = ['controller'];

		$this->context = new MockFormRenderer(compact('request'));
		$this->form = new Form(['context' => $this->context]);

		$this->base = $this->context->request()->env('base') . '/';
	}

	public function tearDown() {
		Router::reset();
	}

	public function testFormCreation() {
		$result = $this->form->create();
		$this->assertTags($result, ['form' => [
			'action' => "{$this->base}posts", 'method' => 'post'
		]]);

		$result = $this->form->create(null, ['method' => 'get']);
		$this->assertTags($result, [
			'form' => ['action' => "{$this->base}posts", 'method' => 'get']
		]);

		$result = $this->form->create(null, ['type' => 'file']);
		$this->assertTags($result, ['form' => [
			'action' => "{$this->base}posts",
			'enctype' => 'multipart/form-data',
			'method' => 'post'
		]]);

		$result = $this->form->create(null, ['method' => 'get', 'type' => 'file']);
		$this->assertTags($result, ['form' => [
			'action' => "{$this->base}posts",
			'method' => 'post',
			'enctype' => 'multipart/form-data'
		]]);

		$result = $this->form->create(null, ['id' => 'Registration']);
		$this->assertTags($result, [
			'form' => [
				'action' => "{$this->base}posts",
				'method' => 'post',
				'id' => 'Registration'
			]
		]);
	}

	/**
	 * Tests creating forms with non-browser compatible HTTP methods, required for REST interfaces.
	 */
	public function testRestFormCreation() {
		$result = $this->form->create(null, ['action' => 'delete', 'method' => 'delete']);

		$this->assertTags($result, [
			'form' => [
				'action' => "{$this->base}posts/delete", 'method' => 'post'
			],
			'input' => ['type' => "hidden", 'name' => '_method', 'value' => 'DELETE']
		]);

		$result = $this->form->create(null, ['method' => 'put', 'type' => 'file']);
		$this->assertTags($result, [
			'form' => [
				'action' => "{$this->base}posts",
				'method' => 'post',
				'enctype' => 'multipart/form-data'
			],
			'input' => ['type' => "hidden", 'name' => '_method', 'value' => 'PUT']
		]);

		$record = new Record(['exists' => true, 'model' => $this->_model]);
		$result = $this->form->create($record);

		$this->assertTags($result, [
			'form' => ['action' => "{$this->base}posts", 'method' => 'post'],
			'input' => ['type' => "hidden", 'name' => '_method', 'value' => 'PUT']
		]);
	}

	public function testFormCreationWithBinding() {
		$record = new Record(['model' => $this->_model, 'data' => [
			'id' => '5',
			'author_id' => '2',
			'title' => 'This is a saved post',
			'body' => 'This is the body of the saved post'
		]]);

		$this->assertTags($this->form->create($record), [
			'form' => ['action' => "{$this->base}posts", 'method' => 'post']
		]);
	}

	/**
	 * Ensures that password fields aren't rendered with pre-populated values from bound record or
	 * document objects.
	 */
	public function testPasswordWithBindingValue() {
		$this->form->create(new Record([
			'model' => $this->_model, 'data' => ['pass' => 'foobar']
		]));
		$result = $this->form->password('pass');

		$this->assertTags($result, [
			'input' => ['type' => 'password', 'name' => 'pass', 'id' => 'MockFormPostPass']
		]);
	}

	public function testFormDataBinding() {
		try {
			MockFormPost::config(['meta' => ['connection' => false]]);
		} catch (Exception $e) {
			MockFormPost::config(['meta' => ['connection' => false]]);
		}

		$record = new Record(['model' => $this->_model, 'data' => [
			'id' => '5',
			'author_id' => '2',
			'title' => 'This is a saved post',
			'body' => 'This is the body of the saved post',
			'zeroInt' => 0,
			'zeroString' => "0"
		]]);

		$result = $this->form->create($record);
		$this->assertTags($result, [
			'form' => ['action' => "{$this->base}posts", 'method' => 'post']
		]);

		$result = $this->form->text('title');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'title',
			'value' => 'This is a saved post', 'id' => 'MockFormPostTitle'
		]]);

		$result = $this->form->text('zeroInt');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'zeroInt',
			'value' => '0', 'id' => 'MockFormPostZeroInt'
		]]);
		$result = $this->form->text('zeroString');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'zeroString',
			'value' => '0', 'id' => 'MockFormPostZeroString'
		]]);

		$this->assertEqual('</form>', $this->form->end());

		$this->assertTags($this->form->text('title'), ['input' => [
			'type' => 'text', 'name' => 'title', 'id' => 'Title'
		]]);
	}

	public function testTextBox() {
		$result = $this->form->text('foo');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'id' => 'Foo'
		]]);
	}

	public function testElementsWithDefaultConfiguration() {
		$this->form = new Form([
			'context' => new MockFormRenderer(), 'base' => ['class' => 'editable']
		]);

		$result = $this->form->text('foo');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'class' => 'editable', 'id' => 'Foo'
		]]);

		$this->form->config(['base' => ['maxlength' => 255]]);

		$result = $this->form->text('foo');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'class' => 'editable',
			'maxlength' => '255', 'id' => 'Foo'
		]]);

		$this->form->config(['text' => ['class' => 'locked']]);

		$result = $this->form->text('foo');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'class' => 'locked',
			'maxlength' => '255', 'id' => 'Foo'
		]]);

		$result = $this->form->config();
		$expected = [
			'base' => ['class' => 'editable', 'maxlength' => 255],
			'text' => ['class' => 'locked'],
			'textarea' => [],
			'templates' => ['create' => 'form', 'end' => 'form-end'],
			'attributes' => [
				'id' => $result['attributes']['id'],
				'name' => $result['attributes']['name']
			]
		];
		$this->assertEqual($expected, $result);
		$this->assertInternalType('callable', $result['attributes']['id']);
		$this->assertInternalType('callable', $result['attributes']['name']);
	}

	public function testFormElementWithDefaultValue() {
		$result = $this->form->text('foo', ['default' => 'Message here']);

		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'value' => 'Message here', 'id' => 'Foo'
		]]);

		$result = $this->form->text('foo', [
			'default' => 'Message here', 'value' => 'My Name Is Jonas', 'id' => 'Foo'
		]);
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'value' => 'My Name Is Jonas', 'id' => 'Foo'
		]]);

		$result = $this->form->text('foo', ['value' => 'My Name Is Jonas']);
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo', 'value' => 'My Name Is Jonas', 'id' => 'Foo'
		]]);
	}

	public function testFormInputField() {
		$tag = ['input' => ['type' => 'file', 'name' => 'upload', 'id' => 'Upload']];

		$result = $this->form->file('upload');
		$this->assertTags($result, $tag);

		$value = new Document(['model' => $this->_model]);
		$result = $this->form->file('upload', compact('value'));
		$tag['input']['value'] = '';
		$this->assertTags($result, $tag);
	}

	public function testHiddenFieldWithId() {
		$result = $this->form->hidden('my_field');
		$this->assertTags($result, ['input' => [
			'type' => 'hidden', 'name' => 'my_field', 'id' => 'MyField'
		]]);
	}

	public function testHiddenFieldWithValue() {
		$result = $this->form->hidden('my_field', ['value' => 'custom']);
		$this->assertTags($result, ['input' => [
			'type' => 'hidden', 'name' => 'my_field', 'id' => 'MyField', 'value' => 'custom'
		]]);
	}

	public function testLabelGeneration() {
		$result = $this->form->label('next', 'Enter the next value >>');
		$this->assertTags($result, [
			'label' => ['for' => 'next'],
			'Enter the next value &gt;&gt;',
			'/label'
		]);

		$result = $this->form->label('user_name');
		$this->assertTags($result, [
			'label' => ['for' => 'user_name'],
			'User Name',
			'/label'
		]);

		$result = $this->form->label('first_name', [
			'First Name' => ['id' => 'first_name_label']
		]);
		$this->assertTags($result, [
			'label' => ['for' => 'first_name', 'id' => 'first_name_label'],
			'First Name',
			'/label'
		]);

		$result = $this->form->label('first_name', [
			null => ['id' => 'first_name_label']
		]);
		$this->assertTags($result, [
			'label' => ['for' => 'first_name', 'id' => 'first_name_label'],
			'First Name',
			'/label'
		]);
	}

	public function testLabelGenerationWithNoEscape() {
		$result = $this->form->label('next', 'Enter the next value >>', ['escape' => false]);
		$this->assertTags($result, [
			'label' => ['for' => 'next'],
			'Enter the next value >>',
			'/label'
		]);
	}

	public function testSubmitGeneration() {
		$result = $this->form->submit('Continue >');
		$this->assertTags($result, ['input' => [
			'type' => 'submit',
			'value' => 'Continue &gt;'
		]]);

		$result = $this->form->submit('Continue >', ['class' => 'special']);
		$this->assertTags($result, ['input' => [
			'type' => 'submit',
			'value' => 'Continue &gt;',
			'class' => 'special'
		]]);
	}

	public function testTextareaGeneration() {
		$result = $this->form->textarea('foo', ['value' => 'some content >']);
		$this->assertTags($result, [
			'textarea' => ['name' => 'foo', 'id' => 'Foo'],
			'some content &gt;',
			'/textarea'
		]);
	}

	public function testCheckboxGeneration() {
		$result = $this->form->checkbox('foo');
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->checkbox('foo', ['checked' => false]);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->checkbox('foo', ['checked' => true]);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'foo',
				'checked' => 'checked', 'id' => 'Foo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => true]]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo');
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'foo', 'id' => 'MockFormPostFoo',
				'checked' => 'checked'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => false]]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo');
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'foo', 'id' => 'MockFormPostFoo'
			]]
		]);

		$document = new Document([
			'model' => $this->_model,
			'data' => [
				'subdocument' => [
					'foo' => true
				]
			]
		]);
		$this->form->create($document);

		$result = $this->form->checkbox('subdocument.foo');
		$this->assertTags($result, [
			['input' => [
				'type' => 'hidden', 'value' => '', 'name' => 'subdocument[foo]']
			],
			['input' => [
				'type' => 'checkbox', 'value' => '1', 'name' => 'subdocument[foo]',
				'checked' => 'checked', 'id' => 'MockFormPostSubdocumentFoo'
			]]
		]);
	}

	public function testCustomCheckbox() {
		$result = $this->form->checkbox('foo', ['value' => '1']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1',  'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->checkbox('foo', ['checked' => true, 'value' => '1']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1',  'name' => 'foo',
				'checked' => 'checked', 'id' => 'Foo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => true]]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo', ['value' => '1']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1',  'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);

		$result = $this->form->checkbox('foo', ['value' => true]);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '1',  'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);
	}

	public function testCustomValueCheckbox() {
		$result = $this->form->checkbox('foo', ['value' => 'HERO']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => 'HERO', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->checkbox('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => 'nose', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => 'nose']]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => 'nose', 'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => 'foot']]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => 'nose', 'name' => 'foo', 'id' => 'MockFormPostFoo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => false]]);
		$this->form->create($record);
		$result = $this->form->checkbox('foo', ['value' => '0']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => '0', 'name' => 'foo', 'id' => 'MockFormPostFoo',
				'checked' => 'checked'
			]]
		]);
	}

	public function testRadioGeneration() {
		$result = $this->form->radio('foo');
		$this->assertTags($result, [[
			'input' => ['type' => 'radio', 'value' => '1', 'name' => 'foo', 'id' => 'Foo']]
		]);

		$result = $this->form->radio('foo', ['checked' => false]);
		$this->assertTags($result, [[
			'input' => ['type' => 'radio', 'value' => '1', 'name' => 'foo', 'id' => 'Foo']]
		]);

		$result = $this->form->radio('foo', ['checked' => true]);
		$this->assertTags($result, [[
			'input' => [
				'type' => 'radio',
				'value' => '1',
				'name' => 'foo',
				'checked' => 'checked',
				'id' => 'Foo'
			]
		]]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => true]]);
		$this->form->create($record);

		$result = $this->form->radio('foo');
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1', 'name' => 'foo', 'id' => 'MockFormPostFoo',
				'checked' => 'checked'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => false]]);
		$this->form->create($record);

		$result = $this->form->radio('foo');
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1', 'name' => 'foo', 'id' => 'MockFormPostFoo'
			]]
		]);

		$document = new Document([
			'model' => $this->_model,
			'data' => [
				'subdocument' => [
					'foo' => true
				]
			]
		]);
		$this->form->create($document);

		$result = $this->form->radio('subdocument.foo');
		$this->assertTags($result, [['input' => [
			'type' => 'radio',
			'value' => '1',
			'name' => 'subdocument[foo]',
			'id' => 'MockFormPostSubdocumentFoo',
			'checked' => 'checked'
		]]]);
	}

	public function testCustomRadio() {
		$result = $this->form->radio('foo', ['value' => '1']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1',  'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->radio('foo', ['checked' => true, 'value' => '1']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1',  'name' => 'foo',
				'checked' => 'checked', 'id' => 'Foo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => true]]);
		$this->form->create($record);

		$result = $this->form->radio('foo', ['value' => '1']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1',  'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);

		$result = $this->form->radio('foo', ['value' => true]);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '1',  'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);
	}

	public function testCustomValueRadio() {
		$result = $this->form->radio('foo', ['value' => 'HERO']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => 'HERO', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$result = $this->form->radio('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => 'nose', 'name' => 'foo', 'id' => 'Foo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => 'nose']]);
		$this->form->create($record);

		$result = $this->form->radio('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => 'nose', 'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => 'foot']]);
		$this->form->create($record);

		$result = $this->form->checkbox('foo', ['value' => 'nose']);
		$this->assertTags($result, [
			['input' => ['type' => 'hidden', 'value' => '', 'name' => 'foo']],
			['input' => [
				'type' => 'checkbox', 'value' => 'nose', 'name' => 'foo', 'id' => 'MockFormPostFoo'
			]]
		]);

		$record = new Record(['model' => $this->_model, 'data' => ['foo' => false]]);
		$this->form->create($record);
		$result = $this->form->radio('foo', ['value' => '0']);
		$this->assertTags($result, [
			['input' => [
				'type' => 'radio', 'value' => '0',  'name' => 'foo',
				'id' => 'MockFormPostFoo', 'checked' => 'checked'
			]]
		]);
	}

	public function testSelectGeneration() {
		$result = $this->form->select('foo');

		$this->assertTags($result, [
			'select' => ['name' => 'foo', 'id' => 'Foo'], '/select'
		]);

		$result = $this->form->select(
			'colors',
			['r' => 'red', 'g "' => 'green', 'b' => 'blue'],
			['id' => 'Colors', 'value' => 'g "']
		);

		$this->assertTags($result, [
			'select' => ['name' => 'colors', 'id' => 'Colors'],
			['option' => ['value' => 'r']],
			'red',
			'/option',
			['option' => ['value' => 'g &quot;', 'selected' => 'selected']],
			'green',
			'/option',
			['option' => ['value' => 'b']],
			'blue',
			'/option',
			'/select'
		]);
	}

	/**
	 * When trying to determine which option of a select box should be selected, we should be
	 * integer/string agnostic because it all looks the same in HTML.
	 *
	 */
	public function testSelectTypeAgnosticism() {
		$taglist = [
			'select' => ['name' => 'numbers', 'id' => 'Numbers'],
			['option' => ['value' => '0']],
			'Zero',
			'/option',
			['option' => ['value' => '1', 'selected' => 'selected']],
			'One',
			'/option',
			['option' => ['value' => '2']],
			'Two',
			'/option',
			'/select'
		];

		$result = $this->form->select(
			'numbers',
			[0 => 'Zero', 1 => 'One', 2 => 'Two'],
			['id' => 'Numbers', 'value' => '1']
		);

		$this->assertTags($result, $taglist);

		$result = $this->form->select(
			'numbers',
			['0' => 'Zero', '1' => 'One', '2' => 'Two'],
			['id' => 'Numbers', 'value' => 1]
		);

		$this->assertTags($result, $taglist);
	}

	public function testSelectWithEmptyOption() {
		$result = $this->form->select('numbers', ['zero', 'first', 'second'], [
			'empty' => true
		]);

		$this->assertTags($result, [
			'select' => ['id' => 'Numbers', 'name' => 'numbers'],
			['option' => ['value' => '', 'selected' => 'selected']],
			'/option',
			['option' => ['value' => '0']],
			'zero',
			'/option',
			['option' => ['value' => '1']],
			'first',
			'/option',
			['option' => ['value' => '2']],
			'second',
			'/option',
			'/select'
		]);

		$result = $this->form->select('numbers', ['zero', 'first', 'second'], [
			'empty' => '> Make a selection'
		]);

		$this->assertTags($result, [
			'select' => ['name' => 'numbers', 'id' => 'Numbers'],
			['option' => ['value' => '', 'selected' => 'selected']],
			'&gt; Make a selection',
			'/option',
			['option' => ['value' => '0']],
			'zero',
			'/option',
			['option' => ['value' => '1']],
			'first',
			'/option',
			['option' => ['value' => '2']],
			'second',
			'/option',
			'/select'
		]);
	}

	/**
	 * Tests that calling `select()` with nested arrays will produce lists of `<option />`s wrapped
	 * in `<optgroup />` elements.
	 */
	public function testRecursiveSelect() {
		$list = [
			'Linux' => [
				'1' => 'Ubuntu 10.10',
				'2' => 'CentOS 5'
			],
			'Other' => [
				'4' => 'Solaris',
				'5' => 'Windows Server 2010 R2'
			]
		];
		$result = $this->form->select('opsys', $list, [
			'empty' => 'Select one', 'value' => '5'
		]);

		$this->assertTags($result, [
			'select' => ['name' => 'opsys', 'id' => 'Opsys'],
			['option' => ['value' => '']],
			'Select one',
			'/option',
			['optgroup' => ['label' => 'Linux']],
			['option' => ['value' => '1']],
			'Ubuntu 10.10',
			'/option',
			['option' => ['value' => '2']],
			'CentOS 5',
			'/option',
			'/optgroup',
			['optgroup' => ['label' => 'Other']],
			['option' => ['value' => '4']],
			'Solaris',
			'/option',
			['option' => ['value' => '5', 'selected' => 'selected']],
			'Windows Server 2010 R2',
			'/option',
			'/optgroup',
			'/select'
		]);
	}

	public function testTemplateRemapping() {
		$result = $this->form->password('passwd');
		$this->assertTags($result, ['input' => [
			'type' => 'password', 'name' => 'passwd', 'id' => 'Passwd'
		]]);

		$this->form->config(['templates' => ['password' => 'text']]);

		$result = $this->form->password('passwd');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'passwd', 'id' => 'Passwd'
		]]);
	}

	public function testMultiSelect() {
		$expected = [
			'select' => ['name' => 'numbers[]', 'id' => 'Numbers', 'multiple' => 'multiple'],
			['option' => ['value' => '', 'selected' => 'selected']],
			'&gt; Make a selection',
			'/option',
			['option' => ['value' => '1']],
			'first',
			'/option',
			['option' => ['value' => '2']],
			'second',
			'/option',
			'/select'
		];
		$result = $this->form->select('numbers', ['1' => 'first', '2' => 'second'], [
			'empty' => '> Make a selection',
			'multiple' => true
		]);
		$this->assertTags($result, $expected);

		$expected = [
			'select' => [
				'name' => 'numbers[]', 'multiple' => 'multiple', 'size' => 5, 'id' => 'Numbers'
			],
			['option' => ['value' => '1']],
			'first',
			'/option',
			['option' => ['value' => '2']],
			'second',
			'/option',
			'/select'
		];
		$result = $this->form->select('numbers', ['1' => 'first', '2' => 'second'], [
			'multiple' => true,
			'size' => 5
		]);
		$this->assertTags($result, $expected);
	}

	public function testMultiselected() {
		$expected = [
			'select' => ['name' => 'numbers[]', 'id' => 'Numbers', 'multiple' => 'multiple'],
			['option' => ['value' => '1', 'selected' => 'selected']],
			'first',
			'/option',
			['option' => ['value' => '2']],
			'second',
			'/option',
			['option' => ['value' => '3', 'selected' => 'selected']],
			'third',
			'/option',
			['option' => ['value' => '4', 'selected' => 'selected']],
			'fourth',
			'/option',
			'/select'
		];
		$list = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth'];
		$options = ['value' => [1, 3, 4], 'multiple' => true];
		$result = $this->form->select('numbers', $list, $options);
		$this->assertTags($result, $expected);
	}

	public function testFormCreateWithMoreParams() {
		$request = new Request();
		$request->params = ['controller' => 'mock', 'action' => 'test', 'args' => ['1']];
		$context = new MockFormRenderer(compact('request'));
		$form = new Form(compact('context'));

		$result = $form->create();
		$this->assertTags($result, ['form' => [
			'action' => "{$this->base}mock/test/1",
			'method' => 'post'
		]]);
	}

	public function testFormCreateWithMoreParamsButSpecifiedAction() {
		$request = new Request();
		$request->params = ['controller' => 'mock', 'action' => 'test', 'args' => ['1']];
		$request->persist = ['controller'];
		$context = new MockFormRenderer(compact('request'));
		$form = new Form(compact('context'));

		$result = $form->create(null, ['action' => 'radness']);
		$this->assertTags($result, ['form' => [
			'action' => "{$this->base}mock/radness",
			'method' => 'post'
		]]);
	}

	public function testFormField() {
		$result = $this->form->field('name');
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Name'], 'Name', '/label',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name'],
			'/div'
		]);

		$result = $this->form->field('name', ['type' => 'radio', 'value' => 'foo']);
		$this->assertTags($result, [
			'div' => [],
			'input' => ['type' => 'radio', 'name' => 'name', 'value' => 'foo', 'id' => 'Name'],
			'label' => ['for' => 'Name'], 'Name', '/label',
			'/div'
		]);

		$result = $this->form->field('name', ['type' => 'checkbox']);
		$expected = [
			'<div>',
			'<input type="hidden" name="name" value="" />',
			'<input type="checkbox" name="name" id="Name" value="1" />',
			'<label for="Name">Name</label></div>'
		];
		$this->assertEqual(join('', $expected), $result);
	}

	public function testFormFieldWithCustomConfig() {
		$this->form->config(['field' => ['class' => 'custom-field']]);
		$result = $this->form->field('username');

		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Username'],
			'Username',
			'/label',
			'input' => [
				'type' => 'text',
				'name' => 'username',
				'class' => 'custom-field',
				'id' => 'Username'
			],
			'/div'
		]);

		$this->assertTags($this->form->end(), ['/form']);

		$this->form->config(['templates' => ['end' => "</table></form>"]]);
		$this->assertTags($this->form->end(), ['/table', '/form']);
	}

	public function testFormFieldCheckboxWithCustomConfig() {
		$this->form->config([
			'field-checkbox' => ['wrap' => ['class' => 'custom-field-checkbox']]
		]);
		$result = $this->form->field('checkbox', ['type' => 'checkbox']);

		$this->assertTags($result, [
			'div' => ['class' => 'custom-field-checkbox'],
			['input' => [
				'type' => 'hidden',
				'name' => 'checkbox',
				'value' => ''
			]],
			['input' => [
				'type' => 'checkbox',
				'name' => 'checkbox',
				'id' => 'Checkbox',
				'value' => 1
			]],
			'label' => ['for' => 'Checkbox'],
			'Checkbox',
			'/label',
			'/div'
		]);
	}

	/**
	 * Verifies that calls to `field()` with `'type' => 'hidden'` do not produce `<label />`s.
	 */
	public function testHiddenFieldWithNoLabel() {
		$result = $this->form->field('foo', ['type' => 'hidden']);
		$this->assertTags($result, [
			'div' => [],
			'input' => ['type' => 'hidden', 'name' => 'foo', 'id' => 'Foo'],
			'/div'
		]);
	}

	public function testFormFieldWithCustomTemplate() {
		$result = $this->form->field('name', [
			'template' => '<div{:wrap}>{:label}: {:input}{:error}</div>'
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Name'], 'Name', '/label', ':',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']
		]);
	}

	public function testFieldWithLabelShorthand() {
		$result = $this->form->field(['name' => 'Enter a name']);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Name'], 'Enter a name', '/label',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']
		]);
	}

	/**
	 * Demonstrates that the options for a `<label />` element can be passed through the `field()`
	 * method, using the label text as a key.
	 */
	public function testFieldLabelWithOptions() {
		$result = $this->form->field('name', [
			'label' => ['Item Name' => ['class' => 'required']]
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Name', 'class' => 'required'], 'Item Name', '/label',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']
		]);

		$result = $this->form->field('video_preview', [
			'label' => ['<a href="http://www.youtube.com/">Youtube</a>' => [
				'escape' => false
			]]
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'VideoPreview'],
			'a' => ['href' => 'http://www.youtube.com/'], 'Youtube', '/a', '/label',
			'input' => ['type' => 'text', 'name' => 'video_preview', 'id' => 'VideoPreview']
		]);
	}

	public function testMultipleFields() {
		$result = $this->form->field([
			'name' => 'Enter a name',
			'phone_number',
			'email' => 'Enter a valid email'
		]);
		$this->assertTags($result, [
			['div' => []],
			['label' => ['for' => 'Name']],
			'Enter a name',
			'/label',
			['input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']],
			'/div',
			['div' => []],
			['label' => ['for' => 'PhoneNumber']],
			'Phone Number',
			'/label',
			['input' => [
				'type' => 'text', 'name' => 'phone_number', 'id' => 'PhoneNumber'
			]],
			'/div',
			['div' => []],
			['label' => ['for' => 'Email']],
			'Enter a valid email',
			'/label',
			['input' => ['type' => 'text', 'name' => 'email', 'id' => 'Email']],
			'/div'
		]);
	}

	public function testCustomInputTypes() {
		// Creates an HTML5 'range' input slider:
		$range = $this->form->range('completion', ['min' => 0, 'max' => 100]);
		$this->assertTags($range, ['input' => [
			'type' => 'range', 'name' => 'completion',
			'min' => '0', 'max' => '100', 'id' => 'Completion'
		]]);
	}

	public function testFieldWithCustomType() {
		$field = $this->form->field('completion', [
			'type' => 'range', 'id' => 'completion', 'min' => '0', 'max' => '100',
			'label' => 'Completion %', 'wrap' => ['class' => 'input']
		]);
		$this->assertTags($field, [
			'div' => ['class' => 'input'],
			'label' => ['for' => 'completion'], 'Completion %', '/label',
			'input' => [
				'type' => 'range', 'name' => 'completion',
				'id' => 'completion', 'min' => '0', 'max' => '100'
			],
			'/div'
		]);
	}

	public function testFormFieldSelect() {
		$result = $this->form->field('states', [
			'type' => 'select', 'list' => ['CA', 'RI']
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'States'], 'States', '/label',
			'select' => ['name' => 'states', 'id' => 'States'],
			['option' => ['value' => '0']],
			'CA',
			'/option',
			['option' => ['value' => '1']],
			'RI',
			'/option',
			'/select'
		]);
	}

	public function testFormFieldTextDatalist() {
		$result = $this->form->field('states', [
			'type' => 'text', 'list' => ['CA', 'RI']
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'States'], 'States', '/label',
			'input' => [
				'type' => 'text','name' => 'states',
				'id' => 'States', 'list' => 'StatesList'
			],
			'datalist' => ['id' => 'StatesList'],
				['option' => ['value' => 'CA']],
				'/option',
				['option' => ['value' => 'RI']],
				'/option',
			'/datalist',
			'/div',
		]);

		$result = $this->form->field('states', [
			'type' => 'text', 'list' => 'StatesList'
		]);
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'States'], 'States', '/label',
			'input' => [
				'type' => 'text','name' => 'states',
				'id' => 'States', 'list' => 'StatesList'
			],
		]);
	}

	public function testFormErrorWithout() {
		$this->form->create(null);
		$result = $this->form->error('name');
		$this->assertInternalType('null', $result);
	}

	public function testFormErrorWithRecordAndStringError() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => 'Please enter a name']);
		$this->form->create($record);

		$result = $this->form->error('name');
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Please enter a name', '/div'
		]);
	}

	public function testFormMultipleErrors() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['email' => ['Empty', 'Valid']]);
		$this->form->create($record);

		$result = $this->form->error('email');
		$this->assertTags($result, [
			['div' => ['class' => 'error']], 'Empty', '/div',
			['div' => ['class' => 'error']], 'Valid', '/div'
		]);

		$result = $this->form->error('email', 0);
		$this->assertTags($result, ['div' => ['class' => 'error'], 'Empty', '/div']);

		$result = $this->form->error('email', 1);
		$this->assertTags($result, ['div' => ['class' => 'error'], 'Valid', '/div']);

		$result = $this->form->error('email', true);
		$this->assertTags($result, ['div' => ['class' => 'error'], 'Empty', '/div']);
	}

	public function testFormErrorWithRecordAndSpecificKey() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->error('name', 0);
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Please enter a name', '/div'
		]);
	}

	public function testFormErrorWithRecordAndSpecificKeyAndValue() {
		$record = new Record(['model' => $this->_model]);
		$record->name = 'Nils';
		$record->errors(['name' => ['Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->error('name');
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Please enter a name', '/div'
		]);
	}

	public function testFormFieldWithError() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->field('name');
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			'div' => ['class' => "error"], 'Please enter a name', '/div', '/div'
		]);
	}

	public function testErrorWithCustomConfiguration() {
		$this->form->config(['error' => ['class' => 'custom-error-class']]);

		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->field('name');
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			'div' => ['class' => "custom-error-class"], 'Please enter a name', '/div', '/div'
		]);
	}

	public function testFormFieldErrorSurpressed() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['notEmpty' => 'Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->field('name', [
			'error' => false
		]);
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			 '/div'
		]);
	}

	public function testFormFieldErrorWithCustomStringMessage() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['notEmpty' => 'Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->field('name', [
			'error' => 'Nothing.'
		]);
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			'div' => ['class' => "error"], 'Nothing.', '/div', '/div'
		]);
	}

	public function testFormFieldErrorWithCustomArrayMessages() {
		$record = new Record(['model' => $this->_model]);
		$record->errors(['name' => ['notEmpty' => 'Please enter a name']]);
		$this->form->create($record);

		$result = $this->form->field('name', [
			'error' => [
				'notEmpty' => 'Nothing.'
			]
		]);
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			'div' => ['class' => "error"], 'Nothing.', '/div', '/div'
		]);

		$result = $this->form->field('name', [
			'error' => [
				'default' => 'Nothing.'
			]
		]);
		$this->assertTags($result, [
			'<div', 'label' => ['for' => 'MockFormPostName'], 'Name', '/label',
			'input' => ['type' => "text", 'name' => 'name', 'id' => 'MockFormPostName'],
			'div' => ['class' => "error"], 'Nothing.', '/div', '/div'
		]);
	}

	/**
	 * Tests that the string template form `Form::field()` can be overridden.
	 */
	public function testFieldTemplateOverride() {
		$this->form->config(['templates' => ['field' => '{:label}{:input}{:error}']]);
		$result = $this->form->field('name', ['type' => 'text']);
		$this->assertTags($result, [
			'label' => ['for' => 'Name'], 'Name', '/label',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']
		]);
	}

	/**
	 * Tests that the `field()` method properly renders a `<select />` element if the `'list'`
	 * option is passed.
	 */
	public function testFieldAssumeSelectIfList() {
		$result = $this->form->field('colors', [
			'list' => ['r' => 'red', 'g' => 'green', 'b' => 'blue']
		]);
		$expected = [
			'<div',
			['label' => ['for' => 'Colors']],
			'Colors',
			'/label',
			'select' => ['name' => 'colors', 'id' => 'Colors'],
			['option' => ['value' => 'r']],
			'red',
			'/option',
			['option' => ['value' => 'g']],
			'green',
			'/option',
			['option' => ['value' => 'b']],
			'blue',
			'/option',
			'/select',
			'/div'
		];
		$this->assertTags($result, $expected);
	}

	public function testFieldInputIdWithFormId() {
		$this->form->create(null, ['id' => 'registration']);
		$result = $this->form->field('name');

		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'Name'], 'Name', '/label',
			'input' => ['type' => 'text', 'name' => 'name', 'id' => 'Name']
		]);
	}

	/**
	 * Tests that inputs for nested objects can be assigned using dot syntax.
	 */
	public function testNestedFieldAccess() {
		$doc = new Document(['data' => ['foo' => ['bar' => 'value']]]);
		$this->form->create($doc);

		$result = $this->form->text('foo.bar');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'foo[bar]', 'id' => 'FooBar', 'value' => 'value'
		]]);

		$result = $this->form->field('foo.bar');
		$this->assertTags($result, [
			'div' => [],
			'label' => ['for' => 'FooBar'], 'Foo Bar', '/label',
			'input' => [
				'type' => 'text', 'name' => 'foo[bar]', 'id' => 'FooBar', 'value' => 'value'
			]
		]);
	}

	/**
	 * Tests rendering errors for nested fields.
	 */
	public function testNestedFieldError() {
		$doc = new Document(['data' => ['foo' => ['bar' => 'value']]]);
		$doc->errors(['foo.bar' => 'Something bad happened.']);

		$this->form->create($doc);
		$result = $this->form->field('foo.bar');

		$this->assertTags($result, [
			['div' => []],
			'label' => ['for' => 'FooBar'], 'Foo Bar', '/label',
			'input' => [
				'type' => 'text', 'name' => 'foo[bar]', 'id' => 'FooBar', 'value' => 'value'
			],
			'div' => ['class' => 'error'], 'Something bad happened.', '/div',
			['/div' => []]
		]);
	}

	public function testFormCreationWithNoContext() {
		$this->form = new Form(['context' => new MockFormRenderer([
			'request' => new Request(['base' => '/bbq'])
		])]);
		$result = $this->form->create(null, ['url' => '/foo']);

		$this->assertTags($result, ['form' => [
			'action' => "/bbq/foo",
			'method' => "post"
		]]);
	}

	/**
	 * Tests that magic method support can be used to automatically generate a `<button />` tag
	 * based on the default string template.
	 */
	public function testButton() {
		$result = $this->form->button('Foo!', ['id' => 'bar']);
		$this->assertTags($result, ['button' => ['id' => 'bar'], 'Foo!', '/button']);

		$result = $this->form->button('Continue >', ['type' => 'submit']);
		$this->assertTags($result, [
			'button' => ['type' => 'submit', 'id' => 'Continue'],
			'Continue &gt;',
			'/button'
		]);
	}

	/**
	 * Tests that field references passed to `label()` in dot-separated format correctly translate
	 * to DOM ID values.
	 */
	public function testLabelIdGeneration() {
		$this->assertTags($this->form->label('user.name'), [
			'label' => ['for' => 'UserName'], 'User Name', '/label'
		]);
	}

	/**
	 * Test that field already defined template strings with special types (e.g. radio, checkbox,
	 * etc.) and passed customize template, and the template must apply.
	 */
	public function testRadioTypeFieldWithCustomTemplate() {
		$result = $this->form->field('name', [
			'template' => '<span{:wrap}>{:label}: {:input}{:error}</span>',
			'type' => 'radio'
		]);
		$this->assertTags($result, [
			'span' => [],
			'label' => ['for' => 'Name'], 'Name', '/label', ':',
			'input' => ['type' => 'radio', 'name' => 'name', 'id' => 'Name', 'value' => '1']
		]);
	}

	public function testFormCreationMultipleBindings() {
		$record1 = new Record(['model' => $this->_model, 'data' => [
			'author_id' => '2',
			'title' => 'New post',
			'body' => 'New post body'
		]]);
		$record2 = new Record(['model' => $this->_model2, 'data' => [
			'section' => 'New post section',
			'notes' => 'New post notes'
		]]);

		$result = $this->form->create([
			'MockFormPost' => $record1,
			'MockFormPostInfo' => $record2
		]);
		$this->assertTags($result, [
			'form' => ['action' => "{$this->base}posts", 'method' => 'post']
		]);

		$result = $this->form->text('title');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'title',
			'value' => 'New post', 'id' => 'MockFormPostTitle'
		]]);

		$result = $this->form->text('MockFormPost.title');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'MockFormPost[title]',
			'value' => 'New post', 'id' => 'MockFormPostTitle'
		]]);

		$result = $this->form->text('body');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'body',
			'value' => 'New post body', 'id' => 'MockFormPostBody'
		]]);

		$result = $this->form->text('MockFormPostInfo.section');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'MockFormPostInfo[section]',
			'value' => 'New post section', 'id' => 'MockFormPostInfoSection'
		]]);

		$result = $this->form->end();
		$this->assertTags($result, ['/form']);

		$result = $this->form->create(['a' => $record1, 'b' => $record2]);
		$this->assertTags($result, [
			'form' => ['action' => "{$this->base}posts", 'method' => 'post']
		]);

		$result = $this->form->text('title');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'title',
			'value' => 'New post', 'id' => 'MockFormPostTitle'
		]]);

		$result = $this->form->text('a.title');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'a[title]',
			'value' => 'New post', 'id' => 'MockFormPostTitle'
		]]);

		$result = $this->form->text('body');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'body',
			'value' => 'New post body', 'id' => 'MockFormPostBody'
		]]);

		$result = $this->form->text('b.section');
		$this->assertTags($result, ['input' => [
			'type' => 'text', 'name' => 'b[section]',
			'value' => 'New post section', 'id' => 'MockFormPostInfoSection'
		]]);

		$result = $this->form->end();
		$this->assertTags($result, ['/form']);
	}

	public function testFormErrorMultipleBindings() {
		$record1 = new Record(['model' => $this->_model, 'data' => [
			'author_id' => '2',
			'title' => 'New post',
			'body' => 'New post body'
		]]);
		$record2 = new Record(['model' => $this->_model2, 'data' => [
			'section' => 'New post section',
			'notes' => 'New post notes'
		]]);

		$record1->errors(['title' => 'Not a cool title']);
		$record2->errors(['section' => 'Not a cool section']);

		$this->form->create(compact('record1', 'record2'));

		$result = $this->form->error('title');
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Not a cool title', '/div'
		]);

		$result = $this->form->error('body');
		$this->assertEmpty($result);

		$result = $this->form->error('record1.title');
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Not a cool title', '/div'
		]);

		$result = $this->form->error('record2.section');
		$this->assertTags($result, [
			'div' => ['class' => 'error'], 'Not a cool section', '/div'
		]);
	}

	public function testBindingByName() {
		$post = new Record(['model' => $this->_model, 'data' => [
			'author_id' => '2',
			'title' => 'New post',
			'body' => 'New post body'
		]]);
		$info = new Record(['model' => $this->_model2, 'data' => [
			'section' => 'New post section',
			'notes' => 'New post notes'
		]]);

		$this->form->create(compact('post', 'info'));
		$this->assertEqual($post, $this->form->binding('post'));
		$this->assertEqual($info, $this->form->binding('info'));
	}

	public function testRespondsTo() {
		$this->assertTrue($this->form->respondsTo('foobarbaz'));
		$this->assertFalse($this->form->respondsTo(0));
	}

}

?>