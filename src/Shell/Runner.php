<?php

namespace Knutle\ShellExec\Shell;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Knutle\ShellExec\Events\CommandTimeoutEvent;
use Knutle\ShellExec\Events\StandardErrorEmittedEvent;
use Knutle\ShellExec\Events\StandardOutputEmittedEvent;
use Knutle\ShellExec\Exceptions\ShellExecException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Runner
{
    use InteractsWithIO;

    public array $history = [];

    /**
     * How long to wait in seconds before killing the process if it has not completed
     *
     * @var int
     */
    public int $timeout = 5 * 60; // 5 minutes

    /**
     * @param array|string $command
     * @param ?array $pipes
     * @return resource|bool
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function procOpen(array|string $command, ?array &$pipes)
    {
        return proc_open($command, [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w'],  // STDERR
        ], $pipes);
    }

    public function getConsoleOutput(): OutputInterface
    {
        return new ConsoleOutput();
    }

    /**
     * @param string|array $commands
     * @param string|null $input
     * @param int $flags
     * @return ShellExecResponse
     * @throws ShellExecException
     * @throws BindingResolutionException
     */
    public function run(string|array $commands, string $input = null, int $flags = 0): ShellExecResponse
    {
        $liveOutput = (bool)($flags & SHELL_EXEC_RUNNER_WRITE_LIVE_OUTPUT);
        $forceStdErrRedirect = (bool)($flags & SHELL_EXEC_RUNNER_FORCE_STDERR_TO_STDOUT);

        if ($liveOutput) {
            $this->output = app()->make(
                OutputStyle::class,
                ['input' => new ArgvInput(), 'output' => $this->getConsoleOutput()]
            );
        } else {
            $this->output = app()->make(
                OutputStyle::class,
                ['input' => new ArgvInput(), 'output' => new NullOutput()]
            );
        }

        if (is_string($commands)) {
            $commands = [ $commands ];
        }

        $commands = collect($commands)->map(
            fn (string $command) => $forceStdErrRedirect ? "$command 2>&1" : $command
        )->join(PHP_EOL);

        $process = $this->procOpen($commands, $pipes);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);

            if (! blank($input)) {
                fwrite($pipes[0], $input);
                fclose($pipes[0]);
            }

            $buffer_len = $prev_buffer_len = 0;
            $ms = 10;
            $output = '';
            $read_output = true;
            $error = '';
            $read_error = true;

            $startTime = time();

            while ($read_error != false or $read_output != false) {
                $runTime = time() - $startTime;

                if ($runTime > $this->timeout) {
                    // we have run longer than the timeout, so close all pipes and exit

                    if ($read_output) {
                        fclose($pipes[1]);
                    }

                    if ($read_error) {
                        fclose($pipes[2]);
                    }

                    CommandTimeoutEvent::dispatch($this->timeout, time() - $startTime);

                    break;
                }

                if ($read_output) {
                    if (feof($pipes[1])) {
                        fclose($pipes[1]);
                        $read_output = false;
                    } else {
                        $str = fgets($pipes[1], 1024);
                        $len = strlen($str);
                        if ($len) {
                            $this->info(trim($str));
                            StandardOutputEmittedEvent::dispatch(trim($str));
                            $output .= $str;
                            $buffer_len += $len;
                        }
                    }
                }

                if ($read_error) {
                    if (feof($pipes[2])) {
                        fclose($pipes[2]);
                        $read_error = false;
                    } else {
                        $str = fgets($pipes[2], 1024);
                        $len = strlen($str);
                        if ($len) {
                            $this->error(trim($str));
                            StandardErrorEmittedEvent::dispatch(trim($str));
                            $error .= $str;
                            $buffer_len += $len;
                        }
                    }
                }

                if ($buffer_len > $prev_buffer_len) {
                    $prev_buffer_len = $buffer_len;
                    $ms = 10;
                } else {
                    usleep($ms * 1000); // sleep for $ms milliseconds
                    if ($ms < 160) {
                        $ms = $ms * 2;
                    }
                }
            }

            $returnCode = proc_close($process);

            return tap(
                new ShellExecResponse(
                    $commands,
                    $output,
                    $error,
                    $returnCode
                ),
                fn (ShellExecResponse $response) => $this->history[] = $response
            );
        } else {
            throw new ShellExecException('Unable to get info from process');
        }
    }

    public function history(): Collection
    {
        return collect($this->history);
    }

    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function listenForStandardOutputEvents(callable $callable): void
    {
        resolve('events')->listen(StandardOutputEmittedEvent::class, $callable);
    }

    public function listenForStandardErrorEvents(callable $callable): void
    {
        resolve('events')->listen(StandardErrorEmittedEvent::class, $callable);
    }
}
