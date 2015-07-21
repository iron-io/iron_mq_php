#!/bin/bash

/var/www/vendor/bin/phpunit --coverage-html=coverage/
/var/www/vendor/bin/phpcs --standard=PSR2 ../src/ --ignore="*/test/*,autoload_classmap.php,*.js"