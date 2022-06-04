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
 * @method static ShellExecResponse run(string|array $commands, string $input = null, int $flags = 0)
 * @method static Collection history()
 * @method static Runner timeout(int $seconds)
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
        $partialFake = (bool)($flags & SHELL_EXEC_PARTIAL_FAKE);

        /** @var MockInterface|LegacyMockInterface|Runner $mock */
        $mock = Mockery::mock(Runner::class);

        /** @phpstan-ignore-next-line */
        $mock
            ->shouldReceive('run')
            ->andReturnUsing(
                function (string|array $commands) use (&$responses, &$mock, $alwaysRespond, $dumpCommands, $dumpHistoryOnEmptyMockQueue, $partialFake) {
                    if (is_array($commands)) {
                        $commands = implode(
                            PHP_OS == 'WINNT' ? ' && ' : PHP_EOL,
                            $commands
                        );
                    }

                    if ($partialFake) {
                        $response = collect($responses)
                            ->whereInstanceOf(ShellExecFakeResponse::class)
                            ->filter(
                                fn (ShellExecFakeResponse $response) => $response->matchCommand($commands)
                            )->first();

                        if ($alwaysRespond && is_null($response)) {
                            return static::returnFakeResponse($commands, '', '', 0, $dumpCommands, $mock);
                        } elseif ($response instanceof ShellExecFakeResponse) {
                            return static::returnFakeResponse($commands, $response->getOutput(), $response->getError(), $response->getExitCode(), $dumpCommands, $mock);
                        } else {
                            return static::returnFakeResponse($commands, array_shift($responses), '', 0, $dumpCommands, $mock);
                        }
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
                        $exitCode = $response->getCode();
                    } elseif ($response instanceof ShellExecFakeResponse) {
                        $output = $response->getOutput();
                        $error = $response->getError();
                        $exitCode = $response->getExitCode();
                    } else {
                        $output = (string)$response;
                        $error = '';
                        $exitCode = 0;
                    }

                    return static::returnFakeResponse($commands, $output, $error, $exitCode, $dumpCommands, $mock);
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

    protected static function returnFakeResponse(string|array $commands, string $output, string $error, string $exitCode, bool $dumpCommands, $mock)
    {
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

    public static function reset(): void
    {
        app()->bind(Runner::class);

        static::clearResolvedInstances();
    }
}
