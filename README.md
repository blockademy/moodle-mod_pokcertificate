# The pok certificate activity

This activity allows the dynamic generation of POK certificates with complete customisation via the POK API's.

## Installation

There are two installation methods that are available.

Follow one of these, then log into your Moodle site as an administrator and visit the notifications page to complete the install.

## Testing

- Link the plugin folder to the moodle root: `ln -s ~/path/to/plugin/moodle-mod_pokcertificate/ /path/to/moodle/mod/pokcertificate`
- Install phpunit
- Make sure the correct `php` is available and set in your `PATH`
- Bootstrap phpunit: `php admin/tool/phpunit/cli/init.php`
- Run tests: `vendor/bin/phpunit`
- Run tests only for this plugin: `vendor/bin/phpunit --testsuite mod_pokcertificate_testsuite`
- Run specific test file: `vendor/bin/phpunit mod/pokcertificate/tests/generator_test.php`
