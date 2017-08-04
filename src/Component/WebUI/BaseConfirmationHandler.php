<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\WebUI;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * @internal
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class BaseConfirmationHandler
{
    protected $twig;
    protected $tokenManager;
    protected $templateContext = [
        'cancel_url' => null,
        'error' => null,
    ];
    protected $tokenId;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Environment $twig, CsrfTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
        $this->twig = $twig;
    }

    /**
     * @param string $url A full URI (with or without a hostname)
     *
     * @return $this
     */
    public function setCancelUrl(?string $url): self
    {
        $this->templateContext['cancel_url'] = $url;

        return $this;
    }

    /**
     * Handle the request to check for confirmation and extract relevant Request attributes
     * for the token computation.
     *
     * Note: While passing $requestAttrNames is optional it's highly recommended,
     * as it makes harder to re-use this confirmation form for a different item.
     * Usually you only need the ID, when this is used as unique identification.
     *
     * @param Request  $request
     * @param string[] ...$requestAttrNames A list of Request Attributes to include in the unique
     *                                      security token computation
     *
     * @return $this
     */
    public function handleRequest(Request $request, string ...$requestAttrNames): self
    {
        $this->request = $request;
        $this->tokenId = 'confirm.';

        foreach ($requestAttrNames as $requestAttrName) {
            $this->tokenId .= $request->attributes->get($requestAttrName, '').'~';
        }

        return $this;
    }

    /**
     * Returns whether the action was confirmed (and has a valid token).
     *
     * @return bool
     */
    abstract public function isConfirmed(): bool;

    /**
     * Renders the provided Twig template (showing an confirmation box).
     *
     * The template receives:
     * * title
     * * message
     * * yes_btn_label
     * * token (to be set as hidden-field value)
     *
     * @param string $template       A Twig template to render
     * @param array  $extraVariables An additional list of variables for the template
     *                               (cannot overwrite existing configuration)
     *
     * @return string
     */
    public function render(string $template, array $extraVariables = []): string
    {
        if (!isset($this->templateContext['title'])) {
            throw new \BadMethodCallException('Unable render confirmation-ui call configure() first.');
        }

        $extraVariables['token'] = $this->tokenManager->getToken($this->tokenId)->getValue();

        return $this->twig->render($template, array_merge($extraVariables, $this->templateContext));
    }

    protected function checkToken()
    {
        if (!is_string($token = $this->request->request->get('_token'))) {
            $valid = false;
        } elseif (!$valid = $this->tokenManager->isTokenValid(new CsrfToken($this->tokenId, $token))) {
            $this->tokenManager->removeToken($this->tokenId);
        }

        if (!$valid) {
            $this->templateContext['error'] = 'Invalid CSRF token.';
        }

        return $valid;
    }

    protected function guardNeedsRequest(): void
    {
        if (null === $this->request) {
            throw new \BadMethodCallException('Unable perform operation, call handleRequest() first.');
        }
    }
}
