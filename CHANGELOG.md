# Changelog

All notable changes to `shell-exec` will be documented in this file.

## v0.1.9 - 2022-06-14

- append output instead of error in verify fail message suffix if error is empty

## v0.1.8 - 2022-06-05

- do not resolve null output from container to prevent double writing

## v0.1.7 - 2022-06-05

- implement flag to redirect stderr to stdout
- drop effort to support windows for now
- add success/failed to array serialization
- small fixes

## v0.1.6 - 2022-06-04

- add flag for writing live output from command
- emit events for standard/error output lines
- implement process timeout

## v0.1.5 - 2022-06-04

- remove fake error response exit code substitution

## v0.1.4 - 2022-06-04

- fix passing exception as expected output to trigger error with fake response with partial fake

## v0.1.3 - 2022-06-04

- allow passing exception as expected output to trigger error with fake response

## v0.1.2 - 2022-06-04

- implement partial fake
- fix windows tests

## v0.1.1 - 2022-06-02

- implement input to stdin
- fixes for windows

## v0.1.0 - 2022-06-02

- initial release
