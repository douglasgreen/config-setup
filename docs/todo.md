# Config Setup To-Do List

Use https://github.com/xojs/xo instead of Standard for JS formatting

# PHPCS

I used PHPCS as a standlone instead of within ECS because ECS doesn't support PHPCompatibility
sniffs anymore. I need to update the documentation and the file copy to include phpcs.xml.

I made a list of all applicable standards. To produce the list:
1. List all rulesets with vendor/bin/phpcs -i
2. Export the list of rules for each standard like:  vendor/bin/phpcs -e --standard=MySource
3. Remove the deprecated rules marked with an asterisk.
