# Project Notes

## PHPUnit

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
