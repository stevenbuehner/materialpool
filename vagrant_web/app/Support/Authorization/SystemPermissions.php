<?php

namespace App\Support\Authorization;

final class SystemPermissions {
	public const MATERIALS_CREATE = 'materials.create';
	public const MATERIALS_VIEW_ALL = 'materials.view-all';
	public const MATERIALS_UPDATE_OWN = 'materials.update-own';
	public const MATERIALS_UPDATE_ALL = 'materials.update-all';
	public const MATERIALS_UPDATE_METADATA_OWN = 'materials.update-metadata-own';
	public const MATERIALS_UPDATE_METADATA_ALL = 'materials.update-metadata-all';
	public const MATERIALS_DELETE_OWN = 'materials.delete-own';
	public const MATERIALS_DELETE_ALL = 'materials.delete-all';
	public const RESOURCES_CREATE = 'resources.create';
	public const RESOURCES_VIEW_ALL = 'resources.view-all';
	public const RESOURCES_UPDATE_OWN = 'resources.update-own';
	public const RESOURCES_UPDATE_ALL = 'resources.update-all';
	public const RESOURCES_DELETE_OWN = 'resources.delete-own';
	public const RESOURCES_DELETE_ALL = 'resources.delete-all';
	public const KEYWORDS_MANAGE = 'keywords.manage';
	public const BUNDLES_MANAGE = 'bundles.manage';
	public const SYSTEM_SHUTDOWN = 'system.shutdown';

	public const DEFAULT_GROUP = 'Standardnutzer';

	public static function all(): array {
		return array_merge(...array_values(self::grouped()));
	}

	public static function grouped(): array {
		return [
			'materials' => [
				self::MATERIALS_CREATE,
				self::MATERIALS_VIEW_ALL,
				self::MATERIALS_UPDATE_OWN,
				self::MATERIALS_UPDATE_ALL,
				self::MATERIALS_UPDATE_METADATA_OWN,
				self::MATERIALS_UPDATE_METADATA_ALL,
				self::MATERIALS_DELETE_OWN,
				self::MATERIALS_DELETE_ALL,
			],
			'resources' => [
				self::RESOURCES_CREATE,
				self::RESOURCES_VIEW_ALL,
				self::RESOURCES_UPDATE_OWN,
				self::RESOURCES_UPDATE_ALL,
				self::RESOURCES_DELETE_OWN,
				self::RESOURCES_DELETE_ALL,
			],
			'keywords' => [self::KEYWORDS_MANAGE],
			'bundles' => [self::BUNDLES_MANAGE],
			'system' => [self::SYSTEM_SHUTDOWN],
		];
	}

	public static function defaultGroup(): array {
		return [
			self::MATERIALS_CREATE,
			self::MATERIALS_UPDATE_OWN,
			self::MATERIALS_UPDATE_METADATA_OWN,
			self::MATERIALS_DELETE_OWN,
			self::RESOURCES_CREATE,
			self::RESOURCES_UPDATE_OWN,
			self::RESOURCES_DELETE_OWN,
		];
	}

	private function __construct() {
	}
}
