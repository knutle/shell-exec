<?php

namespace Knutle\ShellExec\Facades;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Knutle\ShellExec\Shell\Runner;
use Knutle\ShellExec\Shell\ShellExecFakeResponse;
use Knutle\ShellExec\Shell\ShellExecResponse;
use Mockery;
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

        $mock = Mockery::mock(Runner::class);

        $mock
            ->shouldReceive('run')
            ->andReturnUsing(
                function (string $command) use (&$responses, $mock, $alwaysRespond, $dumpCommands, $dumpHistoryOnEmptyMockQueue) {
                    $response = '';

                    if (! is_null($responses)) {
                        $response = array_shift($responses);
                    }

                    if ($response instanceof ShellExecFakeResponse) {
                        $response->verifyExpectedCommand($command);
                    }

                    if (is_null($response) && ! $alwaysRespond) {
                        if ($dumpHistoryOnEmptyMockQueue) {
                            dump(collect($mock->history)->toArray());
                        }

                        throw new OutOfBoundsException('Mock queue is empty');
                    }

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
                            $command,
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
    }
}
