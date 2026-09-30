<?php

namespace App\Services\PreviewGeneration;

use Intervention\Image\Size;

final class PreviewSize {
	public const SMALL = 'small';
	public const LARGE = 'large';

	public static function small(): Size {
		return new Size(
			config('app.preview.small.maxWidth'),
			config('app.preview.small.maxHeight')
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
			config('app.preview.large.maxWidth'),
			config('app.preview.large.maxHeight')
		);
	}

	public static function profile(Size $size): array {
		$small = self::small();
		$name = $size->getWidth() <= $small->getWidth() && $size->getHeight() <= $small->getHeight()
			? self::SMALL : self::LARGE;

		return config('app.preview.' . $name);
	}
}
