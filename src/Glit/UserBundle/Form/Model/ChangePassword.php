<?php
namespace Glit\UserBundle\Form\Model;

use Symfony\Component\Security\Core\User\UserInterface;

class ChangePassword {

    /**
     * User whose password is changed
     *
     * @var UserInterface
     */
    public $user;

    /**
     * @var string
     */
    public $current;

    /**
     * @var string
     */
    public $new;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

}