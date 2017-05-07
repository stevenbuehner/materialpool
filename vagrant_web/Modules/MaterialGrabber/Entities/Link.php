<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MaterialPoolSyncBundle\Entity\LinkResourceSync;
use MaterialPoolSyncBundle\Entity\MaterialCollectionSync;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Link
 *
 * @ORM\Table(name="link",
 *     indexes={
 *          @ORM\Index(name="link_id", columns={"id"})
 *      })
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\LinkRepository")
 */
class Link {

	/* When changing stati, also change setStatus()*/
	static $STATUS_UNKNOWN              = 0;
	static $STATUS_WAITING              = 1;
	static $STATUS_FINISHED_SUCCESSFULL = 4;
	static $STATUS_FINSIHED_WITH_ERRORS = 5;
	static $STATUS_DELETED              = 6;


	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var Link
	 *
	 * @ORM\ManyToOne(targetEntity="Modules\MaterialGrabber\Entities\Link", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
	 */
	protected $parent;

	/**
	 * @var GrabberConf
	 *
	 * @ORM\ManyToOne(targetEntity="Modules\MaterialGrabber\Entities\GrabberConf", inversedBy="links")
	 */
	protected $grabber;

	/**
	 * @var LinkResourceSync
	 *
	 * @ORM\OneToOne(targetEntity="MaterialPoolSyncBundle\Entity\LinkResourceSync", mappedBy="link", cascade={"remove"})
	 */
	protected $syncState;

	/**
	 * @var int
	 * @ORM\Column(name="priority", type="integer", nullable=false)
	 */
	protected $priority;

	/**
	 * One Category has Many Categories.
	 * @ORM\OneToMany(targetEntity="Modules\MaterialGrabber\Entities\Link", mappedBy="parent")
	 */
	protected $children;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="url", type="text")
	 */
	protected $url;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="filePath", type="text", nullable=true)
	 */
	protected $filePath;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="isIndex", type="boolean")
	 */
	protected $index;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="options", type="array")
	 */
	protected $options;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="status", type="smallint")
	 */
	protected $status;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="lastUpdate", type="datetime", nullable=true)
	 */
	protected $lastUpdate;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="md5Cache", type="string", length=128, nullable=true)
	 */
	protected $md5Cache;

	/**
	 * @var ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="MaterialPoolSyncBundle\Entity\MaterialCollectionSync", mappedBy="link", cascade={"remove"})
	 *
	 */
	protected $materialSyncs;

	/**
	 * @ORM\OneToOne(targetEntity="Material", mappedBy="link", cascade={"persist", "detach", "merge", "remove", "refresh"})
	 * @ORM\JoinColumn(name="material", nullable=true)
	 */
	protected $material;

	public function __construct() {
		$this->setStatus(self::$STATUS_UNKNOWN);
		$this->setOptions([]);
		$this->setPriority(10);
		$this->children      = new ArrayCollection();
		$this->materialSyncs = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Set url
	 *
	 * @param string $url
	 *
	 * @return Link
	 */
	public function setUrl($url) {
		$this->url = $url;

		return $this;
	}

	/**
	 * Get options
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Set options
	 *
	 * @param array $options
	 *
	 * @return Link
	 */
	public function setOptions($options) {
		$this->options = $options;

		return $this;
	}

	/**
	 * Get status
	 *
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Set status
	 *
	 * @param integer $status
	 * @throws InvalidArgumentException
	 *
	 * @return Link
	 */
	public function setStatus($status) {

		switch ($status) {
			case self::$STATUS_UNKNOWN :
			case self::$STATUS_WAITING :
			case self::$STATUS_FINISHED_SUCCESSFULL :
			case self::$STATUS_FINSIHED_WITH_ERRORS :
			case self::$STATUS_DELETED :
				break;
			default:
				throw new InvalidArgumentException();
		}

		$this->status = $status;
		$this->setLastUpdate(new \DateTime('now'));

		return $this;
	}

	/**
	 * Get lastUpdate
	 *
	 * @return \DateTime
	 */
	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	/**
	 * Set lastUpdate
	 *
	 * @param \DateTime $lastUpdate
	 *
	 * @return Link
	 */
	public function setLastUpdate($lastUpdate) {
		$this->lastUpdate = $lastUpdate;

		return $this;
	}

	/**
	 * Get md5Cache
	 *
	 * @return string
	 */
	public function getMd5Cache() {
		return $this->md5Cache;
	}

	/**
	 * Set md5Cache
	 *
	 * @param string $md5Cache
	 *
	 * @return Link
	 */
	public function setMd5Cache($md5Cache) {
		$this->md5Cache = $md5Cache;

		return $this;
	}

	/**
	 * Get filePath
	 *
	 * @return string
	 */
	public function getFilePath() {
		return $this->filePath;
	}

	/**
	 * Set filePath
	 *
	 * @param string $filePath
	 *
	 * @return Link
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;

		return $this;
	}

	/**
	 * Get isIndex
	 *
	 * @return boolean
	 */
	public function isIndex() {
		return $this->index;
	}

	/**
	 * Set isIndex
	 *
	 * @param boolean $index
	 *
	 * @return Link
	 */
	public function setIndex($index) {
		$this->index = $index;

		return $this;
	}

	/**
	 * Get material
	 *
	 * @return \Modules\MaterialGrabber\Entities\Material
	 */
	public function getMaterial() {
		return $this->material;
	}

	/**
	 * Set material
	 *
	 * @param \Modules\MaterialGrabber\Entities\Material $material
	 *
	 * @return Link
	 */
	public function setMaterial(\Modules\MaterialGrabber\Entities\Material $material = NULL) {
		$this->material = $material;

		return $this;
	}

	/**
	 * @return GrabberConf
	 */
	public function getGrabber() {
		return $this->grabber;
	}

	/**
	 * @param GrabberConf $grabber
	 */
	public function setGrabber($grabber) {
		$this->grabber = $grabber;
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @param int $priority
	 */
	public function setPriority($priority) {
		$this->priority = $priority;
	}

	/**
	 * @return Link
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param Link $parent
	 */
	public function setParent($parent) {
		$this->parent = $parent;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getChildren() {
		return $this->children;
	}

	public function __toString() {
		$string = "ID:{$this->id}, parent: (";
		$string .= ($this->parent instanceof Link) ? $this->parent->__toString() : 'NULL';
		$string .= ')';

		$string .= ', material: (';
		$string .= ($this->material instanceof Material) ? $this->material->__toString() : 'NULL';
		$string .= ')';

		$string .= ', children: (';
		foreach ($this->children as $child) {
			$string .= $child->getId() . ', ';
		}
		$string .= ')';

		return $string;
	}

	/**
	 * @return LinkResourceSync
	 */
	public function getSyncState() {
		return $this->syncState;
	}


	/**
	 * @param LinkResourceSync $syncState
	 */
	public function setSyncState($syncState) {
		$this->syncState = $syncState;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getMaterialSyncs() {
		return $this->materialSyncs;
	}

	/**
	 * @param ArrayCollection $materialSyncs
	 */
	public function setMaterialSyncs($materialSyncs) {
		$this->materialSyncs = $materialSyncs;
	}

	public function addMaterialSync(MaterialCollectionSync $materialCollectionSync) {
		if (!$this->materialSyncs->contains($materialCollectionSync)) {
			$this->materialSyncs->add($materialCollectionSync);
		}

		return $this;
	}

	public function removeMaterialSync(MaterialCollectionSync $materialCollectionSync) {
		$this->materialSyncs->removeElement($materialCollectionSync);

		return $this;
	}


}
