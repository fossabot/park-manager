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

namespace ParkManager\Component\Module;

use ParkManager\Component\Module\Traits\ServiceLoaderTrait;
use Rollerworks\Bundle\RouteAutowiringBundle\RouteImporter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * The ParkManagerModuleDependencyExtension provides an addition
 * to the DependencyInjection Extension by wiring some
 * configurations automatically.
 *
 * This extension loads Routes, templates, translations,
 * and Doctrine DBAL Types (if the RegistersDoctrineDbalTypes interface is implemented).
 *
 * Templates: Infrastructure/Resources/templates and UI/Web/Resources/templates
 * Translations: Infrastructure/Resources/translations and UI/Web/Resources/translations
 * Services: Infrastructure/Resources/config/services/
 * Routes: Infrastructure/Resources/config
 *
 * Use the ServiceLoaderTrait if you only need the Services loader.
 */
abstract class ParkManagerModuleDependencyExtension extends Extension implements PrependExtensionInterface
{
    use ServiceLoaderTrait;

    protected $moduleDir;

    /**
     * Name of this Module (with vendor namespace).
     *
     * @return string eg. AcmeWebhosting
     */
    abstract public function getModuleName(): string;

    /**
     * Configures a number of common operations.
     * Use loadModule() to load additional configurations.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @internal
     */
    final public function load(array $configs, ContainerBuilder $container): void
    {
        $this->initModuleDirectory();

        $routeImporter = new RouteImporter($container);
        $routeImporter->addObjectResource($this);
        $this->registerRoutes($routeImporter, realpath($this->moduleDir.'/Infrastructure/Resources/config') ?: null);

        $loader = $this->getServiceLoader($container, $this->moduleDir.'/Infrastructure/Resources/config/services');
        $this->loadModule($configs, $container, $loader);
    }

    /**
     * Configures the translator paths, templates paths, and DomainId
     * DBAL types. Use prependExtra() to prepend extension configurations.
     *
     * Note: Registers only when directory or methods exist.
     *
     * @param ContainerBuilder $container
     *
     * @internal
     */
    final public function prepend(ContainerBuilder $container): void
    {
        $this->initModuleDirectory();
        $resourcesDirectory = $this->moduleDir.'/Infrastructure/Resources';

        if (is_dir($resourcesDirectory.'/translations')) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [$resourcesDirectory.'/translations'],
                ],
            ]);
        }
        if (is_dir($this->moduleDir.'/UI/Web/Resources/translations')) {
            $container->prependExtensionConfig('framework', [
                'translator' => [
                    'paths' => [$this->moduleDir.'/UI/Web/Resources/translations'],
                ],
            ]);
        }

        if (is_dir($resourcesDirectory.'/templates')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [$resourcesDirectory.'/templates' => $this->getModuleName()],
            ]);
        }
        if (is_dir($this->moduleDir.'/UI/Web/Resources/templates')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [$this->moduleDir.'/UI/Web/Resources/templates' => $this->getModuleName()],
            ]);
        }

        if ($this instanceof RegistersDoctrineDbalTypes) {
            $this->registerDoctrineDbalTypes($container, $this->moduleDir);
        }

        $this->prependExtra($container);
    }

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   The configs (unprocessed)
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader    Service definitions loader for all supported types
     *                                    including Glob, Directory, Closure and ini
     */
    protected function loadModule(array $configs, ContainerBuilder $container, LoaderInterface $loader): void
    {
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * prepend() is final, use this method instead.
     *
     * @param ContainerBuilder $container
     */
    protected function prependExtra(ContainerBuilder $container): void
    {
    }

    /**
     * Registers the routes using the RouteImporter importer.
     *
     * Use the following slots for sections:
     *
     * * 'park_manager.client_section.root': Client section root
     * * 'park_manager.admin_section.root': Admin section root
     * * 'park_manager.api_section.root': API (both client and admin)
     *
     * Or use 'park_manager.root' to import at the root (/)
     * of the routing scheme (only for error pages and utils).
     *
     * Example:
     *
     *   $routeImporter->import($configDir.'/routing/client.php', 'park_manager.client_section.root');
     *   $routeImporter->import($configDir.'/routing/admin.php', 'park_manager.admin_section.root');
     *
     * @param RouteImporter $routeImporter
     * @param string        $configDir     Full path of Resources/config directory
     *                                     (null when missing)
     */
    protected function registerRoutes(RouteImporter $routeImporter, ?string $configDir): void
    {
    }

    final protected function initModuleDirectory(): void
    {
        if (null === $this->moduleDir) {
            $this->moduleDir = \dirname((new \ReflectionObject($this))->getFileName(), 3);
        }
    }
}
