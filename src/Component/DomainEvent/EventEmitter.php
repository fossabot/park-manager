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

namespace ParkManager\Component\DomainEvent;

interface EventEmitter
{
    /**
     * @param DomainEvent $event
     *
     * @return DomainEvent
     */
    public function emit(DomainEvent $event): DomainEvent;

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $listener  The listener
     * @param int      $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events they are
     * interested in and added as a listener for these events.
     */
    public function addSubscriber(EventSubscriber $subscriber): void;

    /**
     * Removes an event listener from the specified events.
     *
     * @param string   $eventName The event to remove a listener from
     * @param callable $listener  The listener to remove
     */
    public function removeListener(string $eventName, callable $listener): void;

    public function removeSubscriber(EventSubscriber $subscriber): void;

    /**
     * Gets the listeners of a specific event or all listeners sorted by descending priority.
     *
     * @param string $eventName The name of the event
     *
     * @return array The event listeners for the specified event, or all event listeners by event name
     */
    public function getListeners(string $eventName = null): array;

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $eventName The name of the event
     *
     * @return bool true if the specified event has any listeners, false otherwise
     */
    public function hasListeners(string $eventName = null): bool;
}
