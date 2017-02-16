<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MaterialTyp
 *
 * @ORM\Table(name="material_typ")
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\MaterialTypRepository")
 */
class MaterialType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\Column(name="leftParent", type="integer")
     */
    private $leftParent;

    /**
     * @var int
     *
     * @ORM\Column(name="rightParent", type="integer")
     */
    private $rightParent;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return MaterialType
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return MaterialType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set leftParent
     *
     * @param integer $leftParent
     *
     * @return MaterialType
     */
    public function setLeftParent($leftParent)
    {
        $this->leftParent = $leftParent;

        return $this;
    }

    /**
     * Get leftParent
     *
     * @return int
     */
    public function getLeftParent()
    {
        return $this->leftParent;
    }

    /**
     * Set rightParent
     *
     * @param integer $rightParent
     *
     * @return MaterialType
     */
    public function setRightParent($rightParent)
    {
        $this->rightParent = $rightParent;

        return $this;
    }

    /**
     * Get rightParent
     *
     * @return int
     */
    public function getRightParent()
    {
        return $this->rightParent;
    }
}

