# Project Configuration

Here are notes on the configuration choices made for each project.

## PHP

### Easy Coding Standard (ECS)

Here is a summary of the configuration options in the `ecs.php` file:

-   **Risky Changes**: Enable by setting the environment variable `ECS_RISKY` to true.
-   **PHP Version Detection**: Sets PHP version based on the version specified in `composer.json`.
-   **Package Detection**: Enables or disables relevant ECS rule sets based on the presence of
    PHPUnit, Symfony, or Doctrine in `composer.json`.
-   **Annotation Removal**: Configures the list of annotations to remove using
    `GeneralPhpdocAnnotationRemoveFixer`.
-   **Line Length Fixer**: Uses `Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer` to
    enforce line length rules.
-   **Good Coverage**: Utilizes a comprehensive set of standard rulesets to ensure high code quality
    and consistency. These include:
    -   **PhpCsFixer Rulesets**:
        -   **PSR-12**: Enforces the PHP Standard Recommendations (PSR-12) for code style.
        -   **Symfony**: Applies Symfony coding standards for projects using the Symfony framework.
        -   **Doctrine**: Ensures best practices and standards for projects using Doctrine.
    -   **Symplify Rulesets**:
        -   **Coding Standard**: A set of rules to maintain a consistent coding style across PHP
            projects.
        -   **Coding Style**: Focuses on the aesthetics of the code, such as spacing, indentation,
            and line breaks.
    -   **Other Notable Rulesets**:
        -   **Clean Code**: Ensures that the codebase adheres to clean code principles, making it
            more readable and maintainable.
        -   **PHPUnit**: Applies coding standards and best practices specific to writing PHPUnit
            tests.
        -   **PHPStan**: Integrates static analysis checks to catch potential errors and improve
            code reliability.
        -   **Custom Fixers**: Allows for the inclusion of custom fixers to address specific coding
            standards not covered by existing rulesets.

These rulesets collectively ensure that the codebase adheres to industry standards, follows best
practices, and maintains a high level of code quality.

### Rector

Here is a summary of the configuration options in the `rector.php` file:

-   **Upgrades**: Enable by setting the environment variable `RECTOR_UPGRADE` to true.
-   **PHP Version Detection**: Set PHP version for upgrades based on the version specified in
    `composer.json`.
-   **Package Detection**: Enables or disables relevant ECS rule sets based on the presence of
    PHPUnit, Symfony, or Doctrine in `composer.json`.
-   **Import Names**: Configure import names with options to not import short classes and to remove
    unused imports.
-   **PHP Sets**: Configure Rector with default PHP sets.
-   **Attribute Sets**: Enable attribute sets for Doctrine, FOSRest, Gedmo, JMS, MongoDB, PHPUnit,
    SensioLabs, and Symfony based on their presence.
-   **Prepared Sets**: Enable prepared sets for code quality, coding style, dead code, early return,
    instanceOf, naming, privatization, strict booleans, and type declarations.
-   **Skip Specific Rules**: Skip specific rules such as `AddSeeTestAnnotationRector`.

This configuration file sets up various coding standards and rules for PHP projects using the Rector
tool.

### PHP Mess Detector (PHPMD)

Here is a description of the configuration choices made in the `phpmd.xml` file:

-   **Exclusion of Stubs**: Exclude empty classes and functions defined for PHPStan (`stubs/*`).
-   **Exclusion of Unit Test Files**: Exclude all unit test files (`tests/*`).

-   **Clean Code Ruleset**:

    -   **Excluded Rules**:
        -   `ElseExpression`: Avoiding else expressions is not necessary.
        -   `IfStatementAssignment`: No confusion between `=` and `===`.
        -   `StaticAccess`: Calling static functions is sometimes necessary.

-   **Code Size Ruleset**:

    -   **Excluded Rules**:
        -   `ExcessiveClassComplexity`: Redundant and not visible in code.
        -   `NPathComplexity`: Not good measurements.
        -   `CyclomaticComplexity`: Overcounts case statements.
        -   `ExcessivePublicCount`: Redundant as it lumps together public properties and methods.
        -   `TooManyFields`: Protected and private properties are not part of the class API.
        -   `TooManyMethods`: Lumps together public, protected, and private methods.

-   **Controversial Ruleset**: Include the entire controversial ruleset.
-   **Design Ruleset**: Include the entire design ruleset.
-   **Naming Ruleset**: Include the entire naming ruleset.
-   **Unused Code Ruleset**: Include the entire unused code ruleset.

-   **Customized Rules**:
    -   **Excessive Class Length**: Set the minimum class length to 2500 lines, covering 99% of PHP
        classes.
    -   **Excessive Method Length**: Set the minimum method length to 150 lines, covering 99% of PHP
        functions.
    -   **Excessive Parameter List**: Set the maximum parameter count to 8, covering 99% of PHP
        functions.
    -   **Too Many Public Methods**: Set the maximum public methods to 25, excluding getter and
        setter methods, covering 99% of PHP classes.

