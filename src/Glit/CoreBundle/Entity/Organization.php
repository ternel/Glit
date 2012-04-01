<?php
namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Glit\CoreBundle\Entity\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="organizations")
 */
class Organization extends Account {

    /**
     * @ORM\Column(type="string", length="50")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Glit\UserBundle\Entity\User", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="organizations_users",
     *      joinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true)}
     *      )
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $users;

    public function getType() {
        return 'organization';
    }

    /**
     * Add users
     *
     * @param Glit\UserBundle\Entity\User $users
     */
    public function addUser(\Glit\UserBundle\Entity\User $users) {
        $this->users[] = $users;
    }

    /**
     * Get users
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getUsers() {
        return $this->users;
    }

    public function __construct() {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
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
}