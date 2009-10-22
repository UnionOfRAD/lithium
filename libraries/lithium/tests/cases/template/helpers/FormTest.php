<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helpers;

use \lithium\http\Router;
use \lithium\template\helpers\Form;
use \lithium\template\view\Renderer;

class MyFormRenderer extends Renderer {}

class FormTest extends \lithium\test\Unit {

	/**
	 * Test object instance
	 *
	 * @var object
	 */
	public $form = null;

	protected $_routes = array();

	/**
	 * Initialize test by creating a new object instance with a default context.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->_routes = Router::get();
		Router::connect(null);
		Router::connect('/{:controller}/{:action}/{:id}.{:type}');
		Router::connect('/{:controller}/{:action}.{:type}');

		$this->form = new Form(array('context' => new MyFormRenderer()));
	}

	public function testTextBox() {
		$result = $this->form->text('foo');
		$this->assertTags($result, array('input' => array(
			'type' => 'text', 'name' => 'foo'
		)));
	}

	public function testElementsWithDefaultConfiguration() {
		$this->form = new Form(array(
			'context' => new MyFormRenderer(), 'base' => array('class' => 'editable')
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
			'templates' => array()
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
}

?>