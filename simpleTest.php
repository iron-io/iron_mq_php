<?php

include("IronMQ.class.php");

$ironmq = new IronMQ('config.ini');
$ironmq->debug_enabled = true;


$res = $ironmq->postMessage("test_queue", array("body" => "Test Message"));

print_r($res);
sleep(15);
print "Getting message..";
$message = $ironmq->getMessage("test_queue");
print_r($message);
