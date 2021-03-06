<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\RouteAutofillBundle\DependencyInjection;

use ParkManager\Bundle\RouteAutofillBundle\AutoFilledUrlGenerator;
use ParkManager\Bundle\RouteAutofillBundle\CacheWarmer\RouteRedirectMappingWarmer;
use ParkManager\Bundle\RouteAutofillBundle\EventListener\RouteRedirectResponseListener;
use ParkManager\Bundle\RouteAutofillBundle\MappingFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class DependencyExtension extends Extension
{
    public const EXTENSION_ALIAS = 'park_manager_route_autofill';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register(RouteRedirectMappingWarmer::class)
            ->addArgument(new Reference('router.default'))
            ->addTag('kernel.cache_warmer');

        $container->register(AutoFilledUrlGenerator::class)
            ->setAutowired(true)
            ->setArgument(
                '$autoFillMapping',
                (new Definition(MappingFileLoader::class))->setArguments(['%kernel.cache_dir%/route_autofill_mapping.php'])
            );

        $container->register(RouteRedirectResponseListener::class)
            ->addTag('kernel.event_subscriber')
            ->addArgument(new Reference(AutoFilledUrlGenerator::class));
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }
}
