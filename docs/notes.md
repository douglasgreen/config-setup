# Notes

Here are some miscellaneous formatting choices that I'm documenting:

* Leaving blank lines between PHPDoc tag groups is standard.
* Removing aliases like die instead of exit is standard.
* Using absolute top-level class names like \Exception is standard.
* Using trailing commas is more standard than omitting them.
* Use `fwrite(STDERR, $msg . "\n")` instead of `error_log($msg)` for CLI programs.
