<?php
namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Glit\CoreBundle\Entity\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="accounts", uniqueConstraints={@ORM\UniqueConstraint(name="accounts_uniquename_unq", columns={"uniqueName"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user" = "Glit\UserBundle\Entity\User", "organization" = "Organization"})
 * @DoctrineAssert\UniqueEntity(fields={"uniqueName"}, message="account.uniqueName.allreadyexist")
 */
abstract class Account extends BaseEntity  {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length="50")
     */
    protected $uniqueName;
}