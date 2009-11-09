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
	
}
?>