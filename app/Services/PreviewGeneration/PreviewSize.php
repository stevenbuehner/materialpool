<?php

namespace App\Services\PreviewGeneration;

use Intervention\Image\Size;

final class PreviewSize {
	public const SMALL = 'small';
	public const LARGE = 'large';

	public static function small(): Size {
		return new Size(
			config('app.resource.preview.smallWidth'),
			config('app.resource.preview.smallHeight')
		);
	}

	public static function constrained(?int $width, ?int $height): Size {
		$large = self::large();

		return new Size(
			min(max(1, $width ?? $large->getWidth()), $large->getWidth()),
			min(max(1, $height ?? $large->getHeight()), $large->getHeight())
		);
	}

	public static function large(): Size {
		return new Size(
			config('app.resource.preview.maxWidth'),
			config('app.resource.preview.maxHeight')
		);
	}
}
