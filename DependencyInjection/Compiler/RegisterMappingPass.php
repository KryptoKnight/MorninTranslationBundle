<?php

namespace Mornin\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Mornin\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Doctrine metadata pass to add a driver to load model class mapping.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class RegisterMappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $storage = $container->getParameter('Mornin_translation.storage');

        $name = empty($storage['object_manager']) ? 'default' : $storage['object_manager'];

        $ormDriverId     = sprintf('doctrine.orm.%s_metadata_driver', $name);
        $mongodbDriverId = sprintf('doctrine_mongodb.odm.%s_metadata_driver', $name);

        if (StorageInterface::STORAGE_ORM == $storage['type'] && $container->hasDefinition($ormDriverId)) {
            $container->getDefinition($ormDriverId)->addMethodCall(
                'addDriver',
                array(new Reference('Mornin_translation.orm.metadata.xml'), 'Mornin\Bundle\TranslationBundle\Model')
            );
        }

        if (StorageInterface::STORAGE_MONGODB == $storage['type'] && $container->hasDefinition($mongodbDriverId)) {
            $container->getDefinition($mongodbDriverId)->addMethodCall(
                'addDriver',
                array(new Reference('Mornin_translation.mongodb.metadata.xml'), 'Mornin\Bundle\TranslationBundle\Model')
            );
        }
    }
}
