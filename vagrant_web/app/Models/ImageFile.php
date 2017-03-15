<?php

namespace App\Models;

use App\Http\View\Resource\PreviewableInterface;

class ImageFile extends File implements PreviewableInterface {

	protected static $singleTableType = 'image';

	/**
	 * Returns false if no Preview is available.
	 * If no preview exists this function will generate the preview.
	 *
	 * @return bool
	 */
	public function isPreviewable() {
		if (!empty($this->local_path) && file_exists($this->local_path)) {
			return TRUE;
		} else if (!empty($this->remote_path)) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Returns the public URL to the previewimage
	 * isPreviewable() will be called before this function is called.
	 *
	 * @return string
	 */
	public function getPreviewImageUrl() {
		if (!empty($this->local_path) && file_exists($this->local_path)) {
			// FIXME: This won't work
			return $this->local_path;
		} else if (!empty($this->remote_path)) {
			// FIXME: This won't work
			return $this->remote_path;
		}
	}

	/**
	 * Returns the blade template-name that is used to generate a HTML Preview
	 *
	 * @return string
	 */
	public function getPreviewTemplateName() {
		return 'resource.preview.image';
	}
}
