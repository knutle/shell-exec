<?php

/** @noinspection PhpUnhandledExceptionInspection */

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Knutle\ShellExec\Events\CommandTimeoutEvent;
use Knutle\ShellExec\Events\StandardErrorEmittedEvent;
use Knutle\ShellExec\Events\StandardOutputEmittedEvent;
use Knutle\ShellExec\Facades\ShellExec;
use Knutle\ShellExec\Shell\Runner;
use Knutle\ShellExec\Shell\ShellExecFakeResponse;
use Knutle\ShellExec\Shell\ShellExecResponse;
use function Spatie\Snapshots\assertMatchesTextSnapshot;
use Symfony\Component\Console\Output\BufferedOutput;

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
        ->toHaveProperty('exitCode', 0);
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

it('can reset fake', function () {
    ShellExec::fake([
        'test1',
        'test2',
        'test3',
    ]);

    expect((string)ShellExec::run('cmd1'))
        ->toEqual('test1')
        ->and(ShellExec::history()->pluck('command')->first())
        ->toEqual('cmd1');

    ShellExec::reset();

    expect((string)ShellExec::run("php -i"))
        ->toContain('PHP Version => ')
        ->and(ShellExec::history()->pluck('command')->first())
        ->toEqual('php -i')
        ->and(ShellExec::history()->count())
        ->toEqual(1);
});

it('can handle proc_open failure', function () {
    $mock = mock(Runner::class);

    $mock->expect(
        procOpen: fn () => false
    );

    $mock->makePartial();

    app()->bind(Runner::class, fn () => $mock);

    ShellExec::run("php -i");
})->throws('Unable to get info from process');

it('can pass array of commands', function () {
    $response = ShellExec::run(['echo test1', 'echo test2']);

    $output = $response->output;
    $command = $response->command;

    if (PHP_OS == 'WINNT') {
        $output = str_replace("\r\n", "\n", collect(explode("\n", $output))->map(fn (string $line) => trim($line))->join("\n"));
        $command = str_replace("\r\n", "\n", collect(explode("\n", $command))->map(fn (string $line) => trim($line))->join("\n"));
    }

    expect($output)
        ->toEqual(collect(['test1', 'test2'])->map(fn (string $str) => trim($str))->join("\n"))
        ->and($command)
        ->toEqual(collect(['echo test1', 'echo test2'])->map(fn (string $str) => trim($str))->join(PHP_OS == 'WINNT' ? ' && ' : "\n"));
});

it('can pass array of commands on windows', function () {
    ShellExec::fake([
        implode(" \r\n", ['test1', 'test2']),
    ]);

    $response = ShellExec::run(['echo test1', 'echo test2']);

    $output = $response->output;
    $command = $response->command;

    $output = str_replace("\r\n", "\n", collect(explode("\n", $output))->map(fn (string $line) => trim($line))->join("\n"));
    $command = str_replace("\r\n", "\n", collect(explode("\n", $command))->map(fn (string $line) => trim($line))->join("\n"));

    expect($output)
        ->toEqual(collect(['test1', 'test2'])->map(fn (string $str) => trim($str))->join("\n"))
        ->and($command)
        ->toEqual(collect(['echo test1', 'echo test2'])->map(fn (string $str) => trim($str))->join(' && '));
})->skip(PHP_OS != 'WINNT');

it('can pass array of commands to fake', function () {
    ShellExec::fake([
        implode(PHP_EOL, ['test1', 'test2']),
    ]);

    expect(ShellExec::run(['echo test1', 'echo test2']))
        ->toHaveProperty('output', implode(PHP_EOL, ['test1', 'test2']))
        ->toHaveProperty('command', implode(
            PHP_OS == 'WINNT' ? ' && ' : PHP_EOL,
            ['echo test1', 'echo test2']
        ));
});

it('will not check expected command if it is null', function () {
    ShellExec::fake([
        new ShellExecFakeResponse(null, 'test'),
    ]);

    expect((string)ShellExec::run('abc'))
        ->toEqual('test');
});


it('can determine when response has failed', function () {
    ShellExec::fake([
        'test',
        'test',
        new Exception('there was an error'),
        new Exception('there was an error'),
    ]);

    expect(ShellExec::run('abc')->success())
        ->toBeTrue()
        ->and(ShellExec::run('abc')->failed())
        ->toBeFalse()
        ->and(ShellExec::run('abc')->success())
        ->toBeFalse()
        ->and(ShellExec::run('abc')->failed())
        ->toBeTrue();
});

it('can verify response with default logic', function () {
    ShellExec::fake([
        'test',
        new Exception('there was an error'),
    ]);

    ShellExec::run('abc')->verify('Should not fail');

    expect(fn () => ShellExec::run('abc')->verify('Command has failed!'))
        ->toThrow('Command has failed!');
});

it('can verify response with custom logic', function () {
    ShellExec::fake([
        new Exception('pretend this is not an error'),
        'pretend this is actually an error',
    ]);

    ShellExec::run('abc')->verify('Should not fail', fn (ShellExecResponse $response) => $response->error == 'pretend this is not an error');

    expect(fn () => ShellExec::run('abc')->verify('Command has failed!', fn (ShellExecResponse $response) => $response->output != 'pretend this is actually an error'))
        ->toThrow('Command has failed!');
});

