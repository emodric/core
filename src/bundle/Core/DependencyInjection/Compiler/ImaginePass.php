<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Imagine\Filter\FilterConfiguration;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImaginePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('liip_imagine.filter.configuration')) {
            return;
        }

        $filterConfigDef = $container->findDefinition('liip_imagine.filter.configuration');
        $filterConfigDef->setClass(FilterConfiguration::class);
        $filterConfigDef->addMethodCall('setConfigResolver', [new Reference('ibexa.config.resolver')]);

        if ($container->hasAlias('liip_imagine')) {
            $imagineAlias = (string)$container->getAlias('liip_imagine');
            $driver = substr($imagineAlias, strrpos($imagineAlias, '.') + 1);

            $this->processReduceNoiseFilter($container, $driver);
            $this->processSwirlFilter($container, $driver);
        }
    }

    private function processReduceNoiseFilter(ContainerBuilder $container, $driver)
    {
        if ($driver !== 'imagick' && $driver !== 'gmagick') {
            return;
        }

        $container->setAlias(
            'ibexa.image_alias.imagine.filter.reduce_noise',
            new Alias("ezpublish.image_alias.imagine.filter.reduce_noise.$driver")
        );
    }

    private function processSwirlFilter(ContainerBuilder $container, $driver)
    {
        if ($driver !== 'imagick' && $driver !== 'gmagick') {
            return;
        }

        $container->setAlias(
            'ibexa.image_alias.imagine.filter.swirl',
            new Alias("ezpublish.image_alias.imagine.filter.swirl.$driver")
        );
    }
}

class_alias(ImaginePass::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ImaginePass');
