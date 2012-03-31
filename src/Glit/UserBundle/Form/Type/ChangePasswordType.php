<?php
namespace Glit\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ChangePasswordType extends AbstractType {

    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('current', 'password');
        $builder->add('new', 'repeated', array(
            'type' => 'password',
            'first_name' => 'new_password',
            'second_name' => 'confirm_password',
        ));
    }

    public function getDefaultOptions(array $options) {
        return array(
            'data_class' => 'Glit\UserBundle\Form\Model\ChangePassword',
        );
    }

    public function getName() {
        return 'user_change_password';
    }
}