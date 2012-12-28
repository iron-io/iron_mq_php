<?php

#require("phar://iron_mq.phar");
require("../iron_core_php/IronCore.class.php");
require("IronMQ.class.php");

$ironmq = new IronMQ();
#$ironmq->debug_enabled = true;
$ironmq->ssl_verifypeer = false;

for ($i = 0; $i < 10; $i++){
    echo "Post message..\n";
    $res = $ironmq->postMessage("test_queue", "Test Message $i");
    print_r($res);

    echo "Get message..\n";
    $message = $ironmq->getMessage("test_queue");
    print_r($message);

    echo "Delete message..\n";
    $message = $ironmq->deleteMessage("test_queue", $message->id);
    print_r($message);

    $message = $ironmq->getMessage("test_queue");
    print_r($message);


    echo "\n------$i-------\n";
}


echo "\n done";