<?php
namespace Glit\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints as Constraints;

class ProfileType extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('firstname');
        $builder->add('lastname');
        $builder->add('email', 'email');
        $builder->add('uniqueName', 'text', array('label' => 'Username'));
    }

    public function getDefaultOptions(array $options) {
        return array(
            'data_class' => 'Glit\UserBundle\Entity\User',
        );
    }

    public function getName() {
        return 'user_profile';
    }
}