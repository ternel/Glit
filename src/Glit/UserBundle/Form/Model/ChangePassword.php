<?php
namespace Glit\UserBundle\Form\Model;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
/**
 * @Glit\UserBundle\Validator\Password(passwordProperty="current", userProperty="user", message="glit_user.current_password.invalid")
 */
class ChangePassword {

    public $user;

    public $current;

    /**
     * @Assert\NotBlank()
     * @Assert\MinLength(limit=2)
     */
    public $new;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

}