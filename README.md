
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# Execute shell commands with precision

[![Latest Version on Packagist](https://img.shields.io/packagist/v/knutle/shell-exec.svg?style=flat-square)](https://packagist.org/packages/knutle/shell-exec)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/knutle/shell-exec/run-tests?label=tests)](https://github.com/knutle/shell-exec/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/knutle/shell-exec/Check%20&%20fix%20styling?label=code%20style)](https://github.com/knutle/shell-exec/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/knutle/shell-exec.svg?style=flat-square)](https://packagist.org/packages/knutle/shell-exec)

Execute commands and easily determine status, read standard output or error output separately. Comes with helpers for testing to mock output or set expectations for commands to receive. 

## Installation

You can install the package via composer:

```bash
composer require knutle/shell-exec
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="shell-exec-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$shellExec = new Knutle\ShellExec();
echo $shellExec->echoPhrase('Hello, Knutle!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Knut Leborg](https://github.com/knutle)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
