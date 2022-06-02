<?php

namespace Knutle\ShellExec\Facades;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Knutle\ShellExec\Shell\Runner;
use Knutle\ShellExec\Shell\ShellExecFakeResponse;
use Knutle\ShellExec\Shell\ShellExecResponse;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use OutOfBoundsException;

/**
 * @see \Knutle\ShellExec\Shell\Runner
 *
 * @method static ShellExecResponse run(string|array $commands)
 * @method static Collection history()
 */
class ShellExec extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Runner::class;
    }

    public static function fake(array $responses = null, int $flags = 0): void
    {
        $alwaysRespond = (bool)($flags & SHELL_EXEC_FAKE_ALWAYS_RESPOND);
        $dumpCommands = (bool)($flags & SHELL_EXEC_FAKE_DUMP_COMMANDS);
        $dumpHistoryOnEmptyMockQueue = (bool)($flags & SHELL_EXEC_FAKE_DUMP_HISTORY_ON_EMPTY_MOCK_QUEUE);

        /** @var MockInterface|LegacyMockInterface|Runner $mock */
        $mock = Mockery::mock(Runner::class);

        /** @phpstan-ignore-next-line */
        $mock
            ->shouldReceive('run')
            ->andReturnUsing(
                function (string|array $commands) use (&$responses, $mock, $alwaysRespond, $dumpCommands, $dumpHistoryOnEmptyMockQueue) {
                    if (is_array($commands)) {
                        $commands = implode("\n", $commands);
                    }

                    $response = '';

                    if (! is_null($responses)) {
                        $response = array_shift($responses);
                    }

                    if ($response instanceof ShellExecFakeResponse) {
                        $response->verifyExpectedCommand($commands);
                    }

                    if (is_null($response) && ! $alwaysRespond) {
                        if ($dumpHistoryOnEmptyMockQueue) {
                            dump(collect($mock->history)->toArray());
                        }

                        throw new OutOfBoundsException('Mock queue is empty');
                    }

                    /** @phpstan-ignore-next-line */
                    if (is_null($response) && $alwaysRespond) {
                        $response = '';
                    }

                    if ($response instanceof Exception) {
                        $output = '';
                        $error = $response->getMessage();
                        $exitCode = (int)str_replace(0, 1, $response->getCode());
                    } else {
                        $output = (string)$response;
                        $error = '';
                        $exitCode = 0;
                    }

                    return tap(
                        new ShellExecResponse(
                            $commands,
                            $output,
                            $error,
                            $exitCode,
                            true
                        ),
                        function (ShellExecResponse $response) use (&$mock, $dumpCommands) {
                            $mock->history[] = $response;

                            if ($dumpCommands) {
                                $response->dump();
                            }
                        }
                    );
                }
            );

        /** @phpstan-ignore-next-line */
        $mock
            ->shouldReceive('history')
            ->andReturnUsing(
                fn () => collect($mock->history)
            );

        app()->bind(Runner::class, fn () => $mock);
    }

    public static function reset(): void
    {
        app()->bind(Runner::class);

        static::clearResolvedInstances();
    }
}
