<?php

namespace Knutle\ShellExec\Events;

use Illuminate\Foundation\Events\Dispatchable;

class StandardOutputEmittedEvent
{
    use Dispatchable;

    public string $line;

    public function __construct(string $line)
    {
        $this->line = $line;
    }
}
