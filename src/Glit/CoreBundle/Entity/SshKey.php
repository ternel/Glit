<?php

namespace Glit\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;

/**
 * Glit\UserBundle\Entity\SshKey
 *
 * @ORM\Entity
 * @ORM\Table(name="ssh_keys", uniqueConstraints={@ORM\UniqueConstraint(name="ssh_keys_keyIdentifier_unq", columns={"keyIdentifier"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user_key" = "Glit\UserBundle\Entity\SshKey"})
 * @DoctrineAssert\UniqueEntity(fields={"keyIdentifier"}, message="glit.sshkey.keyIdentifier.allreadyexist")
 */
abstract class SshKey extends BaseEntity {
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $keyIdentifier
     *
     * @ORM\Column(name="keyIdentifier", type="string", length=100)
     */
    protected $keyIdentifier;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=30)
     */
    protected $title;

    /**
     * @var string $publicKey
     *
     * @ORM\Column(name="publicKey", type="string", length=255)
     */
    protected $publicKey;

    //<editor-fold desc="Accessor">

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set keyIdentifier
     *
     * @param string $keyIdentifier
     */
    public function setKeyIdentifier($keyIdentifier) {
        $this->keyIdentifier = $keyIdentifier;
    }

    /**
     * Get keyIdentifier
     *
     * @return string
     */
    public function getKeyIdentifier() {
        return $this->keyIdentifier;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set publicKey
     *
     * @param string $publicKey
     */
    public function setPublicKey($publicKey) {
        $this->publicKey = $publicKey;
    }

    /**
     * Get publicKey
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    //</editor-fold>

    public function generateKeyIdentifier($base) {
        $key = \Gedmo\Sluggable\Util\Urlizer::urlize($base);
        $this->setKeyIdentifier($key . '-' . sha1($this->publicKey . microtime() . $this->getTitle()));
    }
}