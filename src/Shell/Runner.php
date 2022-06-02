<?php

namespace Knutle\ShellExec\Shell;

use Illuminate\Support\Collection;
use Knutle\ShellExec\Exceptions\ShellExecException;

class Runner
{
    public array $history = [];

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

    /**
     * @param string|array $commands
     * @return ShellExecResponse
     * @throws ShellExecException
     */
    public function run(string|array $commands, string $input = null): ShellExecResponse
    {
        if (is_array($commands)) {
            $commands = implode(
                PHP_OS == 'WINNT' ? ' && ' : PHP_EOL,
                $commands
            );
        }

        $process = $this->procOpen($commands, $pipes);

        if (is_resource($process)) {
            if (! blank($input)) {
                fwrite($pipes[0], $input);
                fclose($pipes[0]);
            }

            $stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stdErr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            return tap(
                new ShellExecResponse(
                    $commands,
                    $stdOut,
                    $stdErr,
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
}
