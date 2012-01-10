<?php

include("IronMQ.class.php");

$ironmq = new IronMQ('config.ini');
$ironmq->debug_enabled = true;

$project_id = ""; # using default project_id from config

$res = $ironmq->postMessage($project_id, "test_queue", array("body" => "Test Message"));

#echo "message_id = $res \n";
print_r($res);
#sleep(15);
#$message = $ironmq->getMessage($project_id, "test_queue");
#print_r($message);
?>
