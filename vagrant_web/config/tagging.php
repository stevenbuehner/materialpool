<?php

return [

	'exif' => [
		'keywords' => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~^PDF.+$~i',
					'~^[ \(\)\[\]\.\-\+\*\#\:\;\,]+$~',
				],
				'values'   => []
			],
		],
		'author'   => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~unknown|nobody|niemand~i',
					'~^[ ]*Adobe .*CS.*$~i',
					'~^PDF.+$~i',
				],
				'values'   => ['user', 'unknown', 'Safari']
			],
		]
	]
];