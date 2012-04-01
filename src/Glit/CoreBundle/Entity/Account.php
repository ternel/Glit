<?php
namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Glit\CoreBundle\Entity\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="accounts", uniqueConstraints={@ORM\UniqueConstraint(name="accounts_uniquename_unq", columns={"unique_name"})})
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user" = "Glit\UserBundle\Entity\User", "organization" = "Organization"})
 * @DoctrineAssert\UniqueEntity(fields={"uniqueName"}, message="glit.account.uniqueName.allreadyexist")
 */
abstract class Account extends BaseEntity {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length="50", name="unique_name")
     */
    private $uniqueName;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set uniqueName
     *
     * @param string $uniqueName
     */
    public function setUniqueName($uniqueName) {
        $this->uniqueName = $uniqueName;
    }

    /**
     * Get uniqueName
     *
     * @return string
     */
    public function getUniqueName() {
        return $this->uniqueName;
    }
}