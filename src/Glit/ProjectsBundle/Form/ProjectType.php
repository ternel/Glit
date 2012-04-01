<?php

namespace Glit\ProjectsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ProjectType extends AbstractType {
    public function buildForm(FormBuilder $builder, array $options) {
        $builder
            ->add('name')
            ->add('path')
            ->add('description')
            ->add('isPrivate', 'choice', array(
            'choices' => array(1 => 'Private', 0 => 'Public'),
            'required' => false,
            'label' => 'Visibility',
            'expanded' => true,
            //'help' => 'Private projects can be viewed only by the members that you specify.'
        ));
    }

    public function getName() {
        return 'glit_projectsbundle_projecttype';
    }
}
