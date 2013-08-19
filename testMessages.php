<?php

#require("phar://iron_mq.phar");
require("../iron_core_php/IronCore.class.php");
require("IronMQ.class.php");

$ironmq = new IronMQ();
#$ironmq->debug_enabled = true;
$ironmq->ssl_verifypeer = false;

$queue_name = 'test-php-queue';
$res = $ironmq->postMessage($queue_name, 'foo!');
$res = $ironmq->postMessage('test-queue-multi', 'foo!');


for ($i = 0; $i < 10; $i++){

    print "run #$i: ";

    $msg = "Test Message $i";

    $res = $ironmq->clearQueue($queue_name);
    $res = $ironmq->clearQueue('test-queue-multi');
    if ($res->msg){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Post message:\n";
    $res = $ironmq->postMessage($queue_name, $msg);
    if ($res->id && count($res->ids) == 1){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Post messages:\n";
    $res = $ironmq->postMessages("test-queue-multi", array($msg, $msg));
    if (count($res->ids) == 2){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Get message..\n";
    $message = $ironmq->getMessage($queue_name);
    if ($message->body == $msg){
        print ".";
    }else{
        print 'F';
        var_dump($message);
    }

    #echo "Touch message..\n";
    $res = $ironmq->touchMessage($queue_name, $message->id);
    if ($res->msg){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Release message..\n";
    $res = $ironmq->releaseMessage($queue_name, $message->id);
    if ($res->msg){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Peek message..\n";
    $res = $ironmq->peekMessage($queue_name);
    if ($res->body == $msg){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }

    #echo "Delete message..\n";
    $message = $ironmq->deleteMessage($queue_name, $message->id);

    if ($message){
        print ".";
    }else{
        print 'F';
        var_dump($message);
    }


    $res = $ironmq->getMessage($queue_name);
    if ($res === null){
        print ".";
    }else{
        print 'F';
        var_dump($res);
    }


    echo "\n";
    $ironmq->postMessage($queue_name, $msg);
    $ironmq->postMessage($queue_name, $msg);

}


echo "\n done";