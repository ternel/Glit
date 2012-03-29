<?php

namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;


/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
class BaseEntity {

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * Constructor
     * Never called by doctrine
     */
    public function __construct() {
        // constructor is never called by Doctrine
        $this->createdAt = $this->updatedAt = new \DateTime("now");
    }

    /**
     * @ORM\PrePersist
     */
    public function persist() {
        $this->createdAt = $this->updatedAt = new \DateTime("now");
    }

    /**
     * @ORM\PreUpdate
     */
    public function updated() {
        $this->updatedAt = new \DateTime("now");
    }
}