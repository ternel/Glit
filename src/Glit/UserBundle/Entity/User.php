<?php
namespace Glit\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
use Glit\CoreBundle\Entity\Account;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 *
 */
class User extends Account implements UserInterface {

    /**
     * @ORM\Column(type="string", length="255")
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length="255")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length="50")
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length="50")
     */
    private $lastname;

    /**
     * @ORM\Column(type="boolean", name="require_profil_update")
     */
    private $requireProfileUpdate;

    /**
     * @ORM\OneToMany(targetEntity="SshKey", mappedBy="user", cascade={"persist", "remove"})
     */
    private $sshKeys;

    /**
     * @ORM\OneToMany(targetEntity="Email", mappedBy="user", cascade={"persist", "remove"})
     */
    private $emails;

    /**
     * @ORM\Column(type="boolean", name="require_password_change")
     */
    private $requirePasswordChange;

    /**
     * @ORM\ManyToMany(targetEntity="Glit\CoreBundle\Entity\Organization", mappedBy="users", cascade={"persist", "remove"})
     */
    private $organizations;

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles() {
        return array();
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string The salt
     */
    public function getSalt() {
        if (empty($this->salt)) {
            $this->salt = md5(rand(10000, 999999) . time());
        }

        return $this->salt;
    }

    public function setUsername($value) {
        $this->setUniqueName($value);
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->getUniqueName();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @return void
     */
    public function eraseCredentials() {

    }

    /**
     * Returns whether or not the given user is equivalent to *this* user.
     *
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * @param UserInterface $user
     *
     * @return Boolean
     */
    public function equals(UserInterface $user) {
        return $user->getUsername() == $this->getUsername();
    }

    public function __toString() {
        return $this->getUsername();
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->emails->filter(function($item) {
            return $item->getIsDefault();
        })->first();
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Set requireProfileUpdate
     *
     * @param boolean $requireProfileUpdate
     */
    public function setRequireProfileUpdate($requireProfileUpdate) {
        $this->requireProfileUpdate = $requireProfileUpdate;
    }

    /**
     * Get requireProfileUpdate
     *
     * @return boolean
     */
    public function getRequireProfileUpdate() {
        return $this->requireProfileUpdate;
    }

    /**
     * Set requirePasswordChange
     *
     * @param boolean $requirePasswordChange
     */
    public function setRequirePasswordChange($requirePasswordChange) {
        $this->requirePasswordChange = $requirePasswordChange;
    }

    /**
     * Get requirePasswordChange
     *
     * @return boolean
     */
    public function getRequirePasswordChange() {
        return $this->requirePasswordChange;
    }

    /**
     * Add sshKeys
     *
     * @param \Glit\UserBundle\Entity\SshKey $sshKeys
     */
    public function addSshKey(\Glit\UserBundle\Entity\SshKey $sshKeys) {
        $this->sshKeys[] = $sshKeys;
    }

    /**
     * Get sshKeys
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSshKeys() {
        return $this->sshKeys;
    }

    public function __construct() {
        $this->requirePasswordChange = false;
        $this->requireProfileUpdate  = false;
        $this->sshKeys               = new \Doctrine\Common\Collections\ArrayCollection();
        $this->emails                = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getType() {
        return 'user';
    }

    /**
     * Add organizations
     *
     * @param \Glit\CoreBundle\Entity\Organization $organizations
     */
    public function addOrganization(\Glit\CoreBundle\Entity\Organization $organizations) {
        $this->organizations[] = $organizations;
    }

    /**
     * Get organizations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations() {
        return $this->organizations;
    }

    /**
     * Add emails
     *
     * @param \Glit\UserBundle\Entity\Email $emails
     */
    public function addEmail(\Glit\UserBundle\Entity\Email $emails) {
        $this->emails[] = $emails;
    }

    /**
     * Get emails
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmails() {
        return $this->emails;
    }
}