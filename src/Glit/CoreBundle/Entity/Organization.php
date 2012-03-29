<?php
namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Glit\CoreBundle\Entity\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="organizations")
 */
class Organization extends Account  {

    protected $id;
    protected $uniqueName;

    public function getType() {
        return 'organization';
    }

}