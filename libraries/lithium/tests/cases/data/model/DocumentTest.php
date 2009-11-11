<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\Document;

class DocumentPost extends \lithium\data\Model {

	public static function find($type = 'all', $options = array()) {
		switch ($type) {
			case 'first' : {
				return new Document(array('items' =>
					array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two')					
				));
			}
			case 'all':
			default :
				return new Document(array('items' =>
					array(
						array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
						array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
						array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
					)
				));
			break;		
		}
	
	}

}

class DocumentTest extends \lithium\test\Unit {
	
	public function testFindAllAndIterate() {
	
		$document = DocumentPost::find('all');
		
		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');			
		$result = $document->current();
		$this->assertEqual($expected, $result);
		
		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $document->next();
		$this->assertEqual($expected, $result);
		
		$expected = array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three');
		$document->next();
		$result = $document->current();
		$this->assertEqual($expected, $result);
		
		$result = $document->next();
		$this->assertTrue(empty($result));
					
		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');		
		$result = $document->rewind();		
		$this->assertEqual($expected, $result);
		
	}
	
	public function testFindOne() {
		
		$document = DocumentPost::find('first');
	
		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $document->data();
		$this->assertEqual($expected, $result);
	}
	
	public function testGetFields() {	
		$document = DocumentPost::find('first');	
		
		$expected = 2;
		$result = $document->id;
		$this->assertEqual($expected, $result);
		
		$expected = 'Two';
		$result = $document->name;
		$this->assertEqual($expected, $result);
		
		$expected = 'Lorem ipsum two';
		$result = $document->content;
		$this->assertEqual($expected, $result);		
	}
	
	public function testSetField() {
		$doc = new Document();
		$doc->id = 4;
		$doc->name = 'Four';
		$doc->content = 'Lorem ipsum four';
		
		$expected = array(
			'id' => 4,
			'name' => 'Four',
			'content' => 'Lorem ipsum four'		
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	}
	
	public function testNoItems() {
		$doc = new Document(array('items' => array()));
		$result = $doc->id;
		$this->assertFalse($result);	
	}
	
	public function testWithData() {
		$doc = new Document(array('data' =>
					array(
						array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
						array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
						array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
					)
				));
			
		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $doc->current();
		$this->assertEqual($expected, $result);
		
		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $doc->next();
		$this->assertEqual($expected, $result);			
	}				
	
	public function testExplicitSet() {
		$doc = new Document();
		$doc->set('id', 4);
		$doc->set('name', 'Four');
		$doc->set('content',  'Lorem ipsum four');
		
		$expected = array(
			'id' => 4,
			'name' => 'Four',
			'content' => 'Lorem ipsum four'		
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);
	
	}
	
	public function testSetMultiple() {
		$doc = new Document();
		$doc->set(array(
						array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'),
						array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'),
						array('id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three')
					));
		$expected = array('id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one');
		$result = $doc->current();
		$this->assertEqual($expected, $result);
		
		$expected = array('id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two');
		$result = $doc->next();
		$this->assertEqual($expected, $result);	
	}
	
	public function testSetNested() {
		$doc = new Document();
		$doc->id = 123;
		$doc->type = 'father';
		$doc->set('children', array(
			array('id' => 124, 'type' => 'child', 'children' => null),
			array('id' => 125, 'type' => 'child', 'children' => null)
		));
		
		$this->assertEqual('father', $doc->type);
		
		$this->assertTrue(is_object($doc->children), 'children is not an object');
		$this->assertTrue(is_a($doc->children,'\lithium\data\model\Document'), 
			'Children is not of the type Document');
		$this->skipIf(!is_a($doc->children,'\lithium\data\model\Document'),
			'Children is not of the type Document');

		$expected = array('id' => 124, 'type' => 'child', 'children' => null);
		$result = $doc->children->current();
		$this->assertEqual($expected, $result);
		
		$expected = array('id' => 125, 'type' => 'child', 'children' => null);
		$result = $doc->children->next();
		$this->assertEqual($expected, $result);		
	}
	
	public function testRewindNoData() {
		$doc = new Document();
				
		$expected = null;
		$result = $doc->rewind();
		$this->assertEqual($expected, $result);	
		
	}
	
	public function testRewindData() {
		$doc = new Document(array(
			'items' => array(						
				array('id' => 1, 'name' => 'One'),
				array('id' => 2, 'name' => 'Two'),
				array('id' => 3, 'name' => 'Three')
			)
		));
		
		$expected = array('id' => 1, 'name' => 'One');
		$result = $doc->rewind();
		$this->assertEqual($expected, $result);	
	}
	
	public function testCreating() {
		$doc = new Document(array(
			'model' => __NAMESPACE__ .'\DocumentPost'
		));
		$expected = 'id';
		$result = DocumentPost::meta('key');
		$this->assertEqual($expected, $result);
		
		$doc->id = 3;
		$this->assertFalse($doc->exists());		
				
		$doc->invokeMethod('_update',array(12));
		
		$this->assertTrue($doc->exists());
		
		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);			
	}	
	
	public function testArrayValueNestedDocument() {
		$doc = new Document(array('model' => __NAMESPACE__ .'\DocumentPost',
			'items' => array('id' => 12, 'arr' => array('id' => 33, 'name' => 'stone'), 'name' => 'bird'),

		));

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);	

		$expected = 'bird';
		$result = $doc->name;
		$this->assertEqual($expected, $result);	

		$this->assertTrue(is_object($doc->arr), 'arr is not an object');
		$this->assertTrue(is_a($doc->arr,'\lithium\data\model\Document'), 
			'arr is not of the type Document');
		$this->skipIf(!is_a($doc->arr,'\lithium\data\model\Document'),
			'arr is not of the type Document');

		$expected = 33;
		$result = $doc->arr->id;
		$this->assertEqual($expected, $result);	

		$expected = 'stone';
		$result = $doc->arr->name;
		$this->assertEqual($expected, $result);	
	}
			
	public function testArrayValueGet() {
		$doc = new Document(array('model' => __NAMESPACE__ .'\DocumentPost',
			'items' => array('id' => 12, 'name' => 'Joe', 'sons' => array('Moe','Greg')),

		));

		$expected = 12;
		$result = $doc->id;
		$this->assertEqual($expected, $result);	

		$expected = 'Joe';
		$result = $doc->name;
		$this->assertEqual($expected, $result);	

		$this->assertTrue(is_array($doc->sons), 'arr is not an array');
		
		$expected = array('Moe','Greg');
		$result = $doc->sons;
		$this->assertEqual($expected, $result);	
	}	
	
	public function testArrayValueSet() {
		$doc = new Document();

		$doc->id = 12;
		$doc->name = 'Joe';
		$doc->sons = array('Moe','Greg');
		$doc->set('daughters', array('Susan', 'Tinkerbell'));
		
		$expected = array(
			'id' => 12,
			'name' => 'Joe',
			'sons' => array('Moe','Greg'),
			'daughters' => array('Susan', 'Tinkerbell')
		);
		$result = $doc->data();
		$this->assertEqual($expected, $result);	
	}
		

}
?>