<?php

return [

	'exif' => [
		'keywords' => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i'
				],
				'values'   => []
			],
		],
		'author'   => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~unknown|nobody|niemand~i',
					'~^[ ]*Adobe .*CS.*$~i'
				],
				'values'   => []
			],
		]
	]
];