<?php

namespace Modules\MaterialGrabber\GrabberTemplates\Helper;

interface FileHashHelper {

	/**
	 * Returns the hash for the given local file path
	 *
	 * @param $absoluteLocalPath
	 * @return string
	 */
	public function generateLocalFileHash($absoluteLocalPath);


	/**
	 * Function to generate hash from a Laravel File Object (Stream?)
	 * TODO: Not implemented yet
	 *
	 * @return string
	 */
	public function generateFileHash();
}

