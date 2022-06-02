<?php

namespace Knutle\ShellExec\Commands;

use Illuminate\Console\Command;

class ShellExecCommand extends Command
{
    public $signature = 'shell-exec';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
