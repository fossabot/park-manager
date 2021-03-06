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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Configurator;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\HandlersConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Handler\CancelUserHandler;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Handler\RegisterUserHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 */
final class HandlersConfiguratorTest extends TestCase
{
    /** @test */
    public function it_registers_handlers()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new HandlersConfigurator($busConfigurator, $containerConfigurator->defaults(), 'park_manager.command_bus.users', __DIR__.'/../../Fixtures/Handler');
        $configurator
            ->register(RegisterUserHandler::class)
            ->registerFor(CancelUserHandler::class, 'CancelUser', ['foo']);

        $expectedDef = new Definition(RegisterUserHandler::class);
        $expectedDef->addTag('park_manager.command_bus.users.handler');
        $expectedDef->setPublic(false);

        $expectedDef2 = new Definition(CancelUserHandler::class);
        $expectedDef2->addTag('park_manager.command_bus.users.handler', ['message' => 'CancelUser']);
        $expectedDef2->setArguments(['foo']);
        $expectedDef2->setPublic(false);

        self::assertSame($busConfigurator, $configurator->end());
        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.RegisterUserHandler::class));
        self::assertEquals($expectedDef2, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.CancelUserHandler::class));
    }

    /** @test */
    public function it_registers_handlers_by_psr4_loading()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new HandlersConfigurator($busConfigurator, $containerConfigurator->defaults(), 'park_manager.command_bus.users', realpath(__DIR__.'/../Fixture/config'));
        $configurator
            ->load('ParkManager\\Bundle\\ServiceBusBundle\\Tests\\Fixtures\\Handler\\', '../../../Fixtures/Handler', '../../../Fixtures/Handler/CancelUserHandler.php');

        $expectedDef = new Definition(RegisterUserHandler::class);
        $expectedDef->addTag('park_manager.command_bus.users.handler');
        $expectedDef->setPublic(false);

        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.RegisterUserHandler::class));
        self::assertFalse($containerBuilder->hasDefinition('park_manager.command_bus.users.handler.'.CancelUserHandler::class));
    }

    /** @test */
    public function it_overwrites_handler_handler()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new HandlersConfigurator($busConfigurator, $containerConfigurator->defaults(), 'park_manager.command_bus.users', __DIR__.'/../../Fixtures/Handler');
        $configurator
            ->overwrite(RegisterUserHandler::class, CancelUserHandler::class, 5, ['nope', 'foo']);

        $expectedDef = new Definition(CancelUserHandler::class);
        $expectedDef->setDecoratedService(RegisterUserHandler::class, null, 5);
        $expectedDef->setArguments(['nope', 'foo']);
        $expectedDef->setPublic(false);

        self::assertSame($busConfigurator, $configurator->end());
        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.CancelUserHandler::class));
    }
}
