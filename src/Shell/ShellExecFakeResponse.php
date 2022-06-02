<?php

namespace Knutle\ShellExec\Shell;

use Closure;
use Knutle\ShellExec\Exceptions\ShellExecException;

class ShellExecFakeResponse
{
    public string $expectedOutput;
    public string|Closure|null $expectedCommand;

    public function __construct(string|Closure|null $expectedCommand, string $expectedOutput)
    {
        $this->expectedCommand = $expectedCommand;
        $this->expectedOutput = $expectedOutput;
    }

    public function __toString()
    {
        return $this->expectedOutput;
    }

    public function matchCommand(string $command): bool
    {
        if (is_null($this->expectedCommand)) {
            return true;
        }

        if (is_callable($this->expectedCommand)) {
            return call_user_func($this->expectedCommand, $command);
        }

        return $this->expectedCommand == $command;
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
}
