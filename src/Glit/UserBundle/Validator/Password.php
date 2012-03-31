<?php
namespace Glit\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Password extends Constraint
{
    public $message = 'The entered password is invalid.';
    public $passwordProperty;
    public $userProperty;

    public function getRequiredOptions()
    {
        return array('passwordProperty', 'userProperty');
    }

    public function validatedBy()
    {
        return 'glit_user.validator.password';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}