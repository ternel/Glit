<?php

namespace Glit\ProjectsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;

/**
 * Glit\ProjectsBundle\Entity\Project
 *
 * @ORM\Table(name="projects", uniqueConstraints={@ORM\UniqueConstraint(name="projects_path_unq", columns={"account_id", "path"})})
 * @ORM\Entity
 * @DoctrineAssert\UniqueEntity(fields={"owner", "path"}, message="glit.project.path.allreadyexist")
 */
class Project {
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    private $name;

    /**
     * @var string $path
     *
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    /**
     * @var text $description
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var boolean $isPrivate
     *
     * @ORM\Column(name="is_private", type="boolean")
     */
    private $isPrivate;

    /**
     * @var string $defaultBranch
     *
     * @ORM\Column(name="default_branch", type="string", length=255)
     */
    private $defaultBranch;

    /**
     * @ORM\ManyToOne(targetEntity="\Glit\CoreBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id")
     * @var \Glit\CoreBundle\Entity\Account
     */
    private $owner;

    public function __construct(\Glit\CoreBundle\Entity\Account $account) {
        $this->setOwner($account);
        $this->setDefaultBranch('master');
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
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
     * Set path
     *
     * @param string $path
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set isPrivate
     *
     * @param boolean $isPrivate
     */
    public function setIsPrivate($isPrivate) {
        $this->isPrivate = $isPrivate;
    }

    /**
     * Get isPrivate
     *
     * @return boolean
     */
    public function getIsPrivate() {
        return $this->isPrivate;
    }

    /**
     * Set defaultBranch
     *
     * @param string $defaultBranch
     */
    public function setDefaultBranch($defaultBranch) {
        $this->defaultBranch = $defaultBranch;
    }

    /**
     * Get defaultBranch
     *
     * @return string
     */
    public function getDefaultBranch() {
        return $this->defaultBranch;
    }

    /**
     * Set owner
     *
     * @param \Glit\CoreBundle\Entity\Account $owner
     */
    public function setOwner(\Glit\CoreBundle\Entity\Account $owner) {
        $this->owner = $owner;
    }

    /**
     * Get owner
     *
     * @return \Glit\CoreBundle\Entity\Account
     */
    public function getOwner() {
        return $this->owner;
    }

    public function getFullPath() {
        return $this->getOwner()->getUniqueName() . '/' . $this->getPath();
    }
}