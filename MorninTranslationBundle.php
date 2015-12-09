<?php

namespace Mornin\Bundle\TranslationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Mornin\Bundle\TranslationBundle\DependencyInjection\Compiler\RegisterMappingPass;
use Mornin\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;

/**
 * Bundle main class.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class MorninTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new RegisterMappingPass());
    }
}
