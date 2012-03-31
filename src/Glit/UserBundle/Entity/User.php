<?php
namespace Glit\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
use Glit\CoreBundle\Entity\Organization;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 *
 */
class User extends Organization implements UserInterface {

    protected $id;
    protected $uniqueName;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $salt;

    /**
     * @ORM\Column(type="string", length="255")
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length="100")
     * @Assert\Email()
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length="50")
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length="50")
     */
    protected $lastname;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $requireProfileUpdate;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $requirePasswordChange;

    public function __construct() {
        $this->requirePasswordChange = false;
        $this->requireProfileUpdate = false;
    }

    public function getType() {
        return 'user';
    }

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
            $this->salt = md5(rand(10000, 999999).time());
        }

        return $this->salt;
    }

    public function setUsername($value) {
        return $this->uniqueName = $value;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->uniqueName;
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
        return $this->uniqueName;
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set requireProfileUpdate
     *
     * @param boolean $requireProfileUpdate
     */
    public function setRequireProfileUpdate($requireProfileUpdate)
    {
        $this->requireProfileUpdate = $requireProfileUpdate;
    }

    /**
     * Get requireProfileUpdate
     *
     * @return boolean 
     */
    public function getRequireProfileUpdate()
    {
        return $this->requireProfileUpdate;
    }

    /**
     * Set requirePasswordChange
     *
     * @param boolean $requirePasswordChange
     */
    public function setRequirePasswordChange($requirePasswordChange)
    {
        $this->requirePasswordChange = $requirePasswordChange;
    }

    /**
     * Get requirePasswordChange
     *
     * @return boolean 
     */
    public function getRequirePasswordChange()
    {
        return $this->requirePasswordChange;
    }
}