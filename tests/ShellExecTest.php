<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Illuminate\Support\Str;
use Knutle\ShellExec\Facades\ShellExec;
use Knutle\ShellExec\Shell\Runner;
use Knutle\ShellExec\Shell\ShellExecFakeResponse;
use function Spatie\Snapshots\assertMatchesTextSnapshot;

it('can execute shell command', function () {
    expect((string)ShellExec::run("php -i"))
        ->toContain('PHP Version => ');
});

it('can execute mocked shell commands in order with separately resolved objects', function () {
    ShellExec::fake([
        'test',
        'abc212',
    ]);

    expect((string)ShellExec::run("php -i"))
        ->toEqual('test')
        ->and((string)ShellExec::run("completely invalid command! ***"))
        ->toEqual('abc212');
});

it('can split response into lines', function () {
    ShellExec::fake([
        implode("\n", [
            'root',
            'daemon',
            'bin',
        ]),
    ]);

    expect(ShellExec::run("php -i")->lines())
        ->toEqual([
            'root',
            'daemon',
            'bin',
        ]);
});

it('can record fake commands to object history', function () {
    ShellExec::fake([
        'test',
        'abc212',
    ]);

    expect((string)ShellExec::run("php -i"))
        ->toEqual('test')
        ->and(ShellExec::history()->toArray())
        ->toEqual([
            [
                'command' => 'php -i',
                'output' => 'test',
                'error' => '',
                'exit_code' => 0,
                'faked' => true,
            ],
        ])
        ->and((string)ShellExec::run("ls"))
        ->toEqual('abc212')
        ->and(ShellExec::history()->toArray())
        ->toEqual([
            [
                'command' => 'php -i',
                'output' => 'test',
                'error' => '',
                'exit_code' => 0,
                'faked' => true,
            ],
            [
                'command' => 'ls',
                'output' => 'abc212',
                'error' => '',
                'exit_code' => 0,
                'faked' => true,
            ],
        ]);
});

it('can record real commands to object history', function () {
    expect((string)ShellExec::run("php -i"))
        ->toContain('PHP Version => ')
        ->and(ShellExec::history()->pluck('command')->toArray())
        ->toEqual([
            'php -i',
        ])
        ->and(ShellExec::history()->pluck('output')->first())
        ->toContain('PHP Version => ')
        ->and((string)ShellExec::run("cat composer.json"))
        ->toContain('"name": "knutle/shell-exec",')
        ->and(ShellExec::history()->pluck('command')->toArray())
        ->toEqual([
            'php -i',
            'cat composer.json',
        ])
        ->and(ShellExec::history()->pluck('output')->last())
        ->toContain('"name": "knutle/shell-exec",');
});

it('can throw if fake receives unexpected command', function () {
    ShellExec::fake([
        new ShellExecFakeResponse('cmd1', 'test'),
    ]);

    ShellExec::run("php -i");
})->throws('Mock expected command \'cmd1\' but received \'php -i\'');

it('can throw on unexpected command when combining fake responses with and without command expectations', function () {
    ShellExec::fake([
        'test',
        new ShellExecFakeResponse('cmd2', 'abc212'),
    ]);

    expect((string)ShellExec::run("php -i"))
        ->toEqual('test')
        ->and((string)ShellExec::run("completely invalid command! ***"))
        ->toEqual('abc212');
})->throws('Mock expected command \'cmd2\' but received \'completely invalid command! ***\'');

it('can combine fake responses with and without command expectations but still verify output', function () {
    ShellExec::fake([
        'test',
        'cmd2' => 'abc212',
        'test2',
    ]);

    expect((string)ShellExec::run("php -i"))
        ->toEqual('test')
        ->and((string)ShellExec::run("cmd2"))
        ->toEqual('abc212')
        ->and((string)ShellExec::run("invalid command"))
        ->toEqual('test2');
});

it('can queue exception in fake responses to trigger error response for command', function () {
    ShellExec::fake([
        'error' => new Exception('there was a problem'),
    ]);

    expect(ShellExec::run('error'))
        ->toHaveProperty('output', '')
        ->toHaveProperty('command', 'error')
        ->toHaveProperty('error', 'there was a problem')
        ->toHaveProperty('exitCode', 1);
});

it('can throw on reaching end of mock queue', function () {
    ShellExec::fake([
        'test',
    ]);

    expect((string)ShellExec::run('error'))
        ->toEqual('test')
        ->and(fn () => ShellExec::run('error'))
        ->toThrow('Mock queue is empty');
});

it('can continue with empty responses on reaching end of mock queue if always respond is enabled', function () {
    ShellExec::fake([
        'test',
    ], SHELL_EXEC_FAKE_ALWAYS_RESPOND);

    expect((string)ShellExec::run('error'))
        ->toEqual('test')
        ->and((string)ShellExec::run('error'))
        ->toEqual('')
        ->and((string)ShellExec::run('error'))
        ->toEqual('');
});

it('can pass callable as expected command', function () {
    ShellExec::fake([
        new ShellExecFakeResponse(
            fn (string $command) => Str::of($command)->is('custom * command'),
            'test output'
        ),
    ]);

    expect((string)ShellExec::run("custom matching command"))
        ->toEqual('test output');
});

it('can throw if callable does not verify command', function () {
    ShellExec::fake([
        new ShellExecFakeResponse(
            fn (string $command) => Str::of($command)->is('custom * command'),
            'test output'
        ),
    ]);

    expect((string)ShellExec::run('error'))
        ->toEqual('test output');
})->throws('Mock received unexpected command \'error\'');

it('can dump individual commands', function () {
    ShellExec::fake([
        'test',
    ], SHELL_EXEC_FAKE_ALWAYS_RESPOND | SHELL_EXEC_FAKE_DUMP_COMMANDS);

    $buffer = captureCliDumperOutput();

    expect((string)ShellExec::run('cmd1'))
        ->toEqual('test')
        ->and((string)ShellExec::run('cmd2'))
        ->toEqual('')
        ->and((string)ShellExec::run('cmd3'))
        ->toEqual('');

    assertMatchesTextSnapshot($buffer::$data);
});

it('can dump history on empty mock queue', function () {
    ShellExec::fake([
        'test1',
        'test2',
        'test3',
    ], SHELL_EXEC_FAKE_DUMP_HISTORY_ON_EMPTY_MOCK_QUEUE);

    $buffer = captureCliDumperOutput();

    ShellExec::run('cmd1');
    ShellExec::run('cmd2');
    ShellExec::run('cmd3');
    expect(fn () => ShellExec::run('cmd4'))->toThrow('Mock queue is empty');

    assertMatchesTextSnapshot($buffer::$data);
});

it('can record real commands to object history directly on runner', function () {
    $runner = new Runner();

    expect((string)$runner->run("php -i"))
        ->toContain('PHP Version => ')
        ->and($runner->history()->pluck('command')->toArray())
        ->toEqual([
            'php -i',
        ]);
});

