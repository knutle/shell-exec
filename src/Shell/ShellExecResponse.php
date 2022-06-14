<?php

namespace Knutle\ShellExec\Shell;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
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

    /**
     * @throws ShellExecException
     */
    public function verify(string $failMessage, Closure $test = null): static
    {
        if (is_null($test)) {
            $test = fn (ShellExecResponse $response) => $response->success();
        }

        if (! $test($this)) {
            if (! empty($this->error)) {
                $failMessage .= "\n$this->error";
            }

            throw new ShellExecException($failMessage);
        }

        return $this;
    }

    public function success(): bool
    {
        return empty($this->error) && $this->exitCode == 0;
    }

    public function collect(): Collection
    {
        return collect($this->lines());
    }

    public function lines(): array
    {
        return explode("\n", $this->output);
    }

    public function __toString(): string
    {
        return $this->output;
    }

    public function toString(): string
    {
        return (string)$this;
    }

    public function dump(): static
    {
        dump($this->toArray());

        return $this;
    }

    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'output' => $this->output,
            'error' => $this->error,
            'exit_code' => $this->exitCode,
            'faked' => $this->faked,
            'success' => $this->success(),
            'failed' => $this->failed(),
        ];
    }

    public function failed(): bool
    {
        return ! empty($this->error) || $this->exitCode != 0;
    }
}
