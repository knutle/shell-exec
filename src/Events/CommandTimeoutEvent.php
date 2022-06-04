<?php

namespace Knutle\ShellExec\Events;

use Illuminate\Foundation\Events\Dispatchable;

class CommandTimeoutEvent
{
    use Dispatchable;

    public function __construct(
        public int $timeout,
        public int $elapsed
    ) {
        //
    }
}
