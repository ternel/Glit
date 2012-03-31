<?php
namespace Glit\UserBundle\Form\Model;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;

class ChangePassword {

    public $user;

    /**
     * @Glit\UserBundle\Validator\Password(message="glit_user.current_password.invalid")
     */
    public $current;

    /**
     * @Assert\NotBlank()
     * @Assert\MinLength(limit=2)
     */
    public $new;

    public function __construct(UserInterface $user) {
        $this->user = $user;
    }

}