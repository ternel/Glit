<?php

namespace Glit\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Glit\UserBundle\Entity\SshKey
 *
 * @ORM\Entity
 */
class SshKey extends \Glit\CoreBundle\Entity\SshKey {
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $user;

    /**
     * Set user
     *
     * @param Glit\UserBundle\Entity\User $user
     */
    public function setUser(\Glit\UserBundle\Entity\User $user) {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return Glit\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    public function __construct(User $user) {
        $this->setUser($user);
    }

    public function persist() {
        parent::persist();

        $this->generateKeyIdentifier($this->user->getEmail());
    }
}