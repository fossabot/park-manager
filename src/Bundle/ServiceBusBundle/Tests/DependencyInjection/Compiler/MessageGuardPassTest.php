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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Compiler;

use League\Tactician\CommandBus;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\MessageGuardPass;
use ParkManager\Bundle\ServiceBusBundle\Guard\CliGuard;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Guard\FooGuard;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Middleware\MessageGuardMiddleware;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class MessageGuardPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_registers_guards_when_middleware_is_enabled()
    {
        $this->registerService('park_manager.command_bus.users', CommandBus::class)->addTag('park_manager.service_bus');
        $this->registerService('park_manager.command_bus.users.middleware.message_guard', MessageGuardMiddleware::class)
            ->addTag('park_manager.command_bus.users.middleware');
        $this->registerService('park_manager.command_bus.users.message_guard.foo', FooGuard::class)
            ->addTag('park_manager.command_bus.users.message_guard');
        $this->registerService('park_manager.command_bus.users.message_guard.cli', CliGuard::class)
            ->addTag('park_manager.command_bus.users.message_guard', ['priority' => 5]);

        $this->registerService('park_manager.query_bus.users', CommandBus::class)->addTag('park_manager.service_bus');

        $this->compile();

        $this->assertMessageGuardMiddleware('park_manager.command_bus.users.middleware.message_guard', [
            'park_manager.command_bus.users.message_guard.cli',
            'park_manager.command_bus.users.message_guard.foo',
        ]);

        self::assertEquals(
            [MessageGuardPass::class.': MessageGuardMiddleware is not enabled for park_manager.query_bus.users, ignoring.'],
            $this->container->getCompiler()->getLog()
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MessageGuardPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    private function assertMessageGuardMiddleware(string $busId, array $expectedGuards): void
    {
        $expectedGuards = array_map(
            function ($serviceId) {
                if (\is_string($serviceId)) {
                    return new Reference($serviceId);
                }

                return $serviceId;
            },
            $expectedGuards
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument($busId, 0, new IteratorArgument($expectedGuards));
    }
}
