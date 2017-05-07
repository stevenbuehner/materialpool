<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 25.06.16
 * Time: 15:47
 */

namespace Modules\MaterialGrabber\GrabberTemplates;


use Modules\MaterialGrabber\Entities\GrabberConf;
use Modules\MaterialGrabber\Services\LinkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface GrabberSetupInterface {

	/**
	 * @return AbstractGrabberConfig
	 */
	public function generateGrabberConfigFromStoredConfig(GrabberConf $grabberBundle, ContainerInterface $container);

	/**
	 * @return AbstractGrabberConfig
	 */
	public function generateGrabberConfigFromNoConfig(ContainerInterface $container);

	/**
	 * @return AbstractGrabber
	 */
	public function generateGrabber(AbstractGrabberConfig $grabberConf, LinkManager $linkManager, ContainerInterface $container);

}