<?php

namespace Knutle\ShellExec\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ErrorOutputEmittedEvent
{
    use Dispatchable;

    public string $line;

    public function __construct(string $line)
    {
        $this->line = $line;
    }
}
