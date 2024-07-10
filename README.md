# config-setup

Preconfigured setup files for linting, fixing, and testing PHP and JavaScript projects

## Initial Setup

Add this script to `composer.json` to make links to the config files in your project when you run
`composer install` or `composer update`:

```
{
    "scripts": {
        "post-install-cmd": "config-setup",
        "post-update-cmd": "config-setup"
    }
}
```

The config files will be linked every time you run `composer update` or `composer install`. The link
is a symlink to the original file.

The file linker skips linking files if:

-   The relevant project was not found in your `composer.json` or `package.json` file.
-   The same file is committed into your Git project and shouldn't be overwritten.
-   The same file was already linked before and the link is unchanged.

The file copier will add the list of links to `/.git/info/exclude` to exclude them from being
committed to Git.

## Cloning

After you clone a repository that uses this config setup library, run `composer install` to copy the
files into place. Then you can run `script/setup` to complex the install process and get the Husky
hooks to work.

### Arguments

When running `config-setup`, there are some possible arguments:

-   `--pre-commit`: Use the `pre-commit` hook to do linting.
-   `--pre-push`: Use the `pre-push` hook to do linting.
-   `--wordpress`: Install the `stubs/wordpress.php` file and WordPress extension for PHPStan to
    use.
-   `--woocommerce`: Install the WooCommerce extension for PHPStan to use.
-   `--wrap INT` or `-w INT`: Set a different integer (INT) wrap than the default of 100.

### WordPress Stubs

The WordPress stubs here are for third-party code, including Goodlayers and Infinite Theme. For the
main WordPress stubs, just install:

