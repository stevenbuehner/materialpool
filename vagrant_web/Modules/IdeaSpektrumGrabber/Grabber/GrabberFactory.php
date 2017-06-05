<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 26.06.16
 * Time: 19:29
 */

namespace Modules\IdeaSpektrumBundle\Grabber;

use Modules\MaterialGrabber\Entities\GrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\GrabberSetupInterface;

class GrabberFactory implements GrabberSetupInterface {

	const NAME = "IdeaSpektrum";

	/** @return AbstractGrabberConfig */
	public function generateGrabberConfigFromStoredConfig(GrabberConfig $grabberConf) {
		$tempConfig = new  IdeaSpektrumGrabberConfig($grabberConf);

		return $tempConfig;
	}

	/**
	 * @return AbstractGrabberConfig
	 */
	public function generateGrabberConfigFromNoConfig() {
		$dbConf              = new GrabberConfig();
		$dbConf->author      = "Steven Bühner";
		$dbConf->description = "Grabb the IdeaSpektrum Archive www.idea.de";
		$dbConf->is_active   = FALSE;
		$dbConf->name        = self::NAME;
		$tempConfig          = new IdeaSpektrumGrabberConfig($dbConf);

		return $tempConfig;
	}


	/** @return AbstractGrabber */
	public function generateGrabber(AbstractGrabberConfig $grabberConfig) {
		$grabber = new IdeaSpektrumGrabber($grabberConfig);

		return $grabber;
	}

}