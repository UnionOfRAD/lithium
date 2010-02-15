<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helper;

use \lithium\action\Request;
use \lithium\net\http\Router;
use \lithium\data\model\Record;
use \lithium\template\helper\Form;
use \lithium\tests\mocks\template\helper\MockFormPost;
use \lithium\tests\mocks\template\helper\MockFormRenderer;

class FormTest extends \lithium\test\Unit {

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

	protected $_routes = array();

	/**
	 * Initialize test by creating a new object instance with a default context.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->_routes = Router::get();
		Router::connect(null);
		Router::connect('/{:controller}/{:action}/{:id}.{:type}', array('id' => null));
		Router::connect('/{:controller}/{:action}/{:args}');

		$this->context = new MockFormRenderer();
		$this->form = new Form(array('context' => $this->context));

		$base = trim($this->context->request()->env('base'), '/') . '/';
		$this->base = ($base == '/') ? $base : '/' . $base;
	}

	public function tearDown() {
		Router::connect(null);

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testFormCreation() {
		$result = $this->form->create();
		$this->assertTags($result, array(
			'form' => array('action' => "{$this->base}posts/add", 'method' => 'POST')
		));

		$result = $this->form->create(null, array('method' => 'get'));
		$this->assertTags($result, array(
			'form' => array('action' => "{$this->base}posts/add", 'method' => 'GET')
		));

		$result = $this->form->create(null, array('type' => 'file'));
		$this->assertTags($result, array('form' => array(
			'action' => "{$this->base}posts/add", 'method' => 'POST', 'enctype' => 'multipart/form-data'
		)));

		$result = $this->form->create(null, array('method' => 'GET', 'type' => 'file'));
		$this->assertTags($result, array('form' => array(
			'action' => "{$this->base}posts/add", 'method' => 'POST', 'enctype' => 'multipart/form-data'
		)));
	}

	/**
	 * Tests creating forms with non-browser compatible HTTP methods, required for REST interfaces.
	 *
	 * @return void
	 */
	public function testRestFormCreation() {
		$result = $this->form->create(null, array('action' => 'delete', 'method' => 'delete'));
		$this->assertTags($result, array('form' => array(
			'action' => "{$this->base}posts/delete", 'method' => 'DELETE'
		)));

		$result = $this->form->create(null, array('method' => 'put', 'type' => 'file'));
		$this->assertTags($result, array('form' => array(
			'action' => "{$this->base}posts/add", 'method' => 'PUT', 'enctype' => 'multipart/form-data'
		)));
	}

	public function testFormCreationWithBinding() {
		$record = new Record(array(
			'model' => 'lithium\tests\mocks\template\helper\MockFormPost',
			'data' => array(
				'id' => '5',
				'author_id' => '2',
				'title' => 'This is a saved post',
				'body' => 'This is the body of the saved post'
			)
		));

		$result = $this->form->create($record);
	}

