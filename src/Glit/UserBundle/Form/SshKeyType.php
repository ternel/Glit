<?php

namespace Glit\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SshKeyType extends AbstractType {
    public function buildForm(FormBuilder $builder, array $options) {
        $builder
            ->add('title')
            ->add('publicKey', 'textarea', array('attr' => array('rows' => 10)));
    }

    public function getName() {
        return 'glit_userbundle_sshkeytype';
    }
}
