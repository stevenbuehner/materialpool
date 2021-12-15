<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default filesystem disk that should be used
	| by the framework. The "local" disk, as well as a variety of cloud
	| based disks are available to your application. Just store away!
	|
	*/

	'default' => 'local',

	/*
	|--------------------------------------------------------------------------
	| Default Cloud Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Many applications store files both locally and in the cloud. For this
	| reason, you may specify a default "cloud" driver here. This driver
	| will be bound as the Cloud disk implementation in the container.
	|
	*/

	'cloud' => 's3',

	/*
	|--------------------------------------------------------------------------
	| Filesystem Disks
	|--------------------------------------------------------------------------
	|
	| Here you may configure as many filesystem "disks" as you wish, and you
	| may even configure multiple disks of the same driver. Defaults have
	| been setup for each driver as an example of the required options.
	|
	| Supported Drivers: "local", "ftp", "s3", "rackspace"
	|
	*/

	'disks' => [

		'local' => [
			'driver' => 'local',
			'root'   => storage_path('app'),
		],

		'local_tmp' => [
			'driver' => 'local',
			'root'   => storage_path('tmp'),
		],

		'public' => [
			'driver'     => 'local',
			'root'       => storage_path('app/public'),
			'url'        => env('APP_URL') . '/storage',
			'visibility' => 'public',
		],

		'resources' => [
			'driver'     => 'local',
			'root'       => storage_path('app/resources'),
			'url'        => env('APP_URL') . '/resources',
			'visibility' => 'private',
		],

		'archive' => [
			'driver'     => 'local',
			'root'       => storage_path('app/archived'),
			'visibility' => 'private',
		],

		'bundles' => [
			'driver'     => 'local',
			'root'       => storage_path('app/bundles'),
			'url'        => env('APP_URL') . '/bundle-resources',
			'visibility' => 'private'
		],

		'backup' => [
			'driver' => 'local',
			'root'   => env('BACKUP_PATH', base_path('../backups')) ,
		],

		'testfiles' => [
			'driver'     => 'local',
			'root'       => base_path('tests/testFiles'),
			'visibility' => 'private'
		],

		's3' => [
			'driver' => 's3',
			'key'    => env('AWS_KEY'),
			'secret' => env('AWS_SECRET'),
			'region' => env('AWS_REGION'),
			'bucket' => env('AWS_BUCKET'),
		],

	],


	'uploads' => [
		'driver' => 'local',
		'root'   => public_path('uploads'),
	],

];
