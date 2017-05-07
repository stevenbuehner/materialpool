<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="config_value", indexes={@ORM\Index(name="configvalue_index_id",
 *     columns={"id"}),@ORM\Index(name="configvalue_index_key", columns={"id", "name"})})
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\ConfigRepository")
 */
class ConfigValue {
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	protected $name;

	/**
	 * @var \stdClass
	 *
	 * @ORM\Column(name="value", type="object", nullable=true)
	 */
	protected $value;

	/**
	 * @var GrabberConf
	 *
	 * @ORM\ManyToOne(targetEntity="GrabberConf", inversedBy="configValues", cascade={"persist"})
	 */
	protected $grabber;


	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return ConfigValue
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * Get value
	 *
	 * @return \stdClass
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set value
	 *
	 * @param \stdClass $value
	 *
	 * @return ConfigValue
	 */
	public function setValue($value) {
		$this->value = $value;

		return $this;
	}

	/**
	 * @return GrabberConf
	 */
	public function getGrabber() {
		return $this->grabber;
	}

	/**
	 * @param GrabberConf $grabberId
	 * @return ConfigValue|null
	 */
	public function setGrabber(GrabberConf $grabber) {
		$this->grabber = $grabber;

		return $this;
	}


}

