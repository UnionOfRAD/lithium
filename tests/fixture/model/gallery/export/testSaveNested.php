<?php
return array (
	1 => array (
		'id' => '1',
		'name' => 'Foo Gallery',
		'active' => '1',
		'created' => NULL,
		'modified' => NULL,
		'images' => array (
			1 => array (
				'id' => '1',
				'gallery_id' => '1',
				'image' => 'someimage.png',
				'title' => 'Image1 Title',
				'created' => NULL,
				'modified' => NULL,
				'images_tags' => array (
					1 => array (
						'id' => '1',
						'image_id' => '1',
						'tag_id' => '1',
					),
					2 => array (
						'id' => '2',
						'image_id' => '1',
						'tag_id' => '2',
					),
					3 => array (
						'id' => '3',
						'image_id' => '1',
						'tag_id' => '3',
					),
				),
				'tags' => array (
					1 => array (
						'id' => '1',
						'name' => 'tag1',
						'author_id' => NULL,
					),
					2 => array (
						'id' => '2',
						'name' => 'tag2',
						'author_id' => NULL,
					),
					3 => array (
						'id' => '3',
						'name' => 'tag3',
						'author_id' => NULL,
					),
				),
			),
			2 => array (
				'id' => '2',
				'gallery_id' => '1',
				'image' => 'anotherImage.jpg',
				'title' => 'Our Vacation',
				'created' => NULL,
				'modified' => NULL,
				'images_tags' => array (
					4 => array (
						'id' => '4',
						'image_id' => '2',
						'tag_id' => '4',
					),
					5 => array (
						'id' => '5',
						'image_id' => '2',
						'tag_id' => '5',
					),
				),
				'tags' => array (
					4 => array (
						'id' => '4',
						'name' => 'tag4',
						'author_id' => NULL,
					),
					5 => array (
						'id' => '5',
						'name' => 'tag5',
						'author_id' => NULL,
					),
				),
			),
			3 => array (
				'id' => '3',
				'gallery_id' => '1',
				'image' => 'me.bmp',
				'title' => 'Me.',
				'created' => NULL,
				'modified' => NULL,
				'images_tags' => array (
				),
				'tags' => array (
				),
			),
		),
	),
);
?>