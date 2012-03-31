<?php
namespace Glit\UserBundle\Validator;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PasswordValidator extends ConstraintValidator {
    protected $encoderFactory;
    protected $securityContext;

    public function setEncoderFactory(EncoderFactoryInterface $factory) {
        $this->encoderFactory = $factory;
    }

    public function setSecurityContext(SecurityContextInterface $context) {
        $this->securityContext = $context;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $object     The object that should be validated
     * @param Constraint $constraint The constrain for the validation
     *
     * @return Boolean Whether or not the value is valid
     *
     * @throws UnexpectedTypeException if $object is not an object
     */
    public function isValid($object, Constraint $constraint) {
        $user = $this->securityContext->getToken()->getUser();
        $encoder = $this->encoderFactory->getEncoder($user);
        if (!$encoder->isPasswordValid($user->getPassword(), $object, $user->getSalt())) {
            $this->setMessage($constraint->message);

            return false;
        }

        return true;
    }
}