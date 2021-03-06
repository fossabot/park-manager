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

namespace ParkManager\Component\Security\Token;

final class Argon2SplitToken extends SplitToken
{
    protected function configureHasher(array $config = [])
    {
        $this->config = array_merge(
            [
                'memory_cost' => \PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                'time_cost' => \PASSWORD_ARGON2_DEFAULT_TIME_COST,
                'threads' => \PASSWORD_ARGON2_DEFAULT_THREADS,
            ],
            $config
        );
    }

    protected function verifyHash(string $hash, string $verifier): bool
    {
        return \password_verify($verifier, $hash);
    }

    protected function hashVerifier(string $verifier): string
    {
        return \password_hash($verifier, \PASSWORD_ARGON2I, $this->config);
    }
}