### PHPStan

Here is a summary of the `phpstan.neon` file:

-   **Level**: Set to 8 to avoid using level 9, as PHP functions return mixed types frequently.
-   **PHP Version**: The PHP version is set to the value of the required PHP version in the
    `composer.json` of the repository.

### PHPUnit

Here is a summary of the configuration options in the `phpunit.xml` file:

-   `bootstrap="vendor/autoload.php"` - Use the autoload file by default.
-   `cacheResult="true"` - Cache results to save time.
-   `colors="true"` - Use colors for better display.
-   `executionOrder="random"` - Use random order to check for order dependency issues.
-   `failOnIncomplete="false"` - Don't fail just because the test isn't finished yet.
-   `failOnNotice="true"` - Fail when a notice occurs in tested code.
-   `failOnRisky="false"` - Don't fail just because the test is risky.
-   `failOnWarning="true"` - Fail when a warning occurs in tested code.
-   `stopOnFailure="false"` - Don't stop when failure occurs because a different failure may provide
    more information.

## JavaScript

### Commitlint

The `commitlint.config.js` configuration file for Commitlint specifies that the project should
extend the conventional commit rules provided by the `@commitlint/config-conventional` package.
These rules enforce a standardized commit message format, ensuring that commit messages are
structured and consistent across the project.

### Eslint

Here is a summary of the configuration options in the `.eslintrc.json` file:

```
    "env": {
        "browser": true,            // Enables browser global variables.
        "es2021": true              // Enables ES2021 global variables and syntax.
    },
    "parserOptions": {
        "ecmaVersion": "latest",    // Specifies the ECMAScript version to use.
        "sourceType": "module"      // Allows the use of ECMAScript modules.
    },
    "rules": {
        "comma-dangle": "off",      // Disables the rule that enforces trailing commas.
        "indent": ["error", 4],     // Enforces an indentation of 4 spaces and treats violations as errors.
        "quotes": "off",            // Disables the rule that enforces the use of single or double quotes.
        "semi": "off",              // Disables the rule that enforces the use of semicolons.
        "space-before-function-paren": "off" // Disables the rule that enforces spacing before function parentheses.
    }
}
```

### Mocha

Here is a summary of the configuration options in the `.mocharc.json` file:

-   `"ui": "bdd"` - Use the default style of Behavior Driven Development.
-   `"timeout": 2000` - Use the default test timeout.
-   `"slow": 75` - Use the default slow-test threshold.
-   `"retries": 3` - Allow three retries for flaky tests.
-   `"bail": false` - Don't stop running after a test failure.
-   `"checkLeaks": true` - Check for global variable leaks.
-   `"allowUncaught": false` - Catch errors so they don't propagate.
-   `"asyncOnly": false` - Allow tests that don't return a promise or callback.
-   `"forbidOnly": false` - Allow tests that are marked `.only`.
-   `"forbidPending": false` - Allow tests that are marked pending.

### Prettier

Here is a line-by-line description of the Prettier configuration options in the `.prettierrc.json`
file:

1. **"printWidth": 100**

    - Sets the maximum line length to 100 characters. Lines longer than this will be wrapped
      according to Prettier's wrapping rules.

2. **"proseWrap": "always"**

    - Forces Prettier to wrap markdown text where necessary. This ensures that markdown content
      adheres to the specified `printWidth`.

3. **"tabWidth": 4**

    - Sets the number of spaces per indentation level to 4. This controls how many spaces are used
      for each tab.

4. **"useTabs": false**

    - Instructs Prettier to use spaces for indentation instead of tabs.

5. **"htmlWhitespaceSensitivity": "ignore"**

    - Sets the whitespace sensitivity for HTML files to "ignore". This means that whitespace in HTML
      files will be handled in a way that it does not affect the layout of the HTML.

6. **"singleQuote": true**

    - Enforces the use of single quotes (`'`) instead of double quotes (`"`) for strings in the
      code.

7. **"plugins": ["@prettier/plugin-php", "@prettier/plugin-xml", "prettier-plugin-sh"]**
    - Specifies an array of plugins to extend Prettier's functionality:
        - `@prettier/plugin-php`: Adds support for formatting PHP files.
        - `@prettier/plugin-xml`: Adds support for formatting XML files.
        - `prettier-plugin-sh`: Adds support for formatting shell script files.

### Stylelint

The `.stylelintrc.json` configuration file for Stylelint specifies that the project should extend
the `stylelint-config-standard` rules. This standard configuration enforces a set of commonly
accepted CSS and SCSS coding standards, ensuring that the stylesheets in the project are consistent
and follow best practices.
