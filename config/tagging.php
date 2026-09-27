<?php

return [

	'exif' => [
		'keywords' => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~^(PDF|CorelDRAW).+$~i',
					'~^[ \(\)\[\]\.\-\+\*\#\:\;\,]+$~',
					'~^\s*(Adobe|Microsoft)\s.*$~i',
				],
				'values'   => []
			],
		],
		'author'   => [
			'ignore' => [
				'patterns' => [
					'~.*http://.*~i',
					'~unknown|nobody|niemand~i',
					'~^\s*(Adobe|Microsoft)\s.*$~i',
					'~^PDF.+$~i',
				],
				'values'   => ['user', 'unknown', 'Safari', 'Serif Affinity']
			],
		]
	]
];