it('can dump to string explicitly', function () {
    ShellExec::fake([
        'test',
    ]);

    expect(ShellExec::run('abc')->toString())
        ->toEqual('test');
});

it('can collect lines from response', function () {
    ShellExec::fake([
        implode("\n", [
            'test1', 'test2', 'test3',
        ]),
    ]);

    expect(ShellExec::run('abc')->collect()->join(', '))
        ->toEqual('test1, test2, test3');
});

it('can invoke with input to stdin', function () {
    expect(
        ShellExec::run([
            'while read line; do',
            'printf "$line,"',
            'done',
        ], "test\ntest2\n")
    )->toEqual('test,test2,');
})->skip(PHP_OS == 'WINNT');

it('can use partial fake', function () {
    ShellExec::fake([
        new ShellExecFakeResponse('test', 'test out'),
    ], SHELL_EXEC_PARTIAL_FAKE | SHELL_EXEC_FAKE_ALWAYS_RESPOND);

    expect((string)ShellExec::run('abc'))
        ->toEqual('')
        ->and((string)ShellExec::run('abc2'))
        ->toEqual('')
        ->and((string)ShellExec::run('test'))
        ->toEqual('test out')
        ->and((string)ShellExec::run('abc'))
        ->toEqual('');
});

it('can pass exception as expected output to trigger error with fake response', function () {
    ShellExec::fake([
        new ShellExecFakeResponse('test1', new Exception('there was an error!', 2)),
        new ShellExecFakeResponse('test2', new Exception('there was another error!')),
        new ShellExecFakeResponse('test3', new Exception('so many errors!')),
    ]);

    expect(ShellExec::run('test1'))
        ->toHaveProperty('output', '')
        ->toHaveProperty('error', 'there was an error!')
        ->toHaveProperty('exitCode', 2)
        ->and(ShellExec::run('test2'))
        ->toHaveProperty('output', '')
        ->toHaveProperty('error', 'there was another error!')
        ->toHaveProperty('exitCode', 0)
        ->and(fn () => ShellExec::run('test2'))
        ->toThrow("Mock expected command 'test3' but received 'test2'");
});

it('can pass exception as expected output to trigger error with fake response with partial fake', function () {
    ShellExec::fake([
        new ShellExecFakeResponse('test2', new Exception('there was another error!')),
    ], SHELL_EXEC_PARTIAL_FAKE | SHELL_EXEC_FAKE_ALWAYS_RESPOND);

    expect(ShellExec::run('test1')->success())->toBeTrue()
        ->and(ShellExec::run('test19')->success())->toBeTrue()
        ->and(ShellExec::run('test2'))
        ->toHaveProperty('output', '')
        ->toHaveProperty('error', 'there was another error!')
        ->toHaveProperty('exitCode', 0);
});

it('can return string response from partial fake', function () {
    ShellExec::fake([
        'test',
    ], SHELL_EXEC_PARTIAL_FAKE);

    expect((string)ShellExec::run('test1'))
        ->toEqual('test');
});

it('can fallback to no match when matching command but no strategies were resolved', function () {
    // this is mostly just a workaround to get code coverage on the last return in matchCommand, which should never
    // trigger, but code inspection complains about inconsistent return points if I leave it out...

    $fake = new ShellExecFakeResponse(null, 'test');
    $fake->expectedCommand = 123;

    ShellExec::fake([
        $fake,
    ]);

    ShellExec::run('123');
})->throws("Mock expected command '123' but received '123'");

it('can get live output from commands', function () {
    $mock = mock(Runner::class);

    $buffer = new BufferedOutput();

    $mock->expect(
        getConsoleOutput: fn () => $buffer
    );

    $mock->makePartial();

    app()->bind(Runner::class, fn () => $mock);

    ShellExec::run(['echo out 1', 'echo out 2'], null, SHELL_EXEC_RUNNER_WRITE_LIVE_OUTPUT);

    assertMatchesTextSnapshot($buffer->fetch());

    ShellExec::reset();

    ShellExec::run(['echo out 1', 'echo out 2'], null, SHELL_EXEC_RUNNER_WRITE_LIVE_OUTPUT);
});

it('can emit events for lines written to stdout and stderr', function () {
    Event::fake();

    ShellExec::run(
        ['echo out 1', 'echo out 2']
    );

    ShellExec::run(
        ['echo err 1 1>&2', 'echo err 2 1>&2']
    );

    Event::assertDispatched(fn (StandardOutputEmittedEvent $event) => $event->line == 'out 1');
    Event::assertDispatched(fn (StandardOutputEmittedEvent $event) => $event->line == 'out 2');

    Event::assertDispatched(fn (StandardErrorEmittedEvent $event) => $event->line == 'err 1');
    Event::assertDispatched(fn (StandardErrorEmittedEvent $event) => $event->line == 'err 2');
});

it('can close pipes and exit process when timeout reached', function () {
    Event::fake();

    expect(
        ShellExec::timeout(1)
            ->run([
                'echo a 1',
                'sleep 0.5',
                'echo a 2',
                'sleep 5',
                'echo a 3',
            ])
    )
        ->toHaveProperty('output', "a 1\na 2")
        ->toHaveProperty('exitCode', 1);

    Event::assertDispatched(fn (CommandTimeoutEvent $event) => $event->timeout == 1 && $event->elapsed > 1);
});
