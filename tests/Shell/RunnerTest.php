<?php /** @noinspection PhpUnhandledExceptionInspection */

use Knutle\ShellExec\Events\StandardErrorEmittedEvent;
use Knutle\ShellExec\Events\StandardOutputEmittedEvent;
use Knutle\ShellExec\Shell\Runner;

it('can record real commands to object history directly on runner', function () {
    $runner = new Runner();

    expect((string)$runner->run("php -i"))
        ->toContain('PHP Version => ')
        ->and($runner->history()->pluck('command')->toArray())
        ->toEqual([
            'php -i',
        ]);
});

it('can bind event listeners directly on runner instance', function () {
    $runner = new Runner();
    $events = resolve('events');

    expect($events->hasListeners(StandardOutputEmittedEvent::class))
        ->toBeFalse()
        ->and($events->hasListeners(StandardErrorEmittedEvent::class))
        ->toBeFalse();

    $runner->listenForStandardOutputEvents(fn () => true);
    $runner->listenForStandardErrorEvents(fn () => true);

    expect($events->hasListeners(StandardOutputEmittedEvent::class))
        ->toBeTrue()
        ->and($events->hasListeners(StandardErrorEmittedEvent::class))
        ->toBeTrue();
});
