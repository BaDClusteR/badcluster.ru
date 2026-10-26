<?php

declare(strict_types=1);

namespace BC\Tests\Support;

use Runway\Event\IEventDispatcher;

/**
 * Collects the dispatched events instead of calling the listeners.
 */
final class EventDispatcherSpy implements IEventDispatcher {
    /** @var list<array{name: string, envelope: mixed}> */
    public array $events = [];

    public function dispatch(string $eventName, mixed $envelope): void {
        $this->events[] = ['name' => $eventName, 'envelope' => $envelope];
    }
}
