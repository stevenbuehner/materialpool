<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel {
	public function registerBundles() {
		$bundles = [
			new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
			new Symfony\Bundle\SecurityBundle\SecurityBundle(),
			new Symfony\Bundle\TwigBundle\TwigBundle(),
			new Symfony\Bundle\MonologBundle\MonologBundle(),
			new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
			new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
			new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
			new AppBundle\AppBundle(),
			new StevenBuehner\MaterialResourceStructureBundle\MaterialResourceStructureBundle(),

			// new FOS\RestBundle\FOSRestBundle(),
			new JMS\SerializerBundle\JMSSerializerBundle(),
			new Nelmio\CorsBundle\NelmioCorsBundle(),

			new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),

			// The admin requires some twig functions defined in the security
			// bundle, like is_granted. Register this bundle if it wasn't the case
			// already.
			// new Symfony\Bundle\SecurityBundle\SecurityBundle(),

			// These are the other bundles the SonataAdminBundle relies on
			new Sonata\CoreBundle\SonataCoreBundle(),
			new Sonata\BlockBundle\SonataBlockBundle(),
			new Knp\Bundle\MenuBundle\KnpMenuBundle(),

			// And finally, the storage and SonataAdminBundle
			new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
			new Sonata\AdminBundle\SonataAdminBundle(),
		];

		if (in_array($this->getEnvironment(), ['dev', 'test'], TRUE)) {
			$bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
			$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
			$bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
			$bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
		}

		return $bundles;
	}

	public function getCacheDir() {
		return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
	}

	public function getLogDir() {
		return dirname(__DIR__) . '/var/logs';
	}

	public function registerContainerConfiguration(LoaderInterface $loader) {
		$loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
	}

	public function getRootDir() {
		return __DIR__;
	}
}
