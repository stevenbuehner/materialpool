<?php

return [

	'exif' => [
		'keywords' => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~^(PDF|CorelDRAW).+$~i',
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
					'~^\s*Adobe\s.*$~i',
					'~^PDF.+$~i',
				],
				'values'   => ['user', 'unknown', 'Safari']
			],
		]
	]
];