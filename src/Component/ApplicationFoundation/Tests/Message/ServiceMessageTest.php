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

namespace ParkManager\Component\ApplicationFoundation\Tests\Message;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessage;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ServiceMessageTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideTypes
     */
    public function its_constructable(string $type)
    {
        /** @var ServiceMessage $message */
        $message = ServiceMessage::{$type}('Whoops');

        self::assertEquals($type, $message->type);
        self::assertEquals('Whoops', $message->messageTemplate);
        self::assertEquals([], $message->messageParameters);
        self::assertEquals([], $message->translatedParameters);
        self::assertEquals('', (string) $message);
        self::assertNull($message->systemMessage);
        self::assertNull($message->messagePluralization);
    }

    public function provideTypes(): array
    {
        return [
            ['error'],
            ['notice'],
            ['warning'],
        ];
    }

    /** @test */
    public function its_constructable_with_systemMessage()
    {
        $message = ServiceMessage::error('Whoops', [], 'Fatal system error!');

        self::assertEquals('error', $message->type);
        self::assertEquals('Whoops', $message->messageTemplate);
        self::assertEquals([], $message->messageParameters);
        self::assertEquals([], $message->translatedParameters);
        self::assertEquals('Fatal system error!', $message->systemMessage);
        self::assertEquals('Fatal system error!', (string) $message);
    }

    /** @test */
    public function its_supports_plural()
    {
        $message = ServiceMessage::error('Whoops', [], 'Fatal system error!')->withPlural(2);

        self::assertEquals('error', $message->type);
        self::assertEquals('Whoops', $message->messageTemplate);
        self::assertEquals([], $message->messageParameters);
        self::assertEquals([], $message->translatedParameters);
        self::assertEquals(2, $message->messagePluralization);
        self::assertEquals('Fatal system error!', $message->systemMessage);
        self::assertEquals('Fatal system error!', (string) $message);
    }

    /** @test */
    public function it_supports_translated_parameters()
    {
        $message = ServiceMessage::notice('From the other side', ['hello' => 'me it is', 'wondering' => 'was I'])
            ->translateParameters(['hello']);

        self::assertEquals('notice', $message->type);
        self::assertEquals(['hello' => 'me it is', 'wondering' => 'was I'], $message->messageParameters);
        self::assertEquals(['hello'], $message->translatedParameters);
        self::assertEquals('', (string) $message);
        self::assertNull($message->systemMessage);
    }
}
