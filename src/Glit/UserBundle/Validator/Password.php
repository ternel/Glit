<?php
namespace Glit\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Password extends Constraint {
    public $message = 'The entered password is invalid.';

    public function validatedBy() {
        return 'glit_user.validator.password';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets() {
        return self::PROPERTY_CONSTRAINT;
    }
}