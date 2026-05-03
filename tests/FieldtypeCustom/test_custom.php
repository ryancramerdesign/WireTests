<?php namespace ProcessWire;

return [
	'first_name' => [
		'type' => 'text',
		'label' => 'First name',
	],
	'age' => [
		'type' => 'integer',
		'label' => 'Age',
	],
	'color' => [
		'type' => 'select',
		'label' => 'Favorite color',
		'options' => ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'],
	],
];
