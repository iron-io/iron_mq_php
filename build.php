<?php
/**
 * This script creates  .phar archive with all required dependencies.
 * Archive usage:
 * include("phar://iron_mq.phar");
 * or
 * include("phar://".dirname(__FILE__)."/iron_mq.phar");
 */

@unlink('iron_mq.phar');

$phar = new Phar('iron_mq.phar');

# Loader
$phar->setStub('<?php
Phar::mapPhar("iron_mq.phar");
if (!class_exists("IronCore")) {
    require "phar://iron_mq.phar/IronCore.class.php";
}
require "phar://iron_mq.phar/IronMQ.class.php";
__HALT_COMPILER(); ?>');

# Files
$phar->addFile('../iron_core_php/IronCore.class.php', 'IronCore.class.php');
$phar->addFile('IronMQ.class.php');
$phar->addFile('LICENSE', 'LICENSE');

echo "\ndone - ".(round(filesize('iron_mq.phar')/1024, 2))." KB\n";

# Verification
require "phar://iron_mq.phar";
$worker = new IronMQ();

echo "Build finished successfully\n";
