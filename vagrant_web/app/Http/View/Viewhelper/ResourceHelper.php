<?php
/**
 * This file was created by  steven
 * Created: 13.03.17 19:01
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\View\Viewhelper;


use App\Http\View\Resource\PreviewableInterface;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\View;

class ResourceHelper {

	public static function previewUrl(Resource $resource) {

		if ($resource instanceof PreviewableInterface && $resource->isPreviewable() === TRUE) {
			return $resource->getPreviewImageUrl();
		}
	}

	public static function firstResourcePreviewHtml(Collection $resourceCollection) {
		foreach ($resourceCollection as $r) {
			if ($r instanceof PreviewableInterface && $r->isPreviewable() === TRUE) {
				return self::previewHtml($r);
			}
		}

		return self::noPreviewFoundHtml();
	}

	public static function previewHtml(Resource $resource = NULL) {
		if ($resource !== NULL && $resource instanceof PreviewableInterface && $resource->isPreviewable() === TRUE) {
			$tempName = $resource->getPreviewTemplateName();

			return View::make($tempName)->with(['resource' => $resource])->render();
		}

		return self::noPreviewFoundHtml();
	}

	protected static function noPreviewFoundHtml() {
		return View::make('resource.preview.default')->render();
	}
}