-   [phpstan-wordpress](https://github.com/szepeviktor/phpstan-wordpress)
-   [woocommerce-stubs](https://github.com/php-stubs/woocommerce-stubs)

The file linker automatically adds the include and bootstrap lines for the stub files to
phpstan.neon so you don't need to use the PHPStan extension installer.

### Environment Variables

The ECS file `ecs.php` checks if `ECS_RISKY` is true before running risky tests.

### File Customization

Several scripts are customized during the linking process.

-   `ecs.php` sets the value of `line_length` to the value specified in the `--wrap` parameter.
-   `.eslintrc.json` adds an "extends" field if the Standard (eslint-config-standard) or Airbnb
    (eslint-config-airbnb-base) NPM packages are installed.
-   `phpstan.neon` updates the phpVersion field to the "require" php version in `composer.json`.
-   `phpunit.xml` adds the list of PHP file directories to cover and adds coverage options if either
    pcov or xdebug is detected as a code coverage driver.
-   `.prettierrc.json` adds any Prettier plugins it finds in `package.json` to the "plugins" list
    and updates the `printWidth` to the value specified in the `--wrap` parameter.

## Setup Scripts

This project uses the
[GitLab script system](https://github.blog/2015-06-30-scripts-to-rule-them-all/).

-   To install project dependencies, run `script/bootstrap`.
-   To set up the project, run `script/setup`.
-   To lint the project, run `script/lint`.
-   To lint:fix the project, run `script/fix`.
-   To test the project, run `script/test`.
-   To update the project, run `script/update`.

Most of the scripts just install Composer and NPM dependencies and run scripts for linting, fixing,
and testing.

The setup and update scripts attempt to do more by:

-   Running `script/setup-db` or `script/update-db` for database updates if those scripts exist.
-   Doing `source .env` to set up or update environment variables if the `.env` file exists.

You can create those files to use for standard project configuration.

## Installing Dependencies

Once the config files are linked, you need to install the right project dependencies for each
project and define a script for it if you want to use those tools with those config files. The
php-linter script is included in this project.

### PHP Dependencies

For PHP, that is done with `composer.json` like this:

```
    "require-dev": {
        "phpstan/phpstan": "^1.11",
        "phpunit/phpunit": "^10.5",
        "rector/rector": "^1.2",
        "symplify/easy-coding-standard": "^12.3"
    },
    "scripts": {
        "lint": [
            "ecs",
            "php-linter",
            "phpstan analyse",
            "rector --dry-run"
        ],
        "lint:fix": [
            "ecs --fix",
            "rector"
        ],
        "test": "phpunit"
    }
```

That installs:

-   [PHP Linter](php_linter.md) for code metrics and linting
-   [PHPStan](https://phpstan.org/) for linting
-   [PHPUnit](https://phpunit.de/index.html) for unit tests
-   [Rector](https://github.com/rectorphp/rector) for linting and fixing (reformatting and
    refactoring)
-   [Easy Coding Standard](https://github.com/easy-coding-standard/easy-coding-standard) (ECS) for
    linting and fixing

Each of the commands is configured to use the list of files in `php_paths`. This file is generated
automatically by this project's file linker, which makes a list of the directories and PHP files in
the top level of your project. That enables all of the tools to automatically lint and fix the right
set of files.

For JavaScript/NPM, that is done with `package.json` like this:

```
    "devDependencies": {
        "@commitlint/cli": "^19.3",
        "@commitlint/config-conventional": "^19.2",

        "husky": "^9.0",

        "prettier": "^3.3",
        "prettier-plugin-sh": "^0.14",
        "@prettier/plugin-xml": "^3.4",

        'standard' => '^17.1',

        "stylelint": "^16.6",
        "stylelint-config-standard": "^36.0"
    },
    "scripts": {
        "commitlint": "commitlint --edit",
        "lint": "standard . && stylelint '**/*.css'",
        "lint:fix": "prettier --write . && standard --fix .",
        "prepare": "husky"
    }
```

That installs:

-   [Commitlint](https://commitlint.js.org/) for linting commit messages and
    [@commitlint/config-conventional](https://www.npmjs.com/package/@commitlint/config-conventional)
    for a typical set of rules
-   [Husky](https://www.npmjs.com/package/husky) to run the GitHub actions defined as scripts in the
    `.husky` directory
-   [Standard](https://standardjs.com/) JavaScript linter and formatter
-   [Prettier](https://prettier.io/) for linting and fixing with some plugins
-   [Stylelint](https://stylelint.io/) for CSS linting with its standard plugin

Alternatives include:

-   [ESLint](https://eslint.org/) for linting and fixing and
    [eslint-config-standard](https://github.com/standard/eslint-config-standard) and its required
    dependencies for a typical set of rules

If you install ESlint instead of Standard, you should use its caching feature like this:

```
        "lint": "eslint --cache --cache-location var/cache/eslint/cache . && stylelint '**/*.css'",
        "lint:fix": "prettier --write . && eslint --cache --cache-location var/cache/eslint/cache --fix .",
```

You might also want to install Jest, Vite, or Mocha to do JavaScript unit tests.

## Linting, Fixing, and Testing

### PHP

Scripts to run as needed include:

-   Lint: `composer lint`
-   Fix: `composer lint:fix`
-   Test: `composer test`

### JavaScript

Scripts to run as needed include:

-   Lint: `npm run lint`
-   Fix: `npm run lint:fix`
-   Test: `npm run test` (not shown here)

Automatic scripts include:

-   Commitlint: this script is run by a Husky hook
-   Prepare: this script is run automatically to prepare Husky

## Husky Hooks

Linting and testing are automatically run by `.husky/pre-commit` (or `.husky/pre-push`). Fix any
errors or use `--no-verify` to bypass the check.

Project setup is automatically run by `.husky/post-checkout` and `.husky/post-merge`. That updates
your Composer and NPM dependencies in case your dependencies were changed by incoming code.

[Conventional Commits](https://www.npmjs.com/package/@commitlint/config-conventional) are enforced
by `.husky/commit-msg`. Fix any commit message errors before committing.

## Result Caching

Each of the PHP formatting tools and ESLint is configured to cache its results for speedier
operation using use subdirectories of the `var/cache` directory. These directories are automatically
created by the `bin/config-setup` script if they don't exist.

## Troubleshooting

Sometimes Rector has trouble deleting files from the cache and gives errors. When that happens, just
`rm -Rf var/*` and rerun `composer install`.

## More Information

For more information about the decisions behind configuration choices, see
[Configuration Choices](config_choices.md).
