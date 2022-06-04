<?php

use Knutle\ShellExec\Tests\TestCase;
use Symfony\Component\VarDumper\Dumper\CliDumper;

uses(TestCase::class)->in(__DIR__);

function captureCliDumperOutput(): object
{
    static $loaded;
    static $buffer;

    if (is_null($loaded)) {
        $buffer = new class () {
            public static string $data = '';
        };

        CliDumper::$defaultOutput = function (string $line, int $depth, string $indentPad) use ($buffer) {
            if (-1 !== $depth) {
                $buffer::$data .= str_repeat($indentPad, $depth).$line."\n";
            }
        };

        $loaded = true;
    } else {
        $buffer::$data = '';
    }

    return $buffer;
}

/**
 * @throws Exception
 */
function resolveCommandsForCurrentOs(array $commands): array|string
{
    return $commands[PHP_OS] ?? throw new Exception('Unable to resolve command set for OS ' . PHP_OS);
}
