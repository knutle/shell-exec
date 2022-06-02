<?php

namespace Knutle\ShellExec\Shell;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\NoReturn;
use Knutle\ShellExec\Exceptions\ShellExecException;
use Stringable;

class ShellExecResponse implements Stringable, Arrayable
{
    public string $output;
    public string $command;
    public string $error;
    public int $exitCode;
    public bool $faked;

    public function __construct(string $command, string $output, string $error, int $exitCode, bool $faked = false)
    {
        $this->command = $command;
        $this->output = trim($output);
        $this->error = trim($error);
        $this->exitCode = $exitCode;
        $this->faked = $faked;
    }

    public function success(): bool
    {
        return empty($this->error) && $this->exitCode == 0;
    }

    public function failed(): bool
    {
        return !empty($this->error) || $this->exitCode != 0;
    }

    /**
     * @throws ShellExecException
     */
    public function verify(string $failMessage, Closure $test = null): static
    {
        if(is_null($test)) {
            $test = fn (ShellExecResponse $response) => $response->success();
        }

        if(!$test($this)) {
            if(!empty($this->error)) {
                $failMessage .= "\n$this->error";
            }

            throw new ShellExecException($failMessage);
        }

        return $this;
    }

    #[NoReturn] public function debug(): void
    {
        dd(
            "Command returned exit code $this->exitCode\n\n",
            "--- COMMAND ---\n$this->command\n--- /COMMAND ---\n\n",
            "--- OUTPUT ---\n$this->command\n--- /OUTPUT ---\n\n",
            "--- STDERR ---\n$this->error\n--- /STDERR ---\n\n",
        );
    }

    public function lines(): array
    {
        return explode("\n", $this->output);
    }

    public function collect(): Collection
    {
        return collect($this->lines());
    }

    public function __toString(): string
    {
        return $this->output;
    }

    public function toString(): string
    {
        return (string)$this;
    }

    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'output' => $this->output,
            'error' => $this->error,
            'exit_code' => $this->exitCode,
            'faked' => $this->faked,
        ];
    }

    public function dump(): static
    {
        dump($this->toArray());

        return $this;
    }
}
