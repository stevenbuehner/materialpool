<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 30.06.16
 * Time: 22:28
 */

namespace Modules\MaterialGrabber\GrabberTemplates\Helper;

class Md5Helper implements FileHashHelper {

	/**
	 * Returns the hash for the given local file path
	 *
	 * @param $absoluteLocalPath
	 * @return string
	 */
	public function generateLocalFileHash($absoluteLocalPath) {
		if (file_exists($absoluteLocalPath)) {
			return md5_file($absoluteLocalPath);
		}

		return NULL;
	}

	/**
	 * Function to generate hash from a Laravel File Object (Stream?)
	 * TODO: Not implemented yet
	 *
	 * @return string
	 */
	public function generateFileHash() {
		// TODO: Implement generateFileHash() method.
		throw new \Exception('Not implemented yet');
	}
}