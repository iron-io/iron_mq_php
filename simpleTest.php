<?php

include("phar://iron_mq.phar");

$ironmq = new IronMQ('config.ini');
$ironmq->debug_enabled = true;
$ironmq->ssl_verifypeer = false;


$res = $ironmq->postMessage("test_queue", array("body" => "Test Message"));

print_r($res);
sleep(2);
print "Getting message..";
$message = $ironmq->getMessage("test_queue");
print_r($message);
