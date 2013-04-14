<?php
return array(
	'id' => '1',
	'gallery_id' => NULL,
	'image' => 'someimage.png',
	'title' => 'Image1 Title',
	'created' => NULL,
	'modified' => NULL,
	'images_tags' => array(
		4 => array(
			'id' => '4',
			'image_id' => '1',
			'tag_id' => '1',
		),
		5 => array(
			'id' => '5',
			'image_id' => '1',
			'tag_id' => '3',
		),
		6 => array(
			'id' => '6',
			'image_id' => '1',
			'tag_id' => '6',
		),
	),
	'tags' => array(
		1 => array(
			'id' => '1',
			'name' => 'High Tech',
			'author_id' => '6',
		),
		3 => array(
			'id' => '3',
			'name' => 'Computer',
			'author_id' => '6',
		),
		6 => array(
			'id' => '6',
			'name' => 'City',
			'author_id' => '2',
		),
	),
);
?>