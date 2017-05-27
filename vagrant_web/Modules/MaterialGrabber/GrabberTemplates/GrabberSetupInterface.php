<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 25.06.16
 * Time: 15:47
 */

namespace Modules\MaterialGrabber\GrabberTemplates;


use Modules\MaterialGrabber\Entities\GrabberConfig;

interface GrabberSetupInterface {

	/**
	 * @return AbstractGrabberConfig
	 */
	public function generateGrabberConfigFromStoredConfig(GrabberConfig $grabberConf);

	/**
	 * @return AbstractGrabberConfig
	 */
	public function generateGrabberConfigFromNoConfig();

	/**
	 * @return AbstractGrabber
	 */
	public function generateGrabber(AbstractGrabberConfig $grabberConf);

}