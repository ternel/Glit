<?php

namespace Glit\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GlitCoreBundle extends Bundle {

    public function build(ContainerBuilder $container) {
        parent::build($container);

        // register extensions that do not follow the conventions manually
        $container->registerExtension(new \Glit\CoreBundle\DependencyInjection\GlitExtension());
    }

}
