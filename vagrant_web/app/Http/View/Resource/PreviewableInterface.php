<?php
/**
 * This file was created by  steven
 * Created: 13.03.17 19:09
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\View\Resource;


interface PreviewableInterface {

	/**
	 * Returns false if no Preview is available.
	 * If no preview exists this function will generate the preview.
	 *
	 * @return bool
	 */
	public function isPreviewable();

	/**
	 * Returns the public URL to the previewimage
	 * isPreviewable() will be called before this function is called.
	 *
	 * @return string
	 */
	public function getPreviewImageUrl();

	/**
	 * Returns the blade template-name that is used to generate a HTML Preview
	 *
	 * @return string
	 */
	public function getPreviewTemplateName();
}