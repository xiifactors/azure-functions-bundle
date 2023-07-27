#!/bin/bash
PHP_CLI_SERVER_WORKERS=25 php -S 0.0.0.0:$FUNCTIONS_CUSTOMHANDLER_PORT public/bundles/azurefunctions/index.php