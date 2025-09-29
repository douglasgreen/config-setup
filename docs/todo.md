# Configure rules

Finish configuring rules from https://cs.symfony.com/doc/rules/index.html consistent with standards.

Pay attention to ordering rules.

# Config Setup To-Do List

Use https://github.com/xojs/xo instead of Standard for JS formatting

# Symplify

Consider adding back ECS for https://github.com/symplify/coding-standard, which includes line length
and other formatters.

# PHPCS

I used PHPCS as a standlone instead of within ECS because ECS doesn't support PHPCompatibility
sniffs anymore.

I made a list of all applicable standards. To produce the list:
1. List all rulesets with vendor/bin/phpcs -i
2. Export the list of rules for each standard like:  vendor/bin/phpcs -e --standard=MySource
3. Remove the deprecated rules marked with an asterisk.
