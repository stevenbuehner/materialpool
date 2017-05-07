<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 30.06.16
 * Time: 22:28
 */

namespace Modules\MaterialGrabber\GrabberTemplates\Helper;

use Modules\MaterialGrabber\Entities\Link;

class Md5Helper {

	public function __construct() {
	}

	/**
	 * @param Link $link
	 * @return Link
	 */
	public function insertMd5(Link $link) {

		if (!$link->isIndex()) {
			$path = $link->getFilePath();

			if (file_exists($path)) {
				$md5 = md5_file($path);
				$link->setMd5Cache($md5);
			}
		}

		return $link;
	}
}