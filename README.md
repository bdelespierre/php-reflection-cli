# Laravel Blade Linter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bdelespierre/php-reflection-cli.svg?style=flat-square)](https://packagist.org/packages/bdelespierre/php-reflection-cli)
[![Build Status](https://img.shields.io/travis/bdelespierre/php-reflection-cli/master.svg?style=flat-square)](https://travis-ci.org/bdelespierre/php-reflection-cli)
[![Quality Score](https://img.shields.io/scrutinizer/g/bdelespierre/php-reflection-cli.svg?style=flat-square)](https://scrutinizer-ci.com/g/bdelespierre/php-reflection-cli)
[![Total Downloads](https://img.shields.io/packagist/dt/bdelespierre/php-reflection-cli.svg?style=flat-square)](https://packagist.org/packages/bdelespierre/php-reflection-cli)

This tool provides a command line interface (CLI) to explore the classes and methods of your PHP project.

## Installation

This tool is distributed as a PHP Archive (PHAR):

```bash
wget ---

php php-reflection-cli.phar --version
```

Using Phive is the recommended way for managing the tool dependencies of your project:

```bash
phive install php-reflection-cli

./tools/php-reflection-cli --version
```

You can also install the package via composer:

```bash
composer require --dev bdelespierre/php-reflection-cli
```

## Usage

```bash
# finds all PHP classes in the src/ directory
./tools/php-reflection-cli find:classes src/

# finds all vendor classes and display them as a tree
./tools/php-reflection-cli find:classes --tree vendor/

# find methods of a given class (don't forget to escape backshashes)
./tools/php-reflection-cli find:methods Some\\Package\\Namespace\\Class
```

PHP Reflction CLI is compatible with your favorite tools like grep and xargs!

For example, display controllers' actions:

```bash
# use -e (--escape) option to pass classes through xargs
./tools/php-reflection-cli find:classes -e src/ \
  | grep Controller \
  | xargs -i -n1 ./tools/php-reflection-cli find:methods --short {}
```

### Testing

``` bash
composer test
```

### Building

```bash
# build the project
composer build
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email benjamin.delespierre@gmail.com instead of using the issue tracker.

## Credits

- [Benjamin Delespierre](https://github.com/bdelespierre)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
