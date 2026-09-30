<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Queue Connection Name
	|--------------------------------------------------------------------------
	|
	| Laravel's queue API supports an assortment of back-ends via a single
	| API, giving you convenient access to each back-end using the same
	| syntax for every one. Here you may define a default connection.
	|
	*/

	'default' => env('QUEUE_CONNECTION', 'sync'),
	'prioritized_background_enabled' => (bool)env('PRIORITIZED_BACKGROUND_QUEUE', FALSE),

	/*
	|--------------------------------------------------------------------------
	| Queue Connections
	|--------------------------------------------------------------------------
	|
	| Here you may configure the connection information for each server that
	| is used by your application. A default configuration has been added
	| for each back-end shipped with Laravel. You are free to add more.
	|
	| Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
	|
	*/

	'connections' => [

		'sync' => [
			'driver'       => 'sync',
			'after_commit' => false,
		],

		'database' => [
			'driver'      => 'database',
			'table'       => 'jobs',
			'queue'       => 'default',
			// Must remain longer than the Supervisor worker timeout (120 seconds).
			'retry_after' => env('QUEUE_RETRY_AFTER', 150),
			'after_commit' => false,
		],

		// Prepared for bounded context-search jobs; no worker may consume it before step 3.
		'context_search' => [
			'driver' => 'database',
			'table' => 'jobs',
			'queue' => 'context-search-extraction',
			'retry_after' => (int) env('CONTEXT_SEARCH_QUEUE_RETRY_AFTER', 600),
			'after_commit' => false,
		],

		/*
        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => 'your-public-key',
            'secret' => 'your-secret-key',
            'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
            'queue' => 'your-queue-name',
            'region' => 'us-east-1',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
        ],
		*/
	],

	/*
	|--------------------------------------------------------------------------
	| Failed Queue Jobs
	|--------------------------------------------------------------------------
	|
	| These options configure the behavior of failed queue job logging so you
	| can control which database and table are used to store the jobs that
	| have failed. You may change them to any database / table you wish.
	|
	*/

	'failed' => [
		'database' => env('DB_CONNECTION', 'mysql'),
		'table'    => 'failed_jobs',
		'driver'   => 'database-uuids',
	],

];
