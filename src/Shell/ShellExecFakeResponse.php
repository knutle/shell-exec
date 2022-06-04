<?php

namespace Knutle\ShellExec\Shell;

use Closure;
use Exception;
use Knutle\ShellExec\Exceptions\ShellExecException;

class ShellExecFakeResponse
{
    public string|Exception $expectedOutput;
    public mixed $expectedCommand;

    public function __construct(string|Closure|null $expectedCommand, string|Exception $expectedOutput)
    {
        $this->expectedCommand = $expectedCommand;
        $this->expectedOutput = $expectedOutput;
    }

    public function matchCommand(string $command): bool
    {
        if (is_null($this->expectedCommand)) {
            return true;
        }

        if (is_string($this->expectedCommand)) {
            return $this->expectedCommand == $command;
        }

        if (is_callable($this->expectedCommand)) {
            return call_user_func($this->expectedCommand, $command);
        }

        return false;
    }

    /**
     * @throws ShellExecException
     */
    public function verifyExpectedCommand(string $command): void
    {
        if (! $this->matchCommand($command)) {
            if (is_callable($this->expectedCommand)) {
                throw new ShellExecException("Mock received unexpected command '$command'");
            }

            throw new ShellExecException("Mock expected command '$this->expectedCommand' but received '$command'");
        }
    }

    public function getOutput(): string
    {
        return is_string($this->expectedOutput) ? $this->expectedOutput : '';
    }

    public function getError(): string
    {
        return ($this->expectedOutput instanceof Exception) ? $this->expectedOutput->getMessage() : '';
    }

    public function getExitCode(): int
    {
        if (! ($this->expectedOutput instanceof Exception)) {
            return 0;
        }

        return $this->expectedOutput->getCode();
    }
}
