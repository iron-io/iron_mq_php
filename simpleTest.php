<?php

#require("phar://iron_mq.phar");
require("../iron_core_php/IronCore.class.php");
require("IronMQ.class.php");

$ironmq = new IronMQ();
#$ironmq->debug_enabled = true;
$ironmq->ssl_verifypeer = false;

for ($i = 0; $i < 10; $i++){
    echo "Post message:\n";
    $res = $ironmq->postMessage("test_queue", "Test Message $i");
    var_dump($res);

    echo "Post messages:\n";
    $res = $ironmq->postMessages("test-queue-multi", array("Test Message $i", "Test Message $i-2"));
    var_dump($res);

    echo "Get message..\n";
    $message = $ironmq->getMessage("test_queue");
    print_r($message);

    echo "Touch message..\n";
    $res = $ironmq->touchMessage("test_queue", $message->id);
    print_r($res);

    echo "Release message..\n";
    $res = $ironmq->releaseMessage("test_queue", $message->id);
    print_r($res);

    echo "Peek message..\n";
    $res = $ironmq->peekMessage("test_queue");
    print_r($res);

    echo "Delete message..\n";
    $message = $ironmq->deleteMessage("test_queue", $message->id);
    print_r($message);

    $message = $ironmq->getMessage("test_queue");
    print_r($message);


    echo "\n------$i-------\n";
}


echo "\n done";