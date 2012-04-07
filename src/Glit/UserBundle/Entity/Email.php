<?php
namespace Glit\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Glit\CoreBundle\Entity\BaseEntity;

/**
 * Glit\UserBundle\Entity\Email
 *
 * @ORM\Table(name="emails", uniqueConstraints={@ORM\UniqueConstraint(name="emails_address_unq", columns={"address"}), @ORM\UniqueConstraint(name="emails_activation_key_unq", columns={"activation_key"})})
 * @ORM\Entity
 * @DoctrineAssert\UniqueEntity(fields={"address"}, message="glit.emails.address.allreadyexist")
 */
class Email extends BaseEntity {

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $address
     *
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var boolean $isActive
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var string $activationKey
     *
     * @ORM\Column(name="activation_key", type="string", length=255)
     */
    private $activationKey;

    /**
     * @var boolean $isDefault
     *
     * @ORM\Column(name="is_default", type="boolean")
     */
    private $isDefault;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $user;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address) {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     */
    public function setIsActive($isActive) {
        $this->isActive = $isActive;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive() {
        return $this->isActive;
    }

    /**
     * Set activationKey
     *
     * @param string $activationKey
     */
    public function setActivationKey($activationKey) {
        $this->activationKey = $activationKey;
    }

    /**
     * Get activationKey
     *
     * @return string
     */
    public function getActivationKey() {
        return $this->activationKey;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     */
    public function setIsDefault($isDefault) {
        $this->isDefault = $isDefault;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault() {
        return $this->isDefault;
    }

    /**
     * Set user
     *
     * @param \Glit\UserBundle\Entity\User $user
     */
    public function setUser(\Glit\UserBundle\Entity\User $user) {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return \Glit\UserBundle\Entity\User
     */
    public function getUser() {
        return $this->user;
    }

    public function __construct(User $user) {
        $this->setUser($user);
        $this->setIsActive(false);
        $this->setIsDefault(false);
    }

    public function persist() {
        if ($this->getUser()->getEmails()->count() == 0) {
            $this->setIsDefault(true);
        }
        $this->setActivationKey(sha1($this->getAddress() . rand(0, 9999) . time()));

        parent::persist();
    }
}