	public function testFormDataBinding() {
		$record = new Record(array(
			'model' => 'lithium\tests\mocks\template\helper\MockFormPost',
			'data' => array(
				'id' => '5',
				'author_id' => '2',
				'title' => 'This is a saved post',
				'body' => 'This is the body of the saved post'
			)
		));

		$result = $this->form->create($record);
		$this->assertTags($result, array(
			'form' => array('action' => "{$this->base}posts/add", 'method' => 'POST')
		));

		$result = $this->form->text('title');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'title', 'value' => 'This is a saved post'
		)));

		$result = $this->form->end();
		$this->assertTags($result, array('/form'));

		$result = $this->form->text('title');
		$this->assertTags($result, array('input' => array('type' => 'text', 'name' => 'title')));
	}

	public function testTextBox() {
		$result = $this->form->text('foo');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo'
		)));
	}

	public function testElementsWithDefaultConfiguration() {
		$this->form = new Form(array(
			'context' => new MockFormRenderer(), 'base' => array('class' => 'editable')
		));

		$result = $this->form->text('foo');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'class' => 'editable'
		)));

		$this->form->config(array('base' => array('maxlength' => 255)));

		$result = $this->form->text('foo');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'class' => 'editable', 'maxlength' => '255'
		)));

		$this->form->config(array('text' => array('class' => 'locked')));

		$result = $this->form->text('foo');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'class' => 'locked', 'maxlength' => '255'
		)));

		$result = $this->form->config();
		$expected = array(
			'base' => array('class' => 'editable', 'maxlength' => 255),
			'text' => array('class' => 'locked'),
			'textarea' => array(),
			'templates' => array('create' => 'form', 'end' => 'form-end')
		);
		$this->assertEqual($expected, $result);
	}

	public function testFormElementWithDefaultValue() {
		$result = $this->form->text('foo', array('default' => 'Message here'));

		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'value' => 'Message here'
		)));

		$result = $this->form->text('foo', array(
			'default' => 'Message here', 'value' => 'My Name Is Jonas'
		));
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'value' => 'My Name Is Jonas'
		)));

		$result = $this->form->text('foo', array('value' => 'My Name Is Jonas'));
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo', 'value' => 'My Name Is Jonas'
		)));
	}

	public function testLabelGeneration() {
		$result = $this->form->label('next', 'Enter the next value >>');
		$this->assertTags($result, array(
			'label' => array('for' => 'next'),
			'Enter the next value &gt;&gt;',
			'/label'
		));

		$result = $this->form->label('user_name');
		$this->assertTags($result, array(
			'label' => array('for' => 'user_name'),
			'User Name',
			'/label'
		));
	}

	public function testSubmitGeneration() {
		$result = $this->form->submit('Continue >');
		$this->assertTags($result, array('input' => array(
			'type' => 'submit',
			'value' => 'Continue &gt;'
		)));

		$result = $this->form->submit('Continue >', array('class' => 'special'));
		$this->assertTags($result, array('input' => array(
			'type' => 'submit',
			'value' => 'Continue &gt;',
			'class' => 'special'
		)));
	}

	public function testTextareaGeneration() {
		$result = $this->form->textarea('foo', array('value' => 'some content'));
		$this->assertTags($result, array(
			'textarea' => array('name' => 'foo'),
			'some content',
			'/textarea'
		));
	}

	public function testCheckboxGeneration() {
		$result = $this->form->checkbox('foo');
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'foo')));

		$result = $this->form->checkbox('foo', array('checked' => false));
		$this->assertTags($result, array('input' => array('type' => 'checkbox', 'name' => 'foo')));

		$result = $this->form->checkbox('foo', array('checked' => true));
		$this->assertTags($result, array('input' => array(
			'type' => 'checkbox', 'name' => 'foo', 'checked' => 'checked'
		)));

		$result = $this->form->checkbox('foo', array('value' => true));
		$this->assertTags($result, array('input' => array(
			'type' => 'checkbox', 'name' => 'foo', 'checked' => 'checked'
		)));
	}

	public function testSelectGeneration() {
		$result = $this->form->select('foo');
		$this->assertTags($result, array('select' => array('name' => 'foo'), '/select'));

		$result = $this->form->select(
			'colors',
			array('r' => 'red', 'g' => 'green', 'b' => 'blue'),
			array('id' => 'Colors', 'value' => 'g')
		);

		$this->assertTags($result, array(
			'select' => array('name' => 'colors', 'id' => 'Colors'),
			array('option' => array('value' => 'r')),
			'red',
			'/option',
			array('option' => array('value' => 'g', 'selected' => 'selected')),
			'green',
			'/option',
			array('option' => array('value' => 'b')),
			'blue',
			'/option',
			'/select'
		));
	}

	public function testSelectWithEmptyOption() {
		$result = $this->form->select('numbers', array('1' => 'first', '2' => 'second'), array(
			'empty' => true
		));

		$this->assertTags($result, array(
			'select' => array('name' => 'numbers'),
			array('option' => array('value' => '', 'selected' => 'selected')),
			'/option',
			array('option' => array('value' => '1')),
			'first',
			'/option',
			array('option' => array('value' => '2')),
			'second',
			'/option',
			'/select'
		));

		$result = $this->form->select('numbers', array('1' => 'first', '2' => 'second'), array(
			'empty' => '> Make a selection'
		));

		$this->assertTags($result, array(
			'select' => array('name' => 'numbers'),
			array('option' => array('value' => '', 'selected' => 'selected')),
			'&gt; Make a selection',
			'/option',
			array('option' => array('value' => '1')),
			'first',
			'/option',
			array('option' => array('value' => '2')),
			'second',
			'/option',
			'/select'
		));
	}

	public function testTemplateRemapping() {
		$result = $this->form->password('passwd');
		$this->assertTags($result, array('input' => array(
			'type' => 'password', 'name' => 'passwd'
		)));

		$this->form->config(array('templates' => array('password' => 'text')));

		$result = $this->form->password('passwd');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'passwd'
		)));
	}

	public function testMultiSelect() {
		$expected = array(
			'select' => array('name' => 'numbers[]', 'multiple' => 'multiple'),
			array('option' => array('value' => '', 'selected' => 'selected')),
			'&gt; Make a selection',
			'/option',
			array('option' => array('value' => '1')),
			'first',
			'/option',
			array('option' => array('value' => '2')),
			'second',
			'/option',
			'/select'
		);
		$result = $this->form->select('numbers', array('1' => 'first', '2' => 'second'), array(
			'empty' => '> Make a selection',
			'multiple' => true
		));
		$this->assertTags($result, $expected);

		$expected = array(
			'select' => array('name' => 'numbers[]', 'multiple' => 'multiple', 'size' => 5),
			array('option' => array('value' => '1')),
			'first',
			'/option',
			array('option' => array('value' => '2')),
			'second',
			'/option',
			'/select'
		);
		$result = $this->form->select('numbers', array('1' => 'first', '2' => 'second'), array(
			'multiple' => true,
			'size' => 5
		));
		$this->assertTags($result, $expected);
	}

	public function testMultiselected() {
		$expected = array(
			'select' => array('name' => 'numbers[]', 'multiple' => 'multiple'),
			array('option' => array('value' => '1', 'selected' => 'selected')),
			'first',
			'/option',
			array('option' => array('value' => '2')),
			'second',
			'/option',
			array('option' => array('value' => '3', 'selected' => 'selected')),
			'third',
			'/option',
			array('option' => array('value' => '4', 'selected' => 'selected')),
			'fourth',
			'/option',
			'/select'
		);
		$result = $this->form->select('numbers', array(
			1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth'
		),array(
			'value' => array(1,3,4),
			'multiple' => true
		));
		$this->assertTags($result, $expected);
	}

	public function testFormCreateWithMoreParams() {
		$request = new Request();
		$request->params = array('controller' => 'mock', 'action' => 'test', 'args' => array('1'));
		$context = new MockFormRenderer(compact('request'));
		$form = new Form(compact('context'));

		$result = $form->create();
		$this->assertTags($result, array(
			'form' => array('action' => "{$this->base}mock/test/1", 'method' => 'POST')
		));
	}

	public function testFormCreateWithMoreParamsButSpecifiedAction() {
		$request = new Request();
		$request->params = array('controller' => 'mock', 'action' => 'test', 'args' => array('1'));
		$context = new MockFormRenderer(compact('request'));
		$form = new Form(compact('context'));

		$result = $form->create(null, array('action' => 'radness'));
		$this->assertTags($result, array(
			'form' => array('action' => "{$this->base}mock/radness", 'method' => 'POST')
		));
	}

	public function testFormField() {
		$result = $this->form->field('name');
		$this->assertTags($result, array(
			'div' => array(),
			'label' => array('for' => 'name'), 'Name', '/label',
			'input' => array('type' => 'text', 'name' => 'name'),
		));
	}

	public function testFormFieldSelect() {
		$result = $this->form->field('states', array(
			'type' => 'select', 'list' => array('CA', 'RI')
		));
		$this->assertTags($result, array(
			'div' => array(),
			'label' => array('for' => 'states'), 'States', '/label',
			'select' => array('name' => 'states'),
			array('option' => array('value' => '0', 'selected' => 'selected')),
			'CA',
			'/option',
			array('option' => array('value' => '1')),
			'RI',
			'/option',
			'/select',
		));
	}

	public function testFormErrorWithout() {
		$this->form->create(null);
		$result = $this->form->error('name');
		$this->assertTrue(is_null($result));
	}

	public function testFormErrorWithRecordAndStringError() {
		$record = new Record();
		$record->errors(array('name' => 'Please enter a name'));
		$this->form->create($record);

		$result = $this->form->error('name');
		$this->assertTags($result, array(
			'div' => array(), 'Please enter a name', '/div'
		));
	}

	public function testFormErrorWithRecordAndSpecificKey() {
		$record = new Record();
		$record->errors(array('name' => array('Please enter a name')));
		$this->form->create($record);

		$result = $this->form->error('name', 0);
		$this->assertTags($result, array(
			'div' => array(), 'Please enter a name', '/div'
		));
	}

	public function testFormFieldWithError() {
		$record = new Record();
		$record->errors(array('name' => array('Please enter a name')));
		$this->form->create($record);

		$expected = '<div><label for="name">Name</label><input type="text" name="name" />'
			. '<div>Please enter a name</div></div>';
		$result = $this->form->field('name');
		$this->assertEqual($expected, $result);
	}
}

?>